<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db_connect.php';

// Get all rooms
$rooms = [];
try {
    $filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $filter_price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
    $filter_price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 1000;
    
    $query = "SELECT * FROM rooms WHERE 1=1";
    $params = [];
    
    if ($filter_type) {
        $query .= " AND room_type = ?";
        $params[] = $filter_type;
    }
    
    $query .= " AND price BETWEEN ? AND ?";
    $params[] = $filter_price_min;
    $params[] = $filter_price_max;
    
    $query .= " ORDER BY room_type, price";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error loading rooms: " . $e->getMessage();
}

$room_types = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT room_type FROM rooms ORDER BY room_type");
    $room_types = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Rooms - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Browse Rooms</h2>
            <p class="section-subtitle">Find the perfect room for your stay</p>

            <!-- Filters -->
            <div class="card">
                <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>Room Type</label>
                        <select name="type">
                            <option value="">All Types</option>
                            <?php foreach ($room_types as $rt): ?>
                                <option value="<?php echo htmlspecialchars($rt['room_type']); ?>" <?php echo $filter_type === $rt['room_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rt['room_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Min Price</label>
                        <input type="number" name="price_min" value="<?php echo $filter_price_min; ?>" min="0" step="10">
                    </div>
                    <div class="form-group">
                        <label>Max Price</label>
                        <input type="number" name="price_max" value="<?php echo $filter_price_max; ?>" min="0" step="10">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                    </div>
                </form>
            </div>

            <?php if (isset($error)): ?>
                <div class="flash-message flash-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Rooms Grid -->
            <div class="grid grid-3">
                <?php if (count($rooms) > 0): ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <h3><?php echo htmlspecialchars($room['room_type']); ?> - <?php echo htmlspecialchars($room['room_number']); ?></h3>
                                <span class="badge badge-<?php echo $room['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $room['status'])); ?></span>
                            </div>
                            <p style="color: var(--text-light); margin-bottom: 1rem;">
                                👥 Capacity: <?php echo $room['capacity']; ?> guest<?php echo $room['capacity'] > 1 ? 's' : ''; ?>
                            </p>
                            <p style="font-size: 1.75rem; color: var(--primary); font-weight: bold; margin-bottom: 1rem;">
                                $<?php echo number_format($room['price'], 2); ?> <span style="font-size: 0.8rem; color: var(--text-light);">/night</span>
                            </p>
                            <?php if ($room['status'] === 'available'): ?>
                                <a href="<?php echo app_url('public/room_details.php'); ?>?room_id=<?php echo $room['room_id']; ?>" class="btn btn-primary btn-block">View Details</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-block" disabled>Not Available</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                        <p style="color: var(--text-light); font-size: 1.1rem;">No rooms match your filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
