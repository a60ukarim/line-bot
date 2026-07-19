<?php
// 1. LINE API Credentials
$access_token = 'OmdtK1rjzmRUwifPmUnxKFD9BRJFpnR2Z5Mprmvp7Uhi6DPm+3fQOz0tn2YJDDedK+46IZCwDbfYKR4iiVVJxy2wo5UfIG5rk9X+aULuvsVXeArsSYrWjUqyel3PSHb1GaoxI+KR/py6yXoQjA6rngdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'd9e581b830c67224104eb22bb0c5f518';

// 2. Parse Incoming Webhook Data from LINE
$content = file_get_contents('php://input');
$events = json_decode($content, true);

if (is_null($events['events'])) {
    echo "OK";
    exit();
}

// ملفات تخزين البيانات على السيرفر
$ban_file = '/tmp/banned_users.txt';
$name_file = '/tmp/bot_name.txt';
$admin_file = '/tmp/admins.txt';

if (!file_exists($ban_file)) file_put_contents($ban_file, "");
if (!file_exists($name_file)) file_put_contents($name_file, "majles-alhabd-bot");
if (!file_exists($admin_file)) file_put_contents($admin_file, "");

foreach ($events['events'] as $event) {
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        
        $replyToken = $event['replyToken'];
        $userId = $event['source']['userId']; 
        $userMessage = trim($event['message']['text']);
        $responseText = "";

        // تنظيف النص وتحويله لأحرف صغيرة
        $lowerMessage = mb_strtolower($userMessage, 'UTF-8');
        $cleanCommand = ltrim($lowerMessage, '.');

        // تحديد الأمر بشكل مرن
        $baseCommand = "";
        if (strpos($cleanCommand, 'help') === 0) $baseCommand = 'help';
        elseif (strpos($cleanCommand, 'setadmin') === 0) $baseCommand = 'setadmin';
        elseif (strpos($cleanCommand, 'kickbans') === 0) $baseCommand = 'kickbans';
        elseif (strpos($cleanCommand, 'rname') === 0) $baseCommand = 'rname';
        elseif (strpos($cleanCommand, 'sname') === 0) $baseCommand = 'sname';
        elseif (strpos($cleanCommand, 'c') === 0) $baseCommand = 'c';
        elseif (strpos($cleanCommand, 'u') === 0) $baseCommand = 'u';
        elseif ($userMessage === '.') $baseCommand = 'dot';

        // قراءة قائمة المشرفين
        $current_admins = file_get_contents($admin_file);
        $admin_list = !empty($current_admins) ? explode(',', $current_admins) : [];

        // تعيين المرسل الأول كأدمن أساسي لو الملف فارغ
        if (empty($admin_list)) {
            $admin_list[] = $userId;
            file_put_contents($admin_file, $userId);
        }

        switch ($baseCommand) {
            case 'help':
                $responseText = "◈ 𝐌𝐞𝐧𝐮 𝐇𝐞𝐥𝐩 ◈\n\n" .
                               "𝐆𝐚𝐝𝐦𝐢𝐧:\n\n" .
                               " » 𝐜\n" .
                               " » 𝐤𝐢𝐜𝐤𝐛𝐚𝐧𝐬\n" .
                               " » 𝐮\n" .
                               " » 𝐫𝐧𝐚𝐦𝐞\n" .
                               " » 𝐬𝐧𝐚𝐦𝐞\n" .
                               " » 𝐬𝐞𝐭𝐚𝐝𝐦𝐢𝐧\n" .
                               " » 𝐡𝐞𝐥𝐩";
                break;

            case 'setadmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐍𝐨𝐭 𝐀𝐮𝐭𝐡𝐨𝐫𝐢𝐳𝐞𝐝: 𝐎𝐧𝐥𝐲 𝐀𝐝𝐦𝐢𝐧𝐬 𝐜𝐚𝐧 𝐮𝐬𝐞 𝐭𝐡𝐢𝐬 𝐜𝐨𝐦𝐦𝐚𝐧𝐝.";
                    break;
                }

                // سحب الـ User ID المضمون للمنشن من الهيكل الصحيح لـ LINE API
                $targetUser = "";
                if (isset($event['message']['mention']['mentions']) && is_array($event['message']['mention']['mentions'])) {
                    $targetUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (!empty($targetUser)) {
                    if (!in_array($targetUser, $admin_list)) {
                        $admin_list[] = $targetUser;
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "👑 𝐃𝐎𝐍𝐄 𝐒𝐄𝐓 𝐓𝐇𝐈𝐒 𝐔𝐒𝐄𝐑 𝐀𝐒 𝐀𝐃𝐌𝐈𝐍";
                    } else {
                        $responseText = "𝐓𝐡𝐢𝐬 𝐮𝐬𝐞𝐫 𝐢𝐬 𝐚𝐥𝐫𝐞𝐚𝐝𝐲 𝐚𝐧 𝐚𝐝𝐦𝐢𝐧.";
                    }
                } else {
                    $responseText = "⚠️ 𝐔𝐬𝐚𝐠𝐞: .𝐬𝐞𝐭𝐚𝐝𝐦𝐢𝐧 @𝐌𝐞𝐧𝐭𝐢𝐨𝐧";
                }
                break;

            case 'c':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐀𝐜𝐜𝐞𝐬𝐬 𝐃𝐞𝐧𝐢𝐞𝐝.";
                    break;
                }
                $current_bans = trim(file_get_contents($ban_file));
                $deleted_count = empty($current_bans) ? 0 : count(explode(',', $current_bans));
                file_put_contents($ban_file, ""); 
                $responseText = "𝐃𝐎𝐍𝐄 𝐂𝐋𝐄𝐀𝐑 " . $deleted_count . " 𝐔𝐒𝐄𝐑'𝐒 𝐅𝐑𝐎𝐌 𝐁𝐀𝐍.";
                break;

            case 'u':
                $checkUser = $userId;
                if (isset($event['message']['mention']['mentions']) && is_array($event['message']['mention']['mentions'])) {
                    $checkUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (in_array($checkUser, $admin_list)) {
                    $responseText = "🛡️ 𝐔𝐬𝐞𝐫 𝐑𝐚𝐧𝐤: 𝐀𝐃𝐌𝐈𝐍 / 𝐀𝐜𝐭𝐢𝐯𝐞.";
                } else {
                    $responseText = "👤 𝐔𝐬𝐞𝐫 𝐑𝐚𝐧𝐤: 𝐌𝐞𝐦𝐛𝐞𝐫 / 𝐍𝐨𝐭 𝐁𝐚𝐧𝐧𝐞𝐝.";
                }
                break;

            case 'sname':
                $current_name = file_get_contents($name_file);
                $responseText = "𝐁𝐨𝐭 𝐂𝐮𝐫𝐫𝐞𝐧𝐭 𝐍𝐚𝐦𝐞: " . $current_name;
                break;

            case 'rname':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐀𝐜𝐜𝐞𝐬𝐬 𝐃𝐞𝐧𝐢𝐞𝐝.";
                    break;
                }
                $newName = trim(preg_replace('/^\.?rname/i', '', $userMessage));
                if (!empty($newName)) {
                    file_put_contents($name_file, $newName);
                    $responseText = "⚙️ 𝐁𝐨𝐭 𝐧𝐚𝐦𝐞 𝐜𝐡𝐚𝐧𝐠𝐞𝐝 𝐭𝐨: " . $newName;
                } else {
                    $responseText = "𝐔𝐬𝐚𝐠𝐞: .𝐫𝐧𝐚𝐦𝐞 [𝐍𝐞𝐰 𝐍𝐚𝐦𝐞]";
                }
                break;

            case 'kickbans':
                $current_bans = trim(file_get_contents($ban_file));
                if (empty($current_bans)) {
                    $responseText = "𝐍𝐨 𝐛𝐚𝐧𝐧𝐞𝐝 👑 𝐮𝐬𝐞𝐫𝐬 𝐭𝐨 𝐤𝐢𝐜𝐤.";
                } else {
                    $responseText = "𝐒𝐭𝐚𝐫𝐭𝐢𝐧𝐠 𝐤𝐢𝐜𝐤𝐛𝐚𝐧𝐬 𝐩𝐫𝐨𝐜𝐞𝐬𝐬...";
                }
                break;

            case 'dot':
                $responseText = "الشاي مشروب العظماء ☕";
                break;
        }

        // إرسال الرد عبر سكريبت cURL لشيرفر LINE
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
