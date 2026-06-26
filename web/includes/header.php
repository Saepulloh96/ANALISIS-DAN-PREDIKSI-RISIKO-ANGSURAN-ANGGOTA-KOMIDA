<?php
// Common Header for Koperasi Mitra Dhuafa Risk App
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

// Require login on every page that includes this header
requireLogin();

// Try connecting to DB to run automatic setup, capture any exceptions to display in alert
$dbError = null;
try {
    $pdo = getDB();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// Get current logged-in user data
$currentUser = getCurrentUser();

// Helper function to set active menu class
function isActive($pageName) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === $pageName) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis &amp; Prediksi Risiko Angsuran KOMIDA</title>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="brand-name">
                KOMIDA RISK<br><span style="font-size: 0.75rem; letter-spacing: 0.1em; color: #10b981; font-weight: 700;">PREDICTOR</span>
            </div>
        </div>
        
        <!-- User Info Card in Sidebar -->
        <div class="sidebar-user-card">
            <div class="sidebar-user-avatar">
                <?php echo mb_strtoupper(mb_substr($currentUser['nama'], 0, 1)); ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($currentUser['nama']); ?></div>
                <span class="role-badge <?php echo getRoleBadgeClass($currentUser['role']); ?>">
                    <?php
                    $roleIcons = ['administrator' => 'fa-crown', 'manager' => 'fa-user-tie', 'staff' => 'fa-user'];
                    $icon = $roleIcons[$currentUser['role']] ?? 'fa-user';
                    ?>
                    <i class="fa-solid <?php echo $icon; ?>"></i>
                    <?php echo getRoleLabel($currentUser['role']); ?>
                </span>
            </div>
        </div>

        <div class="sidebar-menu-wrapper">
            <ul class="sidebar-menu">
                <!-- Dashboard — semua role -->
                <li class="menu-item <?php echo isActive('index.php'); ?>">
                    <a href="index.php">
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Prediksi Risiko — semua role -->
                <li class="menu-item <?php echo isActive('predict.php'); ?>">
                    <a href="predict.php">
                        <i class="fa-solid fa-calculator"></i>
                        <span>Prediksi Risiko</span>
                    </a>
                </li>

                <!-- Data Anggota — semua role (aksi edit/delete dikontrol di members.php) -->
                <li class="menu-item <?php echo isActive('members.php'); ?>">
                    <a href="members.php">
                        <i class="fa-solid fa-users"></i>
                        <span>Data Anggota</span>
                    </a>
                </li>

                <!-- Analisis Model ML — administrator & manager only -->
                <?php if (hasAnyRole(['administrator', 'manager'])): ?>
                <li class="menu-item <?php echo isActive('model_analysis.php'); ?>">
                    <a href="model_analysis.php">
                        <i class="fa-solid fa-diagram-project"></i>
                        <span>Analisis Model ML</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Manajemen Pengguna — administrator only -->
                <?php if (hasRole('administrator')): ?>
                <li class="menu-item-divider"></li>
                <li class="menu-item <?php echo isActive('admin_users.php'); ?>">
                    <a href="admin_users.php">
                        <i class="fa-solid fa-users-gear"></i>
                        <span>Kelola Pengguna</span>
                    </a>
                </li>
                <li class="menu-item <?php echo isActive('import_data.php'); ?>">
                    <a href="import_data.php">
                        <i class="fa-solid fa-upload"></i>
                        <span>Impor Data CSV</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar dari sistem?')">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar
            </a>
            <p style="margin-top: 0.75rem;">KOMIDA Risk System v2.0</p>
            <p style="margin-top: 0.25rem;">KOMIDA &copy; 2026</p>
        </div>
    </div>

    <!-- Main Content wrapper -->
    <div class="main-content">
        
        <?php if ($dbError): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Kesalahan Koneksi Database:</strong> <?php echo htmlspecialchars($dbError); ?><br>
                Pastikan XAMPP MySQL Anda sudah dijalankan!
            </div>
        <?php endif; ?>

