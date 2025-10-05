<?php
$token = getenv("BOT_TOKEN"); // توکن از Environment Variables
$update = json_decode(file_get_contents("php://input"), true);

$chat_id = $update['message']['chat']['id'] ?? null;
$text    = trim($update['message']['text'] ?? '');

require_once "menu.php";

if ($text == "/start") {
    sendMainMenu($chat_id, $token);
} else {
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => "برای شروع، دستور /start را بزنید."
    ]));
}
?>


