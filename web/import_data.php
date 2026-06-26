<?php
require_once 'includes/header.php';

// Only allow administrators
requireRole(['administrator']);

$message = '';
$errMessage = '';
$importedCount = 0;

// Handle data import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_csv') {
    try {
        if (!isset($pdo)) {
            throw new Exception("Koneksi database tidak tersedia.");
        }

        // CSV file path
        $csvFile = __DIR__ . '/../data/dataset_komida.csv';
        
        if (!file_exists($csvFile)) {
            throw new Exception("File dataset_komida.csv tidak ditemukan di folder data.");
        }

        // Read CSV file
        $file = fopen($csvFile, 'r');
        if (!$file) {
            throw new Exception("Gagal membuka file CSV.");
        }

        // Skip header
        $header = fgetcsv($file);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO anggota 
            (id_anggota, nama, tujuan_pinjaman, jumlah_pinjaman, tenor, cicilan, 
             simp_wajib, simp_sukarela, simp_pensiun, simp_hari_raya, 
             week_past_due, label_risiko) 
            VALUES (:id_anggota, :nama, :tujuan_pinjaman, :jumlah_pinjaman, :tenor, :cicilan, 
                    :simp_wajib, :simp_sukarela, :simp_pensiun, :simp_hari_raya, 
                    :week_past_due, :label_risiko)");
        
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 14) continue;
            
            $stmt->execute([
                ':id_anggota' => trim($row[0]),
                ':nama' => trim($row[1]),
                ':tujuan_pinjaman' => trim($row[2]),
                ':jumlah_pinjaman' => (float)$row[3],
                ':tenor' => (int)$row[4],
                ':cicilan' => (float)$row[5],
                ':simp_wajib' => (float)$row[6],
                ':simp_sukarela' => (float)$row[7],
                ':simp_pensiun' => (float)$row[8],
                ':simp_hari_raya' => (float)$row[9],
                ':week_past_due' => (int)$row[12],
                ':label_risiko' => trim($row[13])
            ]);
            $importedCount++;
        }
        
        fclose($file);
        $pdo->commit();
        
        $message = "Data berhasil diimpor! Total " . $importedCount . " data anggota ditambahkan/diperbarui.";
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errMessage = "Error: " . $e->getMessage();
    }
}

// Fetch current total count
$totalMembers = 0;
if (isset($pdo)) {
    try {
        $totalMembers = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
    } catch (PDOException $e) {
        // Silently fail
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Impor Data Anggota</h1>
        <p class="page-subtitle">Impor data anggota dari file CSV ke database sistem.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($errMessage): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($errMessage); ?>
    </div>
<?php endif; ?>

<div class="section-grid" style="grid-template-columns: 1fr; max-width: 600px;">
    <div class="card">
        <h2 class="section-title">
            <i class="fa-solid fa-download"></i> Impor dari CSV
        </h2>
        
        <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
            <p style="margin: 0; font-size: 0.9rem; color: #0c4a6e;">
                <i class="fa-solid fa-info-circle"></i>
                File sumber: <code style="background: #fff; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">data/dataset_komida.csv</code>
            </p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #0c4a6e;">
                Data saat ini di database: <strong><?php echo number_format($totalMembers); ?> anggota</strong>
            </p>
        </div>

        <form method="POST" action="import_data.php">
            <input type="hidden" name="action" value="import_csv">
            
            <div style="background: #fef2f2; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
                <p style="margin: 0; font-size: 0.9rem; color: #7f1d1d;">
                    <i class="fa-solid fa-warning"></i>
                    <strong>Perhatian:</strong> Data duplikat akan diabaikan (hanya insert baru yang tidak ada).
                </p>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-upload"></i> Mulai Impor Data
            </button>
        </form>

        <div style="border-top: 1px solid var(--border-color); margin-top: 1.5rem; padding-top: 1.5rem;">
            <h3 style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-secondary);">
                <i class="fa-solid fa-circle-info"></i> Informasi File
            </h3>
            <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.8;">
                <li>Format: CSV (Comma Separated Values)</li>
                <li>Encoding: UTF-8</li>
                <li>Header: ID Anggota, Nama, Tujuan Pinjaman, dsb.</li>
                <li>Primary Key: id_anggota (duplikat akan diabaikan)</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
