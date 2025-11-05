<?php
require __DIR__ . '/../db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    die('ACCESS DENIED: You must be logged in as a manager or admin to perform this action.');
}

$is_logged_in = true;
$user_role = $_SESSION['role'];

$tracking_number = isset($_GET['tracking_number']) ? trim($_GET['tracking_number']) : '';
$message = '';
$message_type = 'error';

if (empty($tracking_number)) {
    $message = "No tracking number provided.";
    $message_type = 'error';
} else {
    $stmt = $pdo->prepare("SELECT id, status, late_fee FROM parcels WHERE tracking_number = ?");
    $stmt->execute([$tracking_number]);
    $parcel = $stmt->fetch();

    if (!$parcel) {
        $message = "Parcel with tracking number '" . htmlspecialchars($tracking_number) . "' not found.";
        $message_type = 'error';
    } elseif ($parcel['status'] === 'Collected') {
        $message = "This parcel has already been collected.";
        $message_type = 'info';
    } else {
        $update_stmt = $pdo->prepare("UPDATE parcels SET status = 'Collected', collected_at = NOW() WHERE id = ?");
        $update_stmt->execute([$parcel['id']]);

        $message = "<strong>Success!</strong> Parcel '" . htmlspecialchars($tracking_number) . "' has been marked as collected.";
        $message_type = 'success';
        
        if ($parcel['late_fee'] > 0) {
            $message .= "<br>Total late fee collected: <strong>RM " . number_format($parcel['late_fee'], 2) . "</strong>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parcel Collection | KoPickUp</title>
    <link rel="stylesheet" href="../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <style>
        .alert-success, .alert-error, .alert-info {
            padding: 15px 25px;
            border-radius: 12px;
            margin: 20px auto;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: fit-content;
        }

        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .alert-info {
            background-color: #fff3cd;
            color: #856404;
        }

        .btn-gradient {
            display: inline-block;
            background: linear-gradient(135deg, #aeeaff, #aeaeea);
            color: #334155;
            padding: 12px 28px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            text-align: center;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
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
        <div class="section-card" style="max-width: 600px; margin: 40px auto; text-align: center;">
            <h2>Parcel Collection Status</h2>
            <div class="alert-<?php echo $message_type; ?>" style="margin-top: 20px;">
                <?php echo $message; ?>
            </div>
            <a href="/manager" class="btn-gradient" style="margin-top: 30px; display:inline-block;">Back to Manager Menu</a>
        </div>
    </main>

    <footer class="site-footer">
        <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
    </footer>
</div>
<?php include __DIR__ . '/../includes/profile_popup.php'; ?>
</body>
</html>