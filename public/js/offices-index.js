// Office Management JavaScript
$(document).ready(function() {
    
    // Handle Add Office button click
    $('.add-office-btn').on('click', function() {
        var url = $(this).data('url');
        var modal = $('#officeCreateModal');
        var content = $('#officeCreateContent');
        
        // Show loading
        content.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Loading create form...</div></div>');
        modal.modal('show');
        
        // Load create form
        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                content.html(data);
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error loading create form. Please try again.',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    });
    
    // Handle view button clicks
    $('.office-view-btn').on('click', function() {
        var officeId = $(this).data('office-id');
        var url = $(this).data('url');
        var modal = $('#officeViewModal');
        var content = $('#officeViewContent');
        
        // Show loading
        content.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Loading office details...</div></div>');
        modal.modal('show');
        
        // Load office details
        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                content.html(data);
            },
            error: function(xhr, status, error) {
                console.error('Error loading office details:', { status, error });
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error loading office details. Please try again.',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    });
    
    // Handle edit button clicks
    $('.office-edit-btn').on('click', function() {
        var officeId = $(this).data('office-id');
        var url = $(this).data('url');
        var modal = $('#officeEditModal');
        var content = $('#officeEditContent');
        
        // Show loading
        content.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Loading edit form...</div></div>');
        modal.modal('show');
        
        // Load edit form
        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                content.html(data);
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error loading edit form. Please try again.',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    });
    
    // Handle form submissions (for dynamically loaded forms)
    $(document).on('submit', '#officeCreateForm, #officeEditForm', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();
        var modal = form.closest('.modal');
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');
        
        // Submit via AJAX
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                // Check if response is JSON or redirect
                if (typeof response === 'object' && response.success) {
                    // Show success SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: form.attr('id') === 'officeCreateForm' ? 'Office Created!' : 'Office Updated!',
                        text: response.message || (form.attr('id') === 'officeCreateForm' ? 'Office has been created successfully.' : 'Office has been updated successfully.'),
                        confirmButtonText: 'Great!',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        modal.modal('hide');
                        window.location.reload();
                    });
                } else {
                    // Redirect response means success
                    Swal.fire({
                        icon: 'success',
                        title: form.attr('id') === 'officeCreateForm' ? 'Office Created!' : 'Office Updated!',
                        text: form.attr('id') === 'officeCreateForm' ? 'Office has been created successfully.' : 'Office has been updated successfully.',
                        confirmButtonText: 'Great!',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        modal.modal('hide');
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                // Handle validation errors
                if (xhr.status === 422 && xhr.responseJSON) {
                    var errors = xhr.responseJSON.errors;
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
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'There was an error saving the office. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            complete: function() {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle dynamic form field interactions
    $(document).on('input', '#create_name, #edit_name', function() {
        var name = $(this).val();
        var modelDisplay = $(this).closest('form').find('#equipment_model_display');
        if (modelDisplay.length) {
            var brand = $(this).closest('form').find('#brand').val();
            var model = $(this).closest('form').find('#model_number').val();
            if (brand && model) {
                modelDisplay.val(brand + ' ' + model);
            }
        }
    });
});
