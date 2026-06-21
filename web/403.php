<?php
/**
 * 403 Forbidden Page — Akses ditolak karena role tidak mencukupi.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin(); // Harus login dulu sebelum menampilkan 403

$user = getCurrentUser();

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak — KOMIDA Risk</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .forbidden-card {
            text-align: center;
            max-width: 480px;
            padding: 3rem 2.5rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            color: white;
        }

        .forbidden-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(239,68,68,0.15);
            border: 2px solid rgba(239,68,68,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #ef4444;
            margin: 0 auto 2rem;
            animation: pulseRed 2s ease-in-out infinite;
        }

        @keyframes pulseRed {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.2); }
            50%       { box-shadow: 0 0 0 16px rgba(239,68,68,0); }
        }

        .forbidden-code {
            font-family: 'Outfit', sans-serif;
            font-size: 5rem;
            font-weight: 800;
            color: #ef4444;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .forbidden-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .forbidden-desc {
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .forbidden-role-info {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 99px;
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            color: #cbd5e1;
            margin-bottom: 2rem;
        }

        .forbidden-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #059669, #0ea5e9);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5,150,105,0.35);
            color: white;
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255,255,255,0.06);
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        .btn-ghost:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
    </style>
</head>
<body>

<div class="forbidden-card">
    <div class="forbidden-icon">
        <i class="fa-solid fa-ban"></i>
    </div>
    <div class="forbidden-code">403</div>
    <div class="forbidden-title">Akses Ditolak</div>
    <p class="forbidden-desc">
        Maaf, halaman ini hanya bisa diakses oleh pengguna dengan 
        hak akses tertentu. Role kamu saat ini tidak memiliki izin untuk melihat konten ini.
    </p>
    <div class="forbidden-role-info">
        <i class="fa-solid fa-user-tag"></i>
        Login sebagai: <strong><?php echo htmlspecialchars($user['nama']); ?></strong>
        &nbsp;|&nbsp; Role: <strong><?php echo htmlspecialchars(getRoleLabel($user['role'])); ?></strong>
    </div>
    <div class="forbidden-actions">
        <a href="index.php" class="btn-back">
            <i class="fa-solid fa-gauge"></i> Ke Dashboard
        </a>
        <a href="javascript:history.back()" class="btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

</body>
</html>
