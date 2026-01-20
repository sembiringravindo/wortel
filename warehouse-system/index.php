<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard/index.php');
    exit();
}

// Ambil data statistik dari database untuk ditampilkan di landing page
try {
    // Total Stok Saat Ini
    $stmt = $pdo->query("
        SELECT 
            (SELECT COALESCE(SUM(jumlah), 0) FROM stock_in) as total_in,
            (SELECT COALESCE(SUM(jumlah), 0) FROM stock_out) as total_out
    ");
    $stock_stats = $stmt->fetch();
    $current_stock = $stock_stats['total_in'] - $stock_stats['total_out'];
    
    // Total Jenis Wortel
    $stmt = $pdo->query("SELECT COUNT(*) as total_wortel FROM wortel");
    $wortel_count = $stmt->fetch()['total_wortel'];
    
    // Total Transaksi Bulan Ini
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_transaksi 
        FROM (
            SELECT id FROM stock_in WHERE MONTH(tanggal_masuk) = MONTH(CURDATE())
            UNION ALL
            SELECT id FROM stock_out WHERE MONTH(tanggal_keluar) = MONTH(CURDATE())
        ) as transactions
    ");
    $transaksi_count = $stmt->fetch()['total_transaksi'];
    
    // Data Grafik Stok 6 Bulan Terakhir
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
    
    // Distribusi Kualitas Wortel
    $stmt = $pdo->query("
        SELECT kualitas, COUNT(*) as jumlah, 
               ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM wortel)), 1) as persentase
        FROM wortel 
        GROUP BY kualitas
        ORDER BY jumlah DESC
    ");
    $quality_distribution = $stmt->fetchAll();
    
    // Aktivitas Terbaru (5 terakhir)
    $stmt = $pdo->query("
        SELECT al.activity, al.timestamp, u.full_name 
        FROM activity_log al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.timestamp DESC 
        LIMIT 5
    ");
    $recent_activities = $stmt->fetchAll();
    
    // Top Distributor
    $stmt = $pdo->query("
        SELECT tujuan_distribusi, SUM(jumlah) as total 
        FROM stock_out 
        WHERE tanggal_keluar >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY tujuan_distribusi 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $top_distributors = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Jika ada error, set default values
    $current_stock = 0;
    $wortel_count = 0;
    $transaksi_count = 0;
    $chart_data = [];
    $quality_distribution = [];
    $recent_activities = [];
    $top_distributors = [];
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Gudang Wortel - Desa Barus Julu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            --primary-dark: #1b5e20;
            --secondary-color: #ff9800;
            --accent-color: #2196f3;
            --light-green: #e8f5e9;
            --dark-bg: #121212;
            --card-shadow: 0 20px 40px rgba(76, 175, 80, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f8fdf8 0%, #ffffff 100%);
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Glass Morphism Effect */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 40px;
            z-index: 1000;
            transition: var(--transition);
        }
        
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .logo-text h1 {
            font-size: 24px;
            color: #2e7d32;
            font-weight: 700;
        }
        
        .logo-text p {
            font-size: 12px;
            color: #666;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }
        
        .nav-links a:hover {
            color: #2e7d32;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-gradient);
            transition: var(--transition);
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .btn-nav {
            padding: 12px 30px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }
        
        .btn-nav:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(rgba(46, 125, 50, 0.95), rgba(46, 125, 50, 0.9)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 100px 20px 60px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .hero-bg-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 2px, transparent 2px),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 2px, transparent 2px);
            background-size: 60px 60px;
            animation: patternFloat 20s linear infinite;
        }
        
        @keyframes patternFloat {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-30px, -30px); }
        }
        
        .hero-content {
            max-width: 1200px;
            z-index: 2;
            position: relative;
        }
        
        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.1;
            background: linear-gradient(to right, #ffffff, #c8e6c9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.95;
            font-weight: 300;
        }
        
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1000px;
            margin: 60px auto;
        }
        
        .hero-stat {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }
        
        .hero-stat:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Live Dashboard Section */
        .dashboard-section {
            padding: 100px 40px;
            background: #f8fdf8;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title {
            font-size: 3rem;
            font-weight: 700;
            color: #2e7d32;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--secondary-color);
            border-radius: 2px;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(76, 175, 80, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--primary-gradient);
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(76, 175, 80, 0.15);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .card-title {
            font-size: 1.8rem;
            color: #2e7d32;
            font-weight: 600;
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            background: var(--light-green);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #2e7d32;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        /* Quality Distribution */
        .quality-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .quality-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 12px;
            transition: var(--transition);
        }
        
        .quality-item:hover {
            background: #f0f7f0;
            transform: translateX(5px);
        }
        
        .quality-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
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
        
        /* Activities List */
        .activities-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 12px;
            transition: var(--transition);
        }
        
        .activity-item:hover {
            background: #f0f7f0;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: var(--light-green);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2e7d32;
            font-size: 18px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .activity-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Distributors List */
        .distributors-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .distributor-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 12px;
            transition: var(--transition);
        }
        
        .distributor-item:hover {
            background: #f0f7f0;
        }
        
        .distributor-name {
            font-weight: 500;
            color: #333;
        }
        
        .distributor-amount {
            color: #2e7d32;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Login Section */
        .login-section {
            padding: 100px 40px;
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-container {
            max-width: 500px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .login-form .form-group {
            margin-bottom: 25px;
        }
        
        .form-input {
            width: 100%;
            padding: 18px 24px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            font-size: 16px;
            color: white;
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .input-with-icon input {
            padding-left: 60px;
        }
        
        .btn-login {
            width: 100%;
            padding: 20px;
            background: white;
            color: #2e7d32;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.3);
        }
        
        /* Footer */
        .footer {
            background: #1b5e20;
            color: white;
            padding: 80px 40px 40px;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 50px;
            margin-bottom: 60px;
        }
        
        .footer-section h4 {
            font-size: 1.3rem;
            margin-bottom: 25px;
            color: #c8e6c9;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 15px;
        }
        
        .footer-links a {
            color: #b0bec5;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .social-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #b0bec5;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 2.5rem;
            }
            
            .dashboard-section,
            .login-section {
                padding: 60px 20px;
            }
            
            .dashboard-card {
                padding: 30px 20px;
            }
            
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .hero-stats {
                grid-template-columns: 1fr;
            }
            
            .hero-stat {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 2.5rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #4caf50, #2e7d32);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #2e7d32, #1b5e20);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="nav-logo">
                <div class="logo-icon">
                    🥕
                </div>
                <div class="logo-text">
                    <h1>Desa Barus Julu</h1>
                    <p>Sistem Gudang Wortel</p>
                </div>
            </a>
            
            <div class="nav-links">
                <a href="#home">Beranda</a>
                <a href="#dashboard">Dashboard</a>
                <a href="#stats">Statistik</a>
                <a href="login.php" class="btn-nav">Masuk Sistem</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-bg-pattern"></div>
        <div class="hero-content animate-fadeInUp">
            <h1 class="hero-title">
                Sistem Digital
                <span style="display: block; color: #ffeb3b;">Gudang Wortel</span>
            </h1>
            <p class="hero-subtitle">
                Kelola stok, distribusi, dan laporan wortel Desa Barus Julu secara real-time.
                Transparan, akurat, dan efisien untuk kemajuan pertanian desa.
            </p>
            
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="stat-value" id="currentStock">0</div>
                    <div class="stat-label">Stok Tersedia (Kg)</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-value"><?php echo $wortel_count; ?></div>
                    <div class="stat-label">Jenis Wortel</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-value"><?php echo $transaksi_count; ?></div>
                    <div class="stat-label">Transaksi Bulan Ini</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Monitoring Real-time</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Dashboard Section -->
    <section class="dashboard-section" id="dashboard">
        <div class="section-header animate-fadeInUp">
            <h2 class="section-title">Dashboard Live Gudang</h2>
            <p class="section-subtitle">
                Data real-time dari sistem pengelolaan gudang wortel Desa Barus Julu
            </p>
        </div>
        
        <div class="dashboard-grid">
            <!-- Chart Card -->
            <div class="dashboard-card animate-fadeInUp" style="animation-delay: 0.1s;">
                <div class="card-header">
                    <h3 class="card-title">Grafik Stok 6 Bulan</h3>
                    <div class="card-icon">📈</div>
                </div>
                <div class="chart-container">
                    <canvas id="stockChart"></canvas>
                </div>
            </div>
            
            <!-- Quality Distribution Card -->
            <div class="dashboard-card animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="card-header">
                    <h3 class="card-title">Distribusi Kualitas</h3>
                    <div class="card-icon">⭐</div>
                </div>
                <div class="quality-list">
                    <?php foreach ($quality_distribution as $quality): ?>
                    <div class="quality-item">
                        <span class="quality-badge quality-<?php echo strtolower($quality['kualitas']); ?>">
                            <?php echo $quality['kualitas']; ?>
                        </span>
                        <span>
                            <strong><?php echo $quality['jumlah']; ?></strong> jenis 
                            (<?php echo $quality['persentase']; ?>%)
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Activities Card -->
            <div class="dashboard-card animate-fadeInUp" style="animation-delay: 0.3s;">
                <div class="card-header">
                    <h3 class="card-title">Aktivitas Terbaru</h3>
                    <div class="card-icon">📝</div>
                </div>
                <div class="activities-list">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php echo strpos($activity['activity'], 'login') !== false ? '🔐' : 
                                   (strpos($activity['activity'], 'Menambah') !== false ? '➕' : '📊'); ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <?php echo htmlspecialchars($activity['activity']); ?>
                            </div>
                            <div class="activity-meta">
                                <span><?php echo htmlspecialchars($activity['full_name']); ?></span>
                                <span><?php echo date('H:i', strtotime($activity['timestamp'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Top Distributors Card -->
            <div class="dashboard-card animate-fadeInUp" style="animation-delay: 0.4s;">
                <div class="card-header">
                    <h3 class="card-title">Top Distributor (30 Hari)</h3>
                    <div class="card-icon">🚚</div>
                </div>
                <div class="distributors-list">
                    <?php foreach ($top_distributors as $distributor): ?>
                    <div class="distributor-item">
                        <span class="distributor-name"><?php echo htmlspecialchars($distributor['tujuan_distribusi']); ?></span>
                        <span class="distributor-amount"><?php echo number_format($distributor['total'], 2); ?> Kg</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Desa Barus Julu</h4>
                <p style="color: #b0bec5; line-height: 1.8;">
                    Sistem Informasi Gudang Wortel terintegrasi untuk mendukung 
                    kemajuan pertanian desa secara digital.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Navigasi</h4>
                <ul class="footer-links">
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#dashboard">Dashboard</a></li>
                    <li><a href="#login">Login Sistem</a></li>
                    <li><a href="dashboard/index.php">Dashboard Admin</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Fitur</h4>
                <ul class="footer-links">
                    <li><a href="#">Manajemen Stok</a></li>
                    <li><a href="#">Laporan Otomatis</a></li>
                    <li><a href="#">Analisis Data</a></li>
                    <li><a href="#">Monitoring Real-time</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Kontak</h4>
                <ul class="footer-links">
                    <li>📞 +62 812-3456-7890</li>
                    <li>📧 info@barusjulu.desa.id</li>
                    <li>📍 Desa Barus Julu</li>
                    <li>🕐 Support 24/7</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 Sistem Informasi Gudang Wortel - Desa Barus Julu. Semua Hak Dilindungi.</p>
            <p style="margin-top: 10px;">Dikembangkan dengan ❤️ untuk kemajuan pertanian desa</p>
        </div>
    </footer>

    <script>
        // Animated counter for current stock
        function animateCounter(elementId, targetValue, duration = 2000) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const step = targetValue / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= targetValue) {
                    element.textContent = targetValue.toLocaleString('id-ID');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current).toLocaleString('id-ID');
                }
            }, 16);
        }

        // Initialize Chart.js
        document.addEventListener('DOMContentLoaded', function() {
            // Animate current stock counter
            animateCounter('currentStock', <?php echo $current_stock; ?>);
            
            // Prepare chart data from PHP
            const chartData = <?php echo json_encode($chart_data); ?>;
            
            if (chartData.length > 0) {
                const months = chartData.map(item => item.month_name);
                const stockIn = chartData.map(item => parseFloat(item.stock_in) || 0);
                const stockOut = chartData.map(item => parseFloat(item.stock_out) || 0);
                
                // Create stock chart
                const ctx = document.getElementById('stockChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [
                            {
                                label: 'Stok Masuk',
                                data: stockIn,
                                borderColor: '#4caf50',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3
                            },
                            {
                                label: 'Stok Keluar',
                                data: stockOut,
                                borderColor: '#ff9800',
                                backgroundColor: 'rgba(255, 152, 0, 0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 14
                                    },
                                    padding: 20
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#4caf50',
                                borderWidth: 1,
                                padding: 15,
                                cornerRadius: 10,
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ${context.parsed.y.toLocaleString('id-ID')} Kg`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    },
                                    callback: function(value) {
                                        return value.toLocaleString('id-ID') + ' Kg';
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Jumlah (Kg)',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'nearest'
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
            
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                const navbar = document.getElementById('navbar');
                if (window.scrollY > 100) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
                
                // Animate elements on scroll
                const elements = document.querySelectorAll('.animate-fadeInUp');
                elements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.classList.add('visible');
                    }
                });
            });
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Auto-refresh data every 30 seconds
            setInterval(() => {
                // Refresh counters
                fetch('includes/get-public-stats.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.current_stock) {
                            animateCounter('currentStock', data.current_stock);
                        }
                    })
                    .catch(error => console.error('Error refreshing data:', error));
            }, 30000);
            
            // Add hover effects to cards
            document.querySelectorAll('.dashboard-card').forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-10px) rotateX(2deg)';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0) rotateX(0)';
                });
            });
            
            // Form validation
            const loginForm = document.querySelector('.login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const username = this.querySelector('[name="username"]').value.trim();
                    const password = this.querySelector('[name="password"]').value.trim();
                    
                    if (!username || !password) {
                        e.preventDefault();
                        alert('Harap isi username dan password!');
                    }
                });
            }
        });
    </script>
</body>
</html>