<?php
/*
function sendMainMenu($chat_id, $token){
    $keyboard = [
        ['ثبت نام', 'جشنواره ثبت نام'],
        ['لیست قیمتها', 'تمدید سرویس'],
        ['تماس با ما']
    ];

    $response = [
        'keyboard' => $keyboard,
        'resize_keyboard' => true
    ];

    // ارسال پیام به کاربر با منو
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => "لطفاً یکی از گزینه‌ها را انتخاب کنید:",
        'reply_markup' => json_encode($response, JSON_UNESCAPED_UNICODE)
    ]));
}*/



function sendMainMenu($chat_id, $token){
    // ساخت کیبورد با ایموجی‌ها
    $keyboard = [
        ['📝 ثبت نام', '🎉 جشنواره ثبت نام'],
        ['💰 لیست قیمتها', '🔄 تمدید سرویس'],
        ['📞 تماس با ما']
    ];

    $response = [
        'keyboard' => $keyboard,
        'resize_keyboard' => true
    ];

    // متن خوش‌آمدگویی
    $text = "🌐 خوش آمدید!\n\n"
          . "لطفاً یکی از گزینه‌ها را انتخاب کنید:";

    // ارسال منو به کاربر
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_markup' => json_encode($response, JSON_UNESCAPED_UNICODE)
    ]));
}


?>
