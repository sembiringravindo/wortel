<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Ambil data statistik untuk public API
    $stmt = $pdo->query("
        SELECT 
            (SELECT COALESCE(SUM(jumlah), 0) FROM stock_in) as total_in,
            (SELECT COALESCE(SUM(jumlah), 0) FROM stock_out) as total_out
    ");
    $stock_stats = $stmt->fetch();
    $current_stock = $stock_stats['total_in'] - $stock_stats['total_out'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_wortel FROM wortel");
    $wortel_count = $stmt->fetch()['total_wortel'];
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_transaksi 
        FROM (
            SELECT id FROM stock_in WHERE MONTH(tanggal_masuk) = MONTH(CURDATE())
            UNION ALL
            SELECT id FROM stock_out WHERE MONTH(tanggal_keluar) = MONTH(CURDATE())
        ) as transactions
    ");
    $transaksi_count = $stmt->fetch()['total_transaksi'];
    
    // Data untuk chart 6 bulan terakhir
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(dates.month, '%M') as month_name,
            DATE_FORMAT(dates.month, '%Y-%m') as month,
            COALESCE(SUM(si.jumlah), 0) as stock_in,
            COALESCE(SUM(so.jumlah), 0) as stock_out
        FROM (
            SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL n MONTH), '%Y-%m-01') as month
            FROM (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) months
        ) dates
        LEFT JOIN stock_in si ON DATE_FORMAT(si.tanggal_masuk, '%Y-%m-01') = dates.month
        LEFT JOIN stock_out so ON DATE_FORMAT(so.tanggal_keluar, '%Y-%m-01') = dates.month
        GROUP BY dates.month
        ORDER BY dates.month
    ");
    $chart_data = $stmt->fetchAll();
    
    // Aktivitas terbaru
    $stmt = $pdo->query("
        SELECT al.activity, al.timestamp, u.full_name 
        FROM activity_log al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.timestamp DESC 
        LIMIT 5
    ");
    $recent_activities = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'current_stock' => $current_stock,
        'wortel_count' => $wortel_count,
        'transaksi_count' => $transaksi_count,
        'chart_data' => $chart_data,
        'recent_activities' => $recent_activities,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'current_stock' => 0,
        'wortel_count' => 0,
        'transaksi_count' => 0,
        'chart_data' => [],
        'recent_activities' => []
    ]);
}
?>