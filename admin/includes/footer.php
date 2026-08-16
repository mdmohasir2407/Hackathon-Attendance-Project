            </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script>
        // Dark mode functionality
        const themeToggleBtn = document.getElementById('theme-toggle');
        const body = document.body;

        // Initialize button icon based on current state (set by header script)
        if (body.classList.contains('dark-mode')) {
            if(themeToggleBtn) themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
        } else {
            if(themeToggleBtn) themeToggleBtn.innerHTML = '<i class="bi bi-moon"></i>';
        }

        if(themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');
                
                if (isDark) {
                    themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
                    localStorage.setItem('theme', 'dark');
                } else {
                    themeToggleBtn.innerHTML = '<i class="bi bi-moon"></i>';
                    localStorage.setItem('theme', 'light');
                }
            });
        }

        // Set active nav link
        $(document).ready(function() {
            var path = window.location.pathname.split("/").pop();
            if (path == '') {
                path = 'dashboard.php';
            }
            var target = $('.sidebar .nav-link[href="'+path+'"]');
            target.addClass('active');

            // Fix modal stacking issue by moving all modals to the body
            $('.modal').appendTo('body');
        });
    </script>
    
    <!-- 3D Background Animation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Apply 3D animation to the body or a specific background container
            VANTA.NET({
                el: "body",
                mouseControls: true,
                touchControls: true,
                gyroControls: false,
                minHeight: 200.00,
                minWidth: 200.00,
                scale: 1.00,
                scaleMobile: 1.00,
                color: 0x0ea5e9,
                backgroundColor: 0xf8fafc,
                points: 10.00,
                maxDistance: 20.00,
                spacing: 20.00
            });
            
            // Fix z-index so content appears above the canvas
            const canvas = document.querySelector('.vanta-canvas');
            if(canvas) {
                canvas.style.zIndex = -1;
                canvas.style.position = 'fixed';
            }
        });
    </script>
</body>
</html>
