<?php
try {
    // اتصال با استفاده از متغیرهای محیطی Render
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

    echo "✅ اتصال به دیتابیس برقرار شد.<br>";

    // ایجاد جدول اگر وجود ندارد
    $sql = "
        CREATE TABLE IF NOT EXISTS user_states (
            chat_id BIGINT PRIMARY KEY,
            step TEXT,
            service TEXT,
            mobile TEXT,
            landline TEXT
        );
    ";
    $pdo->exec($sql);
    echo "✅ جدول user_states ساخته شد یا از قبل وجود داشت.<br>";

    // تست: نمایش جداول موجود
    $stmt = $pdo->query("
        SELECT tablename
        FROM pg_catalog.pg_tables
        WHERE schemaname NOT IN ('pg_catalog', 'information_schema');
    ");

    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "📋 لیست جداول دیتابیس:<br>";
    foreach ($tables as $table) {
        echo "- {$table}<br>";
    }

} catch (PDOException $e) {
    echo "❌ خطا: " . $e->getMessage();
}
