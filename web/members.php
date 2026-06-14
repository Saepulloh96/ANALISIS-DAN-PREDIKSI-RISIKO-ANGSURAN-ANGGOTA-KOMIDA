<?php
require_once 'includes/header.php';

$message = '';
$errMessage = '';

// Handle Adding New Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
    try {
        if (!isset($pdo)) {
            throw new Exception("Koneksi database tidak tersedia.");
        }
        
        $id = trim($_POST['id_anggota']);
        $nama = trim($_POST['nama']);
        $tujuan = $_POST['tujuan_pinjaman'];
        $loanAmt = (float)$_POST['jumlah_pinjaman'];
        $tenor = (int)$_POST['tenor'];
        $wajib = (float)$_POST['simp_wajib'];
        $sukarela = (float)$_POST['simp_sukarela'];
        $pensiun = (float)$_POST['simp_pensiun'];
        $hari_raya = (float)$_POST['simp_hari_raya'];
        $wpd = (int)$_POST['week_past_due'];
        
        if (empty($id) || empty($nama)) {
            throw new Exception("ID Anggota dan Nama harus diisi.");
        }

        // Determine risk label based on Week Past Due
        $label = 'Lancar';
        if ($wpd > 4 && $wpd <= 12) {
            $label = 'Diragukan';
        } elseif ($wpd > 12) {
            $label = 'Macet';
        }

        // Prepare and insert
        $stmt = $pdo->prepare("INSERT INTO anggota (id_anggota, nama, tujuan_pinjaman, jumlah_pinjaman, tenor, cicilan, simp_wajib, simp_sukarela, simp_pensiun, simp_hari_raya, week_past_due, label_risiko) 
                               VALUES (:id_anggota, :nama, :tujuan_pinjaman, :jumlah_pinjaman, :tenor, :cicilan, :simp_wajib, :simp_sukarela, :simp_pensiun, :simp_hari_raya, :week_past_due, :label_risiko)");
        
        $cicilan = round(($loanAmt / $tenor) * 1.12, -2);
        
        $stmt->execute([
            ':id_anggota' => $id,
            ':nama' => $nama,
            ':tujuan_pinjaman' => $tujuan,
            ':jumlah_pinjaman' => $loanAmt,
            ':tenor' => $tenor,
            ':cicilan' => $cicilan,
            ':simp_wajib' => $wajib,
            ':simp_sukarela' => $sukarela,
            ':simp_pensiun' => $pensiun,
            ':simp_hari_raya' => $hari_raya,
            ':week_past_due' => $wpd,
            ':label_risiko' => $label
        ]);

        $message = "Anggota baru berhasil ditambahkan!";
    } catch (Exception $e) {
        $errMessage = $e->getMessage();
    }
}

