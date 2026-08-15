// assets/js/animations.js

document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Scroll Reveal Engine
    const scrollObserverOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                // Optional: Stop observing once animated in
                // observer.unobserve(entry.target); 
            }
        });
    }, scrollObserverOptions);

    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    animatedElements.forEach(el => scrollObserver.observe(el));

    // 2. Button Ripple Effect
    const buttons = document.querySelectorAll('.btn, .nav-link');
    buttons.forEach(btn => {
        // Ensure parent has position relative and overflow hidden
        if(btn.classList.contains('btn')) {
             btn.classList.add('btn-ripple-container');
        }
       
        btn.addEventListener('click', function(e) {
            let x = e.clientX - e.target.getBoundingClientRect().left;
            let y = e.clientY - e.target.getBoundingClientRect().top;

            let ripples = document.createElement('span');
            ripples.style.left = x + 'px';
            ripples.style.top = y + 'px';
            ripples.classList.add('ripple');
            this.appendChild(ripples);

            setTimeout(() => {
                ripples.remove();
            }, 600);
        });
    });

    // 3. 3D Card Hover Tilt Effect
    const tiltCards = document.querySelectorAll('.tilt-card');
    tiltCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left; // x position within the element.
            const y = e.clientY - rect.top;  // y position within the element.
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * -10; // Max tilt 10deg
            const rotateY = ((x - centerX) / centerX) * 10;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
            
            // Glare effect logic
            let glare = card.querySelector('.glare-effect');
            if(glare) {
                const percentX = (x / rect.width) * 100;
                const percentY = (y / rect.height) * 100;
                glare.style.background = `radial-gradient(circle at ${percentX}% ${percentY}%, rgba(255,255,255,0.2), transparent 50%)`;
            }
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
            let glare = card.querySelector('.glare-effect');
            if(glare) glare.style.background = `radial-gradient(circle at 50% 50%, rgba(255,255,255,0.2), transparent 50%)`;
        });
    });

    // 4. Magnetic Buttons Engine
    const magneticElements = document.querySelectorAll('.magnetic-btn');
    magneticElements.forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px) scale(1.05)`;
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = `translate(0px, 0px) scale(1)`;
        });
    });

    // 5. Number Counter Animation
    const counterObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const countTo = parseInt(target.getAttribute('data-count'), 10);
                if (isNaN(countTo)) return;
                
                let current = 0;
                const duration = 2000; // 2 seconds
                const stepTime = Math.abs(Math.floor(duration / countTo));
                const increment = Math.max(1, Math.ceil(countTo / 50));
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= countTo) {
                        target.innerText = countTo;
                        clearInterval(timer);
                    } else {
                        target.innerText = current;
                    }
                }, stepTime);
                
                observer.unobserve(target); // Only animate once
            }
        });
    }, scrollObserverOptions);

    const counters = document.querySelectorAll('.count-up');
    counters.forEach(counter => {
        // Set initial value to 0 if data-count exists
        if(counter.getAttribute('data-count')) {
            counter.innerText = '0';
            counterObserver.observe(counter);
        }
    });

    // 6. Background Parallax Effect - Removed per user request to keep backgrounds static
    // No background movement script

    // 7. Click-to-Expand 3D Cards
    document.querySelectorAll('.premium-glass-card').forEach(card => {
        // Skip cards on the home page (hero section) or cards explicitly marked no-expand
        if(card.closest('.hero-content') || card.classList.contains('no-expand')) return;
        
        card.style.cursor = 'zoom-in';
        card.addEventListener('click', function(e) {
            // Prevent if clicking a button or link inside
            if(e.target.closest('a') || e.target.closest('button') || e.target.closest('input')) return;
            
            if(this.classList.contains('is-expanded')) return; // Already expanded
            
            // Create backdrop overlay
            const overlay = document.createElement('div');
            overlay.className = 'card-expand-overlay';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100vw';
            overlay.style.height = '100vh';
            overlay.style.backgroundColor = 'rgba(0,0,0,0.6)';
            overlay.style.backdropFilter = 'blur(15px)';
            overlay.style.zIndex = '9999';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.4s ease';
            
            // Clone the card
            const clone = this.cloneNode(true);
            clone.classList.remove('tilt-card', 'animate-on-scroll', 'is-visible');
            clone.classList.add('is-expanded');
            clone.style.width = '80vw';
            clone.style.maxWidth = '1000px';
            clone.style.maxHeight = '90vh';
            clone.style.overflowY = 'auto';
            clone.style.cursor = 'default';
            clone.style.transform = 'scale(0.8) translateY(50px)';
            clone.style.transition = 'all 0.5s cubic-bezier(0.19, 1, 0.22, 1)';
            clone.style.boxShadow = '0 30px 100px rgba(0,0,0,0.8)';
            
            // Close button
            const closeBtn = document.createElement('button');
            closeBtn.className = 'btn btn-outline-light rounded-circle position-absolute';
            closeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
            closeBtn.style.top = '15px';
            closeBtn.style.right = '15px';
            closeBtn.style.zIndex = '10';
            
            closeBtn.onclick = overlay.onclick = function(e) {
                if(e.target === overlay || e.target.closest('button') === closeBtn) {
                    overlay.style.opacity = '0';
                    clone.style.transform = 'scale(0.8) translateY(50px)';
                    setTimeout(() => overlay.remove(), 400);
                }
            };
            
            clone.appendChild(closeBtn);
            overlay.appendChild(clone);
            document.body.appendChild(overlay);
            
            // Trigger animation
            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                clone.style.transform = 'scale(1) translateY(0)';
            });
        });
    });

    // 8. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebarMenu');
    if(sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            // If shown, add a backdrop
            if(sidebar.classList.contains('show')) {
                let backdrop = document.querySelector('.sidebar-backdrop');
                if(!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'sidebar-backdrop';
                    backdrop.style.position = 'fixed';
                    backdrop.style.top = '0';
                    backdrop.style.left = '0';
                    backdrop.style.width = '100vw';
                    backdrop.style.height = '100vh';
                    backdrop.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    backdrop.style.backdropFilter = 'blur(5px)';
                    backdrop.style.zIndex = '999';
                    document.body.appendChild(backdrop);
                    
                    backdrop.addEventListener('click', () => {
                        sidebar.classList.remove('show');
                        backdrop.remove();
                    });
                }
            } else {
                const backdrop = document.querySelector('.sidebar-backdrop');
                if(backdrop) backdrop.remove();
            }
        });
    }

    // 9. Dynamic Table-to-Card Engine for Mobile
    document.querySelectorAll('.table').forEach(table => {
        const headers = Array.from(table.querySelectorAll('th')).map(th => th.innerText.trim());
        table.querySelectorAll('tbody tr').forEach(tr => {
            Array.from(tr.querySelectorAll('td')).forEach((td, index) => {
                if(headers[index]) {
                    td.setAttribute('data-label', headers[index]);
                }
            });
        });
    });

    // 10. Sidebar Scroll State Persistence
    const sidebarElement = document.querySelector('.sidebar-sticky');
    if (sidebarElement) {
        // Restore scroll position on load
        const savedScroll = localStorage.getItem('sidebarScrollPos');
        if (savedScroll) {
            // Need a slight delay to ensure rendering is complete before scrolling
            setTimeout(() => {
                sidebarElement.scrollTop = parseInt(savedScroll, 10);
            }, 50);
        }
        
        // Save scroll position when user scrolls the sidebar
        sidebarElement.addEventListener('scroll', () => {
            localStorage.setItem('sidebarScrollPos', sidebarElement.scrollTop);
        });
        
        // Also save on link click to guarantee accuracy
        document.querySelectorAll('.sidebar-sticky .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                localStorage.setItem('sidebarScrollPos', sidebarElement.scrollTop);
            });
        });
    }
});
