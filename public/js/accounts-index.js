// Accounts Index Page JavaScript
$(document).ready(function() {
    // Handle VIEW button clicks
    $('.view-user-btn').on('click', function() {
        var userId = $(this).data('user-id');
        var url = $(this).data('url');
        var modal = $('#viewUserModal');
        var content = $('#viewUserContent');

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
                bindEditButtons();
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Failed to load user details. Error: ' + xhr.status + ' - ' + error,
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        // Show modal
        if (!modal.parent().is('body')) {
            modal.appendTo('body');
        }
        modal.modal('show');
    });

    // Handle EDIT button clicks
    function bindEditButtons() {
        $('.edit-user-btn').off('click').on('click', function() {
            var url = $(this).data('url');
            var modal = $('#editUserModal');
            var content = $('#editUserContent');
            var viewModal = $('#viewUserModal');

            // Close the view modal if it's open
            if (viewModal.hasClass('show')) {
                viewModal.modal('hide');
            }

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
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to Load',
                        text: 'Failed to load edit form. Error: ' + xhr.status + ' - ' + error,
                        confirmButtonColor: '#3085d6'
                    });
                }
            });

            if (!modal.parent().is('body')) {
                modal.appendTo('body');
            }
            modal.modal('show');
        });
    }

    bindEditButtons();
    
    // Handle toggle status button clicks
    $('.toggle-status-btn').on('click', function() {
        var button = $(this);
        var userId = button.data('user-id');
        var url = button.data('url');
        var userName = button.closest('tr').find('td:first-child').text().trim();
        var isCurrentlyActive = button.hasClass('btn-outline-warning');
        
        var action = isCurrentlyActive ? 'deactivate' : 'activate';
        var confirmMessage = 'Are you sure you want to ' + action + ' the account for "' + userName + '"?';
        
        // Use SweetAlert for confirmation
        Swal.fire({
            title: 'Confirm ' + action.charAt(0).toUpperCase() + action.slice(1),
            text: confirmMessage,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: isCurrentlyActive ? '#dc3545' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, ' + action + ' account',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                // Show loading state
                var originalHtml = button.html();
                button.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');
                
                // Send AJAX request
                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Show success message before reloading
                        var action = isCurrentlyActive ? 'deactivated' : 'activated';
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'User account has been ' + action + ' successfully.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#28a745',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: true
                        }).then(() => {
                            // Reload page to show updated status
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        // Restore button
                        button.prop('disabled', false).html(originalHtml);
                        
                        // Show error message
                        var errorMessage = 'An error occurred while updating the user status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        // Show error using SweetAlert
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    });
});
