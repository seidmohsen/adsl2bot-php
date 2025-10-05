<?php
$token = getenv("BOT_TOKEN");

// دریافت آپدیت از تلگرام
$update = json_decode(file_get_contents("php://input"), true);

$chat_id       = $update['message']['chat']['id'] ?? null;
$text          = trim($update['message']['text'] ?? '');
$callback_data = $update['callback_query']['data'] ?? null;
$callback_chat = $update['callback_query']['message']['chat']['id'] ?? null;
$message_id    = $update['callback_query']['message']['message_id'] ?? null;

// === شروع بررسی پیام‌ها ===

// شروع ربات
if ($chat_id && $text == '/start') {
    require_once __DIR__ . '/menu.php';
    sendMainMenu($chat_id, $token);
    exit;
}

// لیست قیمت‌ها (نمایش انتخاب مدت)
if ($chat_id && $text == "💰 لیست قیمتها") {
    require_once __DIR__ . '/menu_prices.php';
    showPriceDurations($token, $chat_id);
    exit;
}

// اگر callback انتخاب مدت سرویس بود
if ($callback_data && strpos($callback_data, 'price_') === 0) {
    require_once __DIR__ . '/menu_prices.php';
    $duration = str_replace('price_', '', $callback_data);
    sendPriceList($token, $callback_chat, $message_id, $duration);
    exit;
}

// پیام پیش‌فرض
if ($chat_id && $text != '' && 
    $text != '/start' && 
    $text != '💰 لیست قیمتها') {
    sendMessage($token, $chat_id, "برای شروع، دستور /start را بزنید.");
    exit;
}

// تنظیم وبهوک از مرورگر
if (isset($_GET['setwebhook'])) {
    $url = "https://adsl2bot-php.onrender.com/index.php";
    file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?" . http_build_query(['url' => $url]));
    echo "Webhook set!";
}

// === توابع کمکی ===
function sendMessage($token, $chat_id, $text, $keyboard = null) {
    $data = [
        'chat_id' => $chat_id,
        'text'    => $text
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    }
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query($data));
}

function editMessageText($token, $chat_id, $message_id, $text) {
    $data = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $text
    ];
    file_get_contents("https://api.telegram.org/bot{$token}/editMessageText?" . http_build_query($data));
}
?>
