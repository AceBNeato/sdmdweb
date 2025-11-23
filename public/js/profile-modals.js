// Profile modals: open and submit via AJAX
(function($){
    $(document).on('click', '.open-profile-modal', function(e){
        e.preventDefault();
        var url = $(this).data('url');
        if (!url) return;
        $('#viewProfileContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(html){
                $('#viewProfileContent').html(html);
                var modal = new bootstrap.Modal(document.getElementById('viewProfileModal'));
                modal.show();
            },
            error: function(xhr){
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Failed to load profile. Error: '+xhr.status,
                    confirmButtonColor: '#3085d6'
                });
                var modal = new bootstrap.Modal(document.getElementById('viewProfileModal'));
                modal.show();
            }
        });
    });

    $(document).on('click', '.open-edit-profile-modal', function(e){
        e.preventDefault();
        var url = $(this).data('url');
        if (!url) return;
        $('#editProfileContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        // Hide view modal if open
        var viewEl = document.getElementById('viewProfileModal');
        if (viewEl) {
            var viewInstance = bootstrap.Modal.getInstance(viewEl);
            if (viewInstance) viewInstance.hide();
        }
        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(html){
                $('#editProfileContent').html(html);
                var modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
                modal.show();
            },
            error: function(xhr){
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Failed to load edit form. Error: '+xhr.status,
                    confirmButtonColor: '#3085d6'
                });
                var modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
                modal.show();
            }
        });
    });

    $(document).on('submit', '#editProfileModal form', function(e){
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        var method = $(form).attr('method') || 'POST';
        var action = form.action;
        $.ajax({
            url: action,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(resp){
                var editEl = document.getElementById('editProfileModal');
                var editInstance = bootstrap.Modal.getInstance(editEl);
                if (editInstance) editInstance.hide();
                if (resp && resp.redirect) {
                    window.location.href = resp.redirect;
                } else {
                    window.location.reload();
                }
            },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = [];
                    for (var k in errors) { 
                        errorMessages.push(errors[k][0]); 
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
                        text: 'Failed to save profile. Please try again.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            }
        });
    });
})(jQuery);
