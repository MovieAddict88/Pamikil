<?php
require_once '../config.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $id = $_POST['id'] ?? null;
            $make = mysqli_real_escape_string($conn, $_POST['make']);
            $model = mysqli_real_escape_string($conn, $_POST['model']);
            $year = (int)$_POST['year'];
            $color = mysqli_real_escape_string($conn, $_POST['color']);
            $vin = mysqli_real_escape_string($conn, $_POST['vin']);
            $license_plate = mysqli_real_escape_string($conn, $_POST['license_plate']);
            $price = floatval($_POST['price']);
            $mileage = (int)$_POST['mileage'];
            $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
            $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
            $body_type = mysqli_real_escape_string($conn, $_POST['body_type']);
            $doors = (int)$_POST['doors'];
            $seats = (int)$_POST['seats'];
            $engine_size = mysqli_real_escape_string($conn, $_POST['engine_size']);
            $horsepower = (int)$_POST['horsepower'];
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $features = mysqli_real_escape_string($conn, $_POST['features']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            $image = mysqli_real_escape_string($conn, $_POST['image']);
            
            if ($id) {
                $query = "UPDATE cars SET 
                    make = '$make',
                    model = '$model',
                    year = $year,
                    color = '$color',
                    vin = '$vin',
                    license_plate = '$license_plate',
                    price = $price,
                    mileage = $mileage,
                    fuel_type = '$fuel_type',
                    transmission = '$transmission',
                    body_type = '$body_type',
                    doors = $doors,
                    seats = $seats,
                    engine_size = '$engine_size',
                    horsepower = $horsepower,
                    description = '$description',
                    features = '$features',
                    status = '$status',
                    image = '$image'
                    WHERE id = $id";
                
                if (mysqli_query($conn, $query)) {
                    $success = 'Car updated successfully!';
                } else {
                    $error = 'Error updating car: ' . mysqli_error($conn);
                }
            } else {
                $query = "INSERT INTO cars (make, model, year, color, vin, license_plate, price, mileage, 
                    fuel_type, transmission, body_type, doors, seats, engine_size, horsepower, 
                    description, features, status, image) 
                    VALUES ('$make', '$model', $year, '$color', '$vin', '$license_plate', $price, $mileage, 
                    '$fuel_type', '$transmission', '$body_type', $doors, $seats, '$engine_size', $horsepower, 
                    '$description', '$features', '$status', '$image')";
                
                if (mysqli_query($conn, $query)) {
                    $success = 'Car added successfully!';
                } else {
                    $error = 'Error adding car: ' . mysqli_error($conn);
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $query = "DELETE FROM cars WHERE id = $id";
            
            if (mysqli_query($conn, $query)) {
                $success = 'Car deleted successfully!';
            } else {
                $error = 'Error deleting car: ' . mysqli_error($conn);
            }
        }
    }
}

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$body_type_filter = $_GET['body_type'] ?? '';

