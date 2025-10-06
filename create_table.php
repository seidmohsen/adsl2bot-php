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

    // درج رکورد تستی
    $chat_id_test = 123456789;
    $step_test    = 'ask_mobile';
    $service_test = 'festival';
    $mobile_test  = '09121234567';
    $landline_test = '02112345678';

    $insert_sql = "
        INSERT INTO user_states (chat_id, step, service, mobile, landline)
        VALUES (:chat_id, :step, :service, :mobile, :landline)
        ON CONFLICT (chat_id) DO UPDATE SET
            step = EXCLUDED.step,
            service = EXCLUDED.service,
            mobile = EXCLUDED.mobile,
            landline = EXCLUDED.landline;
    ";

    $stmt = $pdo->prepare($insert_sql);
    $stmt->execute([
        ':chat_id' => $chat_id_test,
        ':step'    => $step_test,
        ':service' => $service_test,
        ':mobile'  => $mobile_test,
        ':landline'=> $landline_test
    ]);

    echo "✅ رکورد تستی درج/به‌روزرسانی شد.<br>";

    // نمایش رکوردها
    echo "📋 رکوردهای موجود در جدول:<br>";
    $stmt = $pdo->query("SELECT * FROM user_states");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- chat_id: {$row['chat_id']}, step: {$row['step']}, service: {$row['service']}, mobile: {$row['mobile']}, landline: {$row['landline']}<br>";
    }

} catch (PDOException $e) {
    echo "❌ خطا: " . $e->getMessage();
}
