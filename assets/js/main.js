// assets/js/main.js - Super Modern Interactivity & Ultra-Animation Suite

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------
    // 1. Interactive Particle Network Canvas (Hero Background)
    // -------------------------------------------------------------
    const canvas = document.getElementById('heroParticleCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width = (canvas.width = canvas.parentElement.offsetWidth);
        let height = (canvas.height = canvas.parentElement.offsetHeight);

        const particles = [];
        const particleCount = Math.min(Math.floor(width / 22), 65);
        let mouse = { x: null, y: null, radius: 140 };

        window.addEventListener('resize', () => {
            if (canvas.parentElement) {
                width = canvas.width = canvas.parentElement.offsetWidth;
                height = canvas.height = canvas.parentElement.offsetHeight;
            }
        });

        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            heroSection.addEventListener('mousemove', (e) => {
                const rect = canvas.getBoundingClientRect();
                mouse.x = e.clientX - rect.left;
                mouse.y = e.clientY - rect.top;
            });
            heroSection.addEventListener('mouseleave', () => {
                mouse.x = null;
                mouse.y = null;
            });
        }

        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.size = Math.random() * 2.2 + 1;
                this.speedX = (Math.random() - 0.5) * 0.7;
                this.speedY = (Math.random() - 0.5) * 0.7;
                this.color = Math.random() > 0.3 ? '#06b6d4' : '#ff4d2d';
                this.alpha = Math.random() * 0.5 + 0.25;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x < 0) this.x = width;
                if (this.x > width) this.x = 0;
                if (this.y < 0) this.y = height;
                if (this.y > height) this.y = 0;

                // Mouse interaction (repulsion)
                if (mouse.x !== null && mouse.y !== null) {
                    const dx = mouse.x - this.x;
                    const dy = mouse.y - this.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < mouse.radius) {
                        const force = (mouse.radius - distance) / mouse.radius;
                        const angle = Math.atan2(dy, dx);
                        this.x -= Math.cos(angle) * force * 3;
                        this.y -= Math.sin(angle) * force * 3;
                    }
                }
            }
            draw() {
                ctx.save();
                ctx.globalAlpha = this.alpha;
                ctx.fillStyle = this.color;
                ctx.shadowBlur = 8;
                ctx.shadowColor = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();
            }
        }

        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }

        function renderParticles() {
            ctx.clearRect(0, 0, width, height);

            // Connect nearby particles with subtle laser lines
            for (let a = 0; a < particles.length; a++) {
                for (let b = a + 1; b < particles.length; b++) {
                    const dx = particles[a].x - particles[b].x;
                    const dy = particles[a].y - particles[b].y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < 115) {
                        ctx.save();
                        const alpha = (1 - dist / 115) * 0.22;
                        ctx.strokeStyle = `rgba(6, 182, 212, ${alpha})`;
                        ctx.lineWidth = 0.8;
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.stroke();
                        ctx.restore();
                    }
                }
                particles[a].update();
                particles[a].draw();
            }
            requestAnimationFrame(renderParticles);
        }
        renderParticles();
    }

    // -------------------------------------------------------------
    // 2. True 3D Perspective Card Tilt with Dynamic Specular Glare
    // -------------------------------------------------------------
    function initTiltCards() {
        const tiltElements = document.querySelectorAll('.tilt-card');
        
        tiltElements.forEach(card => {
            // Append glare element if not present
            if (!card.querySelector('.tilt-glare')) {
                const glare = document.createElement('div');
                glare.className = 'tilt-glare';
                card.appendChild(glare);
            }

            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = ((y - centerY) / centerY) * -9;
                const rotateY = ((x - centerX) / centerX) * 9;

                card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) scale3d(1.025, 1.025, 1.025)`;
                card.style.setProperty('--mouse-x', `${(x / rect.width) * 100}%`);
                card.style.setProperty('--mouse-y', `${(y / rect.height) * 100}%`);
            });

            card.addEventListener('mouseleave', () => {
                card.style.transition = 'transform 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
                card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
                setTimeout(() => {
                    card.style.transition = '';
                }, 500);
            });
        });
    }
    initTiltCards();

    // -------------------------------------------------------------
    // 3. Scroll-Triggered Entrance Animations (Intersection Observer)
    // -------------------------------------------------------------
    const revealElements = document.querySelectorAll('.reveal-on-scroll');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -30px 0px'
        });

        revealElements.forEach(el => observer.observe(el));
    } else {
        revealElements.forEach(el => el.classList.add('is-visible'));
    }

    // -------------------------------------------------------------
    // 4. Animated Number Counters on Scroll
    // -------------------------------------------------------------
    const statsBar = document.querySelector('.stats-bar');
    let countersStarted = false;

    function animateCounter(el, target, suffix = '', duration = 1900, isFloat = false) {
        let startTime = null;
        function updateCount(currentTime) {
            if (!startTime) startTime = currentTime;
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const easeProgress = 1 - Math.pow(1 - progress, 4);
            const currentVal = isFloat ? (easeProgress * target).toFixed(1) : Math.floor(easeProgress * target).toLocaleString();
            el.innerHTML = `${currentVal}<span class="accent">${suffix}</span>`;
            if (progress < 1) {
                requestAnimationFrame(updateCount);
            } else {
                const finalVal = isFloat ? target.toFixed(1) : target.toLocaleString();
                el.innerHTML = `${finalVal}<span class="accent">${suffix}</span>`;
            }
        }
        requestAnimationFrame(updateCount);
    }

    if (statsBar && 'IntersectionObserver' in window) {
        const statsObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !countersStarted) {
                countersStarted = true;
                const counter1 = document.getElementById('statCounter1');
                const counter2 = document.getElementById('statCounter2');
                const counter3 = document.getElementById('statCounter3');
                const counter4 = document.getElementById('statCounter4');

                if (counter1) animateCounter(counter1, 12500, '+', 1900);
                if (counter2) animateCounter(counter2, 3200, '+', 1900);
                if (counter3) animateCounter(counter3, 4.9, '/5', 1900, true);
                if (counter4) animateCounter(counter4, 48, 'h', 1500);
            }
        }, { threshold: 0.2 });

        statsObserver.observe(statsBar);
    }

    // -------------------------------------------------------------
    // 5. Featured Products Interactive Category Tabs (Homepage)
    // -------------------------------------------------------------
    const tabButtons = document.querySelectorAll('.featured-tab-btn');
    const productCards = document.querySelectorAll('.featured-product-card');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const categoryFilter = btn.getAttribute('data-filter');

            productCards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                if (categoryFilter === 'all' || cardCat === categoryFilter) {
                    card.style.display = 'flex';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(15px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 280);
                }
            });
        });
    });

    // -------------------------------------------------------------
    // 6. Magnetic Pull on CTA Buttons
    // -------------------------------------------------------------
    const magneticBtns = document.querySelectorAll('.btn-primary');
    magneticBtns.forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            btn.style.transform = `translate(${x * 0.18}px, ${y * 0.18}px) scale(1.02)`;
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = '';
        });
    });

    // -------------------------------------------------------------
    // 7. Vehicle Finder Form Navigation
    // -------------------------------------------------------------
    const finderForm = document.getElementById('vehicleFinderForm');
    if (finderForm) {
        finderForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const brand = finderForm.querySelector('[name="brand_id"]').value;
            const category = finderForm.querySelector('[name="category_id"]').value;
            const search = finderForm.querySelector('[name="search"]').value;

            let url = 'products.php?';
            if (brand) url += `brand_id=${encodeURIComponent(brand)}&`;
            if (category) url += `category_id=${encodeURIComponent(category)}&`;
            if (search) url += `search=${encodeURIComponent(search)}`;
            window.location.href = url;
        });
    }

    // -------------------------------------------------------------
    // 8. Mobile Menu Toggle
    // -------------------------------------------------------------
    const menuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // -------------------------------------------------------------
    // 9. Auto-dismiss existing alerts
    // -------------------------------------------------------------
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
});

/**
 * Toast Notification with Animated Progress Bar
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '28px';
        container.style.right = '28px';
        container.style.zIndex = '99999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '12px';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.margin = '0';
    toast.style.minWidth = '310px';
    toast.style.boxShadow = '0 12px 36px rgba(0,0,0,0.65)';
    toast.style.backdropFilter = 'blur(18px)';
    toast.style.position = 'relative';
    toast.style.overflow = 'hidden';
    toast.innerHTML = `<span>${message}</span>`;

    const bar = document.createElement('div');
    bar.style.position = 'absolute';
    bar.style.bottom = '0';
    bar.style.left = '0';
    bar.style.height = '3px';
    bar.style.width = '100%';
    bar.style.backgroundColor = type === 'success' ? '#10b981' : '#ff4d2d';
    bar.style.transition = 'width 3.5s linear';
    toast.appendChild(bar);

    container.appendChild(toast);

    setTimeout(() => {
        bar.style.width = '0%';
    }, 50);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(25px)';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}
