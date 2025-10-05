<?php
// تنظیمات
$token = getenv("BOT_TOKEN");

// گرفتن داده POST از تلگرام
$update = json_decode(file_get_contents("php://input"), true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';

    // پاسخ نمونه
    $reply = "بات فعال شد ✅\nپیام شما: " . $text;

    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($reply));
}

// اگر بخوای Webhook رو ستاپ کنی:
if (isset($_GET['setwebhook'])) {
    $url = "https://" . $_SERVER['HTTP_HOST'] . "/index.php";
    $set = file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?url={$url}");
    echo $set;
    exit;
}

echo "Bot PHP is Running!";
?>

