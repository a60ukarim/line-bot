<?php
// 1. LINE API Credentials
$access_token = 'OmdtK1rjzmRUwifPmUnxKFD9BRJFpnR2Z5Mprmvp7Uhi6DPm+3fQOz0tn2YJDDedK+46IZCwDbfYKR4iiVVJxy2wo5UfIG5rk9X+aULuvsVXeArsSYrWjUqyel3PSHb1GaoxI+KR/py6yXoQjA6rngdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'd9e581b830c67224104eb22bb0c5f518';

// 2. Automatic Database Connection using Environment Variables
// سيقوم بقراءة البيانات تلقائياً من إعدادات سيرفر Render
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'khedni_m3k'; // اسم قاعدة بيانات مشروعك

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// 3. Parse Incoming Webhook Data from LINE
$content = file_get_contents('php://input');
$events = json_decode($content, true);

if (is_null($events['events'])) {
    echo "OK - API Backend is running.";
    exit();
}

foreach ($events['events'] as $event) {
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        
        $replyToken = $event['replyToken'];
        $userMessage = trim($event['message']['text']);
        $responseText = "";

        // 4. Real Functional Command Handler
        switch (strtolower($userMessage)) {
            case '.':
                $responseText = "الشاي مشروب العظماء ☕";
                break;

            // تنفيذ حقيقي لحذف الحظر من قاعدة البيانات
            case '.c':
                if ($conn) {
                    $count_query = "SELECT COUNT(*) as total FROM banned_users";
                    $result = mysqli_query($conn, $count_query);
                    $row = mysqli_fetch_assoc($result);
                    $deleted_count = $row['total'];

                    $delete_query = "DELETE FROM banned_users";
                    mysqli_query($conn, $delete_query);

                    $responseText = "DONE CLEAR " . $deleted_count . " USERS FROM BAN.";
                } else {
                    $responseText = "ERROR: Database connection failed.";
                }
                break;
                
            case '.help':
                $responseText = "=== Available Commands ===\n" .
                               ".help - Show this commands list\n" .
                               ".status - Check system status\n" .
                               ".ping - Test bot responsiveness\n" .
                               ".c - Clear ban list\n" .
                               ". - Dot command shortcut\n" .
                               "========================";
                break;
                
            case '.status':
                $db_status = $conn ? "Connected" : "Disconnected";
                $memory = round(memory_get_usage() / 1024 / 1024, 2) . " MB";
                
                $responseText = "System Status: ONLINE\n" .
                               "Database: " . $db_status . "\n" .
                               "Memory Usage: " . $memory . "\n" .
                               "All services operating normally.";
                break;

            case '.ping':
                $responseText = "Pong! 🏓 Connection is stable.";
                break;
                
            default:
                break;
        }

        // 5. Send Response back to LINE API using cURL
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
if ($conn) mysqli_close($conn);
echo "OK";
?>
