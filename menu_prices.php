<?php
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

        // ساخت متن پیام
        $msg = "💰 لیست قیمت سرویس‌های {$duration}:\n\n";
        $first = true;
        foreach ($grouped as $speed => $list) {
            if (!$first) {
                $msg .= "\n───────────────\n\n";
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

        editMessageTextFormatted($token, $chat_id, $message_id, $msg, 'HTML', $keyboard);
    } else {
        editMessageText($token, $chat_id, $message_id, "⛔ داده‌ای یافت نشد.");
    }
}

function editMessageTextFormatted($token, $chat_id, $message_id, $text, $parse_mode='HTML', $keyboard = null) {
    $data = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $text,
        'parse_mode' => $parse_mode
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    }
    file_get_contents("https://api.telegram.org/bot{$token}/editMessageText?" . http_build_query($data));
}
?>
