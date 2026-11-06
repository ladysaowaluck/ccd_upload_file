<?php
echo "Querying customer_clearance_data via PDO...\n";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=ccd_system_rds', 'root', '', [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->query("SELECT COUNT(*) FROM customer_clearance_data");
    $count = $stmt->fetchColumn();
    echo "Count: {$count}\n";

    $stmt2 = $pdo->query("SELECT id, job_no FROM customer_clearance_data ORDER BY id DESC LIMIT 5");
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Job: {$row['job_no']}\n";
    }
} catch (Throwable $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
}

