<?php
require 'config/db.php';

try {
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'member') NOT NULL
        )
    ");

    // Create members table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            section VARCHAR(50),
            instrument VARCHAR(50),
            dietary_restrictions TEXT,
            is_adjunct BOOLEAN DEFAULT FALSE
        )
    ");

    // Create events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_name VARCHAR(255) NOT NULL,
            event_date DATE NOT NULL,
            start_time TIME,
            end_time TIME,
            location VARCHAR(255),
            event_type VARCHAR(50)
        )
    ");

    // Create attendance table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            event_id INT NOT NULL,
            status ENUM('yes', 'no', 'maybe') DEFAULT 'no',
            time_note VARCHAR(255),
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            UNIQUE KEY unique_attendance (member_id, event_id)
        )
    ");

    // Create import_log table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS import_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert default admin user if not exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['admin', 'password', 'admin']); // Change password as needed

    echo "Database initialized successfully!";
} catch (PDOException $e) {
    die("Initialization failed: " . $e->getMessage());
}
?>