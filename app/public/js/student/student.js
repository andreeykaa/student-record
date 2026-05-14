$(document).on('click', '.open-delete-student-modal', function () {
    const button = $(this);

    $('#deleteStudentModal .student-name-delete').text(button.data('student-name'));
    $('#deleteStudentModal .student-form-delete').attr('action', button.data('delete-url'));
});
