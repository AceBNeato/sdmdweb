<!-- Session Lock Modal - Outside app container to cover everything -->
<div id="session-lock-modal" class="session-lock-overlay" style="display: none;">
    <div class="session-lock-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Session Locked</h5>
            </div>
            <div class="modal-body">
                <p>Your session has been locked due to inactivity. Please enter your password to continue.</p>
                <form id="unlock-form">
                    <div class="mb-3">
                        <label for="unlock-password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="unlock-password" required>
                    </div>
                    <div id="unlock-error" class="alert alert-danger d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-secondary" onclick="event.preventDefault(); if(!this.hasAttribute('disabled')){ this.setAttribute('disabled','disabled'); this.style.pointerEvents='none'; document.getElementById('logout-form').submit(); }"><i class='bx bx-log-out'></i> Logout </a>
                    <button type="button" class="btn btn-primary" id="unlock-btn">Unlock</button>
            </div>
        </div>
    </div>
</div>
