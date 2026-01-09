<?php
require_once '../config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $car_id = (int)$_POST['car_id'];
    $type = mysqli_real_escape_string($conn, $_POST['maintenance_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $cost = floatval($_POST['cost']);
    $date = mysqli_real_escape_string($conn, $_POST['maintenance_date']);
    $next_date = mysqli_real_escape_string($conn, $_POST['next_maintenance_date']);
    $provider = mysqli_real_escape_string($conn, $_POST['service_provider']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    mysqli_query($conn, "INSERT INTO maintenance (car_id, maintenance_type, description, cost, maintenance_date, next_maintenance_date, service_provider, status) 
        VALUES ($car_id, '$type', '$description', $cost, '$date', '$next_date', '$provider', '$status')");
}

$records = mysqli_query($conn, "SELECT m.*, c.make, c.model, c.year FROM maintenance m JOIN cars c ON m.car_id = c.id ORDER BY m.maintenance_date DESC");
$cars = mysqli_query($conn, "SELECT id, make, model, year FROM cars ORDER BY make, model");

$page_title = 'Maintenance Records';
include 'includes/header.php';
?>

<div class="content-area">
    <div class="card">
        <div class="table-header">
            <h3>Maintenance Records</h3>
            <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Record</button>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Car</th><th>Type</th><th>Cost</th><th>Date</th><th>Provider</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($records)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['year'].' '.$r['make'].' '.$r['model']); ?></td>
                            <td><?php echo htmlspecialchars($r['maintenance_type']); ?></td>
                            <td><?php echo formatCurrency($r['cost']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($r['maintenance_date'])); ?></td>
                            <td><?php echo htmlspecialchars($r['service_provider']); ?></td>
                            <td><span class="badge badge-<?php echo $r['status']==='Completed'?'success':'warning'; ?>"><?php echo $r['status']; ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Maintenance Record</h3>
            <button class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label><i class="fas fa-car"></i> Car</label>
                    <select name="car_id" required>
                        <?php mysqli_data_seek($cars, 0); while ($c = mysqli_fetch_assoc($cars)): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['year'].' '.$c['make'].' '.$c['model']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Maintenance Type</label>
                        <input type="text" name="maintenance_type" required>
                    </div>
                    <div class="form-group">
                        <label>Cost</label>
                        <input type="number" name="cost" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="maintenance_date" required>
                    </div>
                    <div class="form-group">
                        <label>Next Maintenance</label>
                        <input type="date" name="next_maintenance_date">
                    </div>
                    <div class="form-group">
                        <label>Service Provider</label>
                        <input type="text" name="service_provider">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Completed">Completed</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() { document.querySelector('#modal form').reset(); document.getElementById('modal').classList.add('active'); }
function closeModal() { document.getElementById('modal').classList.remove('active'); }
document.getElementById('modal').addEventListener('click', e => { if (e.target === document.getElementById('modal')) closeModal(); });
</script>

<?php include 'includes/footer.php'; ?>
