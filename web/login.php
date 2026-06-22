<?php
/**
 * Login Page — KOMIDA Risk Prediction App
 * Handles authentication and session creation.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Jika sudah login, redirect ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login berhasil — buat session
                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_nama']     = $user['nama'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role']     = $user['role'];

                // Update last_login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
                    ->execute([':id' => $user['id']]);

                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah. Silakan coba lagi.';
            }
        } catch (Exception $e) {
            $error = 'Gagal terhubung ke database. Pastikan MySQL sudah aktif di XAMPP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — KOMIDA Risk Predictor</title>
    <meta name="description" content="Halaman login Sistem Analisis dan Prediksi Risiko Angsuran Anggota KOMIDA.">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Login Page Override ── */
        body {
            display: flex;
            min-height: 100vh;
            overflow: hidden;
            background: #0f172a;
        }

        /* Left panel */
        .login-left {
            flex: 1;
            background: linear-gradient(145deg, #065f46 0%, #0f172a 60%, #1e3a5f 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16,185,129,0.18) 0%, transparent 70%);
            pointer-events: none;
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(14,165,233,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
            z-index: 1;
        }

        .login-brand-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #059669, #0ea5e9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: white;
            box-shadow: 0 8px 24px rgba(5,150,105,0.4);
        }

        .login-brand-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: white;
            line-height: 1.1;
        }
        .login-brand-text span {
            font-size: 0.8rem;
            font-weight: 600;
            color: #10b981;
            letter-spacing: 0.15em;
            display: block;
        }

        .login-hero {
            z-index: 1;
            text-align: center;
            max-width: 420px;
        }

        .login-hero-icon {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #10b981;
            margin: 0 auto 2rem;
            animation: floatIcon 4s ease-in-out infinite;
        }

        @keyframes floatIcon {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }

        .login-hero h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .login-hero p {
            font-size: 1rem;
            color: #94a3b8;
            line-height: 1.7;
        }

        /* Stats row */
        .login-stats {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            z-index: 1;
        }
        .login-stat {
            text-align: center;
        }
        .login-stat-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: #10b981;
        }
        .login-stat-label {
            font-size: 0.78rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Right panel */
        .login-right {
            width: 480px;
            min-width: 380px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            box-shadow: -20px 0 60px rgba(0,0,0,0.3);
        }

        .login-form-wrapper {
            width: 100%;
            max-width: 380px;
        }

        .login-form-header {
            margin-bottom: 2.5rem;
        }

        .login-form-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .login-form-header p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .login-field {
            margin-bottom: 1.5rem;
        }

        .login-field label {
            display: block;
            font-weight: 600;
            font-size: 0.88rem;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input-wrap .field-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
        }

        .login-input-wrap input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            transition: all 0.3s;
        }

        .login-input-wrap input:focus {
            border-color: #059669;
            background: white;
            box-shadow: 0 0 0 3px rgba(5,150,105,0.12);
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 0.95rem;
            padding: 0;
            transition: color 0.2s;
        }
        .toggle-password:hover { color: #059669; }

        .login-error {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            animation: shakeError 0.4s ease;
        }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            25%       { transform: translateX(-6px); }
            75%       { transform: translateX(6px); }
        }

        .login-btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #059669, #0ea5e9);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 0.02em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5,150,105,0.35);
        }
        .login-btn:active {
            transform: translateY(0);
        }

        /* Role hint cards */
        .role-hints {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #f1f5f9;
        }

        .role-hints-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .role-hint-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .role-hint-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 0.82rem;
        }

        .role-hint-item .rh-role {
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.15rem 0.55rem;
            border-radius: 99px;
        }

        .rh-admin    { background: #fef3c7; color: #d97706; }
        .rh-manager  { background: #dbeafe; color: #2563eb; }
        .rh-staff    { background: #dcfce7; color: #16a34a; }

        .role-hint-item .rh-cred {
            font-family: 'Courier New', monospace;
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .login-left { display: none; }
            .login-right {
                width: 100%;
                min-width: 0;
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Left Panel — Branding -->
<div class="login-left">
    <div class="login-brand">
        <div class="login-brand-icon">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="login-brand-text">
            KOMIDA RISK
            <span>PREDICTOR</span>
        </div>
    </div>

    <div class="login-hero">
        <div class="login-hero-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1>Sistem Prediksi Risiko Angsuran</h1>
        <p>
            Platform analisis berbasis Machine Learning untuk membantu
            Koperasi Mitra Dhuafa mengelola risiko pembiayaan anggota
            secara akurat dan transparan.
        </p>
    </div>

    <div class="login-stats">
        <div class="login-stat">
            <div class="login-stat-value">2.135</div>
            <div class="login-stat-label">Data Anggota</div>
        </div>
        <div class="login-stat">
            <div class="login-stat-value">90.21%</div>
            <div class="login-stat-label">Akurasi Model</div>
        </div>
        <div class="login-stat">
            <div class="login-stat-value">3</div>
            <div class="login-stat-label">Kategori Risiko</div>
        </div>
    </div>
</div>

<!-- Right Panel — Login Form -->
<div class="login-right">
    <div class="login-form-wrapper">

        <div class="login-form-header">
            <h2>Selamat Datang 👋</h2>
            <p>Masuk ke akun Anda untuk mengakses sistem.</p>
        </div>

        <?php if ($error): ?>
        <div class="login-error" id="login-error-msg">
            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <form id="login-form" method="POST" action="login.php" novalidate>

            <div class="login-field">
                <label for="username">Username</label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-user field-icon"></i>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="login-field">
                <label for="password">Password</label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-lock field-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-password" id="toggle-pw" title="Tampilkan/sembunyikan password">
                        <i class="fa-solid fa-eye" id="toggle-pw-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn" id="login-submit-btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                Masuk ke Sistem
            </button>

        </form>

        <!-- Default credentials hint 
        <div class="role-hints">
            <div class="role-hints-title">Akun Default Sistem</div>
            <div class="role-hint-list">
                <div class="role-hint-item">
                    <span class="rh-role rh-admin">Admin</span>
                    <span class="rh-cred">admin / Admin2026</span>
                </div>
                <div class="role-hint-item">
                    <span class="rh-role rh-manager">Manager</span>
                    <span class="rh-cred">manager / Manager2026</span>
                </div>
                <div class="role-hint-item">
                    <span class="rh-role rh-staff">Staff</span>
                    <span class="rh-cred">staff / Staff2026</span>
                </div>
            </div>
        </div>

    </div>
</div>-->

<script>
// Toggle password visibility
document.getElementById('toggle-pw').addEventListener('click', function() {
    const pwInput  = document.getElementById('password');
    const icon     = document.getElementById('toggle-pw-icon');
    const isHidden = pwInput.type === 'password';
    pwInput.type   = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

// Loading state on submit
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('login-submit-btn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memverifikasi...';
    btn.disabled = true;
});
</script>
</body>
</html>
