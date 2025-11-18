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
                bindDeleteButtons();
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                content.html('<div class="alert alert-danger">Failed to load user details. Error: ' + xhr.status + ' - ' + error + '</div>');
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
                    content.html('<div class="alert alert-danger">Failed to load edit form. Error: ' + xhr.status + ' - ' + error + '</div>');
                }
            });

            if (!modal.parent().is('body')) {
                modal.appendTo('body');
            }
            modal.modal('show');
        });
    }

    var deleteModal = $('#deleteUserModal');
    var deleteUserName = $('#deleteUserName');
    var deleteUserPasswordInput = $('#deleteUserPassword');
    var deleteUserError = $('#deleteUserError');
    var currentDeleteForm = null;
    var deletePasswordToggle = $('#deleteUserPasswordToggle');
    var deletePasswordIcon = deletePasswordToggle.find('i');
    var deleteSubmitButton = $('#confirmDeleteUserBtn');
    var deleteSubmitSpinner = $('#deleteUserLoadingSpinner');
    var deleteSubmitLabel = deleteSubmitButton.find('.btn-label');

    function bindDeleteButtons() {
        $('.delete-user-btn').off('click').on('click', function() {
            currentDeleteForm = $(this).closest('form');
            var userName = $(this).data('user-name');

            deleteUserName.text(userName);
            deleteUserPasswordInput.val('');
            deleteUserError.addClass('d-none').text('');

            if (!deleteModal.parent().is('body')) {
                deleteModal.appendTo('body');
            }

            deleteModal.modal('show');
        });
    }

    $('#confirmDeleteUserBtn').on('click', function() {
        if (!currentDeleteForm) {
            return;
        }

        var passwordVal = deleteUserPasswordInput.val().trim();

        if (!passwordVal.length) {
            deleteUserError.removeClass('d-none').text('Please enter your password to confirm deletion.');
            deleteUserPasswordInput.trigger('focus');
            return;
        }

        deleteSubmitButton.prop('disabled', true);
        deleteSubmitSpinner.removeClass('d-none');
        deleteSubmitLabel.addClass('visually-hidden');

        currentDeleteForm.find('.delete-user-password-input').val(passwordVal);
        deleteModal.modal('hide');
        currentDeleteForm.trigger('submit');
    });

    deleteModal.on('shown.bs.modal', function() {
        deleteUserPasswordInput.trigger('focus');
    });

    deleteModal.on('hidden.bs.modal', function() {
        deleteUserPasswordInput.val('');
        deleteUserError.addClass('d-none').text('');
        currentDeleteForm = null;
        deleteSubmitSpinner.addClass('d-none');
        deleteSubmitLabel.removeClass('visually-hidden');
        deleteSubmitButton.prop('disabled', false);
        deleteUserPasswordInput.attr('type', 'password');
        deletePasswordIcon.removeClass('bx-hide').addClass('bx-show-alt');
    });

    deletePasswordToggle.on('click', function() {
        var isPassword = deleteUserPasswordInput.attr('type') === 'password';
        deleteUserPasswordInput.attr('type', isPassword ? 'text' : 'password');
        deletePasswordIcon.toggleClass('bx-show-alt', !isPassword);
        deletePasswordIcon.toggleClass('bx-hide', isPassword);
        deleteUserPasswordInput.trigger('focus');
    });

    bindEditButtons();
    bindDeleteButtons();
});
