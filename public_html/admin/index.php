<?php
require __DIR__ . '/../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to view this page.');
}

$message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_role'])) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['role'];

        if (in_array($new_role, ['user', 'manager', 'admin'])) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                $message = "User role has been updated successfully.";
            } catch (Exception $e) {
                $error_message = "Error updating user role: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid role specified.";
        }
    }

    if (isset($_POST['update_email_template'])) {
        $email_subject = $_POST['email_subject'];
        $email_body = $_POST['email_body'];

        try {
            $pdo->beginTransaction();
            $stmt_subject = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'email_subject'");
            $stmt_subject->execute([$email_subject]);
            
            $stmt_body = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'email_body'");
            $stmt_body->execute([$email_body]);
            
            $pdo->commit();
            $message = "Email template updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Failed to update email template: " . $e->getMessage();
        }
    }
}

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_parcels = $pdo->query("SELECT COUNT(*) FROM parcels")->fetchColumn();
$parcels_collected = $pdo->query("SELECT COUNT(*) FROM parcels WHERE status = 'Collected'")->fetchColumn();
$active_parcels = $total_parcels - $parcels_collected;

$settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$users = $pdo->query("SELECT id, fullname, email, role FROM users ORDER BY fullname ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Panel | KoPickUp</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .stat-card { background: #f0f4f8; padding: 20px; border-radius: 12px; text-align: center; }
        .stat-card h3 { margin: 0 0 10px; font-size: 18px; }
        .stat-card p { font-size: 32px; font-weight: 700; margin: 0; }
        h3, h2 { text-align: center; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .alert-success, .alert-error {
            padding: 15px 25px;
            border-radius: 12px;
            margin: 20px auto;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: fit-content;
        }
        .status-badge.role-admin {
            background-color: #dc3545;
            color: white;
        }
        .status-badge.role-manager {
            background-color: #ffc107;
            color: #333;
        }
        .status-badge.role-user {
            background-color: #007bff;
            color: white;
        }

    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="logo"><h1>KoPickUp</h1></div>
        <nav class="nav-links">
            <a href="/">HOME</a>
            <a href="/shop">SHOP</a>
            <a href="/about">ABOUT</a>
            <a href="/manager">MANAGER PANEL</a>
            <a href="#" onclick="openProfilePopup(); return false;">PROFILE</a>
        </nav>
        <div class="header-buttons"><a href="/logout" class="signup">Logout</a></div>
    </header>

    <main>
        <h2>Admin Dashboard</h2>

        <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert-error"><?php echo $error_message; ?></div><?php endif; ?>
        
        <div class="section-card">
            <h3>System Overview</h3>
            <div class="stats-grid">
                <div class="stat-card"><h3>Total Users</h3><p><?php echo $total_users; ?></p></div>
                <div class="stat-card"><h3>Total Parcels</h3><p><?php echo $total_parcels; ?></p></div>
                <div class="stat-card"><h3>Parcels Collected</h3><p><?php echo $parcels_collected; ?></p></div>
                <div class="stat-card"><h3>Active Parcels</h3><p><?php echo $active_parcels; ?></p></div>
            </div>
        </div>

        <!--<div class="section-card">
            <h3>Notification Email Template</h3>
            <p>Edit the automated email sent to students. Use <code>{recipient_name}</code> and <code>{tracking_number}</code> as placeholders.</p>
            
            <form action="admin.php" method="POST">
                <div class="form-grid" style="grid-template-columns: 1fr;">
                    <div>
                        <label>Email Subject</label>
                        <input type="text" name="email_subject" value="<?php echo htmlspecialchars($settings['email_subject'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label>Email Body (HTML is allowed)</label>
                        <textarea name="email_body" rows="12" style="font-family: monospace; line-height: 1.5;" required><?php echo htmlspecialchars($settings['email_body'] ?? ''); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="update_email_template">Save Template</button>
            </form>
        </div>-->

        <div class="section-card">
            <h3>User Management</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <form action="" method="POST" class="button-group" style="justify-content: flex-end;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" style="padding: 10px; border-radius: 8px;">
                                            <option value="user" <?php if ($user['role'] == 'user') echo 'selected'; ?>>User</option>
                                            <option value="manager" <?php if ($user['role'] == 'manager') echo 'selected'; ?>>Manager</option>
                                            <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                        </select>
                                        <button type="submit" name="update_role">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
    </footer>
</div>
<?php include __DIR__ . '/../includes/profile_popup.php'; ?>
</body>
</html>