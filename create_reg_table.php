<?php
echo "123";
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
        CREATE TABLE IF NOT EXISTS festival_registrations (
        id SERIAL PRIMARY KEY,
        chat_id BIGINT NOT NULL,
        service TEXT,
        mobile TEXT,
        adsl TEXT,
        landline TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

    ";
    $pdo->exec($sql);
    echo "✅ جدول user_states ساخته شد یا از قبل وجود داشت.<br>";

    // درج رکورد تستی
    $chat_id_test = 123456789;
    $service_test = 'festival';
    $mobile_test  = '09121234567';
    $adsl_test = '02537732240';
    $landline_test = '02112345678';

    $insert_sql = "
        INSERT INTO festival_registrations (chat_id, service, mobile, adsl, landline)
        VALUES (:chat_id, :service, :mobile, :adsl, :landline)
        ON CONFLICT (chat_id) DO UPDATE SET
            service = EXCLUDED.service,
            mobile = EXCLUDED.mobile,
            adsl = EXCLUDED.adsl,
            landline = EXCLUDED.landline;
    ";

    $stmt = $pdo->prepare($insert_sql);
    $stmt->execute([
        ':chat_id' => $chat_id_test,
        ':service' => $service_test,
        ':mobile'  => $mobile_test,
        'adsl' => $adsl_test,
        ':landline'=> $landline_test
    ]);

    echo "✅ رکورد تستی درج/به‌روزرسانی شد.<br>";

    // نمایش رکوردها
    echo "📋 رکوردهای موجود در جدول:<br>";
    $stmt = $pdo->query("SELECT * FROM festival_registrations");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- chat_id: {$row['chat_id']},  service: {$row['service']}, mobile: {$row['mobile']}, adsl: {$row['adsl']], landline: {$row['landline']}<br>";
    }

} catch (PDOException $e) {
    echo "❌ خطا: " . $e->getMessage();
}
