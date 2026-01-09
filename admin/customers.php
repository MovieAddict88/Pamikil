<?php
require_once '../config.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $id = $_POST['id'] ?? null;
            $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
            $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $address = mysqli_real_escape_string($conn, $_POST['address']);
            $city = mysqli_real_escape_string($conn, $_POST['city']);
            $province = mysqli_real_escape_string($conn, $_POST['province']);
            $postal_code = mysqli_real_escape_string($conn, $_POST['postal_code']);
            $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
            $license_number = mysqli_real_escape_string($conn, $_POST['license_number']);
            
            if ($id) {
                $query = "UPDATE customers SET 
                    first_name = '$first_name',
                    last_name = '$last_name',
                    email = '$email',
                    phone = '$phone',
                    address = '$address',
                    city = '$city',
                    province = '$province',
                    postal_code = '$postal_code',
                    date_of_birth = '$date_of_birth',
                    license_number = '$license_number'
                    WHERE id = $id";
                
                if (mysqli_query($conn, $query)) {
                    $success = 'Customer updated successfully!';
                } else {
                    $error = 'Error updating customer: ' . mysqli_error($conn);
                }
            } else {
                $query = "INSERT INTO customers (first_name, last_name, email, phone, address, city, province, postal_code, date_of_birth, license_number) 
                    VALUES ('$first_name', '$last_name', '$email', '$phone', '$address', '$city', '$province', '$postal_code', '$date_of_birth', '$license_number')";
                
                if (mysqli_query($conn, $query)) {
                    $success = 'Customer added successfully!';
                } else {
                    $error = 'Error adding customer: ' . mysqli_error($conn);
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM sales WHERE customer_id = $id");
            $has_sales = mysqli_fetch_assoc($check)['count'] > 0;
            
            if ($has_sales) {
                $error = 'Cannot delete customer with existing sales records!';
            } else {
                $query = "DELETE FROM customers WHERE id = $id";
                if (mysqli_query($conn, $query)) {
                    $success = 'Customer deleted successfully!';
                } else {
                    $error = 'Error deleting customer: ' . mysqli_error($conn);
                }
            }
        }
    }
}

$search = $_GET['search'] ?? '';
$where_clause = '';
if ($search) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where_clause = "WHERE first_name LIKE '%$search_escaped%' OR last_name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%' OR phone LIKE '%$search_escaped%'";
}

$customers = mysqli_query($conn, "SELECT * FROM customers $where_clause ORDER BY created_at DESC");

$page_title = 'Customers Management';
include 'includes/header.php';
?>

<div class="content-area">
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="table-header">
            <h3>All Customers</h3>
            <button onclick="openModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Customer
            </button>
        </div>
        
        <div style="padding: 0 1.5rem;">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                <button onclick="applySearch()" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City, Province</th>
                        <th>License Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($customers) > 0): ?>
                        <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td><?php echo htmlspecialchars($customer['city'] . ', ' . $customer['province']); ?></td>
                                <td><?php echo htmlspecialchars($customer['license_number']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='editCustomer(<?php echo json_encode($customer); ?>)' class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteCustomer(<?php echo $customer['id']; ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><div class="empty-state"><i class="fas fa-users"></i><p>No customers found</p></div></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Customer</h3>
            <button class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="customerId">
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="first_name"><i class="fas fa-user"></i> First Name</label>
                        <input type="text" name="first_name" id="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name"><i class="fas fa-user"></i> Last Name</label>
                        <input type="text" name="last_name" id="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                        <input type="tel" name="phone" id="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="city"><i class="fas fa-city"></i> City</label>
                        <input type="text" name="city" id="city" required>
                    </div>
                    <div class="form-group">
                        <label for="province"><i class="fas fa-map"></i> Province</label>
                        <input type="text" name="province" id="province" required>
                    </div>
                    <div class="form-group">
                        <label for="postal_code"><i class="fas fa-mail-bulk"></i> Postal Code</label>
                        <input type="text" name="postal_code" id="postal_code" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth"><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label for="license_number"><i class="fas fa-id-card"></i> License Number</label>
                        <input type="text" name="license_number" id="license_number">
                    </div>
                </div>
                <div class="form-group">
                    <label for="address"><i class="fas fa-home"></i> Address</label>
                    <textarea name="address" id="address" rows="2" required></textarea>
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
function openModal() {
    document.getElementById('modalTitle').textContent = 'Add Customer';
    document.getElementById('formAction').value = 'add';
    document.querySelector('#modal form').reset();
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function editCustomer(customer) {
    document.getElementById('modalTitle').textContent = 'Edit Customer';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('customerId').value = customer.id;
    document.getElementById('first_name').value = customer.first_name;
    document.getElementById('last_name').value = customer.last_name;
    document.getElementById('email').value = customer.email;
    document.getElementById('phone').value = customer.phone;
    document.getElementById('address').value = customer.address;
    document.getElementById('city').value = customer.city;
    document.getElementById('province').value = customer.province;
    document.getElementById('postal_code').value = customer.postal_code;
    document.getElementById('date_of_birth').value = customer.date_of_birth;
    document.getElementById('license_number').value = customer.license_number;
    document.getElementById('modal').classList.add('active');
}

function deleteCustomer(id) {
    if (confirm('Are you sure you want to delete this customer?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function applySearch() {
    const search = document.getElementById('searchInput').value;
    window.location.href = 'customers.php' + (search ? '?search=' + encodeURIComponent(search) : '');
}

document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include 'includes/footer.php'; ?>
