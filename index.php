<?php
$token = getenv("BOT_TOKEN");

// دریافت آپدیت از تلگرام
$update = json_decode(file_get_contents("php://input"), true);

// اگر کاربر دستوری ارسال کرده
if (isset($update['message']['chat']['id'])) {
    $chat_id = $update['message']['chat']['id'];
    $text    = trim($update['message']['text'] ?? '');

    if ($text == '/start') {
        require_once __DIR__ . '/menu.php';
        sendMainMenu($chat_id, $token);
    } else {
        sendMessage($token, $chat_id, "برای شروع، دستور /start را بزنید.");
    }
}

// ست‌کردن وبهوک فقط وقتی از مرورگر اجرا شد
if (isset($_GET['setwebhook'])) {
    $url = "https://adsl2bot-php.onrender.com/index.php";
    file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?" . http_build_query([
        'url' => $url
    ]));
    echo "Webhook set!";
}

// تابع ارسال پیام
function sendMessage($token, $chat_id, $text) {
    if (!empty($chat_id)) {
        file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
            'chat_id' => $chat_id,
            'text' => $text
        ]));
    }
}
?>
