$(document).on('click', '.open-delete-user-modal', function () {
    const button = $(this);

    $('#deleteUserModal .user-name-delete').text(button.data('user-name'));
    $('#deleteUserModal .user-form-delete').attr('action', button.data('delete-url'));
});
