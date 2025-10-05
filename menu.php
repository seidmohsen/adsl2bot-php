<?php
function sendMainMenu($chat_id, $token){
    // هر دکمه به جای متن فارسی دستور ثابت رو ارسال می‌کنه
    $keyboard = [
        [['text' => '📝 ثبت نام', 'callback_data' => '/register']], // هنوز پیاده‌سازی نشده
        [['text' => '🎉 جشنواره ثبت نام', 'callback_data' => '/festival']],
        [['text' => '💰 لیست قیمتها', 'callback_data' => '/prices']],
        [['text' => '🔄 تمدید سرویس', 'callback_data' => '/renew']], // هنوز پیاده‌سازی نشده
        [['text' => '📞 تماس با ما', 'callback_data' => '/contact']] // هنوز پیاده‌سازی نشده
    ];

    $response = [
        'keyboard' => [
            ['/festival', '/prices'], // دستور واقعی که ارسال می‌شود
            ['/register', '/renew'],
            ['/contact']
        ],
        'resize_keyboard' => true
    ];

    $text = "🌐 خوش آمدید!\n\n"
          . "لطفاً یکی از گزینه‌ها را انتخاب کنید:";

    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => json_encode($response, JSON_UNESCAPED_UNICODE)
    ]));
}
?>
