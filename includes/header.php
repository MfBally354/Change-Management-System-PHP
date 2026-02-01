<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Change Management System</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <?php if (isLoggedIn()): 
        $user = getCurrentUser();
    ?>
    <header>
        <div class="container">
            <h1>🔧 Change Management System</h1>
            <nav>
                <ul>
                    <li><a href="/index.php">Dashboard</a></li>
                    <li><a href="/change-management/changes/list.php">Change Requests</a></li>
                    
                    <?php if (canPerformAction('view_audit_logs')): ?>
                    <li><a href="/change-management/logs/audit.php">Audit Logs</a></li>
                    <?php endif; ?>
                    
                    <?php if (canPerformAction('manage_users')): ?>
                    <li><a href="/change-management/admin/users.php">Users</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-info">
                <div>
                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                    <div class="role-badge"><?php echo getRoleName($user['role']); ?></div>
                </div>
                <a href="/change-management/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </header>
    <?php endif; ?>
    
    <main class="container">
        <?php echo displayFlashMessage(); ?>
