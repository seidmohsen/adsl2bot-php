<?php /*
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
if ($chat_id && $text === '💰 لیست قیمتها') {
    require_once __DIR__ . '/menu_prices.php';
    showPriceDurations($token, $chat_id);
    exit;
}

// جشنواره ثبت نام
if ($chat_id && $text === '🎉 جشنواره ثبت نام') {
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
if ($chat_id && $text != '' && !in_array($text, ['/start','💰 لیست قیمتها','🎉 جشنواره ثبت نام'])) {
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
}*/


use PDO;

// --- اتصال به PostgreSQL ---
function getDb() {
    static $pdo;
    if (!$pdo) {
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
    }
    return $pdo;
}

function setUserState($chat_id, $step, $service = null, $mobile = null, $landline = null) {
    $pdo = getDb();
    $stmt = $pdo->prepare("
        INSERT INTO user_states (chat_id, step, service, mobile, landline)
        VALUES (:chat_id, :step, :service, :mobile, :landline)
        ON CONFLICT (chat_id) DO UPDATE
        SET step = EXCLUDED.step,
            service = EXCLUDED.service,
            mobile = EXCLUDED.mobile,
            landline = EXCLUDED.landline
    ");
    $stmt->execute([
        ':chat_id' => $chat_id,
        ':step' => $step,
        ':service' => $service,
        ':mobile' => $mobile,
        ':landline' => $landline
    ]);
}

function getUserState($chat_id) {
    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT * FROM user_states WHERE chat_id = :chat_id");
    $stmt->execute([':chat_id' => $chat_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function clearUserState($chat_id) {
    $pdo = getDb();
    $stmt = $pdo->prepare("DELETE FROM user_states WHERE chat_id = :chat_id");
    $stmt->execute([':chat_id' => $chat_id]);
}

// --- توابع ارسال پیام ---
function sendMessage($token, $chat_id, $text, $keyboard = null, $parse_mode = null) {
    $data = ['chat_id' => $chat_id, 'text' => $text];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query($data));
}

function editMessageTextWithKeyboard($token, $chat_id, $message_id, $text, $keyboard, $parse_mode = null) {
    $data = ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $text];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    file_get_contents("https://api.telegram.org/bot{$token}/editMessageText?" . http_build_query($data));
}

// --- اطلاعات آپدیت ---
$token = getenv("BOT_TOKEN");
$update = json_decode(file_get_contents("php://input"), true);

$chat_id       = $update['message']['chat']['id'] ?? null;
$text          = trim($update['message']['text'] ?? '');
$callback_data = $update['callback_query']['data'] ?? null;
$callback_chat = $update['callback_query']['message']['chat']['id'] ?? null;
$message_id    = $update['callback_query']['message']['message_id'] ?? null;

// --- مسیرها ثابت (منو اصلی) ---
if ($chat_id && $text === '/start') {
    require_once __DIR__ . '/menu.php';
    sendMainMenu($chat_id, $token);
    exit;
}

if ($chat_id && $text === '💰 لیست قیمتها') {
    require_once __DIR__ . '/menu_prices.php';
    showPriceDurations($token, $chat_id);
    exit;
}

if ($chat_id && $text === '🎉 جشنواره ثبت نام') {
    require_once __DIR__ . '/menu_festival.php';
    sendFestivalOffers($token, $chat_id);
    exit;
}

if ($callback_data && strpos($callback_data, 'price_') === 0) {
    require_once __DIR__ . '/menu_prices.php';
    $duration = str_replace('price_', '', $callback_data);
    sendPriceList($token, $callback_chat, $message_id, $duration);
    exit;
}

if ($callback_data && $callback_data === 'change_duration') {
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '۱ ماهه', 'callback_data' => 'price_1ماهه'],
             ['text' => '۳ ماهه', 'callback_data' => 'price_3ماه']],
            [['text' => '۶ ماهه', 'callback_data' => 'price_6ماه'],
             ['text' => '۱۲ ماهه', 'callback_data' => 'price_12ماه']]
        ]
    ];
    editMessageTextWithKeyboard($token, $callback_chat, $message_id, "📅 مدت زمان سرویس را انتخاب کنید:", $keyboard);
    exit;
}

if ($callback_data && $callback_data === 'main_menu') {
    require_once __DIR__ . '/menu.php';
    sendMainMenu($callback_chat, $token);
    exit;
}

// --- سناریوی جشنواره چندمرحله‌ای ---
if ($callback_data && strpos($callback_data, 'fest_offer_') === 0) {
    setUserState($callback_chat, 'ask_mobile', $callback_data);
    $keyboard = [
        'keyboard' => [
            [['text' => '📱 ارسال شماره موبایل', 'request_contact' => true]]
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ];
    sendMessage($token, $callback_chat, "لطفاً شماره موبایل خود را ارسال کنید:", $keyboard);
    exit;
}

if ($chat_id) {
    $state = getUserState($chat_id);

    // دریافت موبایل
    if ($state && $state['step'] === 'ask_mobile') {
        $mobile = isset($update['message']['contact']['phone_number'])
            ? $update['message']['contact']['phone_number']
            : $text;

        setUserState($chat_id, 'ask_landline', $state['service'], $mobile);
        sendMessage($token, $chat_id, "📞 لطفاً شماره تلفن ثابت خود را به همراه کد شهر ارسال کنید (مثال: 021-12345678):");
        exit;
    }

    // دریافت تلفن ثابت + اطلاع مدیر
    if ($state && $state['step'] === 'ask_landline') {
        $landline = $text;
        setUserState($chat_id, 'done', $state['service'], $state['mobile'], $landline);

        sendMessage($token, $chat_id, "✅ با تشکر از حسن انتخاب شما\nپس از امکان‌سنجی ارائه خدمات آسیاتک، به زودی با شما تماس خواهیم گرفت.");

        // ارسال پیام به مدیر
        $admin_chat_id = getenv('ADMIN_CHAT_ID'); // یا مستقیم آیدی عددی خودتون
        $msg = "📢 ثبت‌نام جدید جشنواره:\n"
             . "👤 Chat ID: {$chat_id}\n"
             . "🎯 سرویس: {$state['service']}\n"
             . "📱 موبایل: {$state['mobile']}\n"
             . "☎ تلفن ثابت: {$landline}";
        if ($admin_chat_id) {
            sendMessage($token, $admin_chat_id, $msg);
        }

        clearUserState($chat_id);
        exit;
    }
}

// --- پیام پیش‌فرض ---
if ($chat_id && $text !== '' && !in_array($text, ['/start','💰 لیست قیمتها','🎉 جشنواره ثبت نام'])) {
    sendMessage($token, $chat_id, "برای شروع از منوی زیر استفاده کنید:", null);
    exit;
}

// --- تنظیم وبهوک ---
if (isset($_GET['setwebhook'])) {
    $url = "https://your-service-name.onrender.com/index.php";
    file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?" . http_build_query(['url' => $url]));
    echo "Webhook set!";
    exit;
}

