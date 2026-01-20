<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireLogin();

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        $update_fields = "full_name = ?";
        $params = [$_POST['full_name'], $_SESSION['user_id']];
        
        // Update password if provided
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            // Verify current password
            if (password_verify($_POST['current_password'], $user['password'])) {
                $update_fields .= ", password = ?";
                array_splice($params, 1, 0, [password_hash($_POST['new_password'], PASSWORD_DEFAULT)]);
                $password_updated = true;
            } else {
                $error = "Password saat ini salah!";
            }
        }
        
        if (!isset($error)) {
            $stmt = $pdo->prepare("UPDATE users SET $update_fields WHERE id = ?");
            $stmt->execute($params);
            
            // Update session
            $_SESSION['full_name'] = $_POST['full_name'];
            
            $auth->logActivity($_SESSION['user_id'], "Memperbarui profil");
            $success = "Profil berhasil diperbarui!" . (isset($password_updated) ? " Password telah diubah." : "");
        }
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = "Gagal memperbarui profil: " . $e->getMessage();
    }
}

// Get user activity stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT DATE(timestamp)) as active_days,
        COUNT(*) as total_activities,
        MAX(timestamp) as last_activity
    FROM activity_log 
    WHERE user_id = ?
    AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([$_SESSION['user_id']]);
$activity_stats = $stmt->fetch();

// Get recent activities
$stmt = $pdo->prepare("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY timestamp DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Sistem Gudang Wortel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        
        .profile-info {
            margin-bottom: 30px;
        }
        
        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle .toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f7f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4caf50;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-time {
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Profil Pengguna</h1>
                <p>Kelola informasi akun Anda</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="profile-container">
                <!-- Profile Info Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo substr($user['full_name'], 0, 1); ?>
                        </div>
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p>
                            <span class="role-badge role-<?php echo $user['role']; ?>" 
                                  style="display: inline-block; padding: 4px 12px; border-radius: 20px; background: #e3f2fd; color: #1565c0;">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="profile-info">
                        <div class="info-item">
                            <div class="info-label">Username</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'Belum diatur'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Tanggal Bergabung</div>
                            <div class="info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Status Akun</div>
                            <div class="info-value">
                                <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $activity_stats['active_days']; ?></div>
                            <div class="stat-label">Hari Aktif</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $activity_stats['total_activities']; ?></div>
                            <div class="stat-label">Aktivitas</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php 
                                $join_days = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
                                echo $join_days; 
                                ?>
                            </div>
                            <div class="stat-label">Hari Bergabung</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo $recent_activities ? date('H:i', strtotime($recent_activities[0]['timestamp'])) : '--:--'; ?>
                            </div>
                            <div class="stat-label">Aktivitas Terakhir</div>
                        </div>
                    </div>
                </div>
                
                <!-- Update Form Card -->
                <div class="profile-card">
                    <h3 style="margin-bottom: 25px; color: #2e7d32;">Ubah Profil</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="full_name">Nama Lengkap *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small style="color: #666;">Username tidak dapat diubah</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                   placeholder="email@contoh.com">
                        </div>
                        
                        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
                        
                        <h4 style="margin-bottom: 20px; color: #2e7d32;">Ubah Password</h4>
                        <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
                            Kosongkan jika tidak ingin mengubah password
                        </p>
                        
                        <div class="form-group password-toggle">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                            <button type="button" class="toggle-btn" onclick="togglePassword('current_password')">👁️</button>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            <button type="button" class="toggle-btn" onclick="togglePassword('new_password')">👁️</button>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')">👁️</button>
                        </div>
                        
                        <div class="form-actions" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <button type="reset" class="btn btn-secondary">Reset Form</button>
                        </div>
                    </form>
                    
                    <!-- Recent Activities -->
                    <hr style="margin: 40px 0; border: none; border-top: 1px solid #eee;">
                    
                    <h4 style="margin-bottom: 20px; color: #2e7d32;">Aktivitas Terakhir</h4>
                    
                    <div class="activities-list">
                        <?php if (empty($recent_activities)): ?>
                            <p style="color: #666; text-align: center; padding: 20px;">Belum ada aktivitas</p>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    📝
                                </div>
                                <div class="activity-content">
                                    <div style="font-weight: 500; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($activity['activity']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('d/m/Y H:i', strtotime($activity['timestamp'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
        }
        
        // Password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.length > 0 && confirmPassword.value.length > 0) {
                if (password !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#f44336';
                } else {
                    confirmPassword.style.borderColor = '#4caf50';
                }
            }
        });
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password.length > 0 && confirmPassword.length > 0) {
                if (password !== confirmPassword) {
                    this.style.borderColor = '#f44336';
                } else {
                    this.style.borderColor = '#4caf50';
                }
            }
        });
    </script>
</body>
</html>