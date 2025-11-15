/**
 * Simple Page Transition & Smooth Scroll Handler (CRUD safe + Konfirmasi)
 * v3.4 (Clean) - Fungsi perbaikan sidebar dihapus, akan dipindah inline.
 */
(function() {
    'use strict';

    // ========= SMOOTH SCROLL =========
    function smoothScrollTo(element, duration = 700) {
        const targetPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        let startTime = null;
        function easeInOut(t) {
            return t < .5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        }
        function animate(currentTime) {
            if (startTime === null) startTime = currentTime;
            let timeElapsed = currentTime - startTime;
            let progress = Math.min(timeElapsed / duration, 1);
            let ease = easeInOut(progress);
            window.scrollTo(0, startPosition + distance * ease);
            if (timeElapsed < duration) requestAnimationFrame(animate);
        }
        requestAnimationFrame(animate);
    }
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                let href = this.getAttribute('href');
                if (!href || href === '#') return;
                let target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    smoothScrollTo(target);
                    if (history.pushState) history.pushState(null, null, href);
                }
            });
        });
    }

    // ========= PAGE TRANSITIONS (CRUD & confirm safe) =========
    function initPageTransitions() {
        const internalLinks = document.querySelectorAll(
            'a[href]:not([href^="#"]):not([href^="http"]):not([target="_blank"])'
        );
        internalLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                let href = this.getAttribute('href');
                
                const isExcluded = 
                    e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1 ||
                    this.hasAttribute('data-confirm') || 
                    this.classList.contains('no-transition') || 
                    this.hasAttribute('data-confirm-delete') || 
                    this.hasAttribute('data-confirm-logout') || 
                    this.hasAttribute('data-confirm-restore') || 
                    (this.getAttribute('onclick') || '').toLowerCase().match(/confirm|swal|bootbox/) || 
                    (href && href.match(/\/(edit|add|tambah|create|view|show)\.php/i));
                
                if (isExcluded) {
                    return; 
                }
                
                let pageDelay = 300; 

                e.preventDefault();
                document.body.classList.add('page-transitioning');
                setTimeout(() => {
                    window.location.href = href;
                }, pageDelay);
            });
        });
    }

    // ========= SCROLL TO TOP =========
    function initScrollToTop() {
        const scrollBtn = document.createElement('button');
        scrollBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
        scrollBtn.className = 'scroll-to-top-btn';
        scrollBtn.setAttribute('aria-label', 'Scroll to top');
        scrollBtn.style.cssText =
            'position:fixed;bottom:30px;right:30px;width:45px;height:45px;border-radius:50%;background:#435ebe;color:white;border:none;cursor:pointer;opacity:0;visibility:hidden;transition:all .3s;z-index:1000;box-shadow:0 4px 12px rgba(67,94,190,.3);display:flex;align:items:center;justify-content:center;font-size:1.2rem;';
        document.body.appendChild(scrollBtn);

        function toggleScrollButton() {
            if (window.pageYOffset > 300) {
                scrollBtn.style.opacity = '1'; scrollBtn.style.visibility = 'visible';
            } else {
                scrollBtn.style.opacity = '0'; scrollBtn.style.visibility = 'hidden';
            }
        }
        window.addEventListener('scroll', toggleScrollButton);
        scrollBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // ========= LAZY ANIMATION =========
    function initLazyAnimations() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const observerOpts = { threshold: .15, rootMargin: '0px 0px -60px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOpts);
        document.querySelectorAll('.lazy-animate').forEach(el => observer.observe(el));
    }

    // ========= BACK BUTTON FIX =========
    function handleBackButton() {
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.body.classList.remove('page-transitioning');
                let main = document.getElementById('main-content');
                if (main) { 
                    main.style.animation = 'none'; 
                    setTimeout(() => main.style.animation = '', 10); 
                }
            }
        });
    }

    // ========= INISIALISASI =========
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        initSmoothScroll();
        initPageTransitions();
        initScrollToTop();
        initLazyAnimations();
        handleBackButton();
        // Fungsi initSidebarScrollFix() sudah dihapus dari sini
    }
    
    init();
})();