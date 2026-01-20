<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireLogin();

// Get statistics
$stmt = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(jumlah), 0) FROM stock_in) as total_in,
        (SELECT COALESCE(SUM(jumlah), 0) FROM stock_out) as total_out
");
$stats = $stmt->fetch();
$current_stock = $stats['total_in'] - $stats['total_out'];

// Get recent activities
$stmt = $pdo->query("
    SELECT al.*, u.full_name 
    FROM activity_log al 
    JOIN users u ON al.user_id = u.id 
    ORDER BY timestamp DESC 
    LIMIT 10
");
$activities = $stmt->fetchAll();

// Get monthly data for chart
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_masuk, '%Y-%m') as month,
        COALESCE(SUM(si.jumlah), 0) as stock_in,
        COALESCE(SUM(so.jumlah), 0) as stock_out
    FROM stock_in si
    LEFT JOIN stock_out so ON DATE_FORMAT(so.tanggal_keluar, '%Y-%m') = DATE_FORMAT(si.tanggal_masuk, '%Y-%m')
    GROUP BY DATE_FORMAT(tanggal_masuk, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
$monthlyData = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Gudang Wortel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4caf50;">
                        <i>📦</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($current_stock, 2); ?> Kg</h3>
                        <p>Stok Saat Ini</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196f3;">
                        <i>⬇️</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_in'], 2); ?> Kg</h3>
                        <p>Total Stok Masuk</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ff9800;">
                        <i>⬆️</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_out'], 2); ?> Kg</h3>
                        <p>Total Stok Keluar</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9c27b0;">
                        <i>📊</i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($activities); ?></h3>
                        <p>Aktivitas Terbaru</p>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-container">
                    <h3>Grafik Stok Bulanan</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Perbandingan Stok Masuk vs Keluar</h3>
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="card">
                <div class="card-header">
                    <h3>Aktivitas Terbaru</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($activity['timestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($activity['activity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/charts.js"></script>
    <script>
        // Prepare chart data from PHP
        const monthlyData = <?php echo json_encode($monthlyData); ?>;
        
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month).reverse(),
                datasets: [{
                    label: 'Stok Masuk',
                    data: monthlyData.map(d => parseFloat(d.stock_in)).reverse(),
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    fill: true
                }, {
                    label: 'Stok Keluar',
                    data: monthlyData.map(d => parseFloat(d.stock_out)).reverse(),
                    borderColor: '#ff9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Comparison Chart
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        const comparisonChart = new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: ['Stok Masuk', 'Stok Keluar'],
                datasets: [{
                    label: 'Total (Kg)',
                    data: [
                        <?php echo $stats['total_in']; ?>,
                        <?php echo $stats['total_out']; ?>
                    ],
                    backgroundColor: [
                        'rgba(76, 175, 80, 0.7)',
                        'rgba(255, 152, 0, 0.7)'
                    ],
                    borderColor: [
                        '#4caf50',
                        '#ff9800'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>