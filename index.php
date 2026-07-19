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

        // تنظيف النص وتحويله لأحرف صغيرة للفحص
        $lowerMessage = mb_strtolower($userMessage, 'UTF-8');
        $cleanCommand = ltrim($lowerMessage, '.');

        // تحديد الأمر بشكل مرن ودقيق
        $baseCommand = "";
        if (strpos($cleanCommand, 'help') === 0) $baseCommand = 'help';
        elseif (strpos($cleanCommand, 'setadmin') === 0) $baseCommand = 'setadmin';
        elseif (strpos($cleanCommand, 'deladmin') === 0) $baseCommand = 'deladmin';
        elseif (strpos($cleanCommand, 'kickbans') === 0) $baseCommand = 'kickbans';
        elseif (strpos($cleanCommand, 'rname') === 0) $baseCommand = 'rname';
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
                               " » 𝐬𝐞𝐭𝐚𝐝𝐦𝐢𝐧\n" .
                               " » 𝐝e𝐥𝐚𝐝𝐦𝐢𝐧\n" .
                               " » 𝐡𝐞𝐥𝐩";
                break;

            case 'setadmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐍𝐨𝐭 𝐀𝐮𝐭𝐡𝐨𝐫𝐢𝐳𝐞𝐝: 𝐎𝐧𝐥𝐲 𝐀𝐝𝐦𝐢𝐧𝐬 𝐜𝐚𝐧 𝐮𝐬𝐞 𝐭𝐡𝐢𝐬 𝐜𝐨𝐦𝐦𝐚𝐧𝐝.";
                    break;
                }

                // سحب معرّف المستخدم المضمون من مصفوفة منشن LINE
                $targetUser = "";
                if (isset($event['message']['mention']['mentions'][0]['userId'])) {
                    $targetUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (!empty($targetUser)) {
                    if (!in_array($targetUser, $admin_list)) {
                        $admin_list[] = $targetUser;
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "👑 𝐃𝐎𝐍𝐄 𝐒𝐄𝐓 𝐓𝐇𝐈𝐒 𝐔𝐒𝐄𝐑 𝐀𝐒 𝐀𝐃𝐌class_𝐈𝐍";
                    } else {
                        $responseText = "𝐓𝐡𝐢𝐬 𝐮𝐬𝐞𝐫 𝐢𝐬 𝐚𝐥𝐫𝐞𝐚𝐝𝐲 𝐚𝐧 𝐚𝐝𝐦𝐢𝐧.";
                    }
                } else {
                    $responseText = "⚠️ 𝐔𝐬𝐚𝐠𝐞: .𝐬𝐞𝐭𝐚𝐝𝐦𝐢𝐧 @𝐌𝐞𝐧𝐭𝐢𝐨𝐧";
                }
                break;

            case 'deladmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐍𝐨𝐭 𝐀𝐮𝐭𝐡𝐨𝐫𝐢𝐳𝐞𝐝: 𝐎𝐧𝐥𝐲 𝐀𝐝𝐦𝐢𝐧𝐬 𝐜𝐚𝐧 𝐮𝐬𝐞 𝐭𝐡𝐢𝐬 𝐜𝐨𝐦𝐦𝐚𝐧𝐝.";
                    break;
                }

                $targetUser = "";
                if (isset($event['message']['mention']['mentions'][0]['userId'])) {
                    $targetUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (!empty($targetUser)) {
                    if ($targetUser === $userId) {
                        $responseText = "❌ 𝐘𝐨𝐮 𝐜𝐚𝐧𝐧𝐨𝐭 𝐫𝐞𝐦𝐨𝐯𝐞 𝐲𝐨𝐮𝐫𝐬𝐞𝐥ф 𝐟𝐫𝐨𝐦 𝐚𝐝𝐦𝐢𝐧 𝐥𝐢𝐬𝐭.";
                        break;
                    }

                    if (($key = array_search($targetUser, $admin_list)) !== false) {
                        unset($admin_list[$key]);
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "🗑️ 𝐃𝐎𝐍𝐄 𝐑𝐄𝐌class_𝐎𝐕𝐄𝐃 𝐓𝐇𝐈𝐒 𝐔𝐒class_𝐄𝐑 𝐅𝐑𝐎𝐌 𝐀𝐃𝐌class_𝐍𝐒";
                    } else {
                        $responseText = "𝐓𝐡𝐢𝐬 𝐮𝐬𝐞𝐫 𝐢𝐬 𝐧𝐨𝐭 𝐚𝐧 𝐚𝐝𝐦𝐢𝐧.";
                    }
                } else {
                    $responseText = "⚠️ 𝐔𝐬𝐚𝐠𝐞: .𝐝𝐞class_𝐥𝐚𝐝𝐦class_𝐢𝐧 @𝐌𝐞𝐧𝐭𝐢𝐨𝐧";
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
                $responseText = "𝐃𝐎𝐍𝐄 𝐂𝐋class_𝐄𝐀Ｒ " . $deleted_count . " 𝐔𝐒class_𝐄𝐑'𝐒 𝐅𝐑𝐎𝐌 𝐁class_𝐀𝐍.";
                break;

            case 'u':
                $checkUser = $userId;
                if (isset($event['message']['mention']['mentions'][0]['userId'])) {
                    $checkUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (in_array($checkUser, $admin_list)) {
                    $responseText = "🛡️ 𝐔𝐬𝐞𝐫 𝐑𝐚𝐧𝐤: 𝐀class_𝐀𝐃𝐌class_𝐈𝐍 / 𝐀𝐜𝐭𝐢𝐯𝐞.";
                } else {
                    $responseText = "👤 𝐔𝐬𝐞𝐫 𝐑𝐚𝐧𝐤: 𝐌𝐞𝐦𝐛class_𝐞𝐫 / 𝐍𝐨𝐭 𝐁class_𝐚𝐧𝐧class_𝐞𝐝.";
                }
                break;

            case 'rname':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐀𝐜𝐜class_𝐞𝐬𝐬 𝐃𝐞class_𝐧𝐢𝐞𝐝.";
                    break;
                }
                $newName = trim(preg_replace('/^\.?rname/i', '', $userMessage));
                
                if (!empty($newName)) {
                    file_put_contents($name_file, $newName);
                    $responseText = "⚙️ 𝐁𝐨𝐭 𝐧class_𝐚𝐦class_𝐞 𝐜𝐡class_𝐚𝐧𝐠class_𝐞𝐝 𝐭𝐨: " . $newName;
                } else {
                    $current_name = file_get_contents($name_file);
                    $responseText = "🤖 𝐁𝐨𝐭 𝐂𝐮𝐫𝐫class_𝐞𝐧𝐭 𝐍class_𝐚𝐦class_𝐞: " . $current_name;
                }
                break;

            case 'kickbans':
                $current_bans = trim(file_get_contents($ban_file));
                if (empty($current_bans)) {
                    $responseText = " can_ can_ can_𝐍𝐨 𝐛class_𝐚𝐧𝐧class_𝐞𝐝 𝐮𝐬class_𝐞𝐫𝐬 𝐭𝐨 𝐤class_𝐢𝐜𝐤.";
                } else {
                    $responseText = "𝐒𝐭class_𝐚𝐫𝐭class_𝐢𝐧𝐠 𝐤class_𝐢𝐜𝐤𝐛class_𝐚𝐧𝐬 𝐩𝐫𝐨𝐜class_class_𝐞𝐬𝐬...";
                }
                break;

            case 'dot':
                $responseText = "الشاي مشروب العظماء ☕";
                break;
        }

        // إرسال الرد
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
