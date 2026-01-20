<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireLogin();

// Filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'daily';

// Generate report based on type
$where_conditions = ["tanggal_masuk BETWEEN ? AND ?"];
$params = [$start_date, $end_date];

if ($report_type === 'incoming') {
    $query = "
        SELECT 
            si.tanggal_masuk as tanggal,
            si.jumlah,
            si.asal_panen,
            w.jenis_wortel,
            u.full_name as petugas,
            si.catatan,
            'Masuk' as jenis
        FROM stock_in si
        LEFT JOIN wortel w ON si.kode_wortel = w.kode_wortel
        JOIN users u ON si.petugas_id = u.id
        WHERE " . implode(" AND ", $where_conditions) . "
        ORDER BY si.tanggal_masuk DESC
    ";
} elseif ($report_type === 'outgoing') {
    $query = "
        SELECT 
            so.tanggal_keluar as tanggal,
            so.jumlah,
            so.tujuan_distribusi,
            w.jenis_wortel,
            u.full_name as petugas,
            so.catatan,
            'Keluar' as jenis
        FROM stock_out so
        LEFT JOIN wortel w ON so.kode_wortel = w.kode_wortel
        JOIN users u ON so.petugas_id = u.id
        WHERE so.tanggal_keluar BETWEEN ? AND ?
        ORDER BY so.tanggal_keluar DESC
    ";
    $params = [$start_date, $end_date];
} else {
    // Daily summary
    $query = "
        SELECT 
            COALESCE(si.tanggal_masuk, so.tanggal_keluar) as tanggal,
            COALESCE(SUM(si.jumlah), 0) as masuk,
            COALESCE(SUM(so.jumlah), 0) as keluar,
            (COALESCE(SUM(si.jumlah), 0) - COALESCE(SUM(so.jumlah), 0)) as netto
        FROM (
            SELECT DISTINCT tanggal_masuk as tanggal FROM stock_in 
            WHERE tanggal_masuk BETWEEN ? AND ?
            UNION 
            SELECT DISTINCT tanggal_keluar FROM stock_out 
            WHERE tanggal_keluar BETWEEN ? AND ?
        ) dates
        LEFT JOIN stock_in si ON dates.tanggal = si.tanggal_masuk
        LEFT JOIN stock_out so ON dates.tanggal = so.tanggal_keluar
        GROUP BY dates.tanggal
        ORDER BY tanggal DESC
    ";
    $params = [$start_date, $end_date, $start_date, $end_date];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Calculate totals
$total_in = 0;
$total_out = 0;
foreach ($reports as $report) {
    if ($report_type === 'incoming') {
        $total_in += $report['jumlah'];
    } elseif ($report_type === 'outgoing') {
        $total_out += $report['jumlah'];
    } else {
        $total_in += $report['masuk'];
        $total_out += $report['keluar'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Gudang Wortel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Laporan Gudang</h1>
                <p>Generate dan filter laporan stok wortel</p>
            </div>
            
            <!-- Filter Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Filter Laporan</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                            <div class="form-group">
                                <label for="start_date">Tanggal Mulai</label>
                                <input type="date" id="start_date" name="start_date" 
                                       class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" id="end_date" name="end_date" 
                                       class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="report_type">Jenis Laporan</label>
                                <select id="report_type" name="report_type" class="select-control">
                                    <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Harian</option>
                                    <option value="incoming" <?php echo $report_type === 'incoming' ? 'selected' : ''; ?>>Stok Masuk</option>
                                    <option value="outgoing" <?php echo $report_type === 'outgoing' ? 'selected' : ''; ?>>Stok Keluar</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                            <a href="reports.php" class="btn btn-secondary">Reset Filter</a>
                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                🖨️ Cetak Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Report Summary -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4caf50;">
                        <i>📅</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($reports); ?></h3>
                        <p>Total Transaksi</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196f3;">
                        <i>⬇️</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_in, 2); ?> Kg</h3>
                        <p>Total Masuk</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ff9800;">
                        <i>⬆️</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_out, 2); ?> Kg</h3>
                        <p>Total Keluar</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9c27b0;">
                        <i>⚖️</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_in - $total_out, 2); ?> Kg</h3>
                        <p>Netto</p>
                    </div>
                </div>
            </div>
            
            <!-- Report Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Detail Laporan 
                        (<?php echo date('d/m/Y', strtotime($start_date)); ?> - 
                         <?php echo date('d/m/Y', strtotime($end_date)); ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php if ($report_type === 'daily'): ?>
                                        <th>Tanggal</th>
                                        <th>Stok Masuk (Kg)</th>
                                        <th>Stok Keluar (Kg)</th>
                                        <th>Netto (Kg)</th>
                                    <?php elseif ($report_type === 'incoming'): ?>
                                        <th>Tanggal</th>
                                        <th>Jumlah (Kg)</th>
                                        <th>Asal Panen</th>
                                        <th>Jenis Wortel</th>
                                        <th>Petugas</th>
                                        <th>Catatan</th>
                                    <?php else: ?>
                                        <th>Tanggal</th>
                                        <th>Jumlah (Kg)</th>
                                        <th>Tujuan Distribusi</th>
                                        <th>Jenis Wortel</th>
                                        <th>Petugas</th>
                                        <th>Catatan</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            <p style="color: #666;">Tidak ada data untuk periode yang dipilih.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <?php if ($report_type === 'daily'): ?>
                                            <td><?php echo date('d/m/Y', strtotime($report['tanggal'])); ?></td>
                                            <td><?php echo number_format($report['masuk'], 2); ?></td>
                                            <td><?php echo number_format($report['keluar'], 2); ?></td>
                                            <td><?php echo number_format($report['netto'], 2); ?></td>
                                        <?php elseif ($report_type === 'incoming'): ?>
                                            <td><?php echo date('d/m/Y', strtotime($report['tanggal'])); ?></td>
                                            <td><?php echo number_format($report['jumlah'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($report['asal_panen']); ?></td>
                                            <td><?php echo htmlspecialchars($report['jenis_wortel'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($report['petugas']); ?></td>
                                            <td><?php echo htmlspecialchars($report['catatan'] ?? '-'); ?></td>
                                        <?php else: ?>
                                            <td><?php echo date('d/m/Y', strtotime($report['tanggal'])); ?></td>
                                            <td><?php echo number_format($report['jumlah'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($report['tujuan_distribusi']); ?></td>
                                            <td><?php echo htmlspecialchars($report['jenis_wortel'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($report['petugas']); ?></td>
                                            <td><?php echo htmlspecialchars($report['catatan'] ?? '-'); ?></td>
                                        <?php endif; ?>
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
    
    <script src="../assets/js/main.js"></script>
</body>
</html>