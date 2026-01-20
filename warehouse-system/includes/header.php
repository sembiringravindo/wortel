<?php if (!isset($no_header)): ?>
<header class="header">
    <div class="header-content">
        <div class="logo">
            <div style="font-size: 32px; margin-right: 10px;">🥕</div>
            <div>
                <h1>Desa Barus Julu</h1>
                <p>Sistem Informasi Gudang Wortel</p>
            </div>
        </div>
        
        <div class="user-info">
            <div style="text-align: right;">
                <span style="display: block; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <small style="opacity: 0.8;">
                    <?php echo ucfirst($_SESSION['role']); ?> • 
                    <?php echo date('d/m/Y'); ?>
                </small>
            </div>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>
<?php endif; ?>