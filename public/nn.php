<?php

// Database connection credentials
$host = 'localhost';
$db = 'chingizbot';
$user = 'chingizbot';
$pass = 'chingizbot';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Open a file to write user IDs
$file = fopen('user_ids.txt', 'w');

// Batch size (number of rows to process at a time)
$batchSize = 10000; // Adjust batch size based on server capacity
$offset = 0;

do {
    // Query to fetch user IDs in batches
    $sql = "SELECT chat_id FROM bot_users LIMIT $batchSize OFFSET $offset";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Loop through the result and write each user ID to the file
        while ($row = $result->fetch_assoc()) {
            fwrite($file, $row['chat_id'] . PHP_EOL);
        }
    }

    // Move the offset forward for the next batch
    $offset += $batchSize;

} while ($result->num_rows > 0);

// Close the file and the database connection
fclose($file);
$conn->close();

echo "User IDs saved to user_ids.txt successfully.";
