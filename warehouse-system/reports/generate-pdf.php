<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireLogin();

// This would require a PDF library like TCPDF or Dompdf
// For simplicity, we'll create a print-friendly version

if (isset($_GET['type']) && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $report_type = $_GET['type'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    
    // Generate report data (similar to reports.php)
    if ($report_type === 'incoming') {
        $query = "SELECT * FROM stock_in WHERE tanggal_masuk BETWEEN ? AND ? ORDER BY tanggal_masuk DESC";
        $title = "Laporan Stok Masuk";
    } else {
        $query = "SELECT * FROM stock_out WHERE tanggal_keluar BETWEEN ? AND ? ORDER BY tanggal_keluar DESC";
        $title = "Laporan Stok Keluar";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll();
    
    // Generate HTML for PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #2e7d32; margin: 0; }
            .header p { color: #666; margin: 5px 0 20px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .total-row { font-weight: bold; background-color: #f0f7f0; }
            .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Desa Barus Julu</h1>
            <h2>' . $title . '</h2>
            <p>Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</p>
            <p>Dicetak pada: ' . date('d/m/Y H:i:s') . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jumlah (Kg)</th>
                    <th>Keterangan</th>
                    <th>Petugas</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>';
    
    $total = 0;
    foreach ($data as $row) {
        $html .= '
                <tr>
                    <td>' . date('d/m/Y', strtotime($row[$report_type === 'incoming' ? 'tanggal_masuk' : 'tanggal_keluar'])) . '</td>
                    <td>' . number_format($row['jumlah'], 2) . '</td>
                    <td>' . htmlspecialchars($report_type === 'incoming' ? $row['asal_panen'] : $row['tujuan_distribusi']) . '</td>
                    <td>' . htmlspecialchars($row['petugas_id']) . '</td>
                    <td>' . htmlspecialchars($row['catatan'] ?? '-') . '</td>
                </tr>';
        $total += $row['jumlah'];
    }
    
    $html .= '
                <tr class="total-row">
                    <td colspan="4"><strong>Total</strong></td>
                    <td><strong>' . number_format($total, 2) . ' Kg</strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="footer">
            <p>© 2024 Sistem Informasi Gudang Wortel - Desa Barus Julu</p>
            <p>Laporan ini dicetak secara otomatis dari sistem</p>
        </div>
    </body>
    </html>';
    
    // For now, output HTML for printing
    // In production, use a PDF library like TCPDF
    echo $html;
    exit;
}
?>