<?php
require_once 'includes/header.php';

// Default values in case DB is not initialized
$totalAnggota = 0;
$lancarCount = 0;
$diragukanCount = 0;
$macetCount = 0;
$avgRasioLancar = 0;
$avgRasioDiragukan = 0;
$avgRasioMacet = 0;
$recentPredictions = [];

if (isset($pdo)) {
    try {
        // Fetch KPIs
        $totalAnggota = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
        $lancarCount = $pdo->query("SELECT COUNT(*) FROM anggota WHERE label_risiko = 'Lancar'")->fetchColumn();
        $diragukanCount = $pdo->query("SELECT COUNT(*) FROM anggota WHERE label_risiko = 'Diragukan'")->fetchColumn();
        $macetCount = $pdo->query("SELECT COUNT(*) FROM anggota WHERE label_risiko = 'Macet'")->fetchColumn();
        
        // Fetch average savings ratio by risk level
        $avgRasioLancar = $pdo->query("SELECT AVG(rasio_simpanan) FROM anggota WHERE label_risiko = 'Lancar'")->fetchColumn() ?: 0;
        $avgRasioDiragukan = $pdo->query("SELECT AVG(rasio_simpanan) FROM anggota WHERE label_risiko = 'Diragukan'")->fetchColumn() ?: 0;
        $avgRasioMacet = $pdo->query("SELECT AVG(rasio_simpanan) FROM anggota WHERE label_risiko = 'Macet'")->fetchColumn() ?: 0;
        
        // Fetch recent predictions
        $recentPredictions = $pdo->query("SELECT * FROM prediksi_history ORDER BY id DESC LIMIT 5")->fetchAll();
    } catch (PDOException $e) {
        // Handle gracefully
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Analisis Risiko</h1>
        <p class="page-subtitle">Sistem Analisis dan Prediksi Risiko Angsuran Anggota Koperasi Mitra Dhuafa (KOMIDA)</p>
    </div>
</div>

<!-- KPI Grid -->
<div class="card-grid">
    <div class="card kpi-card">
        <div>
            <div class="kpi-title">Total Anggota Teranalisis</div>
            <div class="kpi-value"><?php echo number_format($totalAnggota); ?></div>
        </div>
        <div class="kpi-icon kpi-blue">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
    
    <div class="card kpi-card">
        <div>
            <div class="kpi-title">Risiko Rendah / Lancar</div>
            <div class="kpi-value"><?php echo number_format($lancarCount); ?></div>
        </div>
        <div class="kpi-icon kpi-green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>
    
    <div class="card kpi-card">
        <div>
            <div class="kpi-title">Risiko Sedang / Diragukan</div>
            <div class="kpi-value"><?php echo number_format($diragukanCount); ?></div>
        </div>
        <div class="kpi-icon kpi-yellow">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>
    
    <div class="card kpi-card">
        <div>
            <div class="kpi-title">Risiko Tinggi / Macet</div>
            <div class="kpi-value"><?php echo number_format($macetCount); ?></div>
        </div>
        <div class="kpi-icon kpi-red">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="section-grid">
    <div class="card">
        <h2 class="section-title">
            <i class="fa-solid fa-chart-pie"></i> Distribusi Risiko Angsuran Anggota
        </h2>
        <div style="height: 300px; display: flex; justify-content: center; align-items: center;">
            <canvas id="riskDistributionChart" style="max-height: 100%; max-width: 100%;"></canvas>
        </div>
    </div>
    
    <div class="card">
        <h2 class="section-title">
            <i class="fa-solid fa-chart-simple"></i> Hubungan Rasio Simpanan vs Risiko
        </h2>
        <div style="height: 300px; display: flex; justify-content: center; align-items: center;">
            <canvas id="savingsRatioChart" style="max-height: 100%; max-width: 100%;"></canvas>
        </div>
    </div>
</div>

<!-- Recent Predictions Table -->
<div class="card" style="margin-bottom: 2rem;">
    <h2 class="section-title">
        <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Prediksi Risiko Terbaru
    </h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Waktu Prediksi</th>
                    <th>Nama Calon Anggota</th>
                    <th>Tujuan Pinjaman</th>
                    <th>Jumlah Pinjaman</th>
                    <th>Total Simpanan</th>
                    <th>Rasio Simpanan</th>
                    <th>Hasil Prediksi Risiko</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentPredictions)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Belum ada riwayat prediksi. Silakan lakukan prediksi pada menu <a href="predict.php" style="color: var(--primary); font-weight: 600;">Prediksi Risiko</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentPredictions as $pred): ?>
                        <tr>
                            <td><?php echo date('d-m-Y H:i', strtotime($pred['created_at'])); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($pred['nama']); ?></td>
                            <td><?php echo htmlspecialchars($pred['tujuan_pinjaman']); ?></td>
                            <td>Rp <?php echo number_format($pred['jumlah_pinjaman']); ?></td>
                            <td>Rp <?php echo number_format($pred['total_simpanan']); ?></td>
                            <td><?php echo number_format($pred['rasio_simpanan'] * 100, 2); ?>%</td>
                            <td>
                                <?php if ($pred['predicted_label'] === 'Lancar'): ?>
                                    <span class="badge badge-lancar"><i class="fa-solid fa-check"></i> Rendah (Lancar)</span>
                                <?php elseif ($pred['predicted_label'] === 'Diragukan'): ?>
                                    <span class="badge badge-diragukan"><i class="fa-solid fa-exclamation"></i> Sedang (Diragukan)</span>
                                <?php else: ?>
                                    <span class="badge badge-macet"><i class="fa-solid fa-xmark"></i> Tinggi (Macet)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Inline script to pass PHP variable data to Chart.js -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Risk Distribution Chart
    const riskCtx = document.getElementById('riskDistributionChart').getContext('2d');
    new Chart(riskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Lancar (Risiko Rendah)', 'Diragukan (Risiko Sedang)', 'Macet (Risiko Tinggi)'],
            datasets: [{
                data: [
                    <?php echo $lancarCount; ?>,
                    <?php echo $diragukanCount; ?>,
                    <?php echo $macetCount; ?>
                ],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Plus Jakarta Sans', size: 12 },
                        color: '#475569'
                    }
                }
            },
            cutout: '65%'
        }
    });

    // 2. Savings Ratio vs Risk Level Chart
    const ratioCtx = document.getElementById('savingsRatioChart').getContext('2d');
    new Chart(ratioCtx, {
        type: 'bar',
        data: {
            labels: ['Lancar (Rendah)', 'Diragukan (Sedang)', 'Macet (Tinggi)'],
            datasets: [{
                label: 'Rata-rata Rasio Simpanan (%)',
                data: [
                    <?php echo round($avgRasioLancar * 100, 2); ?>,
                    <?php echo round($avgRasioDiragukan * 100, 2); ?>,
                    <?php echo round($avgRasioMacet * 100, 2); ?>
                ],
                backgroundColor: ['rgba(16, 185, 129, 0.75)', 'rgba(245, 158, 11, 0.75)', 'rgba(239, 68, 68, 0.75)'],
                borderColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Persentase Simpanan dibanding Pinjaman (%)',
                        font: { family: 'Plus Jakarta Sans', size: 11 }
                    },
                    ticks: {
                        callback: function(value) { return value + '%'; }
                    }
                },
                x: {
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
