<?php
require 'db_connection.php'; // Include database connection

// Fetch all bins
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $query = "SELECT * FROM bins"; // Query to get all bins from the database
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $bins = [];
        
        // Loop through the result set and fetch bin details
        while ($row = $result->fetch_assoc()) {
            $bins[] = [
                'id' => $row['id'],
                'bin_name' => $row['bin_name'],
                'waste_type' => $row['waste_type'],
                'fill_level' => $row['fill_level'],
                'last_emptied' => $row['last_emptied'],
                'status' => $row['status']
            ];
        }

        // Return the data as JSON
        echo json_encode($bins);
    } else {
        echo json_encode(['success' => false, 'message' => 'No bins found.']);
    }
}
?>
