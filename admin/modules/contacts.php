<?php
/**
 * CONTACT MESSAGES MODULE
 * ============================================
 */

$status_filter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    if (Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $contact_id = (int)$_POST['contact_id'];
        $new_status = $_POST['new_status'];
        
        $update = $conn->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $contact_id);
        if ($update->execute()) {
            $success = 'Status updated successfully.';
        }
        $update->close();
    }
}

// Get total
$count_sql = "SELECT COUNT(*) as total FROM contacts $where";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);
$stmt->close();

// Get contacts
$sql = "SELECT * FROM contacts $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$contacts = $stmt->get_result();
$stmt->close();

$csrf_token = Security::generateCSRFToken();
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Contact Messages</h3>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form mb-3">
            <input type="hidden" name="module" value="contacts">
            <div class="filter-row">
                <div class="filter-group">
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Messages</option>
                        <option value="unread" <?php echo $status_filter == 'unread' ? 'selected' : ''; ?>>Unread</option>
                        <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $status_filter == 'replied' ? 'selected' : ''; ?>>Replied</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=contacts" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="contacts-list">
    <?php while ($contact = $contacts->fetch_assoc()): ?>
    <div class="contact-card <?php echo $contact['status']; ?>">
        <div class="contact-header">
            <div class="contact-info">
                <h4><?php echo htmlspecialchars($contact['name']); ?></h4>
                <span class="contact-email"><?php echo htmlspecialchars($contact['email']); ?></span>
                <?php if ($contact['phone']): ?>
                    <span class="contact-phone"><?php echo htmlspecialchars($contact['phone']); ?></span>
                <?php endif; ?>
            </div>
            <div class="contact-meta">
                <span class="contact-date"><?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?></span>
                <?php echo getStatusBadge($contact['status'], 'contact'); ?>
            </div>
        </div>
        <div class="contact-subject">
            <strong>Subject:</strong> <?php echo htmlspecialchars($contact['subject']); ?>
        </div>
        <div class="contact-message">
            <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
        </div>
        <div class="contact-footer">
            <form method="POST" class="status-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="unread" <?php echo $contact['status'] == 'unread' ? 'selected' : ''; ?>>Unread</option>
                    <option value="read" <?php echo $contact['status'] == 'read' ? 'selected' : ''; ?>>Read</option>
                    <option value="replied" <?php echo $contact['status'] == 'replied' ? 'selected' : ''; ?>>Replied</option>
                </select>
                <input type="hidden" name="update_status" value="1">
            </form>
            <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-reply"></i> Reply
            </a>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?module=contacts&status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" 
           class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<style>
.mb-3 { margin-bottom: 1rem; }
.contacts-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.contact-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
}
.contact-card.unread {
    border-left: 4px solid #d97706;
    background: #fffbeb;
}
.contact-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}
.contact-info h4 {
    margin-bottom: 0.25rem;
}
.contact-email, .contact-phone {
    font-size: 0.85rem;
    color: #64748b;
    margin-right: 1rem;
}
.contact-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.contact-date {
    font-size: 0.8rem;
    color: #64748b;
}
.contact-subject {
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}
.contact-message {
    color: #334155;
    line-height: 1.6;
    margin-bottom: 1rem;
}
.contact-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.status-form {
    display: flex;
    gap: 0.5rem;
}
.form-select-sm {
    width: auto;
    padding: 0.25rem 2rem 0.25rem 0.75rem;
    font-size: 0.85rem;
}
</style>