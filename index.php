<?php
// 1. LINE API Credentials
$access_token = 'OmdtK1rjzmRUwifPmUnxKFD9BRJFpnR2Z5Mprmvp7Uhi6DPm+3fQOz0tn2YJDDedK+46IZCwDbfYKR4iiVVJxy2wo5UfIG5rk9X+aULuvsVXeArsSYrWjUqyel3PSHb1GaoxI+KR/py6yXoQjA6rngdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'd9e581b830c67224104eb22bb0c5f518';

// 2. Parse Incoming Webhook Data from LINE
$content = file_get_contents('php://input');
$events = json_decode($content, true);

// If the page is opened in a browser directly, show a health check message
if (is_null($events['events'])) {
    echo "OK - API Backend is running.";
    exit();
}

foreach ($events['events'] as $event) {
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        
        $replyToken = $event['replyToken'];
        $userMessage = trim($event['message']['text']);
        $responseText = "";

        // 3. Command Handler (Switch Case)
        switch (strtolower($userMessage)) {
            case '.help':
                $responseText = "=== Available Commands ===\n" .
                               ".help - Show this commands list\n" .
                               ".status - Check system status\n" .
                               ".ping - Test bot responsiveness\n" .
                               "========================";
                break;
                
            case '.status':
                $responseText = "System Status: ONLINE\nVersion: 1.0.0\nAll services are running smoothly.";
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
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $responseText
                    ]
                ]
            ];
            
            $post = json_encode($data);
            $headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            curl_close($ch);
        }
    }
}
echo "OK";
?>
