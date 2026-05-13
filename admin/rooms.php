<?php
require_once __DIR__ . '/../includes/require_admin.php';

$rooms = [];
$error = '';
$success = '';

// Handle create/update room
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security validation failed. Please try again.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add') {
        $room_number = trim($_POST['room_number'] ?? '');
        $room_type = trim($_POST['room_type'] ?? '');
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
        $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
        $status = $_POST['status'] ?? 'available';
        
        if (empty($room_number) || empty($room_type) || $price <= 0 || $capacity <= 0) {
            $error = 'Please fill in all required fields with valid values (price and capacity must be positive).';
        } elseif (!in_array($status, ['available', 'occupied', 'cleaning'])) {
            $error = 'Invalid status value.';
        } else {
            try {
                // Check for duplicate room number
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ?");
                $check_stmt->execute([$room_number]);
                if ($check_stmt->fetchColumn() > 0) {
                    $error = 'A room with this number already exists.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO rooms (room_number, room_type, price, capacity, status)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$room_number, htmlspecialchars($room_type), $price, $capacity, $status]);
                    set_flash('success', 'Room added successfully!');
                    redirect_to('admin/rooms.php');
                }
            } catch (Exception $e) {
                $error = 'Error adding room. Please try again.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
        $room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
        $room_type = trim($_POST['room_type'] ?? '');
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
        $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
        $status = $_POST['status'] ?? 'available';
        
        if ($room_id <= 0) {
            $error = 'Invalid room ID.';
        } elseif (empty($room_type) || $price <= 0 || $capacity <= 0) {
            $error = 'Please fill in all required fields with valid values (price and capacity must be positive).';
        } elseif (!in_array($status, ['available', 'occupied', 'cleaning'])) {
            $error = 'Invalid status value.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE rooms
                    SET room_type = ?, price = ?, capacity = ?, status = ?
                    WHERE room_id = ?
                ");
                $stmt->execute([htmlspecialchars($room_type), $price, $capacity, $status, $room_id]);
                set_flash('success', 'Room updated successfully!');
                redirect_to('admin/rooms.php');
            } catch (Exception $e) {
                $error = 'Error updating room.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
        if ($room_id <= 0) {
            $error = 'Invalid room ID.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = ?");
                $stmt->execute([$room_id]);
                set_flash('success', 'Room deleted successfully!');
                redirect_to('admin/rooms.php');
            } catch (Exception $e) {
                $error = 'Error deleting room.';
            }
        }
    }
}

// Get all rooms
try {
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number");
    $rooms = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error loading rooms.';
}

$room_types = ['Single', 'Double', 'Deluxe', 'Suite'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Manage Rooms</h2>
            <p class="section-subtitle">Create, edit, and delete rooms</p>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Add Room Form -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3>Add New Room</h3>
                <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="text" name="room_number" placeholder="Room Number" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="room_type" required>
                            <option value="">Select Type</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="number" name="price" placeholder="Price" step="0.01" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="number" name="capacity" placeholder="Capacity" min="1" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="status">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="cleaning">Cleaning</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="height: 44px; align-self: flex-end;">Add Room</button>
                </form>
            </div>

            <!-- Rooms Table -->
            <div class="card">
                <h3>All Rooms</h3>
                <div style="overflow-x: auto; margin-top: 1rem;">
                    <table>
                        <thead>
                            <tr>
                                <th>Room #</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                    <td>$<?php echo number_format($room['price'], 2); ?></td>
                                    <td><?php echo $room['capacity']; ?> guests</td>
                                    <td><span class="badge badge-<?php echo $room['status']; ?>"><?php echo ucfirst($room['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">Edit</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this room?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Room</h3>
                <button class="modal-close" onclick="hideModal('editModal')">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="room_id" id="edit_room_id">
                
                <div class="form-group">
                    <label>Room Type</label>
                    <select name="room_type" id="edit_room_type" required>
                        <option value="">Select Type</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" id="edit_capacity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="cleaning">Cleaning</option>
                    </select>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Room</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editRoom(room) {
            document.getElementById('edit_room_id').value = room.room_id;
            document.getElementById('edit_room_type').value = room.room_type;
            document.getElementById('edit_price').value = room.price;
            document.getElementById('edit_capacity').value = room.capacity;
            document.getElementById('edit_status').value = room.status;
            showModal('editModal');
        }
    </script>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
