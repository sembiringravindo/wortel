<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireRole('admin');

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, role, is_active)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['username'],
            $hashed_password,
            $_POST['full_name'],
            $_POST['role'],
            isset($_POST['is_active']) ? 1 : 0
        ]);
        
        $auth->logActivity($_SESSION['user_id'], "Menambah user: " . $_POST['username']);
        $success = "User berhasil ditambahkan!";
    } catch (Exception $e) {
        $error = "Gagal menambah user: " . $e->getMessage();
    }
}

// Handle update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $update_fields = "full_name = ?, role = ?, is_active = ?";
        $params = [
            $_POST['full_name'],
            $_POST['role'],
            isset($_POST['is_active']) ? 1 : 0,
            $_POST['user_id']
        ];
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            $update_fields .= ", password = ?";
            array_splice($params, 3, 0, [password_hash($_POST['password'], PASSWORD_DEFAULT)]);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET $update_fields WHERE id = ?");
        $stmt->execute($params);
        
        $auth->logActivity($_SESSION['user_id'], "Mengupdate user ID: " . $_POST['user_id']);
        $success = "User berhasil diupdate!";
    } catch (Exception $e) {
        $error = "Gagal mengupdate user: " . $e->getMessage();
    }
}

// Handle delete user (soft delete)
if (isset($_GET['toggle_status'])) {
    try {
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$_GET['toggle_status']]);
        $user = $stmt->fetch();
        
        $new_status = $user['is_active'] ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $_GET['toggle_status']]);
        
        $action = $new_status ? 'mengaktifkan' : 'menonaktifkan';
        $auth->logActivity($_SESSION['user_id'], "$action user ID: " . $_GET['toggle_status']);
        $success = "Status user berhasil diubah!";
    } catch (Exception $e) {
        $error = "Gagal mengubah status user: " . $e->getMessage();
    }
}

// Get all users except current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();

// Statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'petugas' THEN 1 ELSE 0 END) as petugas_count,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
    FROM users
");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Sistem Gudang Wortel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        }
        
        .close-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }
        
        .close-modal:hover {
            color: #000;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-admin {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .role-petugas {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .toggle-password {
            background: none;
            border: none;
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 38px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Manajemen User</h1>
                <p>Kelola pengguna sistem gudang wortel</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4caf50;">
                        <i>👥</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Total User</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196f3;">
                        <i>👑</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['admin_count']; ?></h3>
                        <p>Admin</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ff9800;">
                        <i>👷</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['petugas_count']; ?></h3>
                        <p>Petugas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9c27b0;">
                        <i>✅</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['active_count']; ?></h3>
                        <p>User Aktif</p>
                    </div>
                </div>
            </div>
            
            <!-- User List and Add Button -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Daftar Pengguna</h3>
                    <button type="button" class="btn btn-primary" onclick="openAddModal()">
                        ➕ Tambah User Baru
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            <p style="color: #666;">Tidak ada data user.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-primary btn-small" 
                                                        onclick="editUser(<?php echo $user['id']; ?>)">
                                                    ✏️ Edit
                                                </button>
                                                <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                                   class="btn btn-<?php echo $user['is_active'] ? 'danger' : 'success'; ?> btn-small"
                                                   onclick="return confirm('<?php echo $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?> user ini?')">
                                                    <?php echo $user['is_active'] ? '🚫 Nonaktifkan' : '✅ Aktifkan'; ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Tambah User Baru</h2>
            
            <form id="userForm" method="POST" action="">
                <input type="hidden" name="action" value="add">
                <input type="hidden" id="userId" name="user_id" value="">
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password" id="passwordLabel">Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>
                    <small id="passwordHelp" style="color: #666;">Minimal 6 karakter</small>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Nama Lengkap *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" class="select-control" required>
                            <option value="petugas">Petugas</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px;">Status</label>
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <span>Aktif</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">Simpan User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah User Baru';
            document.querySelector('[name="action"]').value = 'add';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordLabel').innerHTML = 'Password *';
            document.getElementById('passwordHelp').style.display = 'block';
            document.getElementById('userModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }
        
        function editUser(userId) {
            // For simplicity, we'll reload page with GET parameter
            // In real implementation, you'd fetch user data via AJAX
            window.location.href = 'users.php?edit=' + userId;
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }
        
        // Password strength validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const helpText = document.getElementById('passwordHelp');
            
            if (password.length < 6) {
                helpText.textContent = 'Password terlalu pendek (minimal 6 karakter)';
                helpText.style.color = '#f44336';
            } else if (password.length < 8) {
                helpText.textContent = 'Password cukup';
                helpText.style.color = '#ff9800';
            } else {
                helpText.textContent = 'Password kuat';
                helpText.style.color = '#4caf50';
            }
        });
        
        // Handle edit mode from URL
        <?php if (isset($_GET['edit'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch user data via AJAX
            fetch('../includes/get-user.php?id=<?php echo $_GET['edit']; ?>')
                .then(response => response.json())
                .then(user => {
                    document.getElementById('modalTitle').textContent = 'Edit User';
                    document.querySelector('[name="action"]').value = 'update';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('username').readOnly = true;
                    document.getElementById('password').required = false;
                    document.getElementById('passwordLabel').innerHTML = 'Password (kosongkan jika tidak diubah)';
                    document.getElementById('passwordHelp').style.display = 'none';
                    document.getElementById('full_name').value = user.full_name;
                    document.getElementById('role').value = user.role;
                    document.getElementById('is_active').checked = user.is_active == 1;
                    document.getElementById('userModal').style.display = 'block';
                });
        });
        <?php endif; ?>
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>