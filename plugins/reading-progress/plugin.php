<?php
/**
 * Plugin Name: Okuma İlerleme Çubuğu (Reading Progress Bar)
 * Description: Yazı okunurken sayfanın en üstünde okuma yüzdesini gösteren dinamik bir ilerleme çubuğu ekler.
 * Version: 1.0.0
 * Author: NovaCMS Topluluğu
 */

Hook::addAction('after_body_start', function() {
    ?>
    <div id="readingProgressBar" class="fixed top-0 left-0 h-1 bg-gradient-to-r from-pink-500 via-indigo-500 to-emerald-400 z-[100] transition-all duration-100" style="width: 0%;"></div>
    <script>
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            const bar = document.getElementById('readingProgressBar');
            if (bar) {
                bar.style.width = scrolled + '%';
            }
        });
    </script>
    <?php
});

