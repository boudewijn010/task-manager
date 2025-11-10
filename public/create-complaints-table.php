<?php
// First, check if the complaints table already exists
try {
    $res = $db->query("SHOW TABLES LIKE 'complaints'");
    $tableExists = ($res && $res->rowCount() > 0);
} catch (Exception $e) {
    $tableExists = false;
}

// Create the complaints table if it doesn't exist
if (!$tableExists) {
    $createTableSQL = "CREATE TABLE IF NOT EXISTS complaints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        type ENUM('klacht', 'suggestie', 'vraag') DEFAULT 'klacht',
        status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        contact_email VARCHAR(255) NOT NULL,
        address VARCHAR(255) DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        $db->exec($createTableSQL);
        echo "<div class='alert alert-info'>Database tabel 'complaints' is aangemaakt.</div>";
    } catch (Exception $e) {
        die("Fout bij het aanmaken van de tabel: " . $e->getMessage());
    }
} else {
    // Ensure new columns exist (address, latitude, longitude)
    $needed = [
        'address' => "VARCHAR(255) DEFAULT NULL",
        'latitude' => "DECIMAL(10,7) DEFAULT NULL",
        'longitude' => "DECIMAL(10,7) DEFAULT NULL",
    ];
    foreach ($needed as $col => $definition) {
        try {
            $res = $db->query("SHOW COLUMNS FROM complaints LIKE '" . $col . "'");
            if (!$res || $res->rowCount() === 0) {
                $db->exec("ALTER TABLE complaints ADD COLUMN " . $col . " " . $definition);
            }
        } catch (Exception $e) {
            // ignore individual column errors
        }
    }
}
?>