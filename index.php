<?php
$token = getenv("BOT_TOKEN");

// دریافت آپدیت‌ها
$update = json_decode(file_get_contents("php://input"), true);

$chat_id       = $update['message']['chat']['id'] ?? null;
$text          = trim($update['message']['text'] ?? '');
$callback_data = $update['callback_query']['data'] ?? null;
$callback_chat = $update['callback_query']['message']['chat']['id'] ?? null;
$message_id    = $update['callback_query']['message']['message_id'] ?? null;

// /start
if ($chat_id && $text == '/start') {
    require_once __DIR__ . '/menu.php';
    sendMainMenu($chat_id, $token);
    exit;
}

// لیست قیمت‌ها
if ($chat_id && $text == "💰 لیست قیمتها") {
    require_once __DIR__ . '/menu_prices.php';
    showPriceDurations($chat_id);
    exit;
}

// Callback از انتخاب زمان سرویس
if ($callback_data && strpos($callback_data, 'price_') === 0) {
    require_once __DIR__ . '/menu_prices.php';
    $duration = str_replace('price_', '', $callback_data);
    sendPriceList($callback_chat, $message_id, $duration);
    exit;
}

// پیام پیش‌فرض
if ($chat_id && $text != '') {
    sendMessage($token, $chat_id, "برای شروع، دستور /start را بزنید.");
}

// ست کردن وبهوک
if (isset($_GET['setwebhook'])) {
    $url = "https://adsl2bot-php.onrender.com/index.php";
    file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?" . http_build_query(['url' => $url]));
    echo "Webhook set!";
}

// تابع ارسال پیام
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

// تابع ویرایش پیام
function editMessageText($chat_id, $message_id, $text) {
    global $token;
    $data = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $text
    ];
    file_get_contents("https://api.telegram.org/bot{$token}/editMessageText?" . http_build_query($data));
}
?>
