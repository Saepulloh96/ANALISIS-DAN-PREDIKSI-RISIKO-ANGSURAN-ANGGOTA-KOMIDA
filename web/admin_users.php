<?php
/**
 * Admin User Management — Khusus role: administrator
 */
require_once 'includes/header.php';
requireRole(['administrator']);

$currentUser = getCurrentUser();
$message     = '';
$messageType = '';

// ── Handle POST actions ───────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    $action = $_POST['action'] ?? '';

    // Tambah user baru
    if ($action === 'create') {
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'staff';

        if (empty($nama) || empty($username) || empty($password)) {
            $message     = 'Nama, username, dan password tidak boleh kosong.';
            $messageType = 'danger';
        } elseif (!in_array($role, ['administrator','manager','staff'])) {
            $message     = 'Role tidak valid.';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (:nama, :username, :password, :role)");
                $stmt->execute([
                    ':nama'     => $nama,
                    ':username' => $username,
                    ':password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                    ':role'     => $role,
                ]);
                $message     = "Pengguna \"$username\" berhasil ditambahkan.";
                $messageType = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = "Username \"$username\" sudah digunakan. Pilih username lain.";
                } else {
                    $message = 'Gagal menambahkan pengguna: ' . $e->getMessage();
                }
                $messageType = 'danger';
            }
        }
    }

    // Edit user
    if ($action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $nama     = trim($_POST['nama'] ?? '');
        $role     = $_POST['role'] ?? 'staff';
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || empty($nama)) {
            $message = 'Data tidak valid.';
            $messageType = 'danger';
        } else {
            try {
                if (!empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET nama=:nama, role=:role, password=:password, updated_at=NOW() WHERE id=:id");
                    $stmt->execute([':nama' => $nama, ':role' => $role, ':password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), ':id' => $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nama=:nama, role=:role, updated_at=NOW() WHERE id=:id");
                    $stmt->execute([':nama' => $nama, ':role' => $role, ':id' => $id]);
                }
                $message     = 'Data pengguna berhasil diperbarui.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message     = 'Gagal memperbarui: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }

    // Toggle aktif/nonaktif
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $currentUser['id']) {
            $message = 'Anda tidak dapat menonaktifkan akun sendiri.';
            $messageType = 'danger';
        } elseif ($id > 0) {
            $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id")->execute([':id' => $id]);
            $message     = 'Status pengguna berhasil diubah.';
            $messageType = 'success';
        }
    }

    // Hapus user
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $currentUser['id']) {
            $message = 'Anda tidak dapat menghapus akun sendiri.';
            $messageType = 'danger';
        } elseif ($id > 0) {
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
            $message     = 'Pengguna berhasil dihapus.';
            $messageType = 'success';
        }
    }
}

