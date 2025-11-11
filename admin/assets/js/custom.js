/**
 * Custom Admin JS
 * Handle responsive behavior, dropdown, pagination
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== Auto-submit filter on dropdown change =====
    document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // ===== Smooth scroll to top on pagination click =====
    document.querySelectorAll('.smart-pagination .page-link').forEach(link => {
        link.addEventListener('click', function() {
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        });
    });

    // ===== Search on Enter key =====
    document.querySelectorAll('.filter-search').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    });

    // ===== Highlight current filter state =====
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('search') || urlParams.get('deleted') === '1') {
        const filterForm = document.querySelector('.filter-form');
        if (filterForm) {
            filterForm.style.borderColor = 'var(--primary)';
            filterForm.style.borderWidth = '2px';
        }
    }

});
