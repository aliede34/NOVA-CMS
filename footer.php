    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 mt-12 py-8 text-center text-sm text-slate-500 dark:text-slate-400 transition-colors">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <p>&copy; <?= date('Y') ?> <?= sanitize(getSetting('site_name', 'NovaCMS')) ?>. Açık Kaynak İçerik Yönetim Sistemi.</p>
            <div class="flex items-center gap-4 text-xs">
                <a href="contact.php" class="hover:text-indigo-600 dark:hover:text-indigo-400">İletişim</a>
                <a href="feed.php" class="hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1"><i class="fa-solid fa-rss text-amber-500"></i> RSS 2.0</a>
                <a href="sitemap.php" class="hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1"><i class="fa-solid fa-sitemap text-blue-500"></i> Sitemap</a>
                <a href="admin/index.php" class="hover:text-indigo-600 dark:hover:text-indigo-400">Yönetim</a>
            </div>
        </div>
    </footer>

    <!-- Dark Mode Switcher JS -->
    <script>
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        const themeToggleBtn = document.getElementById('theme-toggle');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
                themeToggleDarkIcon.classList.toggle('hidden');
                themeToggleLightIcon.classList.toggle('hidden');

                if (localStorage.getItem('color-theme')) {
                    if (localStorage.getItem('color-theme') === 'light') {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    }
                } else {
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('color-theme', 'light');
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('color-theme', 'dark');
                    }
                }
            });
        }
    </script>
    <?php Hook::doAction('before_body_end'); ?>
</body>
</html>