// Default filter values
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$riskFilter = isset($_GET['risk']) ? trim($_GET['risk']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$members = [];
$totalRows = 0;
$totalPages = 1;

if (isset($pdo)) {
    try {
        // Build query conditions
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(id_anggota LIKE :search_id OR nama LIKE :search_name)";
            $params[':search_id'] = '%' . $search . '%';
            $params[':search_name'] = '%' . $search . '%';
        }
        
        if (!empty($riskFilter)) {
            $where[] = "label_risiko = :risk";
            $params[':risk'] = $riskFilter;
        }
        
        $whereSql = '';
        if (count($where) > 0) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }
        
        // Count total rows
        $countQuery = "SELECT COUNT(*) FROM anggota $whereSql";
        $stmtCount = $pdo->prepare($countQuery);
        foreach ($params as $key => $val) {
            $stmtCount->bindValue($key, $val);
        }
        $stmtCount->execute();
        $totalRows = $stmtCount->fetchColumn();
        $totalPages = ceil($totalRows / $limit);
        if ($totalPages < 1) $totalPages = 1;
        
        // Fetch rows
        $selectQuery = "SELECT * FROM anggota $whereSql ORDER BY id_anggota ASC LIMIT :limit OFFSET :offset";
        $stmtSelect = $pdo->prepare($selectQuery);
        foreach ($params as $key => $val) {
            $stmtSelect->bindValue($key, $val);
        }
        $stmtSelect->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtSelect->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtSelect->execute();
        $members = $stmtSelect->fetchAll();
        
    } catch (PDOException $e) {
        $errMessage = "Error database: " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Database Anggota Koperasi</h1>
        <p class="page-subtitle">Kelola dan telusuri portofolio data angsuran serta simpanan seluruh anggota KOMIDA.</p>
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

<div class="section-grid" style="grid-template-columns: 2fr 1fr; align-items: start;">
    
    <!-- Table list -->
    <div class="card">
        <h2 class="section-title">
            <i class="fa-solid fa-table-list"></i> Daftar Anggota Terdaftar (Total: <?php echo number_format($totalRows); ?>)
        </h2>
        
        <!-- Search and Filter Form -->
        <form method="GET" action="members.php" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <input type="text" name="search" class="form-input" style="flex: 2; min-width: 200px; height: 40px;" placeholder="Cari nama atau ID..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="risk" class="form-input" style="flex: 1; min-width: 150px; height: 40px;">
                <option value="">-- Semua Risiko --</option>
                <option value="Lancar" <?php echo $riskFilter === 'Lancar' ? 'selected' : ''; ?>>Rendah (Lancar)</option>
                <option value="Diragukan" <?php echo $riskFilter === 'Diragukan' ? 'selected' : ''; ?>>Sedang (Diragukan)</option>
                <option value="Macet" <?php echo $riskFilter === 'Macet' ? 'selected' : ''; ?>>Tinggi (Macet)</option>
            </select>
            
            <button type="submit" class="btn btn-secondary" style="height: 40px; padding: 0 1.25rem;">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($riskFilter)): ?>
                <a href="members.php" class="btn btn-secondary" style="height: 40px; display: inline-flex; align-items: center; justify-content: center; padding: 0 1rem;">
                    Reset
                </a>
            <?php endif; ?>
        </form>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID Anggota</th>
                        <th>Nama Anggota</th>
                        <th>Tujuan Pinjaman</th>
                        <th>Jumlah Pinjaman</th>
                        <th>Total Simpanan</th>
                        <th>WPD</th>
                        <th>Status Risiko</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Data anggota tidak ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 500; font-size: 0.85rem;"><?php echo htmlspecialchars($m['id_anggota']); ?></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($m['nama']); ?></td>
                                <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($m['tujuan_pinjaman']); ?></td>
                                <td>Rp <?php echo number_format($m['jumlah_pinjaman']); ?></td>
                                <td>Rp <?php echo number_format($m['total_simpanan']); ?></td>
                                <td style="font-weight: 600; text-align: center;"><?php echo $m['week_past_due']; ?> <span style="font-weight: normal; font-size: 0.8rem; color: var(--text-muted);">Mgg</span></td>
                                <td>
                                    <?php if ($m['label_risiko'] === 'Lancar'): ?>
                                        <span class="badge badge-lancar">Lancar</span>
                                    <?php elseif ($m['label_risiko'] === 'Diragukan'): ?>
                                        <span class="badge badge-diragukan">Diragukan</span>
                                    <?php else: ?>
                                        <span class="badge badge-macet">Macet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination controls -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="members.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&risk=<?php echo urlencode($riskFilter); ?>" class="pagination-item">&laquo;</a>
                <?php endif; ?>
                
                <?php 
                // Show a maximum of 5 pages around the current page to keep it clean
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="members.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&risk=<?php echo urlencode($riskFilter); ?>" class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="members.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&risk=<?php echo urlencode($riskFilter); ?>" class="pagination-item">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add new member form side card -->
    <div class="card">
        <h2 class="section-title">
            <i class="fa-solid fa-user-plus"></i> Tambah Anggota Baru
        </h2>
        <form method="POST" action="members.php">
            <input type="hidden" name="action" value="add_member">
            
            <div class="form-group">
                <label class="form-label" for="id_anggota">ID Anggota / Nomor Loan</label>
                <input type="text" id="id_anggota" name="id_anggota" class="form-input" placeholder="PMB-240-26-06-XXXXXX" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="nama">Nama Anggota</label>
                <input type="text" id="nama" name="nama" class="form-input" placeholder="Nama lengkap" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="tujuan_pinjaman">Tujuan Pinjaman</label>
                <select id="tujuan_pinjaman" name="tujuan_pinjaman" class="form-input" style="height: 40px;">
                    <option value="WARUNGAN">WARUNGAN</option>
                    <option value="JUAL MAKANAN">JUAL MAKANAN</option>
                    <option value="JUAL IKAN DAN LAINNYA">JUAL IKAN DAN LAINNYA</option>
                    <option value="PERTANIAN">PERTANIAN</option>
                    <option value="JUAL PAKAIAN">JUAL PAKAIAN</option>
                    <option value="JUAL SAYURAN">JUAL SAYURAN</option>
                    <option value="LAIN LAIN INVESTASI">LAIN LAIN INVESTASI</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="jumlah_pinjaman">Jumlah Pinjaman (Rp)</label>
                <input type="number" id="jumlah_pinjaman" name="jumlah_pinjaman" class="form-input" min="1000000" step="100000" value="5000000" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="tenor">Tenor (Minggu)</label>
                <select id="tenor" name="tenor" class="form-input" style="height: 40px;">
                    <option value="50">50 Minggu</option>
                    <option value="25">25 Minggu</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="week_past_due">Keterlambatan (Week Past Due)</label>
                <input type="number" id="week_past_due" name="week_past_due" class="form-input" min="0" value="0" required>
                <span class="form-input-help">0 = Lancar, 1-4 = Diragukan, > 4 = Macet.</span>
            </div>
            
            <div style="border-top: 1px solid var(--border-color); margin: 1rem 0; padding-top: 0.5rem;">
                <h3 style="font-size: 0.85rem; margin-bottom: 0.75rem; color: var(--text-secondary); font-weight: 600;">Simpanan</h3>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.8rem;" for="simp_wajib">Wajib</label>
                    <input type="number" id="simp_wajib" name="simp_wajib" class="form-input" min="0" value="500000" required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.8rem;" for="simp_sukarela">Sukarela</label>
                    <input type="number" id="simp_sukarela" name="simp_sukarela" class="form-input" min="0" value="100000" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.8rem;" for="simp_pensiun">Pensiun</label>
                    <input type="number" id="simp_pensiun" name="simp_pensiun" class="form-input" min="0" value="100000" required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.8rem;" for="simp_hari_raya">Hari Raya</label>
                    <input type="number" id="simp_hari_raya" name="simp_hari_raya" class="form-input" min="0" value="50000" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-save"></i> Simpan Data Anggota
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
