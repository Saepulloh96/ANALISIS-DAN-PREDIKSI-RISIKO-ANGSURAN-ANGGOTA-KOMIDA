<?php
require_once 'includes/header.php';
require_once 'includes/DecisionTree.php';

$predictionResult = null;
$error = null;

// Get available list of Tujuan Pinjaman for the select dropdown
$tujuanOptions = [
    'WARUNGAN', 'JUAL MAKANAN', 'JUAL IKAN DAN LAINNYA', 'PERTANIAN', 
    'LAIN LAIN INVESTASI', 'JUAL PAKAIAN', 'JUAL SAYURAN', 'JUAL JASA', 
    'JUAL ALAT RUMAH TANGGA', 'SANITASI', 'WARUNG MAKAN/WARUNG KOPI',
    'RENOVASI RUMAH', 'JUAL OBAT, JAMU, KOSMETIK, CNI DLL', 'PETERNAKAN'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama = trim($_POST['nama']);
        $tujuan = $_POST['tujuan_pinjaman'];
        $loanAmt = (float)$_POST['jumlah_pinjaman'];
        $tenor = (int)$_POST['tenor'];
        $wajib = (float)$_POST['simp_wajib'];
        $sukarela = (float)$_POST['simp_sukarela'];
        $pensiun = (float)$_POST['simp_pensiun'];
        $hari_raya = (float)$_POST['simp_hari_raya'];

        if (empty($nama)) {
            throw new Exception("Nama calon anggota harus diisi.");
        }
        if ($loanAmt <= 0) {
            throw new Exception("Jumlah pinjaman harus lebih besar dari 0.");
        }

        // Calculations
        $total_savings = $wajib + $sukarela + $pensiun + $hari_raya;
        $rasio_simpanan = $total_savings / $loanAmt;
        $cicilan = round(($loanAmt / $tenor) * 1.12, -2); // 12% margin

        // Instatiate and load model
        $modelPath = dirname(__DIR__) . '/ml/model.json';
        $dt = new DecisionTree();
        
        // If model.json doesn't exist, we train it on-the-fly!
        if (!file_exists($modelPath)) {
            $csvFile = dirname(__DIR__) . '/data/dataset_komida.csv';
            if (!file_exists($csvFile)) {
                throw new Exception("Dataset untuk melatih model tidak ditemukan!");
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
            $dt->train($dataset, $features, 'label_risiko');
            $dt->saveModel($modelPath, $dataset, 'label_risiko');
        } else {
            $dt->loadModel($modelPath);
        }

        // Prepare data record for prediction
        $record = [
            'jumlah_pinjaman' => $loanAmt,
            'tenor' => $tenor,
            'cicilan' => $cicilan,
            'simp_wajib' => $wajib,
            'simp_sukarela' => $sukarela,
            'simp_pensiun' => $pensiun,
            'simp_hari_raya' => $hari_raya,
            'total_simpanan' => $total_savings,
            'rasio_simpanan' => $rasio_simpanan,
            'tujuan_pinjaman' => $tujuan
        ];

        // Execute prediction
        $prediction = $dt->predict($record);
        
        $predicted_label = $prediction['class'];
        $probability = $prediction['probability'];

        // Save result to MySQL history if DB connection is active
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO prediksi_history (nama, tujuan_pinjaman, jumlah_pinjaman, tenor, cicilan, total_simpanan, rasio_simpanan, predicted_label, probability) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nama, $tujuan, $loanAmt, $tenor, $cicilan, $total_savings, $rasio_simpanan, $predicted_label, $probability
            ]);
        }

        // Prepare Explainable AI (XAI) points based on criteria
        $xai = [];
        
        // Savings ratio explanation
        $rasio_pct = round($rasio_simpanan * 100, 2);
        if ($rasio_simpanan >= 0.15) {
            $xai[] = [
                'status' => 'positive',
                'icon' => 'fa-circle-check',
                'text' => "<strong>Rasio Simpanan Kuat:</strong> Total simpanan anggota sebesar Rp " . number_format($total_savings) . " ($rasio_pct% dari pinjaman) memberikan bantalan likuiditas yang aman dan mengurangi risiko gagal bayar."
            ];
        } else {
            $xai[] = [
                'status' => 'negative',
                'icon' => 'fa-triangle-exclamation',
                'text' => "<strong>Rasio Simpanan Rendah:</strong> Total simpanan anggota hanya Rp " . number_format($total_savings) . " ($rasio_pct% dari pinjaman, di bawah standar aman 15%). Anggota memiliki jaminan tabungan yang minim jika terjadi masalah usaha."
            ];
        }

        // Loan amount check
        if ($loanAmt > 8000000) {
            $xai[] = [
                'status' => 'negative',
                'icon' => 'fa-circle-exclamation',
                'text' => "<strong>Eksposur Pinjaman Tinggi:</strong> Pengajuan pinjaman sebesar Rp " . number_format($loanAmt) . " tergolong tinggi untuk skala mikro dhuafa, membutuhkan kedisiplinan angsuran mingguan yang ekstra."
            ];
        } else {
            $xai[] = [
                'status' => 'positive',
                'icon' => 'fa-circle-check',
                'text' => "<strong>Jumlah Pinjaman Sesuai:</strong> Pengajuan pinjaman sebesar Rp " . number_format($loanAmt) . " tergolong dalam batas normal dan wajar untuk pembiayaan modal usaha mikro."
            ];
        }

        // Tujuan Pinjaman risk check
        $lowRiskTujuan = ['WARUNGAN', 'JUAL MAKANAN', 'JUAL PAKAIAN'];
        if (in_array($tujuan, $lowRiskTujuan)) {
            $xai[] = [
                'status' => 'positive',
                'icon' => 'fa-shop',
                'text' => "<strong>Sektor Usaha Stabil:</strong> Bidang usaha '$tujuan' memiliki perputaran arus kas harian yang cepat dan tingkat kegagalan yang secara historis lebih rendah pada anggota KOMIDA."
            ];
        } else {
            $xai[] = [
                'status' => 'neutral',
                'icon' => 'fa-circle-info',
                'text' => "<strong>Sektor Usaha Fluktuatif:</strong> Bidang usaha '$tujuan' memiliki perputaran arus kas yang dinamis dan berpotensi dipengaruhi oleh musim (seperti pertanian/peternakan)."
            ];
        }

        // Installment check
        $xai[] = [
            'status' => 'neutral',
            'icon' => 'fa-wallet',
            'text' => "<strong>Estimasi Angsuran:</strong> Anggota berkewajiban membayar angsuran sekitar Rp " . number_format($cicilan) . " per minggu selama $tenor minggu."
        ];

        $predictionResult = [
            'nama' => $nama,
            'loan_amount' => $loanAmt,
            'tenor' => $tenor,
            'total_savings' => $total_savings,
            'rasio_simpanan' => $rasio_simpanan,
            'label' => $predicted_label,
            'confidence' => $probability,
            'xai' => $xai
        ];

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Prediksi Risiko Angsuran</h1>
        <p class="page-subtitle">Hitung skor kelayakan angsuran calon anggota koperasi berdasarkan profil finansial dan tabungan.</p>
    </div>
