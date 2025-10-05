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

// *** منطق جدید: جشنواره ثبت نام ***
if ($chat_id && $text == "🎁 جشنواره ثبت نام") {
    require_once __DIR__ . '/menu_festival.php'; // فایل جدید را include می‌کنیم
    sendFestivalOffers($token, $chat_id);
    exit;
}
// ------------------------------------


// اگر callback انتخاب مدت سرویس بود (مثلاً price_1ماهه)
if ($callback_data && strpos($callback_data, 'price_') === 0) {
    require_once __DIR__ . '/menu_prices.php';
    $duration = str_replace('price_', '', $callback_data);
    sendPriceList($token, $callback_chat, $message_id, $duration);
    exit;
}

// *** منطق جدید برای دکمه '🔄 تغییر مدت' ***
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
    // به جای ارسال پیام جدید، پیام قبلی (لیست قیمت) را ویرایش می‌کنیم و کیبورد انتخاب مدت را جایگزین می‌کنیم.
    editMessageTextWithKeyboard($token, $callback_chat, $message_id, "📅 مدت زمان سرویس را انتخاب کنید:", $keyboard);
    exit;
}
// -----------------------------------------------------------------

// پیام پیش‌فرض
if ($chat_id && $text != '' && 
    $text != '/start' && 
    $text != '💰 لیست قیمتها') {
    sendMessage($token, $chat_id, "برای شروع، دستور /start را بزنید.");
    exit;
}

// تنظیم وبهوک از مرورگر
if (isset($_GET['setwebhook'])) {
    // آدرس Render را به آدرس واقعی خودت تغییر بده
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

// تابع جدید: ویرایش پیام با کیبورد (برای استفاده در Callback Queryها)
function editMessageTextWithKeyboard($token, $chat_id, $message_id, $text, $keyboard, $parse_mode = null) {
    $data = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $text,
        'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE)
    ];
    if ($parse_mode) {
        $data['parse_mode'] = $parse_mode;
    }
    file_get_contents("https://api.telegram.org/bot{$token}/editMessageText?" . http_build_query($data));
}

function editMessageText($token, $chat_id, $message_id, $text) {
    // این تابع ساده را با تابع جدید جایگزین می‌کنیم تا تابع اصلی ویرایش در همه جا یکسان باشد
    // اما برای سازگاری موقت اینجا می‌گذارمش.
    $data = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $text
    ];
    file_get_contents("https://api.telegram.org/bot{$token}/editMessageText?" . http_build_query($data));
}

?>






