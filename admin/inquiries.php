<?php
require_once '../config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE inquiries SET status = '$status' WHERE id = $id");
}

$inquiries = mysqli_query($conn, "SELECT i.*, c.make, c.model FROM inquiries i LEFT JOIN cars c ON i.car_id = c.id ORDER BY i.created_at DESC");

$page_title = 'Inquiries';
include 'includes/header.php';
?>

<div class="content-area">
    <div class="card">
        <div class="table-header">
            <h3>Customer Inquiries</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Car</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($inquiries) > 0): ?>
                        <?php while ($inq = mysqli_fetch_assoc($inquiries)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inq['name']); ?></td>
                                <td><?php echo htmlspecialchars($inq['email']); ?></td>
                                <td><?php echo htmlspecialchars($inq['phone']); ?></td>
                                <td><?php echo $inq['make'] ? htmlspecialchars($inq['make'].' '.$inq['model']) : 'General'; ?></td>
                                <td><?php echo htmlspecialchars(substr($inq['message'], 0, 50)).'...'; ?></td>
                                <td><span class="badge badge-<?php echo $inq['status']==='New'?'danger':($inq['status']==='Contacted'?'warning':'success'); ?>"><?php echo $inq['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo $inq['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto;">
                                            <option value="New" <?php echo $inq['status']==='New'?'selected':''; ?>>New</option>
                                            <option value="Contacted" <?php echo $inq['status']==='Contacted'?'selected':''; ?>>Contacted</option>
                                            <option value="Closed" <?php echo $inq['status']==='Closed'?'selected':''; ?>>Closed</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-envelope"></i><p>No inquiries</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
