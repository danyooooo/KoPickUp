<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../db.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'guest';

$tracking_number = isset($_GET['tracking_number']) ? trim($_GET['tracking_number']) : '';
$parcel = null;
$error_message = '';

if (!empty($tracking_number)) {
    $stmt = $pdo->prepare(
        "SELECT p.*, COALESCE(u.fullname, p.recipient_name) as recipient_name_display
         FROM parcels p
         LEFT JOIN users u ON p.user_id = u.id
         WHERE p.tracking_number = ?"
    );
    $stmt->execute([$tracking_number]);
    $parcel = $stmt->fetch();

    if (!$parcel) {
        $error_message = "We haven't registered a parcel with this tracking number yet. It may have just arrived! Please check back in a few hours, or ask a staff member for help.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Track Parcel | KoPickUp</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .timeline { list-style: none; padding: 0; position: relative; margin-top: 30px; }
        .timeline:before {
            content: ''; position: absolute; top: 0; bottom: 0; left: 20px; width: 4px; background: #e9ecef;
        }
        .timeline-item { margin-bottom: 20px; position: relative; padding-left: 60px; }
        .timeline-icon {
            position: absolute; left: 0; top: 0; width: 42px; height: 42px; border-radius: 50%;
            background: #e9ecef; color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: bold;
        }
        .timeline-item.active .timeline-icon { background: #007bff; }
        .timeline-content { background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .timeline-content h4 { margin: 0 0 10px; }
        .timeline-content p { margin: 0; color: #6c757d; }
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
        <div class="section-card" style="max-width: 800px; margin: 40px auto;">
            <h3>Track a Parcel</h3>
            <p>Enter a tracking number below to see its current status.</p>
            <form action="/track/" method="GET" class="tracking-input" style="margin-top:20px;">
                <input type="text" name="tracking_number" placeholder="Enter tracking number" value="<?php echo htmlspecialchars($tracking_number); ?>" required>
                <button type="submit" class="track-btn">Track</button>
            </form>
        </div>

        <?php if ($tracking_number):?>
            <div class="section-card" style="max-width: 800px; margin: 40px auto;">
                <?php if ($error_message): ?>
                    <div class="alert-error"><?php echo $error_message; ?></div>
                <?php elseif ($parcel): ?>
                    <h3>Tracking Results for #<?php echo htmlspecialchars($parcel['tracking_number']); ?></h3>
                    
                    <div style="text-align: left; margin: 20px 0 30px; border-left: 3px solid #007bff; padding-left: 15px;">
                        <p><strong>Recipient:</strong> <?php echo htmlspecialchars($parcel['recipient_name_display']); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $parcel['status'])); ?>"><?php echo htmlspecialchars($parcel['status']); ?></span></p>
                    </div>

                    <ul class="timeline">
                        <li class="timeline-item active">
                            <div class="timeline-icon">✓</div>
                            <div class="timeline-content">
                                <h4>Parcel Registered</h4>
                                <p>The parcel was registered at our facility on <?php echo date('F j, Y, g:i a', strtotime($parcel['registered_at'])); ?></p>
                            </div>
                        </li>
                        
                        <?php if ($parcel['status'] === 'Collected' && $parcel['collected_at']): ?>
                        <li class="timeline-item active">
                            <div class="timeline-icon">✓</div>
                            <div class="timeline-content">
                                <h4>Parcel Collected</h4>
                                <p>The parcel was collected by the recipient on <?php echo date('F j, Y, g:i a', strtotime($parcel['collected_at'])); ?></p>
                            </div>
                        </li>
                        <?php else: ?>
                        <li class="timeline-item">
                            <div class="timeline-icon">...</div>
                            <div class="timeline-content">
                                <h4>Awaiting Collection</h4>
                                <p>The parcel is ready for pickup at the co-op shop.</p>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
    </footer>
</div>

<?php 
if ($is_logged_in) {
    include __DIR__ . '/../includes/profile_popup.php'; 
}
?>
</body>
</html>