// ── Fetch users ───────────────────────────────────────────────────────────────
$users = [];
if (isset($pdo)) {
    try {
        $users = $pdo->query("SELECT * FROM users ORDER BY role, nama")->fetchAll();
    } catch (PDOException $e) {}
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-users-gear" style="color:var(--primary);"></i> Manajemen Pengguna</h1>
        <p class="page-subtitle">Kelola akun dan hak akses pengguna sistem KOMIDA Risk.</p>
    </div>
    <button class="btn btn-primary" id="btn-open-create-modal">
        <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
    </button>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" id="admin-msg">
    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="card" style="margin-bottom:2rem;">
    <h2 class="section-title"><i class="fa-solid fa-list-ul"></i> Daftar Pengguna (<?php echo count($users); ?>)</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Terakhir Login</th>
                    <th>Dibuat</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">
                        Belum ada data pengguna.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:0.85rem;"><?php echo $i+1; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <div class="user-avatar-sm" style="background:<?php echo $u['role']==='administrator' ? '#fef3c7' : ($u['role']==='manager' ? '#dbeafe' : '#dcfce7'); ?>; color:<?php echo $u['role']==='administrator' ? '#d97706' : ($u['role']==='manager' ? '#2563eb' : '#16a34a'); ?>;">
                                <?php echo mb_strtoupper(mb_substr($u['nama'], 0, 1)); ?>
                            </div>
                            <span style="font-weight:600;"><?php echo htmlspecialchars($u['nama']); ?></span>
                            <?php if ($u['id'] == $currentUser['id']): ?>
                                <span style="font-size:0.72rem;background:#f0fdf4;color:#16a34a;border-radius:99px;padding:0.1rem 0.5rem;border:1px solid #bbf7d0;">Anda</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><code style="background:#f1f5f9;padding:0.2rem 0.5rem;border-radius:6px;font-size:0.9rem;"><?php echo htmlspecialchars($u['username']); ?></code></td>
                    <td>
                        <?php
                        $roleColors = [
                            'administrator' => ['bg'=>'#fef3c7','color'=>'#d97706','icon'=>'fa-crown'],
                            'manager'       => ['bg'=>'#dbeafe','color'=>'#2563eb','icon'=>'fa-user-tie'],
                            'staff'         => ['bg'=>'#dcfce7','color'=>'#16a34a','icon'=>'fa-user'],
                        ];
                        $rc = $roleColors[$u['role']] ?? $roleColors['staff'];
                        ?>
                        <span class="badge" style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['color']; ?>;">
                            <i class="fa-solid <?php echo $rc['icon']; ?>"></i>
                            <?php echo getRoleLabel($u['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge badge-lancar"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-macet"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-secondary);font-size:0.88rem;">
                        <?php echo $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '<span style="color:var(--text-muted);">Belum pernah</span>'; ?>
                    </td>
                    <td style="color:var(--text-secondary);font-size:0.88rem;">
                        <?php echo date('d/m/Y', strtotime($u['created_at'])); ?>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:0.4rem;justify-content:center;">
                            <!-- Edit -->
                            <button class="btn btn-secondary btn-sm btn-edit-user"
                                style="padding:0.35rem 0.7rem;font-size:0.82rem;"
                                data-id="<?php echo $u['id']; ?>"
                                data-nama="<?php echo htmlspecialchars($u['nama']); ?>"
                                data-role="<?php echo $u['role']; ?>"
                                title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <!-- Toggle -->
                            <?php if ($u['id'] != $currentUser['id']): ?>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Ubah status pengguna ini?')">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm"
                                    style="padding:0.35rem 0.7rem;font-size:0.82rem;color:<?php echo $u['is_active'] ? '#f59e0b' : '#10b981'; ?>;"
                                    title="<?php echo $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                    <i class="fa-solid fa-<?php echo $u['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                                </button>
                            </form>
                            <!-- Delete -->
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Hapus pengguna ini? Tindakan ini tidak bisa dibatalkan.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm"
                                    style="padding:0.35rem 0.7rem;font-size:0.82rem;color:#ef4444;"
                                    title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Modal: Tambah Pengguna ────────────────────────────────────────────── -->
<div id="modal-create" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Tambah Pengguna Baru</h3>
            <button class="modal-close" id="btn-close-create">×</button>
        </div>
        <form method="POST" action="admin_users.php">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-input" placeholder="Contoh: Budi Santoso" required>
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" placeholder="Contoh: budi.santoso" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-input">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="administrator">Administrator</option>
                </select>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fa-solid fa-check"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" id="btn-close-create-2" style="flex:1;">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal: Edit Pengguna ──────────────────────────────────────────────── -->
<div id="modal-edit" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen"></i> Edit Pengguna</h3>
            <button class="modal-close" id="btn-close-edit">×</button>
        </div>
        <form method="POST" action="admin_users.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" id="edit-nama" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password Baru <small style="color:var(--text-muted);">(kosongkan jika tidak diubah)</small></label>
                <input type="password" name="password" class="form-input" placeholder="Biarkan kosong jika tidak berubah">
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" id="edit-role" class="form-input">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="administrator">Administrator</option>
                </select>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                <button type="button" class="btn btn-secondary" id="btn-close-edit-2" style="flex:1;">Batal</button>
            </div>
        </form>
    </div>
</div>

<style>
.user-avatar-sm {
    width: 34px; height: 34px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.9rem;
    flex-shrink: 0;
}
.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.82rem; }

/* Modal */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex; align-items: center; justify-content: center;
    animation: fadeInOverlay 0.25s ease;
}
@keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }

.modal-box {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    animation: slideUpModal 0.3s cubic-bezier(0.4,0,0.2,1);
}
@keyframes slideUpModal { from { transform: translateY(30px); opacity:0; } to { transform: translateY(0); opacity:1; } }

.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.5rem;
}
.modal-header h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex; align-items: center; gap: 0.5rem;
}
.modal-close {
    background: none; border: none; font-size: 1.5rem;
    cursor: pointer; color: var(--text-muted); line-height: 1;
    transition: color 0.2s;
}
.modal-close:hover { color: var(--text-primary); }
</style>

<script>
// ── Modal logic ──────────────────────────────────────────────────────────────
const modalCreate = document.getElementById('modal-create');
const modalEdit   = document.getElementById('modal-edit');

function openModal(el)  { el.style.display = 'flex'; }
function closeModal(el) { el.style.display = 'none'; }

document.getElementById('btn-open-create-modal').addEventListener('click', () => openModal(modalCreate));
document.getElementById('btn-close-create').addEventListener('click',      () => closeModal(modalCreate));
document.getElementById('btn-close-create-2').addEventListener('click',    () => closeModal(modalCreate));
document.getElementById('btn-close-edit').addEventListener('click',        () => closeModal(modalEdit));
document.getElementById('btn-close-edit-2').addEventListener('click',      () => closeModal(modalEdit));

// Close on overlay click
[modalCreate, modalEdit].forEach(m => {
    m.addEventListener('click', (e) => { if (e.target === m) closeModal(m); });
});

// Populate edit modal
document.querySelectorAll('.btn-edit-user').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit-id').value   = btn.dataset.id;
        document.getElementById('edit-nama').value  = btn.dataset.nama;
        document.getElementById('edit-role').value  = btn.dataset.role;
        openModal(modalEdit);
    });
});

// Auto-dismiss alert
const alertMsg = document.getElementById('admin-msg');
if (alertMsg) setTimeout(() => { alertMsg.style.opacity = '0'; alertMsg.style.transition = '0.5s'; }, 4000);
</script>

<?php require_once 'includes/footer.php'; ?>
