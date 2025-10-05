<?php
/*
$token = getenv("BOT_TOKEN"); // توکن از Environment Variables
$update = json_decode(file_get_contents("php://input"), true);

$chat_id = $update['message']['chat']['id'] ?? null;
$text    = trim($update['message']['text'] ?? '');

require_once "menu.php";

if ($text == "/start") {
    sendMainMenu($chat_id, $token);
} else {
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => "برای شروع، دستور /start را بزنید."
    ]));
}
*/


// توکن بات - بهتره در Environment Variable قرارش بدی
$token = getenv("BOT_TOKEN");;

// دریافت آپدیت از تلگرام
$update = json_decode(file_get_contents("php://input"), true);

// اگر کاربر دستوری ارسال کرده
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text    = $update['message']['text'];

    // اگر دستور /start بود
    if ($text == '/start') {
        require_once __DIR__ . '/menu.php';
        sendMainMenu($chat_id, $token);
    } else {
        // پیام پیشفرض
        file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
            'chat_id' => $chat_id,
            'text' => "برای شروع، دستور /start را بزنید."
        ]));
    }
}

// اگر درخواست برای ست‌کردن وبهوک بود
if (isset($_GET['setwebhook'])) {
    $url = "https://adsl2bot-php.onrender.com/index.php";
    file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?" . http_build_query([
        'url' => $url
    ]));
    echo "Webhook set!";
}

?>



