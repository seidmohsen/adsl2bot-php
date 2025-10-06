<?php

function sendFestivalOffers($token, $chat_id) {
    // داده‌های استخراج شده از تصویری که ارسال کردی:
    $offers = [
        [
            'title'    => 'ویژه',
            'price'    => '1,336,000',
            'duration' => '۱۲ ماهه',
            'volume'   => '۲۴۰۰ گیگابایت بین الملل',
            'speed'    => '۸ مگابیت',
            'customer' => 'جدید',
            'callback' => 'fest_offer_vezheh'
        ],
        [
            'title'    => 'پرحجم',
            'price'    => '874,000',
            'duration' => '۶ ماهه',
            'volume'   => '۱200 گیگابایت بین الملل',
            'speed'    => '۸ مگابیت',
            'customer' => 'جدید',
            'callback' => 'fest_offer_porhajm'
        ],
        [
            'title'    => 'اقتصادی',
            'price'    => '280,000',
            'duration' => '۳ ماهه',
            'volume'   => '۲۴۰ گیگابایت بین الملل',
            'speed'    => '۸ مگابیت',
            'customer' => 'جدید',
            'callback' => 'fest_offer_eghtesadi'
        ]
    ];

    $msg = "🎉 **سرویس‌های ویژه جشنواره پاییز ۱۴۰۴** 🎉\n\n";

    $keyboard_buttons = [];
    $first = true;
    
    foreach ($offers as $offer) {
        if (!$first) {
            $msg .= "---------------------------------------\n";
        }
        
        $msg .= "<b>⭐ سرویس {$offer['title']}</b>\n";
        $msg .= "💰 قیمت: <b>{$offer['price']} تومان</b>\n";
        $msg .= "📅 مدت: {$offer['duration']}\n";
        $msg .= "🌐 حجم: {$offer['volume']}\n";
        $msg .= "⚡ سرعت: {$offer['speed']}\n";
        $msg .= "👥 مشترک: {$offer['customer']}\n\n";
        
        // اضافه کردن دکمه Inline برای هر سرویس
        $keyboard_buttons[] = [['text' => "✅ ثبت نام سرویس {$offer['title']}", 'callback_data' => $offer['callback']]];
        $first = false;
    }

    // اضافه کردن دکمه بازگشت به منو اصلی در انتها
    $keyboard_buttons[] = [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'main_menu']];

    $inline_keyboard = [
        'inline_keyboard' => $keyboard_buttons
    ];

    // ارسال پیام با استفاده از sendMessage (تعریف شده در index.php)
    sendMessage($token, $chat_id, $msg, $inline_keyboard, 'HTML');
}
?>
