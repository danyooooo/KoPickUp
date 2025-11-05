<?php
require __DIR__ . '/../db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            session_write_close();

            if ($user['role'] === 'admin') {
                header("Location: /admin");
            } elseif ($user['role'] === 'manager') {
                header("Location: /manager");
            } else {
                header("Location: /");
            }
            exit;
        } else {
            $error_message = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | KoPickUp</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
      background: rgba(255,255,255,0.96);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      box-shadow: 0 10px 36px rgba(168,230,255,0.15);
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
      box-shadow: 0 3px 10px rgba(168,230,255,0.15);
    }
    .auth-btn:hover {
      box-shadow: 0 6px 20px rgba(255,214,204,0.25);
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
      .auth-card { padding: 36px 8vw 26px 8vw; }
      h2 { font-size: 22px;}
    }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-logo">
        <a href="/">KoPickUp</a>
    </div>
    <h2>Login to your account</h2>
    <?php if ($error_message): ?>
        <p class="alert-error"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="auth-btn" type="submit">Login</button>
    </form>
    <div class="switch-auth">
        Don't have an account? <a href="/signup">Sign Up</a>
    </div>
    <br><br>
    <footer class="site-footer">
        <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
    </footer>
  </div>
</body>
</html>