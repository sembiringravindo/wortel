<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO stock_in (tanggal_masuk, jumlah, asal_panen, kode_wortel, petugas_id, catatan)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['tanggal_masuk'],
            $_POST['jumlah'],
            $_POST['asal_panen'],
            $_POST['kode_wortel'],
            $_SESSION['user_id'],
            $_POST['catatan'] ?? null
        ]);
        
        // Log activity
        $auth->logActivity($_SESSION['user_id'], "Menambah stok masuk: " . $_POST['jumlah'] . " kg");
        
        $success = "Stok masuk berhasil ditambahkan!";
    } catch (Exception $e) {
        $error = "Gagal menambah stok: " . $e->getMessage();
    }
}

// Get carrot data
$stmt = $pdo->query("SELECT kode_wortel, jenis_wortel FROM wortel ORDER BY jenis_wortel");
$wortel_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Masuk - Sistem Gudang Wortel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Stok Masuk</h1>
                <p>Input data wortel yang masuk ke gudang</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Form Input Stok Masuk</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div class="form-group">
                                <label for="tanggal_masuk">Tanggal Masuk *</label>
                                <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="jumlah">Jumlah (Kg) *</label>
                                <input type="number" id="jumlah" name="jumlah" class="form-control" 
                                       step="0.01" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="asal_panen">Asal Panen *</label>
                                <input type="text" id="asal_panen" name="asal_panen" class="form-control" 
                                       placeholder="Contoh: Ladang Utara, Kebun Selatan" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="kode_wortel">Kode Wortel</label>
                                <select id="kode_wortel" name="kode_wortel" class="select-control">
                                    <option value="">-- Pilih Kode Wortel --</option>
                                    <?php foreach ($wortel_list as $wortel): ?>
                                        <option value="<?php echo $wortel['kode_wortel']; ?>">
                                            <?php echo $wortel['kode_wortel']; ?> - <?php echo $wortel['jenis_wortel']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="catatan">Catatan (Opsional)</label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="3" 
                                      placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Simpan Data</button>
                            <button type="reset" class="btn btn-secondary">Reset Form</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recent Stock In -->
            <div class="card">
                <div class="card-header">
                    <h3>Riwayat Stok Masuk Terakhir</h3>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT si.*, u.full_name 
                        FROM stock_in si 
                        JOIN users u ON si.petugas_id = u.id 
                        ORDER BY tanggal_masuk DESC 
                        LIMIT 10
                    ");
                    $recent_stock = $stmt->fetchAll();
                    ?>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah (Kg)</th>
                                <th>Asal Panen</th>
                                <th>Petugas</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_stock as $stock): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($stock['tanggal_masuk'])); ?></td>
                                <td><?php echo number_format($stock['jumlah'], 2); ?></td>
                                <td><?php echo htmlspecialchars($stock['asal_panen']); ?></td>
                                <td><?php echo htmlspecialchars($stock['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($stock['catatan'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/form-validation.js"></script>
</body>
</html>