$where = [];
if ($search) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where[] = "(make LIKE '%$search_escaped%' OR model LIKE '%$search_escaped%' OR vin LIKE '%$search_escaped%' OR license_plate LIKE '%$search_escaped%')";
}
if ($status_filter) {
    $where[] = "status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
if ($body_type_filter) {
    $where[] = "body_type = '" . mysqli_real_escape_string($conn, $body_type_filter) . "'";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$cars = mysqli_query($conn, "SELECT * FROM cars $where_clause ORDER BY created_at DESC");

$page_title = 'Cars Management';
include 'includes/header.php';
?>

<div class="content-area">
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="table-header">
            <h3>All Cars</h3>
            <button onclick="openCarModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add New Car
            </button>
        </div>
        
        <div style="padding: 0 1.5rem;">
            <div class="filters">
                <div class="filter-group">
                    <input type="text" id="searchInput" placeholder="Search cars..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                </div>
                <div class="filter-group">
                    <select id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="Available" <?php echo $status_filter === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Sold" <?php echo $status_filter === 'Sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="Reserved" <?php echo $status_filter === 'Reserved' ? 'selected' : ''; ?>>Reserved</option>
                        <option value="Maintenance" <?php echo $status_filter === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="bodyTypeFilter" class="form-control">
                        <option value="">All Body Types</option>
                        <option value="Sedan" <?php echo $body_type_filter === 'Sedan' ? 'selected' : ''; ?>>Sedan</option>
                        <option value="SUV" <?php echo $body_type_filter === 'SUV' ? 'selected' : ''; ?>>SUV</option>
                        <option value="Truck" <?php echo $body_type_filter === 'Truck' ? 'selected' : ''; ?>>Truck</option>
                        <option value="Van" <?php echo $body_type_filter === 'Van' ? 'selected' : ''; ?>>Van</option>
                        <option value="Coupe" <?php echo $body_type_filter === 'Coupe' ? 'selected' : ''; ?>>Coupe</option>
                        <option value="Hatchback" <?php echo $body_type_filter === 'Hatchback' ? 'selected' : ''; ?>>Hatchback</option>
                        <option value="Convertible" <?php echo $body_type_filter === 'Convertible' ? 'selected' : ''; ?>>Convertible</option>
                    </select>
                </div>
                <button onclick="applyFilters()" class="btn btn-primary">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>
                <button onclick="clearFilters()" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Car Details</th>
                        <th>VIN</th>
                        <th>License</th>
                        <th>Price</th>
                        <th>Mileage</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($cars) > 0): ?>
                        <?php while ($car = mysqli_fetch_assoc($cars)): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($car['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>" 
                                         style="width: 80px; height: 60px; object-fit: cover; border-radius: 0.5rem;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($car['year'] . ' ' . $car['make'] . ' ' . $car['model']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($car['color'] . ' - ' . $car['body_type']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($car['vin']); ?></td>
                                <td><?php echo htmlspecialchars($car['license_plate']); ?></td>
                                <td><strong><?php echo formatCurrency($car['price']); ?></strong></td>
                                <td><?php echo number_format($car['mileage']); ?> km</td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $car['status'] === 'Available' ? 'success' : 
                                            ($car['status'] === 'Sold' ? 'danger' : 
                                            ($car['status'] === 'Reserved' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo $car['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='editCar(<?php echo json_encode($car); ?>)' class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteCar(<?php echo $car['id']; ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-car"></i>
                                    <p>No cars found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="carModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Car</h3>
            <button class="close-modal" onclick="closeCarModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="carId">
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="make"><i class="fas fa-car"></i> Make</label>
                        <input type="text" name="make" id="make" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="model"><i class="fas fa-car"></i> Model</label>
                        <input type="text" name="model" id="model" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="year"><i class="fas fa-calendar"></i> Year</label>
                        <input type="number" name="year" id="year" min="1900" max="2099" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="color"><i class="fas fa-palette"></i> Color</label>
                        <input type="text" name="color" id="color" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="vin"><i class="fas fa-hashtag"></i> VIN</label>
                        <input type="text" name="vin" id="vin" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="license_plate"><i class="fas fa-id-card"></i> License Plate</label>
                        <input type="text" name="license_plate" id="license_plate" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price"><i class="fas fa-peso-sign"></i> Price (PHP)</label>
                        <input type="number" name="price" id="price" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mileage"><i class="fas fa-tachometer-alt"></i> Mileage (km)</label>
                        <input type="number" name="mileage" id="mileage" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuel_type"><i class="fas fa-gas-pump"></i> Fuel Type</label>
                        <select name="fuel_type" id="fuel_type" required>
                            <option value="Gasoline">Gasoline</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Electric">Electric</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="transmission"><i class="fas fa-cogs"></i> Transmission</label>
                        <select name="transmission" id="transmission" required>
                            <option value="Manual">Manual</option>
                            <option value="Automatic">Automatic</option>
                            <option value="CVT">CVT</option>
                            <option value="Semi-Automatic">Semi-Automatic</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="body_type"><i class="fas fa-car-side"></i> Body Type</label>
                        <select name="body_type" id="body_type" required>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="Truck">Truck</option>
                            <option value="Van">Van</option>
                            <option value="Coupe">Coupe</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="Convertible">Convertible</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="doors"><i class="fas fa-door-open"></i> Doors</label>
                        <input type="number" name="doors" id="doors" min="2" max="5" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="seats"><i class="fas fa-chair"></i> Seats</label>
                        <input type="number" name="seats" id="seats" min="2" max="9" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="engine_size"><i class="fas fa-engine"></i> Engine Size</label>
                        <input type="text" name="engine_size" id="engine_size">
                    </div>
                    
                    <div class="form-group">
                        <label for="horsepower"><i class="fas fa-horse"></i> Horsepower</label>
                        <input type="number" name="horsepower" id="horsepower">
                    </div>
                    
                    <div class="form-group">
                        <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                        <select name="status" id="status" required>
                            <option value="Available">Available</option>
                            <option value="Sold">Sold</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image"><i class="fas fa-image"></i> Image URL</label>
                    <input type="url" name="image" id="image">
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" id="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="features"><i class="fas fa-list"></i> Features (comma separated)</label>
                    <textarea name="features" id="features" rows="2"></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeCarModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Car
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCarModal() {
    document.getElementById('modalTitle').textContent = 'Add New Car';
    document.getElementById('formAction').value = 'add';
    document.getElementById('carId').value = '';
    document.querySelector('#carModal form').reset();
    document.getElementById('carModal').classList.add('active');
}

function closeCarModal() {
    document.getElementById('carModal').classList.remove('active');
}

function editCar(car) {
    document.getElementById('modalTitle').textContent = 'Edit Car';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('carId').value = car.id;
    document.getElementById('make').value = car.make;
    document.getElementById('model').value = car.model;
    document.getElementById('year').value = car.year;
    document.getElementById('color').value = car.color;
    document.getElementById('vin').value = car.vin;
    document.getElementById('license_plate').value = car.license_plate;
    document.getElementById('price').value = car.price;
    document.getElementById('mileage').value = car.mileage;
    document.getElementById('fuel_type').value = car.fuel_type;
    document.getElementById('transmission').value = car.transmission;
    document.getElementById('body_type').value = car.body_type;
    document.getElementById('doors').value = car.doors;
    document.getElementById('seats').value = car.seats;
    document.getElementById('engine_size').value = car.engine_size;
    document.getElementById('horsepower').value = car.horsepower;
    document.getElementById('description').value = car.description;
    document.getElementById('features').value = car.features;
    document.getElementById('status').value = car.status;
    document.getElementById('image').value = car.image;
    document.getElementById('carModal').classList.add('active');
}

function deleteCar(id) {
    if (confirm('Are you sure you want to delete this car?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const bodyType = document.getElementById('bodyTypeFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (bodyType) params.append('body_type', bodyType);
    
    window.location.href = 'cars.php' + (params.toString() ? '?' + params.toString() : '');
}

function clearFilters() {
    window.location.href = 'cars.php';
}

document.getElementById('carModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCarModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
