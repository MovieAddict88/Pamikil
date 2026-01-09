<?php
require_once '../config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE test_drives SET status = '$status' WHERE id = $id");
}

$test_drives = mysqli_query($conn, "SELECT t.*, c.make, c.model, c.year FROM test_drives t JOIN cars c ON t.car_id = c.id ORDER BY t.preferred_date DESC, t.preferred_time DESC");

$page_title = 'Test Drives';
include 'includes/header.php';
?>

<div class="content-area">
    <div class="card">
        <div class="table-header">
            <h3>Test Drive Appointments</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Customer</th><th>Email</th><th>Phone</th><th>Car</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($test_drives) > 0): ?>
                        <?php while ($td = mysqli_fetch_assoc($test_drives)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($td['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($td['customer_email']); ?></td>
                                <td><?php echo htmlspecialchars($td['customer_phone']); ?></td>
                                <td><?php echo htmlspecialchars($td['year'].' '.$td['make'].' '.$td['model']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($td['preferred_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($td['preferred_time'])); ?></td>
                                <td><span class="badge badge-<?php echo $td['status']==='Scheduled'?'warning':($td['status']==='Completed'?'success':'danger'); ?>"><?php echo $td['status']; ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo $td['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto;">
                                            <option value="Scheduled" <?php echo $td['status']==='Scheduled'?'selected':''; ?>>Scheduled</option>
                                            <option value="Completed" <?php echo $td['status']==='Completed'?'selected':''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $td['status']==='Cancelled'?'selected':''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-key"></i><p>No test drives scheduled</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
