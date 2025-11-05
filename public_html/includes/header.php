<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'guest';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        
        <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | KoPickUp' : 'KoPickUp'; ?></title>
        
        <link rel="stylesheet" href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>style.css">
    </head>
<body>
<div class="container">
    <header>
        <div class="logo">
            <h1><a href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>/" style="text-decoration:none; color:inherit;">KoPickUp</a></h1>
        </div>
        <nav class="nav-links">
            <?php if ($user_role === 'admin'): ?>
                <a href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>/admin/">ADMIN PANEL</a>
                <a href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>/manager/">MANAGER PANEL</a>
            <?php elseif ($user_role === 'manager'): ?>
                 <a href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>/manager/">MANAGER PANEL</a>
            <?php endif; ?>
            
            <?php if ($is_logged_in): ?>
                <a href="#" onclick="openProfilePopup(); return false;">Profile</a>
            <?php endif; ?>
        </nav>
        <div class="header-buttons">
            <?php if ($is_logged_in): ?>
                <a href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>/logout/" class="login">Logout</a>
            <?php else: ?>
                <a href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>/login/" class="login">Login</a>
                <a href="<?php echo isset($path_to_root) ? $path_to_root : ''; ?>/signup/" class="signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </header>
    
    <main>