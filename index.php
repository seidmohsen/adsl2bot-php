<?php 
$token = getenv("BOT_TOKEN");

// دریافت آپدیت از تلگرام
$update = json_decode(file_get_contents("php://input"), true);

$chat_id       = $update['message']['chat']['id'] ?? null;
$text          = trim($update['message']['text'] ?? '');
$callback_data = $update['callback_query']['data'] ?? null;
$callback_chat = $update['callback_query']['message']['chat']['id'] ?? null;
$message_id    = $update['callback_query']['message']['message_id'] ?? null;

// === مسیرها (بر اساس دستور ثابت) ===

// شروع ربات
if ($chat_id && $text === '/start') {
    require_once __DIR__ . '/menu.php';
    sendMainMenu($chat_id, $token);
    exit;
}

// لیست قیمت‌ها
if ($chat_id && $text === '/prices') {
    require_once __DIR__ . '/menu_prices.php';
    showPriceDurations($token, $chat_id);
    exit;
}

// جشنواره ثبت نام
if ($chat_id && $text === '/festival') {
    require_once __DIR__ . '/menu_festival.php';
    sendFestivalOffers($token, $chat_id);
    exit;
}

// اگر callback انتخاب مدت سرویس بود (مثلاً price_1ماهه)
if ($callback_data && strpos($callback_data, 'price_') === 0) {
    require_once __DIR__ . '/menu_prices.php';
    $duration = str_replace('price_', '', $callback_data);
    sendPriceList($token, $callback_chat, $message_id, $duration);
    exit;
}

// تغییر مدت
if ($callback_data && $callback_data === 'change_duration') {
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '۱ ماهه', 'callback_data' => 'price_1ماهه'],
                ['text' => '۳ ماهه', 'callback_data' => 'price_3ماه']
            ],
            [
                ['text' => '۶ ماهه', 'callback_data' => 'price_6ماه'],
                ['text' => '۱۲ ماهه', 'callback_data' => 'price_12ماه']
            ]
        ]
    ];
    editMessageTextWithKeyboard($token, $callback_chat, $message_id, "📅 مدت زمان سرویس را انتخاب کنید:", $keyboard);
    exit;
}

// بازگشت به منو اصلی
if ($callback_data && $callback_data === 'main_menu') {
    require_once __DIR__ . '/menu.php';
    sendMainMenu($callback_chat, $token);
    exit;
}

// پیام پیش‌فرض
if ($chat_id && $text != '' && !in_array($text, ['/start','/prices','/festival'])) {
    sendMessage($token, $chat_id, "برای شروع از منوی زیر استفاده کنید:", null);
    exit;
}

// تنظیم وبهوک از مرورگر (اختیاری)
if (isset($_GET['setwebhook'])) {
    $url = "https://adsl2bot-php.onrender.com/index.php"; 
    file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?" . http_build_query(['url' => $url]));
    echo "Webhook set!";
    exit;
}

// === توابع کمکی ===
function sendMessage($token, $chat_id, $text, $keyboard = null, $parse_mode = null) {
    $data = [
        'chat_id' => $chat_id,
        'text'    => $text
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    }
    if ($parse_mode) {
        $data['parse_mode'] = $parse_mode;
    }
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query($data));
}

function editMessageTextWithKeyboard($token, $chat_id, $message_id, $text, $keyboard, $parse_mode = null) {
    $data = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $text
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    }
    if ($parse_mode) {
        $data['parse_mode'] = $parse_mode;
    }
    file_get_contents("https://api.telegram.org/bot{$token}/editMessageText?" . http_build_query($data));
}
