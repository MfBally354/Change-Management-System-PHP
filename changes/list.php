<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

requireLogin();

$page_title = 'Change Requests';

// Filters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';
$assigned_filter = $_GET['assigned_to'] ?? '';

// Build query
$where_clauses = [];
$params = [];
$types = '';

if (canPerformAction('view_all_changes')) {
    // Manager & Admin bisa lihat semua
    $base_sql = "SELECT cr.*, 
                 u.full_name as requester_name,
                 a.full_name as assigned_name
                 FROM change_requests cr
                 JOIN users u ON cr.requester_id = u.id
                 LEFT JOIN users a ON cr.assigned_to = a.id";
} else {
    // Staff & Client hanya lihat punya sendiri
    $base_sql = "SELECT cr.*, 
                 u.full_name as requester_name,
                 a.full_name as assigned_name
                 FROM change_requests cr
                 JOIN users u ON cr.requester_id = u.id
                 LEFT JOIN users a ON cr.assigned_to = a.id";
    $where_clauses[] = "(cr.requester_id = ? OR cr.assigned_to = ?)";
    $params[] = $_SESSION['user_id'];
    $params[] = $_SESSION['user_id'];
    $types .= 'ii';
}

if (!empty($status_filter)) {
    $where_clauses[] = "cr.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($priority_filter)) {
    $where_clauses[] = "cr.priority = ?";
    $params[] = $priority_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_clauses[] = "(cr.change_number LIKE ? OR cr.title LIKE ? OR cr.description LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($assigned_filter === 'me') {
    $where_clauses[] = "cr.assigned_to = ?";
    $params[] = $_SESSION['user_id'];
    $types .= 'i';
}

// Combine query
$sql = $base_sql;
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY cr.created_at DESC";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

include '../includes/header.php';
?>

<h1>Change Requests</h1>

<!-- Filters -->
<div class="card">
    <form method="GET" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" 
                       placeholder="Change number, title, description..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">-- All Status --</option>
                    <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="submitted" <?php echo $status_filter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="reviewing" <?php echo $status_filter === 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="rolled_back" <?php echo $status_filter === 'rolled_back' ? 'selected' : ''; ?>>Rolled Back</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="">-- All Priority --</option>
                    <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $priority_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            
            <?php if (hasRole(['staff'])): ?>
            <div class="form-group">
                <label for="assigned_to">Assigned</label>
                <select id="assigned_to" name="assigned_to">
                    <option value="">-- All --</option>
                    <option value="me" <?php echo $assigned_filter === 'me' ? 'selected' : ''; ?>>Assigned to Me</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="list.php" class="btn btn-secondary">Reset</a>
            <a href="create.php" class="btn btn-success">➕ New Change Request</a>
        </div>
    </form>
</div>

<!-- Results -->
<div class="card">
    <div class="card-header">
        <h2>Results (<?php echo $result->num_rows; ?> found)</h2>
    </div>
    
    <?php if ($result->num_rows > 0): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Change Number</th>
                <th>Title</th>
                <th>Requester</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($row['change_number']); ?></strong>
                </td>
                <td>
                    <?php echo htmlspecialchars($row['title']); ?>
                    <?php if ($row['assigned_to'] && $row['assigned_to'] == $_SESSION['user_id']): ?>
                    <span class="badge badge-info" style="margin-left: 5px;">Assigned to you</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['requester_name']); ?></td>
                <td>
                    <span style="text-transform: capitalize;">
                        <?php echo htmlspecialchars($row['category']); ?>
                    </span>
                </td>
                <td><?php echo getPriorityBadge($row['priority']); ?></td>
                <td><?php echo getStatusBadge($row['status']); ?></td>
                <td><?php echo formatTanggal($row['created_at']); ?></td>
                <td>
                    <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85em;">
                        View
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>😔 Tidak ada change request yang ditemukan.</p>
        <a href="create.php" class="btn btn-primary">Create New Change Request</a>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
