<?php

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

// --- مدیریت وضعیت کاربر ---
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
        ':step'    => $step,
        ':service' => $service,
        ':mobile'  => $mobile,
        ':landline'=> $landline
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
    if ($keyboard)   $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    if ($parse_mode) $data['parse_mode']   = $parse_mode;
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query($data));
}



function editMessageTextWithKeyboard($token, $chat_id, $message_id, $text, $keyboard, $parse_mode = null) {
    $data = ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $text];
    if ($keyboard)   $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    if ($parse_mode) $data['parse_mode']   = $parse_mode;
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

require_once __DIR__ . '/menu.php';
// ============================
// 🟢 شروع مسیرهای اصلی منو
// ============================

// دستور /start
if ($chat_id && str_starts_with(strtolower($text), '/start')) {
    clearUserState($chat_id);
    sendMainMenu($token, $chat_id);
    exit;
}

// قیمت‌ها
if ($chat_id && $text === '💰 لیست قیمتها') {
    require_once __DIR__ . '/menu_prices.php';
    showPriceDurations($token, $chat_id);
    exit;
}

// جشنواره
if ($chat_id && $text === '🎉 جشنواره ثبت نام') {
    require_once __DIR__ . '/menu_festival.php';
    sendFestivalOffers($token, $chat_id);
    exit;
}

// کال‌بک انتخاب مدت سرویس
if ($callback_data && str_starts_with($callback_data, 'price_')) {
    require_once __DIR__ . '/menu_prices.php';
    $duration = str_replace('price_', '', $callback_data);
    sendPriceList($token, $callback_chat, $message_id, $duration);
    exit;
}

// کال‌بک تغییر مدت زمان
if ($callback_data === 'change_duration') {
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

// بازگشت به منوی اصلی
if ($callback_data === 'main_menu') {
    sendMainMenu($token, $callback_chat);
    exit;
}

// ============================
// 🟡 سناریوی چندمرحله‌ای جشنواره
// ============================

if ($callback_data && str_starts_with($callback_data, 'fest_offer_')) {
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

    // مرحله ۲: دریافت موبایل
    if ($state && $state['step'] === 'ask_mobile') {
        $mobile = $update['message']['contact']['phone_number'] ?? $text;
        setUserState($chat_id, 'ask_landline', $state['service'], $mobile);
        sendMessage($token, $chat_id, "📞 لطفاً شماره تلفن ثابت خود را با کد شهر وارد کنید (مثال: 021-12345678):");
        exit;
    }

    // مرحله ۳: دریافت تلفن ثابت و اتمام فرایند
    if ($state && $state['step'] === 'ask_landline') {
        $landline = $text;
        setUserState($chat_id, 'done', $state['service'], $state['mobile'], $landline);

        // پیام پایانی برای کاربر
        sendMessage($token, $chat_id, "✅ با تشکر از حسن انتخاب شما\nپس از امکان‌سنجی ارائه خدمات آسیاتک، به زودی با شما تماس خواهیم گرفت.");

        // اطلاع‌رسانی به مدیر
        $admin_chat_id = getenv('ADMIN_CHAT_ID');
        $msg = "📢 ثبت‌نام جدید جشنواره:\n"
             . "👤 Chat ID: {$chat_id}\n"
             . "🎯 سرویس: {$state['service']}\n"
             . "📱 موبایل: {$state['mobile']}\n"
             . "☎ تلفن ثابت: {$landline}";

        if ($admin_chat_id) {
            sendMessage($token, $admin_chat_id, $msg);
        }

        clearUserState($chat_id);
        sendMainMenu($token, $chat_id);
        exit;
    }
}

// ============================
// 🔴 پیام پیش‌فرض
// ============================
if ($chat_id && $text !== '' && !in_array($text, ['/start', '💰 لیست قیمتها', '🎉 جشنواره ثبت‌نام'])) {
    sendMessage($token, $chat_id, "برای شروع از منوی زیر استفاده کنید:");
    sendMainMenu($token, $chat_id);
    exit;
}

// ============================
// ⚙️ تنظیم وبهوک دستی
// ============================
if (isset($_GET['setwebhook'])) {
    $url = "https://adsl2bot-php.onrender.com/index.php"; // آدرس واقعی سرویس Render
    file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?" . http_build_query(['url' => $url]));
    echo "Webhook set!";
    exit;
}




