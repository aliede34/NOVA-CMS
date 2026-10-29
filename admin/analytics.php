<?php
// admin/analytics.php - Dahili Gizlilik Dostu Ziyaretçi İstatistikleri
$adminTitle = "Ziyaretçi Analitiği";
require_once __DIR__ . '/layout_header.php';
requireRole('admin', 'editor');

$db = getDB();

// Son 7 günün günlük tekil ziyaret sayıları
$dailyVisits = $db->query("SELECT visit_date, COUNT(DISTINCT ip_hash) as unique_visits, COUNT(id) as total_views 
                          FROM analytics 
                          WHERE visit_date >= DATE('now', '-7 days') 
                          GROUP BY visit_date 
                          ORDER BY visit_date ASC")->fetchAll();

// En çok okunan 5 sayfa
$topPages = $db->query("SELECT page_url, COUNT(id) as view_count 
                        FROM analytics 
                        GROUP BY page_url 
                        ORDER BY view_count DESC LIMIT 5")->fetchAll();

// Tarayıcı dağılımı
$browsers = $db->query("SELECT browser, COUNT(id) as count 
                        FROM analytics 
                        GROUP BY browser 
                        ORDER BY count DESC")->fetchAll();

$todayVisits = $db->query("SELECT COUNT(DISTINCT ip_hash) FROM analytics WHERE visit_date = DATE('now')")->fetchColumn() ?: 0;
$todayViews = $db->query("SELECT COUNT(id) FROM analytics WHERE visit_date = DATE('now')")->fetchColumn() ?: 0;
$totalAllTime = $db->query("SELECT COUNT(id) FROM analytics")->fetchColumn() ?: 0;
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8">
    <!-- Özet İstatistik Kartları -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800"><?= number_format($todayVisits) ?></div>
                <div class="text-xs text-slate-500 font-medium">Bugünkü Tekil Ziyaretçi</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-eye"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800"><?= number_format($todayViews) ?></div>
                <div class="text-xs text-slate-500 font-medium">Bugünkü Sayfa Görüntüleme</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800"><?= number_format($totalAllTime) ?></div>
                <div class="text-xs text-slate-500 font-medium">Toplam Kayıtlı Hit</div>
            </div>
        </div>
    </div>

    <!-- Grafik Bölümü -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-chart-line text-indigo-600"></i> Son 7 Günün Ziyaret Trendi
        </h3>
        <div class="h-64">
            <canvas id="visitsChart"></canvas>
        </div>
    </div>

    <!-- 2 Kolonlu Tablo Alanı: En Çok Okunanlar & Tarayıcılar -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- En Çok Okunan Sayfalar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100 flex items-center gap-2 text-sm">
                <i class="fa-solid fa-fire text-amber-500"></i> En Çok Ziyaret Edilen Sayfalar
            </h3>
            <div class="divide-y divide-slate-100 text-xs">
                <?php if (empty($topPages)): ?>
                    <p class="py-4 text-center text-slate-400">Henüz yeterli analitik verisi yok.</p>
                <?php else: ?>
                    <?php foreach ($topPages as $tp): ?>
                        <div class="py-3 flex items-center justify-between">
                            <span class="font-mono text-slate-700 truncate max-w-xs"><?= sanitize($tp['page_url']) ?></span>
                            <span class="px-2.5 py-1 bg-slate-100 rounded-md font-bold text-indigo-600"><?= $tp['view_count'] ?> hit</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tarayıcı Dağılımı -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100 flex items-center gap-2 text-sm">
                <i class="fa-brands fa-chrome text-blue-500"></i> Tarayıcı Dağılımı
            </h3>
            <div class="space-y-3 text-xs">
                <?php if (empty($browsers)): ?>
                    <p class="py-4 text-center text-slate-400">Veri bulunmuyor.</p>
                <?php else: ?>
                    <?php foreach ($browsers as $b): ?>
                        <?php $pct = $totalAllTime > 0 ? round(($b['count'] / $totalAllTime) * 100, 1) : 0; ?>
                        <div>
                            <div class="flex justify-between items-center mb-1 font-semibold text-slate-700">
                                <span><?= sanitize($b['browser']) ?></span>
                                <span class="text-slate-400"><?= $b['count'] ?> (<?= $pct ?>%)</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('visitsChart').getContext('2d');
    const labels = <?= json_encode(array_column($dailyVisits, 'visit_date')) ?>;
    const uniqueData = <?= json_encode(array_column($dailyVisits, 'unique_visits')) ?>;
    const viewsData = <?= json_encode(array_column($dailyVisits, 'total_views')) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length ? labels : ['Bugün'],
            datasets: [
                {
                    label: 'Tekil Ziyaretçi',
                    data: uniqueData.length ? uniqueData : [<?= $todayVisits ?>],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Sayfa Görüntüleme',
                    data: viewsData.length ? viewsData : [<?= $todayViews ?>],
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } }
        }
    });
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

