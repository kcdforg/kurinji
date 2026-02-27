<?php
// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (authenticateUser($username, $password)) {
        $_SESSION['user_id'] = md5($username);
        $_SESSION['username'] = $username;
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kurinji Poultry Farm — Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --primary: #1a6e3c; --accent: #f5a623; }
body {
    background: linear-gradient(135deg, var(--primary) 0%, #0d4620 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}
.login-container {
    width: 100%;
    max-width: 380px;
}
.login-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    background: white;
}
.login-header {
    background: var(--primary);
    color: white;
    padding: 2rem 1.5rem;
    border-radius: 16px 16px 0 0;
    text-align: center;
}
.login-header i {
    font-size: 3rem;
    display: block;
    margin-bottom: 0.5rem;
}
.login-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}
.login-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 0.9rem;
}
.login-body {
    padding: 2rem 1.5rem;
}
.form-group {
    margin-bottom: 1.2rem;
}
.form-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.9rem;
}
.form-control {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: border-color 0.3s;
}
.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(26, 110, 60, 0.1);
}
.btn-login {
    width: 100%;
    padding: 0.9rem;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    background: var(--primary);
    color: white;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1rem;
}
.btn-login:hover {
    background: #135a2d;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26, 110, 60, 0.3);
}
.btn-login:active {
    transform: translateY(0);
}
.alert {
    border-radius: 8px;
    border: none;
    margin-bottom: 1.5rem;
}
.demo-credentials {
    background: #f0f4f8;
    border-left: 4px solid var(--accent);
    padding: 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #555;
    margin-top: 1.5rem;
}
.demo-credentials strong {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
}
.demo-credentials code {
    background: white;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    color: var(--primary);
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-egg-fried"></i>
            <h2>Kurinji Poultry</h2>
            <p>Farm Management System</p>
        </div>
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-lock me-2"></i>Login
                </button>
            </form>

            <div class="demo-credentials">
                <strong>Demo Credentials:</strong>
                <div><code>admin</code> / <code>admin123</code></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
