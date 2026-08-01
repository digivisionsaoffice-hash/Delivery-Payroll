<?php
// db_import.php
$host = 'sql205.infinityfree.com';
$db   = 'if0_42292084_db_walim';
$user = 'if0_42292084';
$pass = 'Bwyzwq2m72X6';
$sqlFile = 'database.sql';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (file_exists($sqlFile)) {
    $query = file_get_contents($sqlFile);
    if ($mysqli->multi_query($query)) {
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        echo "Database imported successfully.<br>";
        unlink($sqlFile); // clean up
    } else {
        echo "Error importing database: " . $mysqli->error;
    }
} else {
    echo "SQL file not found.";
}

$mysqli->close();
