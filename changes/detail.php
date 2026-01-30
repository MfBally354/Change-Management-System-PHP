<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

requireLogin();

$change_id = (int)($_GET['id'] ?? 0);

if ($change_id === 0) {
    $_SESSION['error'] = 'Change request tidak ditemukan.';
    header('Location: list.php');
    exit;
}

// Get change request details
$sql = "SELECT cr.*, 
        u.full_name as requester_name, u.email as requester_email,
        a.full_name as assigned_name, a.email as assigned_email,
        m.full_name as approver_name
        FROM change_requests cr
        JOIN users u ON cr.requester_id = u.id
        LEFT JOIN users a ON cr.assigned_to = a.id
        LEFT JOIN users m ON cr.approved_by = m.id
        WHERE cr.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $change_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Change request tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$change = $result->fetch_assoc();
$page_title = $change['change_number'];

// Check permission
if (!canPerformAction('view_all_changes')) {
    $user_id = $_SESSION['user_id'];
    if ($change['requester_id'] != $user_id && $change['assigned_to'] != $user_id) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke change request ini.';
        header('Location: list.php');
        exit;
    }
}

// Get approval history
$sql_approvals = "SELECT ca.*, u.full_name as approver_name 
                  FROM change_approvals ca
                  JOIN users u ON ca.approver_id = u.id
                  WHERE ca.change_id = ?
                  ORDER BY ca.created_at DESC";
$stmt = $conn->prepare($sql_approvals);
$stmt->bind_param("i", $change_id);
$stmt->execute();
$approvals = $stmt->get_result();

// Get comments
$sql_comments = "SELECT cc.*, u.full_name as commenter_name, u.role as commenter_role
                 FROM change_comments cc
                 JOIN users u ON cc.user_id = u.id
                 WHERE cc.change_id = ?
                 ORDER BY cc.created_at ASC";
$stmt = $conn->prepare($sql_comments);
$stmt->bind_param("i", $change_id);
$stmt->execute();
$comments = $stmt->get_result();

// Get audit logs for this change
$sql_logs = "SELECT al.*, u.full_name as user_name
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.entity_type = 'change_request' AND al.entity_id = ?
             ORDER BY al.created_at DESC
             LIMIT 20";
$stmt = $conn->prepare($sql_logs);
$stmt->bind_param("i", $change_id);
$stmt->execute();
$logs = $stmt->get_result();

include '../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h1><?php echo htmlspecialchars($change['change_number']); ?></h1>
        <p style="color: #888; margin: 0;">
            Created by <?php echo htmlspecialchars($change['requester_name']); ?> 
            on <?php echo formatTanggal($change['created_at']); ?>
        </p>
    </div>
    <div>
        <?php echo getStatusBadge($change['status']); ?>
        <?php echo getPriorityBadge($change['priority']); ?>
    </div>
</div>

<!-- Action Buttons -->
<div class="card">
    <div class="btn-group">
        <a href="list.php" class="btn btn-secondary">← Back to List</a>
        
        <?php if (canPerformAction('edit_change', $change)): ?>
        <a href="edit.php?id=<?php echo $change_id; ?>" class="btn btn-warning">✏️ Edit</a>
        <?php endif; ?>
        
        <?php if (canPerformAction('approve_change') && $change['status'] === 'submitted'): ?>
        <a href="approve.php?id=<?php echo $change_id; ?>" class="btn btn-success">✅ Review & Approve</a>
        <?php endif; ?>
        
        <?php if (canPerformAction('assign_change') && $change['status'] === 'approved'): ?>
        <a href="assign.php?id=<?php echo $change_id; ?>" class="btn btn-info">👤 Assign to Staff</a>
        <?php endif; ?>
        
        <?php if (canPerformAction('execute_change', $change) && in_array($change['status'], ['approved', 'scheduled'])): ?>
        <a href="execute.php?id=<?php echo $change_id; ?>" class="btn btn-primary">🚀 Start Execution</a>
        <?php endif; ?>
        
        <?php if (canPerformAction('execute_change', $change) && $change['status'] === 'in_progress'): ?>
        <a href="complete.php?id=<?php echo $change_id; ?>" class="btn btn-success">✔️ Mark Complete</a>
        <?php endif; ?>
    </div>
</div>

