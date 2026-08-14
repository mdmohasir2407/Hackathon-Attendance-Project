            </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode functionality
        const themeToggleBtn = document.getElementById('theme-toggle');
        const body = document.body;

        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
        }

        themeToggleBtn.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            let theme = 'light';
            if (body.classList.contains('dark-mode')) {
                theme = 'dark';
                themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
            } else {
                themeToggleBtn.innerHTML = '<i class="bi bi-moon"></i>';
            }
            localStorage.setItem('theme', theme);
        });

        // Set active nav link
        $(document).ready(function() {
            var path = window.location.pathname.split("/").pop();
            if (path == '') {
                path = 'dashboard.php';
            }
            var target = $('.sidebar .nav-link[href="'+path+'"]');
            target.addClass('active');
        });
    </script>
</body>
</html>
