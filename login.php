<?php
require_once 'config/database.php';
require_once 'auth/auth_check.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi.';
    } else {
        // Cari user di database
        $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                
                // Log audit
                logAudit($user['id'], 'login');
                
                // Redirect ke dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Change Management System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>🔧 Change Management</h1>
            <h3 style="text-align: center; color: #888; margin-bottom: 30px;">Login ke Sistem</h3>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    Login
                </button>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9em; color: #888;">
                <strong>Demo Accounts:</strong><br>
                <small>
                    Admin: admin / admin123<br>
                    Manager: manager1 / manager123<br>
                    IT Staff: staff1 / staff123<br>
                    Client: client1 / client123
                </small>
            </div>
        </div>
    </div>
</body>
</html>
