<?php
require __DIR__ . '/../db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = strtoupper(trim($_POST['fullname']));
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmpassword'];
    $tracking_number = trim($_POST['tracking_number'] ?? '');

    if (empty($fullname) || empty($email) || empty($password)) {
        $error_message = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error_message = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$fullname, $email, $hashed_password]);
            
            $new_user_id = $pdo->lastInsertId();

            if ($new_user_id) {
                if (!empty($tracking_number)) {
                    $assign_stmt_track = $pdo->prepare(
                        "UPDATE parcels SET user_id = ? WHERE user_id IS NULL AND tracking_number = ?"
                    );
                    $assign_stmt_track->execute([$new_user_id, $tracking_number]);
                }

                $assign_stmt_name = $pdo->prepare(
                    "UPDATE parcels SET user_id = ? WHERE user_id IS NULL AND recipient_name = ?"
                );
                $assign_stmt_name->execute([$new_user_id, $fullname]);
            }
            
            header("Location: /login?status=registered");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up | KoPickUp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #a8e6ff 0%, #ffd6cc 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 10px 36px rgba(168, 230, 255, 0.15);
            padding: 48px 36px 32px 36px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .auth-logo {
            font-size: 32px;
            font-weight: 700;
            color: transparent;
            background: linear-gradient(135deg, #a8e6ff, #ffd6cc);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 32px;
        }

        h2 {
            margin-bottom: 24px;
            font-size: 28px;
            font-weight: 700;
            color: #222831;
            letter-spacing: .02em;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 14px 16px;
            border-radius: 10px;
            border: 1.5px solid #b3e5fc;
            font-size: 16px;
            font-family: inherit;
            background: #fffafa;
            outline: none;
            transition: border 0.2s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #a8e6ff;
            background: #fff;
        }

        .auth-btn {
            background: linear-gradient(135deg, #a8e6ff, #ffd6cc);
            color: #222831;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            padding: 14px 0;
            margin-top: 10px;
            cursor: pointer;
            transition: box-shadow 0.2s, transform 0.15s;
            box-shadow: 0 3px 10px rgba(168, 230, 255, 0.15);
        }

        .auth-btn:hover {
            box-shadow: 0 6px 20px rgba(255, 214, 204, 0.25);
            transform: translateY(-2px);
        }

        .switch-auth {
            margin-top: 24px;
            font-size: 15px;
        }

        .switch-auth a {
            color: #a8e6ff;
            font-weight: 600;
            text-decoration: none;
            margin-left: 8px;
            transition: color 0.2s;
        }

        .switch-auth a:hover {
            color: #ffd6cc;
        }

        @media (max-width: 500px) {
            .auth-card {
                padding: 36px 8vw 26px 8vw;
            }

            h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="auth-logo">
            <a href="/">KoPickUp</a>
        </div>
        <h2>Create a new account</h2>
        <?php if ($error_message): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="/signup/index.php" method="POST">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirmpassword" placeholder="Confirm Password" required>
            <input type="text" name="tracking_number" placeholder="Tracking Number (Optional)" value="<?php echo htmlspecialchars($_GET['tracking_number'] ?? ''); ?>">
            <button class="auth-btn" type="submit">Sign Up</button>
        </form>
        <div class="switch-auth">
            Already have an account? <a href="/login">Log in</a>
        </div>
        <br><br>
        <footer class="site-footer">
            <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
        </footer>
    </div>
</body>

</html>