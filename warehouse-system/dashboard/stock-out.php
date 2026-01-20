<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if enough stock is available
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(jumlah), 0) as total_in,
                COALESCE((
                    SELECT SUM(jumlah) FROM stock_out 
                    WHERE kode_wortel = ? 
                    AND DATE(tanggal_keluar) <= ?
                ), 0) as total_out
            FROM stock_in 
            WHERE kode_wortel = ?
        ");
        
        $stmt->execute([
            $_POST['kode_wortel'],
            $_POST['tanggal_keluar'],
            $_POST['kode_wortel']
        ]);
        
        $stock = $stmt->fetch();
        $available = $stock['total_in'] - $stock['total_out'];
        
        if ($_POST['jumlah'] > $available) {
            $error = "Stok tidak mencukupi! Stok tersedia: " . number_format($available, 2) . " Kg";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO stock_out (tanggal_keluar, jumlah, tujuan_distribusi, kode_wortel, petugas_id, catatan)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['tanggal_keluar'],
                $_POST['jumlah'],
                $_POST['tujuan_distribusi'],
                $_POST['kode_wortel'],
                $_SESSION['user_id'],
                $_POST['catatan'] ?? null
            ]);
            
            $auth->logActivity($_SESSION['user_id'], "Mengeluarkan stok: " . $_POST['jumlah'] . " kg");
            $success = "Stok keluar berhasil dicatat!";
        }
    } catch (Exception $e) {
        $error = "Gagal mencatat stok keluar: " . $e->getMessage();
    }
}

// Get carrot data
$stmt = $pdo->query("SELECT kode_wortel, jenis_wortel FROM wortel ORDER BY jenis_wortel");
$wortel_list = $stmt->fetchAll();

// Get current stock levels
$stock_levels = [];
foreach ($wortel_list as $wortel) {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(si.jumlah), 0) as total_in,
            COALESCE(SUM(so.jumlah), 0) as total_out
        FROM wortel w
        LEFT JOIN stock_in si ON w.kode_wortel = si.kode_wortel
        LEFT JOIN stock_out so ON w.kode_wortel = so.kode_wortel
        WHERE w.kode_wortel = ?
        GROUP BY w.kode_wortel
    ");
    $stmt->execute([$wortel['kode_wortel']]);
    $stock = $stmt->fetch();
    $stock_levels[$wortel['kode_wortel']] = $stock['total_in'] - $stock['total_out'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Keluar - Sistem Gudang Wortel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Stok Keluar</h1>
                <p>Input data wortel yang keluar dari gudang</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Form Input Stok Keluar</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div class="form-group">
                                <label for="tanggal_keluar">Tanggal Keluar *</label>
                                <input type="date" id="tanggal_keluar" name="tanggal_keluar" 
                                       class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="jumlah">Jumlah (Kg) *</label>
                                <input type="number" id="jumlah" name="jumlah" class="form-control" 
                                       step="0.01" min="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="tujuan_distribusi">Tujuan Distribusi *</label>
                                <input type="text" id="tujuan_distribusi" name="tujuan_distribusi" 
                                       class="form-control" placeholder="Contoh: Pasar Induk, Retail A, Ekspor" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="kode_wortel">Kode Wortel</label>
                                <select id="kode_wortel" name="kode_wortel" class="select-control">
                                    <option value="">-- Pilih Kode Wortel --</option>
                                    <?php foreach ($wortel_list as $wortel): 
                                        $available_stock = $stock_levels[$wortel['kode_wortel']] ?? 0;
                                    ?>
                                        <option value="<?php echo $wortel['kode_wortel']; ?>" 
                                                data-stock="<?php echo $available_stock; ?>">
                                            <?php echo $wortel['kode_wortel']; ?> - <?php echo $wortel['jenis_wortel']; ?>
                                            (Tersedia: <?php echo number_format($available_stock, 2); ?> Kg)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="stockInfo" style="display: none; color: #666; margin-top: 5px;"></small>
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
            
            <!-- Recent Stock Out -->
            <div class="card">
                <div class="card-header">
                    <h3>Riwayat Stok Keluar Terakhir</h3>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT so.*, u.full_name, w.jenis_wortel
                        FROM stock_out so 
                        JOIN users u ON so.petugas_id = u.id
                        LEFT JOIN wortel w ON so.kode_wortel = w.kode_wortel
                        ORDER BY tanggal_keluar DESC 
                        LIMIT 10
                    ");
                    $recent_stock = $stmt->fetchAll();
                    ?>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah (Kg)</th>
                                <th>Tujuan</th>
                                <th>Jenis Wortel</th>
                                <th>Petugas</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_stock as $stock): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($stock['tanggal_keluar'])); ?></td>
                                <td><?php echo number_format($stock['jumlah'], 2); ?></td>
                                <td><?php echo htmlspecialchars($stock['tujuan_distribusi']); ?></td>
                                <td><?php echo htmlspecialchars($stock['jenis_wortel'] ?? '-'); ?></td>
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
    
    <script>
        // Show stock info when wortel is selected
        document.getElementById('kode_wortel').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stock = selectedOption.getAttribute('data-stock');
            const stockInfo = document.getElementById('stockInfo');
            
            if (stock) {
                stockInfo.textContent = `Stok tersedia: ${parseFloat(stock).toFixed(2)} Kg`;
                stockInfo.style.display = 'block';
                
                // Update max value for jumlah input
                document.getElementById('jumlah').max = stock;
            } else {
                stockInfo.style.display = 'none';
            }
        });
        
        // Validate jumlah doesn't exceed available stock
        document.querySelector('form').addEventListener('submit', function(e) {
            const jumlah = parseFloat(document.getElementById('jumlah').value);
            const selectedOption = document.getElementById('kode_wortel').options[document.getElementById('kode_wortel').selectedIndex];
            const availableStock = parseFloat(selectedOption.getAttribute('data-stock') || 0);
            
            if (jumlah > availableStock) {
                e.preventDefault();
                alert(`Stok tidak mencukupi! Stok tersedia: ${availableStock.toFixed(2)} Kg`);
            }
        });
    </script>
</body>
</html>