</div>

<div class="section-grid" style="grid-template-columns: 1.2fr 1fr; align-items: start;">
    
    <!-- Input Form -->
    <div class="card">
        <h2 class="section-title">
            <i class="fa-solid fa-file-invoice-dollar"></i> Form Profil Finansial Calon Anggota
        </h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="predict.php">
            <div class="form-group">
                <label class="form-label" for="nama">Nama Lengkap Calon Anggota</label>
                <input type="text" id="nama" name="nama" class="form-input" placeholder="Masukkan nama lengkap" required value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="tujuan_pinjaman">Tujuan Pinjaman (Sektor Usaha)</label>
                <select id="tujuan_pinjaman" name="tujuan_pinjaman" class="form-input" style="height: 45px;">
                    <?php foreach ($tujuanOptions as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo (isset($_POST['tujuan_pinjaman']) && $_POST['tujuan_pinjaman'] === $opt) ? 'selected' : ''; ?>>
                            <?php echo $opt; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="card-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="jumlah_pinjaman">Jumlah Pinjaman (Rp)</label>
                    <input type="number" id="jumlah_pinjaman" name="jumlah_pinjaman" class="form-input" min="500000" step="50000" placeholder="Contoh: 5000000" required value="<?php echo isset($_POST['jumlah_pinjaman']) ? htmlspecialchars($_POST['jumlah_pinjaman']) : '5000000'; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="tenor">Tenor (Minggu)</label>
                    <select id="tenor" name="tenor" class="form-input" style="height: 45px;">
                        <option value="50" <?php echo (isset($_POST['tenor']) && $_POST['tenor'] == 50) ? 'selected' : ''; ?>>50 Minggu</option>
                        <option value="25" <?php echo (isset($_POST['tenor']) && $_POST['tenor'] == 25) ? 'selected' : ''; ?>>25 Minggu</option>
                    </select>
                </div>
            </div>
            
            <div style="border-top: 1px solid var(--border-color); margin: 1.5rem 0; padding-top: 1rem;">
                <h3 style="font-size: 0.95rem; margin-bottom: 1rem; color: var(--text-secondary); font-family: var(--font-heading); font-weight: 600;">
                    <i class="fa-solid fa-piggy-bank"></i> Posisi Simpanan Anggota (Jaminan Likuid)
                </h3>
            </div>
            
            <div class="card-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="simp_wajib">Simpanan Wajib (Rp)</label>
                    <input type="number" id="simp_wajib" name="simp_wajib" class="form-input" min="0" step="5000" required value="<?php echo isset($_POST['simp_wajib']) ? htmlspecialchars($_POST['simp_wajib']) : '500000'; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="simp_sukarela">Simpanan Sukarela (Rp)</label>
                    <input type="number" id="simp_sukarela" name="simp_sukarela" class="form-input" min="0" step="5000" required value="<?php echo isset($_POST['simp_sukarela']) ? htmlspecialchars($_POST['simp_sukarela']) : '100000'; ?>">
                </div>
            </div>
            
            <div class="card-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="simp_pensiun">Simpanan Pensiun (Rp)</label>
                    <input type="number" id="simp_pensiun" name="simp_pensiun" class="form-input" min="0" step="5000" required value="<?php echo isset($_POST['simp_pensiun']) ? htmlspecialchars($_POST['simp_pensiun']) : '150000'; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="simp_hari_raya">Simpanan Hari Raya (Rp)</label>
                    <input type="number" id="simp_hari_raya" name="simp_hari_raya" class="form-input" min="0" step="5000" required value="<?php echo isset($_POST['simp_hari_raya']) ? htmlspecialchars($_POST['simp_hari_raya']) : '50000'; ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Analisis & Prediksi Kelayakan
            </button>
        </form>
    </div>
    
    <!-- Prediction Output -->
    <div class="card" style="min-height: 520px;">
        <h2 class="section-title">
            <i class="fa-solid fa-square-poll-vertical"></i> Hasil Evaluasi Risiko
        </h2>
        
        <?php if (!$predictionResult): ?>
            <div style="text-align: center; color: var(--text-muted); padding-top: 8rem;">
                <i class="fa-solid fa-calculator" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                <p style="font-weight: 500;">Silakan isi formulir di samping dan tekan tombol "Analisis" untuk memproses prediksi.</p>
            </div>
        <?php else: ?>
            <?php 
                $lblClass = strtolower($predictionResult['label']); 
                $badgeType = 'badge-lancar';
                $scoreColor = 'lancar';
                $labelBahasa = 'Rendah (Lancar)';
                if ($predictionResult['label'] === 'Diragukan') {
                    $badgeType = 'badge-diragukan';
                    $scoreColor = 'diragukan';
                    $labelBahasa = 'Sedang (Diragukan)';
                } elseif ($predictionResult['label'] === 'Macet') {
                    $badgeType = 'badge-macet';
                    $scoreColor = 'macet';
                    $labelBahasa = 'Tinggi (Macet)';
                }
            ?>
            <div class="result-card <?php echo $lblClass; ?>" style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; border-left-width: 6px;">
                <h3 style="font-family: var(--font-heading); font-size: 1.1rem; color: var(--text-secondary);">HASIL PREDIKSI UNTUK:</h3>
                <h2 style="font-family: var(--font-heading); font-size: 1.6rem; font-weight: 800; color: var(--text-primary); margin-top: 0.25rem;">
                    <?php echo htmlspecialchars($predictionResult['nama']); ?>
                </h2>
                
                <div class="score-display">
                    <div class="score-circle <?php echo $scoreColor; ?>">
                        <span><?php echo round($predictionResult['confidence'] * 100); ?>%</span>
                        <span class="score-label">Keyakinan</span>
                    </div>
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase;">Status Klasifikasi</div>
                        <span class="badge <?php echo $badgeType; ?>" style="font-size: 1rem; padding: 0.4rem 1rem; margin-top: 0.25rem;">
                            <?php echo $labelBahasa; ?>
                        </span>
                    </div>
                </div>
                
                <div style="border-top: 1px solid var(--border-color); padding-top: 1.25rem; margin-top: 1.25rem;">
                    <h4 style="font-family: var(--font-heading); font-size: 0.95rem; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <i class="fa-solid fa-brain" style="color: var(--primary);"></i> Explainable AI (XAI) - Rincian Keputusan
                    </h4>
                    
                    <div class="xai-list">
                        <?php foreach ($predictionResult['xai'] as $item): ?>
                            <?php 
                                $borderCls = 'positive';
                                if ($item['status'] === 'negative') $borderCls = 'negative';
                                elseif ($item['status'] === 'neutral') $borderCls = '';
                            ?>
                            <div class="xai-item <?php echo $borderCls; ?>">
                                <i class="fa-solid <?php echo $item['icon']; ?>" style="font-size: 1.1rem; margin-top: 0.1rem;"></i>
                                <span style="font-size: 0.85rem; color: var(--text-primary);"><?php echo $item['text']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
