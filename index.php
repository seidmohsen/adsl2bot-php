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
//چک کردن شماره تکراری در جدول ثبت نام جشنواره

function isLandlineDuplicate(PDO $pdo, string $landline): bool {
    $sql = "SELECT COUNT(*) FROM festival_registrations WHERE landline = :landline";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':landline' => $landline]);
    $count = $stmt->fetchColumn();

    return $count > 0; // اگر حداقل یک رکورد پیدا شد، یعنی تکراری است
}
// چک کردن صحت شماره تلفن ثابت
function isValidLandline(string $landline): bool {
    // حذف فاصله‌ها یا کاراکترهای غیرضروری مثل - یا _
    $landline = preg_replace('/\D/', '', $landline); // فقط اعداد را نگه می‌دارد

    // باید دقیقاً 11 رقم باشد
    if (strlen($landline) !== 11) {
        return false;
    }

    // باید با صفر شروع شود
    if ($landline[0] !== '0') {
        return false;
    }

    // مطمئن شو که همه‌ش عدد است (در واقع با preg_replace بالا تضمین شده)
    if (!ctype_digit($landline)) {
        return false;
    }

    return true;
}


///
// --- اطلاعات آپدیت ---
$token = getenv("BOT_TOKEN");
$update = json_decode(file_get_contents("php://input"), true);

$chat_id       = $update['message']['chat']['id'] ?? null;
$text          = trim($update['message']['text'] ?? '');
$callback_data = $update['callback_query']['data'] ?? null;
$callback_chat = $update['callback_query']['message']['chat']['id'] ?? null;
$message_id    = $update['callback_query']['message']['message_id'] ?? null;

require_once __DIR__ . '/menu.php';
$menu_items = getMainMenuItems();
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
                        
                            // اگر کاربر خواست شماره جدید وارد کند
                            if ($text === '📞 وارد کردن شماره جدید' && $state) {
                                setUserState($chat_id, 'ask_landline', $state['service'], $state['mobile'], null);
                                sendMessage($token, $chat_id, "☎ لطفاً شماره تلفن ثابت خود را وارد کنید:");
                                exit;
                            }
                            
                            // اگر خواست از ثبت‌نام خارج شود
                            if ($text === '🚪 خروج از ثبت‌نام جشنواره') {
                                clearUserState($chat_id);
                                sendMainMenu($token, $chat_id);
                                exit;
                            }
                        
                            // مرحله ۲: دریافت موبایل
                            if ($state && $state['step'] === 'ask_mobile') {
                                $mobile = $update['message']['contact']['phone_number'] ?? $text;
                                setUserState($chat_id, 'ask_landline', $state['service'], $mobile);
                                sendMessage($token, $chat_id, "📞 لطفاً شماره تلفن ثابت خود را با کد شهر وارد کنید (مثال: 021-12345678):");
                                exit;
                            }
                        
                            // مرحله ۳: دریافت تلفن ثابت و اتمام فرایند
                            if ($state && $state['step'] === 'ask_landline') {
                                $landline = trim($text);
                                $pdo = getDb();
                                // بررسی درست وارد شدن شماره تلفن
                               if (!isValidLandline($landline)) {
                                    sendMessage($token, $chat_id, "⚠️ شماره وارد شده معتبر نیست.\nشماره باید عددی، ۱۱ رقمی و با ۰ شروع شود (مثلاً 02112345678).");
                                    
                                    // نمایش منوی انتخاب برای ادامه یا خروج از جشنواره
                                    $keyboard = [
                                        'keyboard' => [
                                            [['text' => '📞 وارد کردن شماره جدید']],
                                            [['text' => '🚪 خروج از ثبت‌نام جشنواره']]
                                        ],
                                        'resize_keyboard' => true
                                    ];
                        
                                    // ✅ فقط خود آرایه ارسال می‌شود، بدون JSON دوباره
                                    sendMessage($token, $chat_id, "لطفاً یکی از گزینه‌های زیر را انتخاب کنید:", $keyboard);
                                    exit;
                                }
                                // بررسی تکراری بودن شماره تلفن ثابت
                                if (isLandlineDuplicate($pdo, $landline)) {
                                    sendMessage($token, $chat_id, "⚠️ شماره $landline قبلاً در جشنواره ثبت شده است.\nلطفاً یک شماره دیگر وارد کنید یا گزینه مورد نظر را انتخاب کنید 👇");
                                    
                                    // نمایش منوی انتخاب برای ادامه یا خروج از جشنواره
                                    $keyboard = [
                                        'keyboard' => [
                                            [['text' => '📞 وارد کردن شماره جدید']],
                                            [['text' => '🚪 خروج از ثبت‌نام جشنواره']]
                                        ],
                                        'resize_keyboard' => true
                                    ];
                        
                                    // ✅ فقط خود آرایه ارسال می‌شود، بدون JSON دوباره
                                    sendMessage($token, $chat_id, "لطفاً یکی از گزینه‌های زیر را انتخاب کنید:", $keyboard);
                                    exit;
                                }
                                /
                                // اگر تکراری نبود → ادامه فرآیند ثبت‌نام
                                setUserState($chat_id, 'done', $state['service'], $state['mobile'], $landline);
                        
                                sendMessage($token, $chat_id, "✅ با تشکر از حسن انتخاب شما\nپس از امکان‌سنجی ارائه خدمات آسیاتک، به زودی با شما تماس خواهیم گرفت.");
                        
                                // اطلاع‌رسانی به مدیر
                                $admin_chat_id = getenv('ADMIN_CHAT_ID');
                                if ($admin_chat_id) {
                                    $msg = "📢 ثبت‌نام جدید جشنواره:\n"
                                         . "👤 Chat ID: {$chat_id}\n"
                                         . "🎯 سرویس: {$state['service']}\n"
                                         . "📱 موبایل: {$state['mobile']}\n"
                                         . "☎ تلفن ثابت: {$landline}";
                                    sendMessage($token, $admin_chat_id, $msg);
                                }
                        
                                // ذخیره در دیتابیس
                                $stmt = $pdo->prepare("
                                    INSERT INTO festival_registrations (chat_id, service, mobile, adsl, landline)
                                    VALUES (:chat_id, :service, :mobile, :adsl, :landline)
                                ");
                                $stmt->execute([
                                    ':chat_id'  => $chat_id,
                                    ':service'  => $state['service'],
                                    ':mobile'   => $state['mobile'],
                                    ':adsl'     => $landline,
                                    ':landline' => $landline
                                ]);
                        
                                clearUserState($chat_id);
                                sendMainMenu($token, $chat_id);
                                exit;
                            }
}

    
// ============================
// 🔴 پیام پیش‌فرض
// ============================
if ($chat_id && $text !== '' ) {
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





