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
        $grouped = [];
        foreach ($prices[$duration] as $srv) {
            $grouped[$srv['speed']][] =
                "{$srv['internal']} داخلی | {$srv['international']} بین‌الملل — " .
                number_format($srv['price']) . " تومان";
        }

        $msg = "💰 لیست قیمت {$duration}:\n";
        foreach ($grouped as $speed => $list) {
            $msg .= "\n⚡ سرعت {$speed} مگابیت:\n" . implode("\n", $list) . "\n";
        }

        editMessageText($token, $chat_id, $message_id, $msg);
    } else {
        editMessageText($token, $chat_id, $message_id, "⛔ داده‌ای یافت نشد.");
    }
}
?>
