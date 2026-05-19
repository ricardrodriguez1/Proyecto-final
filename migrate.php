<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();
$errors = [];
$ok = [];

$tables = [
    "favorites" => "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        recipe_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_fav (user_id, recipe_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "recipe_ratings" => "CREATE TABLE IF NOT EXISTS recipe_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        recipe_id INT NOT NULL,
        rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_rating (user_id, recipe_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "recipe_comments" => "CREATE TABLE IF NOT EXISTS recipe_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        recipe_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "user_challenges" => "CREATE TABLE IF NOT EXISTS user_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        challenge_key VARCHAR(100) NOT NULL,
        progress INT DEFAULT 0,
        completed TINYINT(1) DEFAULT 0,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY unique_challenge (user_id, challenge_key),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
];

foreach ($tables as $name => $sql) {
    try {
        $db->exec($sql);
        $ok[] = "✅ Table '$name' OK";
    } catch (Exception $e) {
        $errors[] = "❌ '$name': " . $e->getMessage();
    }
}

echo implode("\n", $ok) . "\n";
if ($errors) echo implode("\n", $errors) . "\n";
echo count($errors) === 0 ? "\nALL MIGRATIONS SUCCESSFUL!" : "\nSome errors occurred.";
