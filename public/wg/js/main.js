/* ===================================
   恒达信物业 — 交互脚本
   =================================== */

document.addEventListener('DOMContentLoaded', () => {

    // ========== Header scroll effect ==========
    const header = document.getElementById('header');
    let lastScrollY = 0;

    function onScroll() {
        const scrollY = window.scrollY;
        if (scrollY > 60) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        lastScrollY = scrollY;
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // init

    // ========== Mobile nav toggle ==========
    const navToggle = document.getElementById('navToggle');
    const nav = document.getElementById('nav');
    if (navToggle && nav) {
        navToggle.addEventListener('click', () => {
            nav.classList.toggle('open');
            const isOpen = nav.classList.contains('open');
            navToggle.setAttribute('aria-expanded', isOpen);
        });
        // Close nav when clicking a link
        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                nav.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
        // Close nav when clicking outside
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !navToggle.contains(e.target)) {
                nav.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ========== Counter animation ==========
    const counterEls = document.querySelectorAll('[data-count]');
    let countersAnimated = false;

    function animateCounters() {
        if (countersAnimated) return;
        countersAnimated = true;

        counterEls.forEach(el => {
            const target = parseInt(el.getAttribute('data-count'), 10);
            const duration = 2000;
            const startTime = performance.now();

            function update(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // easeOutExpo
                const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                const current = Math.floor(eased * target);
                el.textContent = current.toLocaleString();
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            requestAnimationFrame(update);
        });
    }

    // Trigger counter when hero stats are visible
    const heroStats = document.querySelector('.hero-stats');
    if (heroStats) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        observer.observe(heroStats);
    } else {
        animateCounters();
    }

    // ========== Scroll reveal animation ==========
    const revealEls = document.querySelectorAll('.service-card, .why-card, .value-card, .sf-item, .timeline-item');

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        revealObserver.observe(el);
    });

    // ========== Contact form ==========
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = contactForm.querySelector('.form-submit');
            const originalText = btn.textContent;
            btn.textContent = '提交中...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('name', document.getElementById('name').value.trim());
            formData.append('phone', document.getElementById('phone').value.trim());
            formData.append('type', document.getElementById('type').value);
            formData.append('message', document.getElementById('message').value.trim());

            try {
                const resp = await fetch('/api/consultation/add', {
                    method: 'POST',
                    body: formData
                });
                const result = await resp.json();

                if (result.code === 0) {
                    btn.textContent = '✓ 提交成功！';
                    btn.style.background = '#27ae60';
                    contactForm.reset();
                } else {
                    btn.textContent = result.msg || '提交失败，请重试';
                    btn.style.background = '#e74c3c';
                }
            } catch (err) {
                btn.textContent = '网络错误，请重试';
                btn.style.background = '#e74c3c';
            }

            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
                btn.disabled = false;
            }, 3000);
        });
    }

    // ========== Smooth scroll for anchor links ==========
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ========== Active nav link highlight ==========
    const currentPath = window.location.pathname;
    const navLinks = nav.querySelectorAll('a');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || (href !== '/wg/' && currentPath.startsWith(href.replace(/\/$/, '')))) {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    });

});
