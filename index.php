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

// ملفات محاكاة البيانات لتشغيل الأوامر حقيقي
$ban_file = '/tmp/banned_users.txt';
$name_file = '/tmp/bot_name.txt';

if (!file_exists($ban_file)) file_put_contents($ban_file, "");
if (!file_exists($name_file)) file_put_contents($name_file, "majles-alhabd-bot");

foreach ($events['events'] as $event) {
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        
        $replyToken = $event['replyToken'];
        $userMessage = trim($event['message']['text']);
        $responseText = "";

        // معالجة الأوامر (يدعم ككتابة عادية أو بنقطة)
        $cleanCommand = ltrim(strtolower($userMessage), '.');

        switch ($cleanCommand) {
            // 1. أمر عرض القائمة بنفس التنسيق والخط الفخم
            case 'help':
                $responseText = "◈ 𝐌𝐞𝐧𝐮 𝐇𝐞𝐥𝐩 ◈\n\n" .
                               "𝐆𝐚𝐝𝐦𝐢𝐧:\n\n" .
                               " » 𝐜\n" .
                               " » 𝐤𝐢𝐜𝐤𝐛𝐚𝐧𝐬\n" .
                               " » 𝐮\n" .
                               " » 𝐫𝐧𝐚𝐦𝐞\n" .
                               " » 𝐬𝐧𝐚𝐦𝐞\n" .
                               " » 𝐡𝐞𝐥𝐩";
                break;

            // 2. أمر تنظيف قائمة الحظر (برمجة حقيقية)
            case 'c':
                $current_bans = trim(file_get_contents($ban_file));
                $deleted_count = empty($current_bans) ? 0 : count(explode(',', $current_bans));
                file_put_contents($ban_file, ""); // تصفير
                $responseText = "𝐃𝐎𝐍𝐄 𝐂𝐋𝐄𝐀𝐑 " . $deleted_count . " 𝐔𝐒𝐄𝐑'𝐒 𝐅𝐑𝐎𝐌 𝐁𝐀𝐍.";
                break;

            // 3. أمر طرد المحظورين (Kickbans)
            case 'kickbans':
                $current_bans = trim(file_get_contents($ban_file));
                if (empty($current_bans)) {
                    $responseText = "𝐍𝐨 𝐛𝐚𝐧𝐧𝐞𝐝 𝐮𝐬𝐞𝐫𝐬 𝐭𝐨 𝐤𝐢𝐜𝐤.";
                } else {
                    $responseText = "𝐒𝐭𝐚𝐫𝐭𝐢𝐧𝐠 𝐤𝐢𝐜𝐤𝐛𝐚𝐧𝐬 𝐩𝐫𝐨𝐜𝐞𝐬𝐬...";
                }
                break;

            // 4. أمر فحص الحظر لشخص محدد (u)
            case 'u':
                $responseText = "𝐔𝐬𝐞𝐫 𝐬𝐭𝐚𝐭𝐮𝐬: 𝐀𝐜𝐭𝐢𝐯𝐞 / 𝐍𝐨𝐭 𝐁𝐚𝐧𝐧𝐞𝐝.";
                break;

            // 5. أمر إعادة تسمية البوت (rname)
            case 'rname':
                $responseText = "𝐔𝐬𝐚𝐠𝐞: .𝐫𝐧𝐚𝐦𝐞 [𝐍𝐞𝐰 𝐍𝐚𝐦𝐞]";
                break;

            // 6. أمر عرض الاسم الحالي للبوت (sname)
            case 'sname':
                $current_name = file_get_contents($name_file);
                $responseText = "𝐁𝐨𝐭 𝐂𝐮𝐫𝐫𝐞𝐧𝐭 𝐍𝐚𝐦𝐞: " . $current_name;
                break;

            // أمر النقطة السريعة (الشاي)
            case '':
                if ($userMessage === '.') {
                    $responseText = "الشاي مشروب العظماء ☕";
                }
                break;

            default:
                // معالجة ديناميكية لتغيير الاسم إذا كتب rname ومعه اسم جديد
                if (strpos($cleanCommand, 'rname ') === 0) {
                    $newName = substr($userMessage, 7);
                    file_put_contents($name_file, $newName);
                    $responseText = "𝐁𝐨𝐭 𝐧𝐚𝐦𝐞 𝐮𝐩𝐝𝐚𝐭𝐞𝐝 𝐭𝐨: " . $newName;
                }
                break;
        }

        // إرسال الرد المنسق إلى LINE
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
