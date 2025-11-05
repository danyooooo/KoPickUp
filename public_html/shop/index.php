<?php
require __DIR__ . '/../db.php';

session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Shop | KoPickUp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1><a href="/" style="text-decoration:none; color:inherit;">KoPickUp</a></h1>
            </div>
            <nav class="nav-links">
                <a href="/">HOME</a>
                <a href="/shop">SHOP</a>
                <a href="/about">ABOUT</a>
                <?php if ($user_role === 'admin'): ?>
                    <a href="/admin">ADMIN PANEL</a>
                    <a href="/manager">MANAGER PANEL</a>
                <?php elseif ($user_role === 'manager'): ?>
                    <a href="/manager">MANAGER PANEL</a>
                <?php endif; ?>
                <?php if ($is_logged_in): ?>
                    <a href="#" onclick="openProfilePopup(); return false;">PROFILE</a>
                <?php endif; ?>
            </nav>
            <div class="header-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="/logout" class="signup">Logout</a>
                <?php else: ?>
                    <a href="/login" class="login">Login</a>
                    <a href="/signup" class="signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <section class="section-card">
                <h2>E-Commerce</h2>
                <div class="coming-soon">
                    <div>COMING SOON</div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
        </footer>
    </div>

    <?php if ($is_logged_in): ?>
        <?php include __DIR__ . '/../includes/profile_popup.php'; ?>
    <?php endif; ?>
</body>
</html>