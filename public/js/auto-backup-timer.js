/**
 * Automatic Backup Timer
 * Checks backup settings and triggers automatic backups when time matches
 */

class AutoBackupTimer {
    constructor() {
        this.checkInterval = null;
        this.settings = null;
        this.lastCheckTime = null;
        this.lastBackupTime = null;
        this.isRunning = false;
        this.init();
    }

    async init() {
        try {
            await this.loadSettings();

            if (!this.settings || !this.settings.enabled) {
                console.log('Automatic backups are disabled; auto backup timer not started.');
                return;
            }

            this.startTimer();
            console.log('Auto backup timer initialized');
        } catch (error) {
            console.error('Failed to initialize auto backup timer:', error);
        }
    }

    async loadSettings() {
        try {
            console.log('Loading backup settings...');
            const response = await fetch('/admin/settings/api/backup-settings');
            if (!response.ok) {
                throw new Error('Failed to load backup settings');
            }
            this.settings = await response.json();
            console.log('Backup settings loaded:', this.settings);
        } catch (error) {
            console.error('Error loading backup settings:', error);
            this.settings = {
                enabled: false,
                time: '02:00',
                days: []
            };
        }
    }

    startTimer() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }

        this.isRunning = true;
        this.checkInterval = setInterval(() => {
            this.checkBackupTime();
        }, 30000); // Check every 30 seconds

        // Also check immediately
        this.checkBackupTime();
    }

    stopTimer() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
        this.isRunning = false;
        console.log('Auto backup timer stopped');
    }

    async checkBackupTime() {
        if (!this.settings || !this.settings.enabled) {
            return;
        }

        // Prevent multiple checks in the same minute
        const now = new Date();
        const currentMinute = now.getMinutes();
        if (this.lastCheckTime === currentMinute) {
            return;
        }
        this.lastCheckTime = currentMinute;

        try {
            const now = new Date();
            const currentTime = now.toTimeString().slice(0, 5); // HH:MM format
            const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase(); // monday, tuesday, etc.

            console.log(`Day check: Current day "${currentDay}", Backup days:`, this.settings.days);
            console.log(`Time check: Current time "${currentTime}", Backup time "${this.settings.time}"`);

            // Check if today is a backup day
            if (!this.settings.days.includes(currentDay)) {
                console.log(`Today (${currentDay}) is not a backup day`);
                return;
            }

            // Check if current time matches backup time (within 5-minute window)
            const backupTime = this.settings.time;
            const backupHour = parseInt(backupTime.split(':')[0]);
            const backupMinute = parseInt(backupTime.split(':')[1]);
            
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();

            // Check if we're within 5 minutes of the backup time
            const minuteDiff = Math.abs((currentHour * 60 + currentMinute) - (backupHour * 60 + backupMinute));
            
            console.log(`Backup check: Current ${currentHour}:${currentMinute.toString().padStart(2, '0')} vs Backup ${backupHour}:${backupMinute.toString().padStart(2, '0')} (diff: ${minuteDiff} min)`);
            
            // For testing: always trigger if enabled
            if (this.settings.enabled && minuteDiff <= 0) {
                // Check if we recently ran a backup (within 1 minute)
                if (this.lastBackupTime) {
                    const minutesSinceLastBackup = Math.floor((now - this.lastBackupTime) / (1000 * 60));
                    if (minutesSinceLastBackup < 1) {
                        console.log(`Skipping backup - ran ${minutesSinceLastBackup} minutes ago`);
                        return;
                    }
                }
                
                console.log(`Backup time matched! Current: ${currentTime}, Backup: ${backupTime}`);
                await this.triggerAutoBackup();
                this.lastBackupTime = new Date(); // Record when we last triggered
            } else if (this.settings.enabled) {
                console.log(`Time not matched yet. Diff: ${minuteDiff} minutes`);
            }

        } catch (error) {
            console.error('Error checking backup time:', error);
        }
    }

    async triggerAutoBackup() {
        try {
            console.log('Triggering automatic backup...');
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log('CSRF Token:', csrfToken ? 'found' : 'not found');
            
            const response = await fetch('/admin/backup/auto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });

            console.log('Backup response status:', response.status);
            const result = await response.json();
            console.log('Backup response:', result);

            if (result.success) {
                console.log('Automatic backup successful:', result);
                this.showNotification('Automatic backup created successfully', 'success');
                
                // Show detailed success modal
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '🎉 Automatic Backup Complete!',
                        html: `
                            <div class="text-left">
                                <p class="mb-3"><i class="fas fa-check-circle text-green-500 mr-2"></i>Automatic backup was created successfully!</p>
                                <div class="bg-gray-50 p-3 rounded text-sm">
                                    <strong>Backup Details:</strong><br>
                                    ${result.filename ? `✓ Filename: ${result.filename}` : '✓ Backup created successfully'}<br>
                                    ${result.size ? `✓ Size: ${result.size}` : ''}<br>
                                    ✓ Time: ${new Date().toLocaleString()}
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonColor: '#10b981',
                        confirmButtonText: '<i class="fas fa-check mr-2"></i>Great!',
                        timer: 5000,
                        timerProgressBar: true
                    });
                }
                
                // Refresh backup list if on backup page
                if (window.location.pathname.includes('/settings')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } else {
                console.log('Automatic backup skipped:', result.message);
                
                // Show toast notification for skipped backups (cooldown) - less intrusive
                if (result.skipped && typeof Swal !== 'undefined') {
                    const nextBackupTime = result.next_backup_time || 'Unknown time';
                    Swal.fire({
                        position: 'top-end',
                        toast: true,
                        icon: 'info',
                        title: 'Backup on Cooldown',
                        text: `Next backup: ${nextBackupTime}`,
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });
                } else if (result.message && !result.message.includes('not scheduled') && !result.message.includes('does not match')) {
                    this.showNotification(result.message, 'warning');
                }
            }
        } catch (error) {
            console.error('Error triggering automatic backup:', error);
            this.showNotification('Failed to trigger automatic backup', 'error');
        }
    }

    showNotification(message, type = 'info') {
        // Try to use SweetAlert if available, otherwise fallback to console
        if (typeof Swal !== 'undefined') {
            const swalConfig = {
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                title: message
            };

            switch (type) {
                case 'success':
                    swalConfig.icon = 'success';
                    break;
                case 'error':
                    swalConfig.icon = 'error';
                    break;
                case 'warning':
                    swalConfig.icon = 'warning';
                    break;
                default:
                    swalConfig.icon = 'info';
            }

            Swal.fire(swalConfig);
        } else {
            // Fallback to browser notification if permitted
            if (Notification.permission === 'granted') {
                new Notification('Auto Backup', {
                    body: message,
                    icon: '/images/SDMDlogo.png'
                });
            } else {
                console.log(`[Auto Backup ${type.toUpperCase()}]: ${message}`);
            }
        }
    }

    async refreshSettings() {
        await this.loadSettings();
        console.log('Backup settings refreshed');
    }
}

// Initialize the auto backup timer when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize on all pages for testing
    console.log('DOM loaded, checking for auto backup initialization...');
    
    // Always initialize for testing purposes
    window.autoBackupTimer = new AutoBackupTimer();
    
    // Also make it globally accessible for manual testing
    window.testAutoBackup = function() {
        if (window.autoBackupTimer) {
            console.log('Manual backup trigger test...');
            window.autoBackupTimer.triggerAutoBackup();
        }
    };
});

// Export for manual control
window.AutoBackupTimer = AutoBackupTimer;
