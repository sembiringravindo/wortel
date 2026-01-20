<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireLogin();

// Handle add carrot data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        // Generate kode wortel otomatis
        $kode_wortel = 'WR' . date('Ym') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO wortel (kode_wortel, jenis_wortel, berat, kualitas, lokasi_penyimpanan, tanggal_input, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $kode_wortel,
            $_POST['jenis_wortel'],
            $_POST['berat'],
            $_POST['kualitas'],
            $_POST['lokasi_penyimpanan'],
            $_POST['tanggal_input'],
            $_SESSION['user_id']
        ]);
        
        $auth->logActivity($_SESSION['user_id'], "Menambah data wortel: " . $kode_wortel);
        $success = "Data wortel berhasil ditambahkan!";
    } catch (Exception $e) {
        $error = "Gagal menambah data: " . $e->getMessage();
    }
}

// Handle delete carrot data
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM wortel WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        
        $auth->logActivity($_SESSION['user_id'], "Menghapus data wortel");
        $success = "Data wortel berhasil dihapus!";
    } catch (Exception $e) {
        $error = "Gagal menghapus data: " . $e->getMessage();
    }
}

// Search and filter
$search = $_GET['search'] ?? '';
$kualitas = $_GET['kualitas'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(jenis_wortel LIKE ? OR kode_wortel LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($kualitas)) {
    $where_conditions[] = "kualitas = ?";
    $params[] = $kualitas;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get carrot data
$stmt = $pdo->prepare("SELECT * FROM wortel $where_sql ORDER BY tanggal_input DESC");
$stmt->execute($params);
$wortel_data = $stmt->fetchAll();

// Statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(berat) as total_berat,
        AVG(berat) as avg_berat
    FROM wortel
");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Wortel - Sistem Gudang Wortel</title>
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
            max-width: 600px;
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
        
        .quality-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .quality-premium {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .quality-standard {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .quality-kelas2 {
            background: #fce4ec;
            color: #c2185b;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Data Wortel</h1>
                <p>Manajemen data master wortel di gudang</p>
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
                        <i>🥕</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_items']); ?></h3>
                        <p>Total Jenis Wortel</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196f3;">
                        <i>⚖️</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_berat'], 2); ?> Kg</h3>
                        <p>Total Berat</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ff9800;">
                        <i>📊</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['avg_berat'], 2); ?> Kg</h3>
                        <p>Rata-rata Berat</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9c27b0;">
                        <i>🏷️</i>
                    </div>
                    <div class="stat-info">
                        <h3>3</h3>
                        <p>Kategori Kualitas</p>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter -->
            <div class="card">
                <div class="card-header">
                    <h3>Filter Data</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="search">Cari (Jenis/Kode)</label>
                                <input type="text" id="search" name="search" class="form-control" 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Cari jenis atau kode wortel...">
                            </div>
                            
                            <div class="form-group">
                                <label for="kualitas">Kualitas</label>
                                <select id="kualitas" name="kualitas" class="select-control">
                                    <option value="">Semua Kualitas</option>
                                    <option value="Premium" <?php echo $kualitas === 'Premium' ? 'selected' : ''; ?>>Premium</option>
                                    <option value="Standard" <?php echo $kualitas === 'Standard' ? 'selected' : ''; ?>>Standard</option>
                                    <option value="Kelas 2" <?php echo $kualitas === 'Kelas 2' ? 'selected' : ''; ?>>Kelas 2</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="align-self: end;">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Filter Data</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Data Table and Add Button -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Daftar Data Wortel</h3>
                    <button type="button" class="btn btn-primary" onclick="openAddModal()">
                        ➕ Tambah Data Wortel
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Jenis Wortel</th>
                                    <th>Berat (Kg)</th>
                                    <th>Kualitas</th>
                                    <th>Lokasi Penyimpanan</th>
                                    <th>Tanggal Input</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($wortel_data)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <p style="color: #666;">Tidak ada data wortel ditemukan.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($wortel_data as $wortel): ?>
                                    <tr>
                                        <td><strong><?php echo $wortel['kode_wortel']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($wortel['jenis_wortel']); ?></td>
                                        <td><?php echo number_format($wortel['berat'], 2); ?></td>
                                        <td>
                                            <span class="quality-badge quality-<?php echo strtolower(str_replace(' ', '', $wortel['kualitas'])); ?>">
                                                <?php echo $wortel['kualitas']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($wortel['lokasi_penyimpanan']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($wortel['tanggal_input'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-primary btn-small" 
                                                        onclick="editWortel(<?php echo $wortel['id']; ?>)">
                                                    ✏️ Edit
                                                </button>
                                                <a href="?delete=<?php echo $wortel['id']; ?>" 
                                                   class="btn btn-danger btn-small"
                                                   onclick="return confirm('Hapus data ini?')">
                                                    🗑️ Hapus
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
    <div id="wortelModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Tambah Data Wortel Baru</h2>
            
            <form id="wortelForm" method="POST" action="">
                <input type="hidden" name="action" value="add">
                <input type="hidden" id="editId" name="edit_id" value="">
                
                <div class="form-row" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label for="jenis_wortel">Jenis Wortel *</label>
                        <input type="text" id="jenis_wortel" name="jenis_wortel" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="berat">Berat (Kg) *</label>
                        <input type="number" id="berat" name="berat" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="kualitas">Kualitas *</label>
                        <select id="kualitas" name="kualitas" class="select-control" required>
                            <option value="Premium">Premium</option>
                            <option value="Standard" selected>Standard</option>
                            <option value="Kelas 2">Kelas 2</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasi_penyimpanan">Lokasi Penyimpanan</label>
                        <input type="text" id="lokasi_penyimpanan" name="lokasi_penyimpanan" 
                               class="form-control" placeholder="Contoh: Rak A1, Gudang Utara">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tanggal_input">Tanggal Input *</label>
                    <input type="date" id="tanggal_input" name="tanggal_input" 
                           class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-actions" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Data Wortel Baru';
            document.querySelector('[name="action"]').value = 'add';
            document.getElementById('wortelForm').reset();
            document.getElementById('editId').value = '';
            document.getElementById('wortelModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('wortelModal').style.display = 'none';
        }
        
        function editWortel(id) {
            // In real implementation, this would fetch data via AJAX
            // For now, we'll show alert and redirect to edit page
            alert('Fitur edit akan diimplementasikan menggunakan AJAX');
            // You would implement AJAX call here
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('wortelModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>