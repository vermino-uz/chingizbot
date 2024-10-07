<?php

// Database connection settings
$host = 'localhost';
$db = 'chingizbot';
$user = 'chingizbot';
$pass = 'chingizbot';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Open the file
    $file = fopen('user_id.txt', 'r');
    if (!$file) {
        throw new Exception("Could not open the file!");
    }

    $batchSize = 1000; // Insert in batches of 1000
    $batchData = [];

    while (($line = fgets($file)) !== false) {
        $userId = trim($line); // Trim any whitespace
        if (!empty($userId)) {
            $batchData[] = $userId;

            // Once we reach the batch size, insert the batch
            if (count($batchData) === $batchSize) {
                insertBatch($pdo, $batchData);
                $batchData = []; // Reset batch
            }
        }
    }

    // Insert remaining data in case it's less than batch size
    if (!empty($batchData)) {
        insertBatch($pdo, $batchData);
    }

    fclose($file);
    echo "Data inserted successfully.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Function to insert a batch of user IDs into the active_users table
function insertBatch(PDO $pdo, array $batchData) {
    // Prepare the insert statement
    $placeholders = implode(',', array_fill(0, count($batchData), '(?)'));
    $sql = "INSERT INTO active_users (chat_id) VALUES $placeholders";

    // Execute the query with the batch data
    $stmt = $pdo->prepare($sql);
    $stmt->execute($batchData);
}

?>
