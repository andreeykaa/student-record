$(function () {
    const $items = $('.lesson-page-item');
    const $pagination = $('#student-lessons-pagination');

    if (!$items.length || !$pagination.length) {
        return;
    }

    const perPage = 2;
    const totalItems = $items.length;
    const totalPages = Math.ceil(totalItems / perPage);
    let currentPage = 1;

    function renderPage(page) {
        currentPage = page;

        $items.hide();

        const start = (page - 1) * perPage;
        const end = start + perPage;

        $items.slice(start, end).show();

        renderPagination();
    }

    function renderPagination() {
        let html = '<nav aria-label="Пагінація занять"><ul class="pagination mb-0">';

        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a href="#" class="page-link js-lessons-page" data-page="${currentPage - 1}">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a href="#" class="page-link js-lessons-page" data-page="${i}">${i}</a>
                </li>
            `;
        }

        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a href="#" class="page-link js-lessons-page" data-page="${currentPage + 1}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        `;

        html += '</ul></nav>';

        $pagination.html(html);
    }

    $(document).on('click', '.js-lessons-page', function (e) {
        e.preventDefault();

        const $link = $(this);
        const page = parseInt($link.data('page'), 10);

        if (!page || page < 1 || page > totalPages || $link.closest('.page-item').hasClass('disabled')) {
            return;
        }

        renderPage(page);
    });

    renderPage(1);
});
