<?php
// توجه: تابع sendMessage در index.php تعریف شده و در اینجا قابل دسترسی است.
// توجه: تابع editMessageTextWithKeyboard در index.php تعریف شده و در اینجا قابل دسترسی است.

function showPriceDurations($token, $chat_id) {
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
    // در شروع، چون از دکمه منوی اصلی (Reply Keyboard) آمده، پیام جدید ارسال می‌شود.
    sendMessage($token, $chat_id, "📅 مدت زمان سرویس را انتخاب کنید:", $keyboard);
}

function sendPriceList($token, $chat_id, $message_id, $duration) {
    $prices = include __DIR__ . '/pricess.php';

    if (isset($prices[$duration])) {
        // گروه‌بندی بر اساس سرعت
        $grouped = [];
        foreach ($prices[$duration] as $srv) {
            $grouped[$srv['speed']][] =
                "{$srv['international']} بین‌الملل : " .
                number_format($srv['price']) . " تومان";
        }

        // ساخت متن پیام با فرمت HTML/Bold و جداکننده
        $msg = "💰 لیست قیمت سرویس‌های {$duration}:\n\n";
        $first = true;
        foreach ($grouped as $speed => $list) {
            if (!$first) {
                $msg .= "\n───────────────\n\n"; // خط جداکننده
            }
            $msg .= "<b>⚡ سرعت {$speed} مگابیت</b>\n";
            $msg .= implode("\n", $list) . "\n";
            $first = false;
        }

        // اضافه کردن دکمه تغییر مدت
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 تغییر مدت', 'callback_data' => 'change_duration']
                ]
            ]
        ];

        // پیام قبلی (انتخاب مدت) را با لیست قیمت جدید و دکمه 'تغییر مدت' ویرایش می‌کنیم.
        editMessageTextWithKeyboard($token, $chat_id, $message_id, $msg, $keyboard, 'HTML');
    } else {
        editMessageTextWithKeyboard($token, $chat_id, $message_id, "⛔ داده‌ای برای {$duration} یافت نشد.", null);
    }
}
?>
