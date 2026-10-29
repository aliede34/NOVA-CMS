<?php
/**
 * Plugin Name: Duyuru Bandı (Announcement Bar)
 * Description: Sitenin en üst kısmında şık ve dikkat çekici bir duyuru/kampanya bildirimi gösterir.
 * Version: 1.0.0
 * Author: NovaCMS Topluluğu
 */

Hook::addAction('after_body_start', function() {
    ?>
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs py-2 px-4 text-center font-medium shadow-inner flex items-center justify-center gap-2">
        <span class="px-2 py-0.5 rounded-full bg-white/20 text-[10px] uppercase font-bold tracking-wider">Duyuru</span>
        <span>🎉 NovaCMS Eklenti Mimarisi devrede! Kendi modüllerinizi <code>/plugins</code> klasörüne ekleyebilirsiniz.</span>
    </div>
    <?php
});

