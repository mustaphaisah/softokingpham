<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('/pages/dashboard.php');
}

$error = '';
$userCount = countRows('SELECT COUNT(*) FROM users');
if ($userCount === 0) {
    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $stmt->execute(['admin', $passwordHash]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            redirect('/pages/dashboard.php');
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Login - Pharmacy System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        * {
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            perspective: 1000px;
            width: 100%;
            padding: 1rem;
        }
        .login-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            margin: 0 auto;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
        }
        .login-header h2 {
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
        }
        .login-body {
            padding: 2.5rem 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .form-control:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.875rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 1rem;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border-left: 4px solid;
        }
        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            color: #7f1d1d;
            border-left-color: #ef4444;
        }
        .login-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .login-footer p {
            margin: 0;
            font-size: 0.9rem;
            color: #64748b;
        }
        .login-footer strong {
            color: #334155;
        }
        @media (max-width: 576px) {
            .login-card {
                border-radius: 1rem;
            }
            .login-header {
                padding: 1.5rem 1rem;
            }
            .login-header h2 {
                font-size: 1.5rem;
            }
            .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h2>💊 Pharmacy</h2>
            <p>Inventory & Sales Management</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="login-btn">Sign In</button>
            </form>
        </div>
       <!-- <div class="login-footer">
            <p><strong>Demo Credentials:</strong></p>
            <p>👤 <strong>admin</strong> / 🔐 admin123</p>
            <p>👤 <strong>cashier</strong> / 🔐 admin123</p>
        </div> -->
    </div>
</div>
</body>
</html>
