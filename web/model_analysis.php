<?php
require_once 'includes/header.php';
requireRole(['administrator', 'manager']);
require_once 'includes/DecisionTree.php';

$modelData = null;
$error = null;

try {
    $modelPath = dirname(__DIR__) . '/ml/model.json';
    if (!file_exists($modelPath)) {
        // Automatically train model if model.json is missing
        $csvFile = dirname(__DIR__) . '/data/dataset_komida.csv';
        if (!file_exists($csvFile)) {
            throw new Exception("Dataset atau file model.json tidak ditemukan! Silakan pastikan data telah terinisialisasi.");
        }
        
        $dataset = [];
        $fp = fopen($csvFile, 'r');
        $headers = fgetcsv($fp);
        while (($row = fgetcsv($fp)) !== FALSE) {
            $record = array_combine($headers, $row);
            $record['jumlah_pinjaman'] = (float)$record['jumlah_pinjaman'];
            $record['tenor'] = (int)$record['tenor'];
            $record['cicilan'] = (float)$record['cicilan'];
            $record['simp_wajib'] = (float)$record['simp_wajib'];
            $record['simp_sukarela'] = (float)$record['simp_sukarela'];
            $record['simp_pensiun'] = (float)$record['simp_pensiun'];
            $record['simp_hari_raya'] = (float)$record['simp_hari_raya'];
            $record['total_simpanan'] = (float)$record['total_simpanan'];
            $record['rasio_simpanan'] = (float)$record['rasio_simpanan'];
            $record['week_past_due'] = (int)$record['week_past_due'];
            $dataset[] = $record;
        }
        fclose($fp);
        
        $features = [
            'jumlah_pinjaman', 'tenor', 'cicilan', 
            'simp_wajib', 'simp_sukarela', 'simp_pensiun', 'simp_hari_raya', 
            'total_simpanan', 'rasio_simpanan', 'tujuan_pinjaman'
        ];
        
        $dt = new DecisionTree(5, 10);
        $dt->train($dataset, $features, 'label_risiko');
        $modelData = $dt->saveModel($modelPath, $dataset, 'label_risiko');
    } else {
        $modelData = json_decode(file_get_contents($modelPath), true);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

/**
 * Format split threshold value for humans
 */
function formatThreshold($feature, $val) {
    if ($feature === 'rasio_simpanan') {
        return round($val * 100, 2) . '%';
    } elseif (in_array($feature, ['jumlah_pinjaman', 'cicilan', 'simp_wajib', 'simp_sukarela', 'simp_pensiun', 'simp_hari_raya', 'total_simpanan'])) {
        return 'Rp ' . number_format($val);
    } elseif ($feature === 'tenor') {
        return $val . ' minggu';
    }
    return $val;
}

/**
 * Translate features name to Indonesian readable
 */
function translateFeature($feature) {
    $map = [
        'jumlah_pinjaman' => 'Jumlah Pinjaman',
        'tenor' => 'Tenor Pembayaran',
        'cicilan' => 'Besar Cicilan Mingguan',
        'simp_wajib' => 'Simpanan Wajib',
        'simp_sukarela' => 'Simpanan Sukarela',
        'simp_pensiun' => 'Simpanan Pensiun',
        'simp_hari_raya' => 'Simpanan Hari Raya',
        'total_simpanan' => 'Total Simpanan',
        'rasio_simpanan' => 'Rasio Simpanan/Pinjaman',
        'tujuan_pinjaman' => 'Tujuan Sektor Usaha'
    ];
    return $map[$feature] ?? $feature;
}

/**
 * Recursive function to render decision tree rules in HTML format
 */
function renderTreeHtml($node, $indent = 0) {
    if (!$node) return '';
    
    $html = '';
    $indentHtml = str_repeat('<span class="tree-indent"></span>', $indent);
    
    if ($node['type'] === 'leaf') {
        $classColor = 'var(--lancar)';
        $labelBahasa = 'Rendah (Lancar)';
        if ($node['class'] === 'Diragukan') {
            $classColor = 'var(--diragukan)';
            $labelBahasa = 'Sedang (Diragukan)';
        } elseif ($node['class'] === 'Macet') {
            $classColor = 'var(--macet)';
            $labelBahasa = 'Tinggi (Macet)';
        }
        
        $html .= $indentHtml . "➔ <span style='color: {$classColor}; font-weight: bold;'>[KLASIFIKASI: {$labelBahasa}]</span> ";
        $html .= "<span style='color: #94a3b8;'>(Keyakinan: " . round($node['probability'] * 100, 1) . "%, Samples: {$node['samples']})</span><br>";
        return $html;
    }
    
    $featureLabel = translateFeature($node['feature']);
    $valFormatted = formatThreshold($node['feature'], $node['threshold']);
    
    // Split Continuous vs Categorical presentation
    if (is_numeric($node['threshold'])) {
        $html .= $indentHtml . "⚙️ <strong>JIKA {$featureLabel}</strong> &le; {$valFormatted}:<br>";
        $html .= renderTreeHtml($node['left'], $indent + 1);
        
        $html .= $indentHtml . "⚙️ <strong>JIKA {$featureLabel}</strong> &gt; {$valFormatted}:<br>";
        $html .= renderTreeHtml($node['right'], $indent + 1);
    } else {
        $html .= $indentHtml . "⚙️ <strong>JIKA {$featureLabel}</strong> ADALAH '{$valFormatted}':<br>";
        $html .= renderTreeHtml($node['left'], $indent + 1);
        
        $html .= $indentHtml . "⚙️ <strong>JIKA {$featureLabel}</strong> BUKAN '{$valFormatted}':<br>";
        $html .= renderTreeHtml($node['right'], $indent + 1);
    }
    
    return $html;
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Analisis Metrik & Logika Model</h1>
        <p class="page-subtitle">Evaluasi akurasi model CART Decision Tree, tingkat performa klasifikasi, dan representasi visual pohon keputusan.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i> <strong>Gagal memuat detail analisis model:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php elseif ($modelData): ?>
    
    <!-- Model Summary KPI -->
    <div class="card-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom: 2rem;">
        <div class="card kpi-card">
            <div>
                <div class="kpi-title">Akurasi Model (Accuracy)</div>
                <div class="kpi-value"><?php echo round($modelData['accuracy'] * 100, 2); ?>%</div>
            </div>
            <div class="kpi-icon kpi-green">
                <i class="fa-solid fa-gauge-high"></i>
            </div>
        </div>
        
        <div class="card kpi-card">
            <div>
                <div class="kpi-title">Algoritma Klasifikasi</div>
                <div class="kpi-value" style="font-size: 1.5rem; margin-top: 0.9rem;">CART Decision Tree</div>
            </div>
            <div class="kpi-icon kpi-blue">
                <i class="fa-solid fa-diagram-project"></i>
            </div>
        </div>
        
        <div class="card kpi-card">
            <div>
                <div class="kpi-title">Ukuran Kedalaman Pohon</div>
                <div class="kpi-value" style="font-size: 1.5rem; margin-top: 0.9rem;">Maks. 5 Tingkat</div>
            </div>
            <div class="kpi-icon kpi-yellow">
                <i class="fa-solid fa-folder-tree"></i>
            </div>
        </div>
    </div>
    
    <div class="section-grid" style="grid-template-columns: 1.2fr 1fr; align-items: start;">
        
        <!-- Precision Recall Table -->
        <div class="card">
            <h2 class="section-title">
                <i class="fa-solid fa-chart-line"></i> Laporan Kinerja Klasifikasi (Classification Report)
            </h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Kelas Target</th>
                            <th style="text-align: center;">Presisi (Precision)</th>
                            <th style="text-align: center;">Sensitivitas (Recall)</th>
                            <th style="text-align: center;">F1-Score</th>
                            <th style="text-align: center;">Dukungan (Support)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modelData['classification_report'] as $cls => $metrics): ?>
                            <?php
                                $clsLabel = 'Rendah (Lancar)';
                                if ($cls === 'Diragukan') $clsLabel = 'Sedang (Diragukan)';
                                elseif ($cls === 'Macet') $clsLabel = 'Tinggi (Macet)';
                            ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo $clsLabel; ?></td>
                                <td style="text-align: center; font-weight: 500;"><?php echo round($metrics['precision'] * 100, 2); ?>%</td>
                                <td style="text-align: center; font-weight: 500;"><?php echo round($metrics['recall'] * 100, 2); ?>%</td>
                                <td style="text-align: center; font-weight: 500;"><?php echo round($metrics['f1-score'] * 100, 2); ?>%</td>
                                <td style="text-align: center; color: var(--text-secondary);"><?php echo $metrics['support']; ?> data</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1rem; font-size: 0.82rem; color: var(--text-secondary); border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <p>💡 <strong>Presisi:</strong> Seberapa akurat model memprediksi status tertentu dari seluruh prediksi kelas tersebut.</p>
                <p style="margin-top: 0.25rem;">💡 <strong>Recall:</strong> Seberapa banyak anggota dengan status riil tertentu yang berhasil diidentifikasi dengan benar oleh model.</p>
            </div>
        </div>
        
        <!-- Confusion Matrix Heatmap -->
        <div class="card">
            <h2 class="section-title">
                <i class="fa-solid fa-border-all"></i> Confusion Matrix Heatmap
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">Membandingkan kelas aktual anggota (Y) dengan kelas yang diprediksi oleh model (X).</p>
            
            <div class="table-responsive">
                <table class="matrix-table" style="border: 1px solid var(--border-color); width: 100%;">
                    <thead>
                        <tr>
                            <th style="background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">Aktual \ Prediksi</th>
                            <th style="text-align: center; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">Lancar</th>
                            <th style="text-align: center; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">Diragukan</th>
                            <th style="text-align: center; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">Macet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $classes = ['Lancar', 'Diragukan', 'Macet'];
                        $matrix = $modelData['confusion_matrix'];
                        for ($i = 0; $i < 3; $i++): 
                            $actName = $classes[$i];
                        ?>
                            <tr>
                                <td style="font-weight: bold; background-color: #f8fafc; border-right: 2px solid #cbd5e1;"><?php echo $actName; ?></td>
                                <?php for ($j = 0; $j < 3; $j++): 
                                    $val = $matrix[$i][$j];
                                    $cellClass = 'matrix-cell-low';
                                    if ($i == $j) {
                                        $cellClass = ($val > 100) ? 'matrix-cell-high' : 'matrix-cell-mid';
                                    }
                                ?>
                                    <td class="matrix-cell <?php echo $cellClass; ?>" style="border: 1px solid #e2e8f0;">
                                        <?php echo $val; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Feature Importances -->
    <div class="card" style="margin-bottom: 2rem;">
        <h2 class="section-title">
            <i class="fa-solid fa-chart-column"></i> Kontribusi Fitur / Variabel Model (Feature Importance)
        </h2>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.25rem;">
            <?php 
            arsort($modelData['feature_importances']);
            foreach ($modelData['feature_importances'] as $feat => $imp): 
                $pct = round($imp * 100, 2);
            ?>
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem;">
                        <span><?php echo translateFeature($feat); ?> (<code><?php echo $feat; ?></code>)</span>
                        <span style="color: var(--primary);"><?php echo $pct; ?>%</span>
                    </div>
                    <div style="width: 100%; height: 10px; background-color: #e2e8f0; border-radius: 999px; overflow: hidden;">
                        <div style="width: <?php echo $pct; ?>%; height: 100%; background-color: var(--primary); border-radius: 999px;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Tree Visual Rules -->
    <div class="card">
        <h2 class="section-title">
            <i class="fa-solid fa-code-fork"></i> Visualisasi Logika Pohon Keputusan (Decision Tree Rules)
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
            Dosen penguji / pengguna dapat menelusuri alur logika pohon di bawah ini (Explainable Decision Paths) yang digunakan model untuk mengambil keputusan klasifikasi risiko:
        </p>
        <div class="decision-tree-rules">
            <?php echo renderTreeHtml($modelData['tree']); ?>
        </div>
    </div>
    
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