<!-- Basic Information -->
<div class="card">
    <div class="card-header">
        <h2>Basic Information</h2>
    </div>
    
    <div class="detail-grid">
        <div class="detail-item">
            <label>Change Number</label>
            <div><strong><?php echo htmlspecialchars($change['change_number']); ?></strong></div>
        </div>
        
        <div class="detail-item">
            <label>Category</label>
            <div style="text-transform: capitalize;"><?php echo htmlspecialchars($change['category']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Priority</label>
            <div><?php echo getPriorityBadge($change['priority']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Impact Level</label>
            <div style="text-transform: capitalize;"><?php echo htmlspecialchars($change['impact']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Risk Level</label>
            <div style="text-transform: capitalize;"><?php echo htmlspecialchars($change['risk_level']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Status</label>
            <div><?php echo getStatusBadge($change['status']); ?></div>
        </div>
    </div>
    
    <div style="margin-top: 20px;">
        <label style="font-weight: 600; color: #555;">Title</label>
        <div style="margin-top: 5px; font-size: 1.1em;">
            <?php echo htmlspecialchars($change['title']); ?>
        </div>
    </div>
    
    <div style="margin-top: 20px;">
        <label style="font-weight: 600; color: #555;">Description</label>
        <div style="margin-top: 5px; white-space: pre-wrap; line-height: 1.6;">
            <?php echo htmlspecialchars($change['description']); ?>
        </div>
    </div>
</div>

<!-- People Involved -->
<div class="card">
    <div class="card-header">
        <h2>People Involved</h2>
    </div>
    
    <div class="detail-grid">
        <div class="detail-item">
            <label>Requester</label>
            <div>
                <strong><?php echo htmlspecialchars($change['requester_name']); ?></strong><br>
                <small><?php echo htmlspecialchars($change['requester_email']); ?></small>
            </div>
        </div>
        
        <div class="detail-item">
            <label>Assigned To</label>
            <div>
                <?php if ($change['assigned_name']): ?>
                    <strong><?php echo htmlspecialchars($change['assigned_name']); ?></strong><br>
                    <small><?php echo htmlspecialchars($change['assigned_email']); ?></small>
                <?php else: ?>
                    <em style="color: #999;">Not assigned yet</em>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="detail-item">
            <label>Approved By</label>
            <div>
                <?php if ($change['approver_name']): ?>
                    <strong><?php echo htmlspecialchars($change['approver_name']); ?></strong>
                <?php else: ?>
                    <em style="color: #999;">Pending approval</em>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Technical Details -->
<div class="card">
    <div class="card-header">
        <h2>Technical Details</h2>
    </div>
    
    <?php if ($change['systems_affected']): ?>
    <div class="detail-section">
        <h3>Systems Affected</h3>
        <div style="white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <?php echo htmlspecialchars($change['systems_affected']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($change['implementation_plan']): ?>
    <div class="detail-section">
        <h3>Implementation Plan</h3>
        <div style="white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <?php echo htmlspecialchars($change['implementation_plan']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($change['rollback_plan']): ?>
    <div class="detail-section">
        <h3>Rollback Plan</h3>
        <div style="white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <?php echo htmlspecialchars($change['rollback_plan']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($change['test_plan']): ?>
    <div class="detail-section">
        <h3>Test Plan</h3>
        <div style="white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <?php echo htmlspecialchars($change['test_plan']); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Schedule -->
<div class="card">
    <div class="card-header">
        <h2>Schedule</h2>
    </div>
    
    <div class="detail-grid">
        <div class="detail-item">
            <label>Planned Start</label>
            <div><?php echo formatTanggal($change['planned_start']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Planned End</label>
            <div><?php echo formatTanggal($change['planned_end']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Actual Start</label>
            <div><?php echo formatTanggal($change['actual_start']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Actual End</label>
            <div><?php echo formatTanggal($change['actual_end']); ?></div>
        </div>
    </div>
    
    <?php if ($change['completion_notes']): ?>
    <div style="margin-top: 20px;">
        <label style="font-weight: 600; color: #555;">Completion Notes</label>
        <div style="margin-top: 5px; white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <?php echo htmlspecialchars($change['completion_notes']); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Approval History -->
<?php if ($approvals->num_rows > 0): ?>
<div class="card">
    <div class="card-header">
        <h2>Approval History</h2>
    </div>
    
    <div class="timeline">
        <?php while ($approval = $approvals->fetch_assoc()): ?>
        <div class="timeline-item">
            <div class="timeline-date"><?php echo formatTanggal($approval['created_at']); ?></div>
            <div class="timeline-content">
                <strong><?php echo htmlspecialchars($approval['approver_name']); ?></strong>
                <?php if ($approval['status'] === 'approved'): ?>
                    <span class="badge badge-success">Approved</span>
                <?php elseif ($approval['status'] === 'rejected'): ?>
                    <span class="badge badge-danger">Rejected</span>
                <?php else: ?>
                    <span class="badge badge-warning">Pending</span>
                <?php endif; ?>
                
                <?php if ($approval['comments']): ?>
                <div style="margin-top: 10px; color: #555;">
                    <?php echo nl2br(htmlspecialchars($approval['comments'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- Comments Section -->
<div class="card">
    <div class="card-header">
        <h2>Comments & Discussion</h2>
    </div>
    
    <?php if ($comments->num_rows > 0): ?>
    <div class="comments-section">
        <?php while ($comment = $comments->fetch_assoc()): ?>
        <div class="comment">
            <div class="comment-header">
                <div class="comment-author">
                    <?php echo htmlspecialchars($comment['commenter_name']); ?>
                    <span class="badge badge-secondary" style="margin-left: 5px; font-size: 0.75em;">
                        <?php echo getRoleName($comment['commenter_role']); ?>
                    </span>
                </div>
                <div class="comment-date"><?php echo formatTanggal($comment['created_at']); ?></div>
            </div>
            <div class="comment-body">
                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
    
    <!-- Add Comment Form -->
    <form method="POST" action="add_comment.php" style="margin-top: 20px;">
        <input type="hidden" name="change_id" value="<?php echo $change_id; ?>">
        <div class="form-group">
            <label for="comment">Add Comment</label>
            <textarea id="comment" name="comment" rows="3" required 
                      placeholder="Write your comment here..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">💬 Post Comment</button>
    </form>
</div>

<!-- Activity Log -->
<?php if (canPerformAction('view_audit_logs')): ?>
<div class="card">
    <div class="card-header">
        <h2>Activity Log</h2>
    </div>
    
    <?php if ($logs->num_rows > 0): ?>
    <div class="timeline">
        <?php while ($log = $logs->fetch_assoc()): ?>
        <div class="timeline-item">
            <div class="timeline-date"><?php echo formatTanggal($log['created_at']); ?></div>
            <div class="timeline-content">
                <strong><?php echo htmlspecialchars($log['user_name'] ?: 'System'); ?></strong>
                <span style="color: #888;">
                    - <?php echo htmlspecialchars(str_replace('_', ' ', $log['action'])); ?>
                </span>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <p style="color: #888; text-align: center; padding: 20px;">No activity logs yet.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
