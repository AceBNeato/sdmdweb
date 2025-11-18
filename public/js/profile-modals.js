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
                $('#viewProfileContent').html('<div class="alert alert-danger">Failed to load profile. Error: '+xhr.status+'</div>');
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
                $('#editProfileContent').html('<div class="alert alert-danger">Failed to load edit form. Error: '+xhr.status+'</div>');
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
                    var html = '<div class="alert alert-danger"><ul class="mb-0">';
                    for (var k in errors) { html += '<li>' + errors[k][0] + '</li>'; }
                    html += '</ul></div>';
                    $('#editProfileContent').prepend(html);
                } else {
                    $('#editProfileContent').prepend('<div class="alert alert-danger">Failed to save profile. Please try again.</div>');
                }
            }
        });
    });
})(jQuery);
