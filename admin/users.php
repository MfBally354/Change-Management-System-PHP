<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

requireLogin();
requireRole(['admin']);

$page_title = 'User Management';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $department = trim($_POST['department'] ?? '');
        
        $errors = [];
        
        if (empty($username)) $errors[] = 'Username wajib diisi.';
        if (empty($password)) $errors[] = 'Password wajib diisi.';
        if (empty($full_name)) $errors[] = 'Full name wajib diisi.';
        if (empty($role)) $errors[] = 'Role wajib dipilih.';
        
        // Check username uniqueness
        if (!empty($username)) {
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errors[] = 'Username sudah digunakan.';
            }
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, password, full_name, email, role, department) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $role, $department);
            
            if ($stmt->execute()) {
                logAudit($_SESSION['user_id'], 'create_user', 'user', $conn->insert_id, null, json_encode(['username' => $username]));
                $_SESSION['success'] = 'User berhasil dibuat.';
                header('Location: users.php');
                exit;
            } else {
                $_SESSION['error'] = 'Gagal membuat user.';
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
    
    if ($action === 'toggle_status') {
        $user_id = (int)$_POST['user_id'];
        $new_status = (int)$_POST['new_status'];
        
        $sql = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $new_status, $user_id);
        
        if ($stmt->execute()) {
            $status_text = $new_status ? 'activated' : 'deactivated';
            logAudit($_SESSION['user_id'], 'toggle_user_status', 'user', $user_id, null, json_encode(['is_active' => $new_status]));
            $_SESSION['success'] = "User berhasil di-{$status_text}.";
        } else {
            $_SESSION['error'] = 'Gagal mengubah status user.';
        }
        
        header('Location: users.php');
        exit;
    }
    
    if ($action === 'reset_password') {
        $user_id = (int)$_POST['user_id'];
        $new_password = $_POST['new_password'];
        
        if (strlen($new_password) < 6) {
            $_SESSION['error'] = 'Password minimal 6 karakter.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed, $user_id);
            
            if ($stmt->execute()) {
                logAudit($_SESSION['user_id'], 'reset_password', 'user', $user_id);
                $_SESSION['success'] = 'Password berhasil direset.';
            } else {
                $_SESSION['error'] = 'Gagal reset password.';
            }
        }
        
        header('Location: users.php');
        exit;
    }
}

// Get all users
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM change_requests WHERE requester_id = u.id) as request_count,
        (SELECT COUNT(*) FROM change_requests WHERE assigned_to = u.id) as assigned_count
        FROM users u
        ORDER BY u.created_at DESC";
$users = $conn->query($sql);

include '../includes/header.php';
?>

<h1>User Management</h1>

<!-- Create User Form -->
<div class="card">
    <div class="card-header">
        <h2>Create New User</h2>
    </div>
    
    <form method="POST" action="">
        <input type="hidden" name="action" value="create">
        
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
                <small>Minimal 6 karakter</small>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">
            </div>
            
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin">Administrator</option>
                    <option value="manager">Manager</option>
                    <option value="staff">IT Staff</option>
                    <option value="client">Client/User</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" placeholder="e.g., IT, Finance">
            </div>
        </div>
        
        <button type="submit" class="btn btn-success">➕ Create User</button>
    </form>
</div>

<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h2>All Users (<?php echo $users->num_rows; ?>)</h2>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Stats</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <span class="badge badge-info"><?php echo getRoleName($user['role']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($user['department']); ?></td>
                <td>
                    <small>
                        <?php echo $user['request_count']; ?> requests<br>
                        <?php echo $user['assigned_count']; ?> assigned
                    </small>
                </td>
                <td>
                    <?php if ($user['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($user['id'] != $_SESSION['user_id']): // Can't modify self ?>
                    <div style="display: flex; gap: 5px;">
                        <!-- Toggle Status -->
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                            <button type="submit" class="btn <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                    style="padding: 4px 8px; font-size: 0.8em;"
                                    onclick="return confirm('Toggle status user ini?')">
                                <?php echo $user['is_active'] ? '🔒 Deactivate' : '✅ Activate'; ?>
                            </button>
                        </form>
                        
                        <!-- Reset Password -->
                        <button class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.8em;"
                                onclick="resetPassword(<?php echo $user['id']; ?>)">
                            🔑 Reset PW
                        </button>
                    </div>
                    <?php else: ?>
                        <em style="color: #999;">Current user</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Role Statistics -->
<div class="stats-grid">
    <?php
    $stats = $conn->query("
        SELECT role, COUNT(*) as count 
        FROM users 
        WHERE is_active = 1 
        GROUP BY role
    ");
    while ($stat = $stats->fetch_assoc()):
    ?>
    <div class="stat-card">
        <h3><?php echo $stat['count']; ?></h3>
        <p><?php echo getRoleName($stat['role']); ?>s</p>
    </div>
    <?php endwhile; ?>
</div>

<script>
function resetPassword(userId) {
    const newPassword = prompt('Masukkan password baru (minimal 6 karakter):');
    
    if (newPassword && newPassword.length >= 6) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const action = document.createElement('input');
        action.type = 'hidden';
        action.name = 'action';
        action.value = 'reset_password';
        form.appendChild(action);
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        form.appendChild(userIdInput);
        
        const passwordInput = document.createElement('input');
        passwordInput.type = 'hidden';
        passwordInput.name = 'new_password';
        passwordInput.value = newPassword;
        form.appendChild(passwordInput);
        
        document.body.appendChild(form);
        form.submit();
    } else if (newPassword !== null) {
        alert('Password minimal 6 karakter.');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
