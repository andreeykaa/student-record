$(function () {
    const $input = $('#studentSearchInput');
    const $results = $('#studentSearchResults');

    if (!$input.length || !$results.length) {
        return;
    }

    const searchUrl = $input.data('search-url');
    const minChars = 2;

    let searchTimer = null;
    let currentRequest = null;

    function hideResults() {
        $results.addClass('d-none').empty();
    }

    function showLoading() {
        $results
            .removeClass('d-none')
            .empty()
            .append(
                $('<div>', {
                    class: 'student-navbar-search-empty',
                    text: 'Пошук...'
                })
            );
    }

    function showEmpty() {
        $results
            .removeClass('d-none')
            .empty()
            .append(
                $('<div>', {
                    class: 'student-navbar-search-empty',
                    text: 'Нічого не знайдено'
                })
            );
    }

    function renderStudents(students) {
        $results.empty();

        if (!students.length) {
            showEmpty();
            return;
        }

        students.forEach(function (student) {
            $('<a>', {
                class: 'student-navbar-search-item',
                href: student.url || '#',
                text: student.full_name || ''
            }).appendTo($results);
        });

        $results.removeClass('d-none');
    }

    function searchStudents(query) {
        if (currentRequest) {
            currentRequest.abort();
        }

        showLoading();

        currentRequest = $.ajax({
            url: searchUrl,
            method: 'GET',
            dataType: 'json',
            data: {
                q: query
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        currentRequest
            .done(function (response) {
                const students = Array.isArray(response.students)
                    ? response.students
                    : [];

                renderStudents(students);
            })
            .fail(function (xhr, status) {
                if (status !== 'abort') {
                    hideResults();
                }
            });
    }

    $input.on('input', function () {
        const query = $.trim($(this).val());

        clearTimeout(searchTimer);

        if (currentRequest) {
            currentRequest.abort();
        }

        if (query.length < minChars) {
            hideResults();
            return;
        }

        searchTimer = setTimeout(function () {
            searchStudents(query);
        }, 250);
    });

    $('.navbar-student-search').on('submit', function (event) {
        event.preventDefault();
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.navbar-student-search').length) {
            hideResults();
        }
    });

    $input.on('keydown', function (event) {
        if (event.key === 'Escape') {
            hideResults();
            $input.blur();
        }
    });
});
