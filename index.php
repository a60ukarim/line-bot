<?php
// 1. LINE API Credentials
$access_token = 'OmdtK1rjzmRUwifPmUnxKFD9BRJFpnR2Z5Mprmvp7Uhi6DPm+3fQOz0tn2YJDDedK+46IZCwDbfYKR4iiVVJxy2wo5UfIG5rk9X+aULuvsVXeArsSYrWjUqyel3PSHb1GaoxI+KR/py6yXoQjA6rngdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'd9e581b830c67224104eb22bb0c5f518';

// 2. Parse Incoming Webhook Data from LINE
$content = file_get_contents('php://input');
$events = json_decode($content, true);

if (is_null($events['events'])) {
    echo "OK - API Backend is running.";
    exit();
}

// ملف محلي لتخزين الحظر
$ban_file = '/tmp/banned_users.txt';
if (!file_exists($ban_file)) {
    file_put_contents($ban_file, "");
}

foreach ($events['events'] as $event) {
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        
        $replyToken = $event['replyToken'];
        $userMessage = trim($event['message']['text']);
        $responseText = "";

        // 3. Functional Command Handler
        switch (strtolower($userMessage)) {
            case '.':
                $responseText = "الشاي مشروب العظماء ☕";
                break;

            case '.c':
                $current_bans = trim(file_get_contents($ban_file));
                if (empty($current_bans)) {
                    $deleted_count = 0;
                } else {
                    $banned_array = explode(',', $current_bans);
                    $deleted_count = count($banned_array);
                }
                file_put_contents($ban_file, "");
                $responseText = "DONE CLEAR " . $deleted_count . " USERS FROM BAN.";
                break;
                
            // القائمة الفخمة الجديدة بخط Unicode مزخرف ومميز
            case '.help':
                $responseText = "╭━━━  𝔖𝔜𝔖𝔗𝔈𝔐 ℭ𝔒𝔐𝔐𝔒𝔑𝔇𝔖  ━━━╮\n\n" .
                               " 📌  [ .help ]\n" .
                               " ╰─▸ 𝘚𝘩𝘰𝘸 𝘵𝘩𝘪𝘴 𝘱𝘳𝘦𝘮𝘪𝘶𝘮 𝘮𝘦𝘯𝘶.\n\n" .
                               " ⚡  [ .status ]\n" .
                               " ╰─▸ 𝘊𝘩𝘦𝘤𝘬 𝘳𝘦𝘢𝘭-𝘵𝘪𝘮𝘦 𝘴𝘺𝘴𝘵𝘦𝘮 𝘩𝘦𝘢𝘭𝘵𝘩.\n\n" .
                               " 📡  [ .ping ]\n" .
                               " ╰─▸ 𝘛𝘦𝘴𝘵 𝘭𝘢𝘵𝘦𝘯𝘤𝘺 & 𝘳𝘦𝘴𝘱𝘰𝘯𝘴𝘪𝘷𝘦𝘯𝘦𝘴𝘴.\n\n" .
                               " 🛡️  [ .c ]\n" .
                               " ╰─▸ 𝘞𝘪𝘱𝘦 𝘢𝘭𝘭 𝘣𝘢𝘯𝘯𝘦𝘥 𝘶𝘴𝘦𝘳𝘴 𝘪𝘯𝘴𝘵𝘢𝘯𝘵𝘭𝘺.\n\n" .
                               " ☕  [ . ]\n" .
                               " ╰─▸ 𝘛𝘩𝘦 𝘭𝘦𝘨𝘦𝘯𝘥𝘢𝘳𝘺 𝘥𝘰𝘵 𝘴𝘩𝘰𝘳𝘵𝘤𝘶𝘵.\n\n" .
                               "╰━━━━━━━━━━━━━━━━━━━━━━╯";
                break;
                
            case '.status':
                $memory = round(memory_get_usage() / 1024 / 1024, 2) . " MB";
                $php_version = phpversion();
                
                $responseText = "System Status: ONLINE 🚀\n" .
                               "Engine: PHP " . $php_version . "\n" .
                               "Memory Usage: " . $memory . "\n" .
                               "Environment: Docker (Free Instance)\n" .
                               "All background tasks running smoothly.";
                break;

            case '.ping':
                $responseText = "Pong! 🏓 Connection is stable.";
                break;
                
            default:
                break;
        }

        // 4. Send Response back to LINE API using cURL
        if (!empty($responseText)) {
            $url = 'https://api.line.me/v2/bot/message/reply';
            $data = [
                'replyToken' => $replyToken,
                'messages' => [['type' => 'text', 'text' => $responseText]]
            ];
            
            $post = json_encode($data);
            $headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
echo "OK";
?>
