<?php
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_fullname = strtoupper(trim($_POST['fullname']));
    $new_student_id = trim($_POST['student_id']);

    if (empty($new_fullname)) {
        $error_message = "Full name cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, student_id = ? WHERE id = ?");
            $stmt->execute([$new_fullname, $new_student_id, $user_id]);
            $message = "Your profile has been updated successfully!";
        } catch (Exception $e) {
            $error_message = "An error occurred while updating your profile.";
            error_log("Profile update failed: " . $e->getMessage());
        }
    }
}

$stmt = $pdo->prepare("SELECT fullname, email, student_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | KoPickUp</title>
    <link rel="stylesheet" href="../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body>
<div class="container">
    <header>
        <div class="logo"><h1><a href="/" style="text-decoration:none; color:inherit;">KoPickUp</a></h1></div>
        <nav class="nav-links"><a href="/">Home</a></nav>
    </header>

    <main>
        <div class="section-card" style="max-width: 600px; margin: 40px auto; text-align: left;">
            <h2>Edit Your Profile</h2>
            <p>Keep your information up to date to ensure smooth parcel registration.</p>
            
            <?php if ($message): ?><div class="alert-success" style="text-align:center;"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="alert-error" style="text-align:center;"><?php echo $error_message; ?></div><?php endif; ?>

            <form action="" method="POST" style="margin-top: 20px;">
                <div class="form-grid" style="grid-template-columns: 1fr;">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    <div>
                        <label>Student ID</label>
                        <input type="text" name="student_id" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" placeholder="Enter your student ID number">
                    </div>
                    <div>
                        <label>Email Address (Cannot be changed)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                </div>
                <button type="submit" name="update_profile" style="width:100%; margin-top: 20px;">Update Profile</button>
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
    </footer>
</div>

<?php 
// You can include your centralized popup file here if you want it on this page too.
// include __DIR__ . '/../includes/profile_popup.php'; 
?>
</body>
</html>