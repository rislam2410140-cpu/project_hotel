<?php
require_once __DIR__ . '/database/db_connect.php';

try {
    // Check if rooms already exist
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM rooms');
    $result = $stmt->fetch();
    $count = $result['count'];
    
    if ($count >= 8) {
        echo "✅ Database already has $count rooms!\n\n";
        echo "📋 Available Rooms:\n";
        $rooms = $pdo->query('SELECT room_number, room_type, price FROM rooms ORDER BY room_number')->fetchAll();
        foreach ($rooms as $room) {
            echo "  - Room " . $room['room_number'] . ": " . $room['room_type'] . " (\$" . $room['price'] . ")\n";
        }
        echo "\n✅ Ready to book! Login to: http://localhost/modern_hotel_management/\n";
    } else if ($count < 8) {
        echo "Found $count room(s). Adding more test rooms...\n";
        echo "Adding test rooms...\n";
        
        // Add test rooms
        $rooms_data = [
            ['101', 'Single', 50.00, 'available', 1],
            ['102', 'Single', 50.00, 'available', 1],
            ['103', 'Double', 75.00, 'available', 2],
            ['104', 'Double', 75.00, 'available', 2],
            ['105', 'Deluxe', 120.00, 'available', 3],
            ['106', 'Deluxe', 120.00, 'available', 3],
            ['107', 'Suite', 200.00, 'available', 4],
            ['108', 'Suite', 200.00, 'available', 4],
        ];
        
        $stmt = $pdo->prepare('INSERT INTO rooms (room_number, room_type, price, status, capacity) VALUES (?, ?, ?, ?, ?)');
        foreach ($rooms_data as $room) {
            $stmt->execute($room);
        }
        
        echo "✅ Added 8 test rooms successfully!\n\n";
        echo "📋 New Rooms:\n";
        foreach ($rooms_data as $room) {
            echo "  - Room " . $room[0] . ": " . $room[1] . " (\$" . $room[2] . ")\n";
        }
        echo "\n✅ Now ready to test! Login to: http://localhost/modern_hotel_management/\n";
        echo "   Guest: guest@hotel.com / Guest123\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
