<?php

function getMainMenuItems() {
    return [
        '🎉 جشنواره ثبت نام' => 'festival',
        '💰 لیست قیمتها'   => 'prices',
        '📝 ثبت نام'       => 'register',
        '🔄 تمدید سرویس'   => 'renew',
        '📞 تماس با ما'     => 'contact'
    ];
}

function sendMainMenu($token, $chat_id){
    // منوی اصلی به صورت Reply Keyboard
    // هر دکمه متن فارسی دارد ولی پشت‌صحنه همان دستور ثابت ارسال می‌شود
    $keyboard = [
        ['🎉 جشنواره ثبت نام', '💰 لیست قیمتها'], 
        ['📝 ثبت نام', '🔄 تمدید سرویس'],
        ['📞 تماس با ما']
    ];

    $response = [
        'keyboard' => $keyboard,
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
