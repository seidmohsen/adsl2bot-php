<?php

use PDO;

// اتصال به DB
$dsn = sprintf(
    "pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s",
    getenv('PGHOST'),
    getenv('PGPORT'),
    getenv('PGDATABASE'),
    getenv('PGUSER'),
    getenv('PGPASSWORD')
);
$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// گرفتن مخاطبین ثبت‌نام شده
$stmt = $pdo->query("SELECT * FROM user_states ORDER BY chat_id ASC");
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// نمایش HTML
echo "<h2>📋 لیست ثبت‌نام‌های جشنواره</h2>";
if (!$registrations) {
    echo "<p>هیچ ثبت‌نامی موجود نیست.</p>";
} else {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>Chat ID</th>
            <th>مرحله</th>
            <th>سرویس</th>
            <th>موبایل</th>
            <th>تلفن ثابت</th>
          </tr>";
    foreach ($registrations as $row) {
        echo "<tr>
                <td>{$row['chat_id']}</td>
                <td>{$row['step']}</td>
                <td>{$row['service']}</td>
                <td>{$row['mobile']}</td>
                <td>{$row['landline']}</td>
              </tr>";
    }
    echo "</table>";
}
