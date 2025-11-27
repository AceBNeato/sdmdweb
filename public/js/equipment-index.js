// Equipment Index Page JavaScript
$(document).ready(function() {

    // Automatic Search Functionality
    const searchInput = $('#search');
    const statusFilter = $('#status');
    const equipmentTypeFilter = $('#equipment_type');
    const officeFilter = $('#office_id');
    const equipmentRows = $('tbody tr');

    function filterEquipment() {
        const searchTerm = searchInput.val().toLowerCase();
        const statusValue = statusFilter.val().toLowerCase();
        const equipmentTypeValue = equipmentTypeFilter.val().toLowerCase();
        const officeValue = officeFilter.val().toLowerCase();

        equipmentRows.each(function() {
            const row = $(this);
            const serial = row.data('serial').toLowerCase();
            const model = row.data('model').toLowerCase();
            const type = row.data('type').toLowerCase();
            const office = row.data('office').toLowerCase();
            const status = row.data('status').toLowerCase();
            const text = row.text().toLowerCase();

            const matchesSearch = searchTerm === '' || 
                text.includes(searchTerm) || 
                serial.includes(searchTerm) || 
                model.includes(searchTerm) || 
                type.includes(searchTerm) || 
                office.includes(searchTerm);

            const matchesStatus = statusValue === 'all' || status.includes(statusValue);
            const matchesEquipmentType = equipmentTypeValue === 'all' || type.includes(equipmentTypeValue);
            const matchesOffice = officeValue === 'all' || office.includes(officeValue);

            if (matchesSearch && matchesStatus && matchesEquipmentType && matchesOffice) {
                row.show();
            } else {
                row.hide();
            }
        });
    }

    // Add event listeners for real-time filtering
    searchInput.on('input', filterEquipment);
    statusFilter.on('change', filterEquipment);
    equipmentTypeFilter.on('change', filterEquipment);
    officeFilter.on('change', filterEquipment);

    // Also try vanilla JS event listeners as backup
    if (searchInput[0]) {
        searchInput[0].addEventListener('input', filterEquipment);
    }
    if (statusFilter[0]) {
        statusFilter[0].addEventListener('change', filterEquipment);
    }
    if (equipmentTypeFilter[0]) {
        equipmentTypeFilter[0].addEventListener('change', filterEquipment);
    }
    if (officeFilter[0]) {
        officeFilter[0].addEventListener('change', filterEquipment);
    }

    // Debug: Check if elements are found
    console.log('Search elements found:', {
        searchInput: searchInput.length,
        statusFilter: statusFilter.length,
        equipmentTypeFilter: equipmentTypeFilter.length,
        officeFilter: officeFilter.length,
        equipmentRows: equipmentRows.length
    });

    const printQrcodesModal = $('#printQrcodesModal');
    const printQrcodesContent = $('#printQrcodesContent');
    const printModalLoadingMarkup = '<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    function loadPrintQrcodesModal(options = {}) {
        if (!printQrcodesModal.length || !printQrcodesContent.length) {
            return;
        }

        const requestUrl = options.url || ($('.print-qrcodes-btn').data('url') || null);

        if (!requestUrl) {
            return;
        }

        let requestData;

        if (options.data !== undefined) {
            if (typeof options.data === 'string') {
                requestData = options.data;
            } else {
                requestData = options.data;
            }
        } else if (options.params instanceof URLSearchParams) {
            requestData = options.params.toString();
        } else {
            // Get office_id from hidden element or default to 'all' for staff
            const officeId = options.officeId || $('#office_id').val() || $('meta[name="current-office-id"]').attr('content') || 'all';
            requestData = { office_id: officeId };
        }

        printQrcodesContent.html(printModalLoadingMarkup);
        printQrcodesModal.modal('show');

        $.ajax({
            url: requestUrl,
            type: 'GET',
            data: requestData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                printQrcodesContent.html(response);
                initPrintQrcodesModalInteractions();
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Failed to load QR codes. Please try again.',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    }

    function initPrintQrcodesModalInteractions() {
        const modal = $('#printQrcodesModal');

        if (!modal.length) {
            return;
        }

        const selectAllCheckbox = modal.find('#selectAllQrcodes');
        const selectedCountDisplay = modal.find('.selected-count-value');
        const printButton = modal.find('.print-selected-btn');
        const qrPreviewGrid = modal.find('.qr-preview-grid');
        const pdfForm = modal.find('#printQrcodesPdfForm');
        const pdfUrl = printButton.data('pdf-url');
        const searchInput = modal.find('#qrEquipmentSearch');

        function getSelectedIds() {
            return modal.find('.qr-select-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
        }

        function updateSelections() {
            const selectedIds = getSelectedIds();
            const totalCheckboxes = modal.find('.qr-select-checkbox').length;

            modal.find('.qr-preview-item').each(function() {
                const id = $(this).data('equipment-id').toString();
                $(this).toggle(selectedIds.includes(id));
            });

            selectedCountDisplay.text(selectedIds.length);

            if (selectAllCheckbox.length) {
                selectAllCheckbox.prop('checked', selectedIds.length === totalCheckboxes && totalCheckboxes > 0);
            }

            if (printButton.length) {
                printButton.prop('disabled', selectedIds.length === 0);
            }

            if (pdfForm && pdfForm.length) {
                pdfForm.find('input[name="equipment_ids"]').val(selectedIds.join(','));
            }
        }

        function applySearchFilter() {
            if (!searchInput.length) {
                return;
            }

            const term = searchInput.val().toLowerCase().trim();

            modal.find('.qr-select-item').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(!term || text.indexOf(term) !== -1);
            });
        }

        modal.off('submit', '#printQrFilterForm').on('submit', '#printQrFilterForm', function(e) {
            e.preventDefault();
            const form = $(this);
            loadPrintQrcodesModal({
                url: form.attr('action'),
                data: form.serialize()
            });
        });

        modal.off('change', '.qr-select-checkbox').on('change', '.qr-select-checkbox', function() {
            updateSelections();
        });

        modal.off('change', '#selectAllQrcodes').on('change', '#selectAllQrcodes', function() {
            const isChecked = $(this).is(':checked');
            modal.find('.qr-select-checkbox').prop('checked', isChecked);
            updateSelections();
        });

        if (searchInput.length) {
            modal.off('input', '#qrEquipmentSearch').on('input', '#qrEquipmentSearch', function() {
                applySearchFilter();
            });
        }

        modal.off('click', '.print-selected-btn').on('click', '.print-selected-btn', function() {
            if (!pdfUrl || pdfUrl === '#') {
                return;
            }

            const selectedIds = getSelectedIds();

            if (!selectedIds.length) {
                return;
            }

            if (pdfForm && pdfForm.length) {
                pdfForm.attr('action', pdfUrl);
                pdfForm.find('input[name="equipment_ids"]').val(selectedIds.join(','));
                pdfForm.trigger('submit');
            } else {
                const queryString = $.param({ equipment_ids: selectedIds.join(',') });
                window.open(`${pdfUrl}?${queryString}`, '_blank');
            }
        });

        modal.off('click', '.reset-print-filters').on('click', '.reset-print-filters', function(e) {
            e.preventDefault();
            const targetUrl = $(this).data('url') || $('#printQrFilterForm').attr('action');
            loadPrintQrcodesModal({
                url: targetUrl,
                data: { office_id: 'all' }
            });
        });

        applySearchFilter();
        updateSelections();
    }

    if (printQrcodesModal.length) {
        printQrcodesModal.on('hidden.bs.modal', function() {
            const url = new URL(window.location);

            if (url.searchParams.has('print_qrcodes')) {
                url.searchParams.delete('print_qrcodes');
                url.searchParams.delete('office_id');
                window.history.replaceState({}, document.title, url.toString());
            }
        });
    }

    $('.print-qrcodes-btn').on('click', function() {
        const buttonUrl = $(this).data('url');
        const officeId = $('#office_id').val() || $('meta[name="current-office-id"]').attr('content') || 'all';
        loadPrintQrcodesModal({ url: buttonUrl, officeId: officeId });
    });

    const initialUrlParams = new URLSearchParams(window.location.search);
    if ($('.print-qrcodes-btn').length && initialUrlParams.has('print_qrcodes')) {
        loadPrintQrcodesModal({
            url: $('.print-qrcodes-btn').data('url'),
            params: initialUrlParams
        });
    }

    // Handle ADD button clicks
    $('.add-equipment-btn').on('click', function() {
        var url = $(this).data('url');
        var modal = $('#addEquipmentModal');
        var content = $('#addEquipmentContent');

        if (!url || !modal.length || !content.length) {
            return;
        }

        // Show loading spinner
        content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                content.html(response);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                
                // Handle special case for no categories
                if (xhr.status === 400 && xhr.responseJSON && xhr.responseJSON.error_type === 'no_categories') {
                    // Close modal if it's open
                    modal.modal('hide');
                    // Show SweetAlert warning
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Add Equipment',
                        text: xhr.responseJSON.message,
                        confirmButtonText: 'Go to Settings',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to settings page
                            window.location.href = '/settings';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to Load',
                        text: 'Failed to load equipment form. Error: ' + xhr.status + ' - ' + error,
                        confirmButtonColor: '#3085d6'
                    });
                }
            }
        });

        // Show modal
        modal.modal('show');
    });

    // Handle VIEW button clicks
    $('.view-equipment-btn').on('click', function() {
        var equipmentId = $(this).data('equipment-id');
        var url = $(this).data('url');
        var modal = $('#viewEquipmentModal');
        var content = $('#viewEquipmentContent');

        // Show loading spinner
        content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        // Load content via AJAX
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                content.html(response);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Failed to load equipment details. Error: ' + xhr.status + ' - ' + error,
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        // Show modal
        modal.modal('show');
    });

    // Handle EDIT button clicks (both in table and in modal)
    $('.edit-equipment-btn').on('click', function() {
        var equipmentId = $(this).data('equipment-id');
        var url = $(this).data('url');
        var modal = $('#editEquipmentModal');
        var content = $('#editEquipmentContent');

        // Clear any existing session messages to prevent unwanted alerts
        if (window.sessionMessages) {
            window.sessionMessages = {};
        }

        // Close the view modal if it's open
        $('#viewEquipmentModal').modal('hide');

        // Show loading spinner
        content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        // Load content via AJAX
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                content.html(response);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Failed to load equipment form. Error: ' + xhr.status + ' - ' + error,
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        // Show modal
        modal.modal('show');
    });

    // Handle HISTORY button clicks
    $('.history-equipment-btn').on('click', function() {
        var equipmentId = $(this).data('equipment-id');
        var url = $(this).data('url');
        var modal = $('#historyEquipmentModal');
        var content = $('#historyEquipmentContent');

        // Close other modals if open
        $('#viewEquipmentModal').modal('hide');
        $('#editEquipmentModal').modal('hide');

        // Show loading spinner
        content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        // Load content via AJAX
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                content.html(response);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Failed to load history form. Error: ' + xhr.status + ' - ' + error,
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        // Show modal
        modal.modal('show');
    });

    // Handle form submission within modals
    $(document).on('submit', '#editEquipmentModal form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Close modal
                $('#editEquipmentModal').modal('hide');

                // Check if response is JSON with success/message
                if (response && typeof response === 'object' && response.success) {
                    // Show success toast directly
                    showToast(response.message, 'success');

                    // Redirect after a short delay to allow toast to be seen
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    }, 1000);
                } else {
                    // Fallback to page reload for backward compatibility
                    location.reload();
                }
            },
            error: function(xhr) {
                // Handle errors - show validation errors
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    // Display validation errors with SweetAlert
                    var errorMessages = [];
                    for (var field in errors) {
                        errorMessages.push(errors[field][0]);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: '<div style="text-align: left;">' + errorMessages.join('<br>') + '</div>',
                        confirmButtonColor: '#3085d6'
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    // Show error toast for JSON error responses
                    showToast(xhr.responseJSON.message, 'error');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            }
        });
    });

    // Handle add equipment form submission within modal
    $(document).on('submit', '#addEquipmentModal form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Close modal
                $('#addEquipmentModal').modal('hide');

                // Check if response is JSON with success/message
                if (response && typeof response === 'object' && response.success) {
                    // Show success toast directly
                    showToast(response.message, 'success');

                    // Redirect after a short delay to allow toast to be seen
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    }, 1000);
                } else {
                    // Fallback to page reload for backward compatibility
                    location.reload();
                }
            },
            error: function(xhr) {
                // Handle errors - show validation errors
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    // Display validation errors with SweetAlert
                    var errorMessages = [];
                    for (var field in errors) {
                        errorMessages.push(errors[field][0]);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: '<div style="text-align: left;">' + errorMessages.join('<br>') + '</div>',
                        confirmButtonColor: '#3085d6'
                    });
                } else if (xhr.status === 400 && xhr.responseJSON && xhr.responseJSON.error_type === 'no_categories') {
                    // Close modal and show SweetAlert for no categories
                    $('#addEquipmentModal').modal('hide');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Add Equipment',
                        text: xhr.responseJSON.message,
                        confirmButtonText: 'Go to Settings',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to settings page
                            window.location.href = '/settings';
                        }
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    // Show error toast for JSON error responses
                    showToast(xhr.responseJSON.message, 'error');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            }
        });
    });

    // Handle history form submission within modal
    $(document).on('submit', '#historyEquipmentModal form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Close modal
                $('#historyEquipmentModal').modal('hide');

                // Check if response is JSON with success/message
                if (response && typeof response === 'object' && response.success) {
                    // Show success toast directly
                    showToast(response.message, 'success');

                    // Redirect after a short delay to allow toast to be seen
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    }, 1000);
                } else {
                    // Fallback to page reload for backward compatibility
                    location.reload();
                }
            },
            error: function(xhr) {
                // Handle errors - show validation errors
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    // Display validation errors with SweetAlert
                    var errorMessages = [];
                    for (var field in errors) {
                        errorMessages.push(errors[field][0]);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: '<div style="text-align: left;">' + errorMessages.join('<br>') + '</div>',
                        confirmButtonColor: '#3085d6'
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    // Show error toast for JSON error responses
                    showToast(xhr.responseJSON.message, 'error');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            }
        });
    });

    // Auto-open modals based on URL parameters (for QR scanner integration)
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);

        // Auto-open view modal
        if (urlParams.has('view_equipment')) {
            const equipmentId = urlParams.get('view_equipment');
            const viewBtn = $(`.view-equipment-btn[data-equipment-id="${equipmentId}"]`);
            if (viewBtn.length > 0) {
                viewBtn.trigger('click');
            }
        }

        // Auto-open history modal
        if (urlParams.has('history_equipment')) {
            const equipmentId = urlParams.get('history_equipment');
            const historyBtn = $(`.history-equipment-btn[data-equipment-id="${equipmentId}"]`);
            if (historyBtn.length > 0) {
                historyBtn.trigger('click');
            }
        }
    });

});
