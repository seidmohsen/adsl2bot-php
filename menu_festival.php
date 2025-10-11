<?php

function sendFestivalOffers($token, $chat_id) {
    // 🎉 داده‌های سرویس‌های جشنواره
    $offers = [
        [
            'title'    => 'ویژه',
            'price'    => '1,336,000',
            'duration' => '۱۲ ماهه',
            'volume'   => '۲۴۰۰ گیگابایت بین‌الملل',
            'speed'    => '۸ مگابیت',
            'customer' => 'جدید',
            'callback' => 'fest_offer_12M2400G'
        ],
        [
            'title'    => 'پرحجم',
            'price'    => '874,000',
            'duration' => '۶ ماهه',
            'volume'   => '۱۲۰۰ گیگابایت بین‌الملل',
            'speed'    => '۸ مگابیت',
            'customer' => 'جدید',
            'callback' => 'fest_offer_6M1200G'
        ],
        [
            'title'    => 'اقتصادی',
            'price'    => '280,000',
            'duration' => '۳ ماهه',
            'volume'   => '۲۴۰ گیگابایت بین‌الملل',
            'speed'    => '۸ مگابیت',
            'customer' => 'جدید',
            'callback' => 'fest_offer_3M240G'
        ]
    ];

    // ساخت پیام اصلی جشنواره
    $msg  = "🎉 <b>سرویس‌های ویژه جشنواره پاییز ۱۴۰۴</b> 🎉\n\n";
    $msg .= "با انتخاب هر یک از طرح‌های زیر می‌توانید در جشنواره آسیاتک شرکت کنید:\n\n";

    $keyboard_buttons = [];
    $first = true;

    foreach ($offers as $offer) {
        if (!$first) {
            $msg .= "---------------------------------------\n";
        }

        $msg .= "⭐ <b>{$offer['title']}</b>\n";
        $msg .= "💰 قیمت: <b>{$offer['price']} تومان</b>\n";
        $msg .= "📅 مدت: {$offer['duration']}\n";
        $msg .= "🌐 حجم: {$offer['volume']}\n";
        $msg .= "⚡ سرعت: {$offer['speed']}\n";
        $msg .= "👥 مشترک: {$offer['customer']}\n\n";

        // دکمه اختصاصی هر سرویس
        $keyboard_buttons[] = [
            ['text' => "✅ ثبت‌نام سرویس {$offer['title']}", 'callback_data' => $offer['callback']]
        ];

        $first = false;
    }

    // دکمه بازگشت به منوی اصلی
    $keyboard_buttons[] = [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'main_menu']];

    $inline_keyboard = [
        'inline_keyboard' => $keyboard_buttons
    ];

    // ارسال پیام
    sendMessage($token, $chat_id, $msg, $inline_keyboard, 'HTML');
}

?>
