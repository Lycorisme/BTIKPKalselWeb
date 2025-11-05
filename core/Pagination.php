<?php
/**
 * Pagination Class
 * Handle pagination logic
 */

class Pagination {
    private $totalRecords;
    private $perPage;
    private $currentPage;
    private $totalPages;
    private $baseUrl;
    
    public function __construct($totalRecords, $perPage = null, $currentPage = 1, $baseUrl = '') {
        $this->totalRecords = (int)$totalRecords;
        $this->perPage = $perPage ?: ITEMS_PER_PAGE;
        $this->currentPage = max(1, (int)$currentPage);
        $this->totalPages = (int)ceil($this->totalRecords / $this->perPage);
        $this->baseUrl = $baseUrl;
        
        // Adjust current page if exceeds total pages
        if ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }
    }
    
    /**
     * Get LIMIT offset for SQL
     */
    public function getOffset() {
        return ($this->currentPage - 1) * $this->perPage;
    }
    
    /**
     * Get LIMIT count for SQL
     */
    public function getLimit() {
        return $this->perPage;
    }
    
    /**
     * Get current page
     */
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    /**
     * Get total pages
     */
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    /**
     * Check if has previous page
     */
    public function hasPrevious() {
        return $this->currentPage > 1;
    }
    
    /**
     * Check if has next page
     */
    public function hasNext() {
        return $this->currentPage < $this->totalPages;
    }
    
    /**
     * Get previous page number
     */
    public function getPreviousPage() {
        return $this->hasPrevious() ? $this->currentPage - 1 : 1;
    }
    
    /**
     * Get next page number
     */
    public function getNextPage() {
        return $this->hasNext() ? $this->currentPage + 1 : $this->totalPages;
    }
    
    /**
     * Get pagination info text
     */
    public function getInfo() {
        if ($this->totalRecords == 0) {
            return 'Tidak ada data';
        }
        
        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->perPage, $this->totalRecords);
        
        return "Menampilkan $start - $end dari $this->totalRecords data";
    }
    
    /**
     * Render Bootstrap 5 pagination HTML
     */
    public function render($showInfo = true) {
        if ($this->totalPages <= 1) {
            return '';
        }
        
        $html = '<nav aria-label="Page navigation">';
        
        // Info text
        if ($showInfo) {
            $html .= '<p class="text-muted mb-2">' . $this->getInfo() . '</p>';
        }
        
        $html .= '<ul class="pagination">';
        
        // Previous button
        $prevDisabled = !$this->hasPrevious() ? 'disabled' : '';
        $prevUrl = $this->buildUrl($this->getPreviousPage());
        $html .= '<li class="page-item ' . $prevDisabled . '">';
        $html .= '<a class="page-link" href="' . $prevUrl . '" aria-label="Previous">';
        $html .= '<span aria-hidden="true">&laquo;</span></a></li>';
        
        // Page numbers
        $range = $this->getPageRange();
        foreach ($range as $page) {
            if ($page == '...') {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            } else {
                $active = $page == $this->currentPage ? 'active' : '';
                $pageUrl = $this->buildUrl($page);
                $html .= '<li class="page-item ' . $active . '">';
                $html .= '<a class="page-link" href="' . $pageUrl . '">' . $page . '</a></li>';
            }
        }
        
        // Next button
        $nextDisabled = !$this->hasNext() ? 'disabled' : '';
        $nextUrl = $this->buildUrl($this->getNextPage());
        $html .= '<li class="page-item ' . $nextDisabled . '">';
        $html .= '<a class="page-link" href="' . $nextUrl . '" aria-label="Next">';
        $html .= '<span aria-hidden="true">&raquo;</span></a></li>';
        
        $html .= '</ul></nav>';
        
        return $html;
    }
    
    /**
     * Get page range for display
     */
    private function getPageRange() {
        $delta = 2; // Number of pages to show on each side
        $range = [];
        
        if ($this->totalPages <= 7) {
            // Show all pages
            $range = range(1, $this->totalPages);
        } else {
            // Show with ellipsis
            $range[] = 1;
            
            if ($this->currentPage - $delta > 2) {
                $range[] = '...';
            }
            
            $start = max(2, $this->currentPage - $delta);
            $end = min($this->totalPages - 1, $this->currentPage + $delta);
            
            for ($i = $start; $i <= $end; $i++) {
                $range[] = $i;
            }
            
            if ($this->currentPage + $delta < $this->totalPages - 1) {
                $range[] = '...';
            }
            
            $range[] = $this->totalPages;
        }
        
        return $range;
    }
    
    /**
     * Build URL for page
     */
    private function buildUrl($page) {
        if (empty($this->baseUrl)) {
            // Use current URL
            $url = $_SERVER['PHP_SELF'];
            $query = $_GET;
            $query['page'] = $page;
            return $url . '?' . http_build_query($query);
        }
        
        // Use provided base URL
        $separator = strpos($this->baseUrl, '?') !== false ? '&' : '?';
        return $this->baseUrl . $separator . 'page=' . $page;
    }
}
