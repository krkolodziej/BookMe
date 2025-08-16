class OpinionsManager {
    constructor() {
        this.pageSize = 10;
        this.pageNumber = 1;
        this.init();
    }

    init() {
        this.initPageSizeButtons();
        this.openPage(1);
    }

    initPageSizeButtons() {
        const self = this;
        $('.page-size-button').each(function() {
            if (parseInt($(this).text()) === self.pageSize) {
                $(this).addClass('active');
            }
        });

        $('.page-size-button').on('click', function() {
            self.pageSize = parseInt($(this).text());
            $('.page-size-button').removeClass('active');
            $(this).addClass('active');
            self.openPage(1);
        });
    }

    openPage(page) {
        const self = this;
        const encodedName = $('.service-encoded-name').text();

        $.ajax({
            url: `/api/opinions/${encodedName}`,
            method: 'POST',
            data: JSON.stringify({
                pageSize: this.pageSize,
                page: page,
                sorts: "-createdAt"
            }),
            contentType: 'application/json',
            success: function(response) {
                self.appendOpinions(response.items);
                self.createPagination(response.totalPages, page);
                self.pageNumber = page;
            },
            error: function(error) {
                console.error('Error fetching opinions:', error);
            }
        });
    }

    appendOpinions(opinions) {
        $('.opinions').empty();

        opinions.forEach(opinion => {
            const stars = Array(5).fill(0).map((_, index) =>
                `<i class="fa-${index < opinion.rating ? 'solid' : 'regular'} fa-star text-warning"></i>`
            ).join('');

            const date = new Date(opinion.createdAt).toLocaleDateString('pl-PL', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            const opinionCard = $(`
                <div class="col-md-6 mb-4">
                    <div class="card h-100 opinion-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="stars">
                                    ${stars}
                                </div>
                                <small class="text-muted">${date}</small>
                            </div>
                            <p class="card-text mb-4">${opinion.content}</p>
                            <div class="d-flex justify-content-between align-items-end mt-auto">
                                <div class="user-info">
                                    <p class="mb-0 fw-bold">${opinion.firstName} ${opinion.lastName}</p>
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-check-circle text-success me-1"></i>
                                        Zweryfikowana opinia
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">${opinion.offerName}</small>
                                    <small class="text-muted d-block">${opinion.employeeFullName}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('.opinions').append(opinionCard);
        });
    }

    createPagination(totalPages, currentPage) {
        const $pagination = $('.pagination');
        $pagination.empty();

        if (totalPages <= 1) return;

        // Previous button
        this.addPaginationItem($pagination, '<i class="fa-solid fa-chevron-left"></i>', currentPage - 1, currentPage === 1);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages ||
                (i >= currentPage - 1 && i <= currentPage + 1)) {
                this.addPaginationItem($pagination, i, i, false, currentPage === i);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                this.addPaginationItem($pagination, '...', null, true);
            }
        }

        // Next button
        this.addPaginationItem($pagination, '<i class="fa-solid fa-chevron-right"></i>', currentPage + 1, currentPage === totalPages);
    }

    addPaginationItem($container, text, page, disabled = false, active = false) {
        const self = this;
        const $li = $('<li>').addClass(`page-item ${disabled ? 'disabled' : ''} ${active ? 'active bg-dark border-dark' : ''}`);
        const $a = $('<a>').addClass('page-link border-dark text-dark').html(text);

        if (!disabled && page !== null) {
            $li.attr('role', 'button');
            $a.on('click', function() {
                self.openPage(page);
            });
        }

        $li.append($a);
        $container.append($li);
    }
}

$(document).ready(function() {
    new OpinionsManager();
});