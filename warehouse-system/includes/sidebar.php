<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <span>📊</span> Dashboard
                </a>
            </li>
            <li>
                <a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
                    <span>🥕</span> Data Wortel
                </a>
            </li>
            <li>
                <a href="stock-in.php" class="<?php echo $current_page == 'stock-in.php' ? 'active' : ''; ?>">
                    <span>📥</span> Stok Masuk
                </a>
            </li>
            <li>
                <a href="stock-out.php" class="<?php echo $current_page == 'stock-out.php' ? 'active' : ''; ?>">
                    <span>📤</span> Stok Keluar
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <span>📈</span> Laporan
                </a>
            </li>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li>
                <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <span>👥</span> Manajemen User
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Divider -->
            <li style="margin: 20px 0; border-top: 1px solid #eee;"></li>
            
            <li>
                <a href="../logout.php" style="color: #f44336;">
                    <span>🚪</span> Logout
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Quick Stats in Sidebar -->
    <div style="padding: 20px; margin-top: auto; border-top: 1px solid #eee;">
        <h4 style="color: #2e7d32; margin-bottom: 10px;">Statistik Cepat</h4>
        <?php
        $stmt = $pdo->query("
            SELECT 
                (SELECT COALESCE(SUM(jumlah), 0) FROM stock_in WHERE DATE(tanggal_masuk) = CURDATE()) as today_in,
                (SELECT COALESCE(SUM(jumlah), 0) FROM stock_out WHERE DATE(tanggal_keluar) = CURDATE()) as today_out
        ");
        $today_stats = $stmt->fetch();
        ?>
        <div style="font-size: 14px; color: #666;">
            <p>📥 Hari Ini: <strong><?php echo number_format($today_stats['today_in'], 2); ?> Kg</strong></p>
            <p>📤 Hari Ini: <strong><?php echo number_format($today_stats['today_out'], 2); ?> Kg</strong></p>
        </div>
    </div>
</aside>