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

$name_file = '/tmp/bot_name.txt';
$admin_file = '/tmp/admins.txt';

if (!file_exists($name_file)) file_put_contents($name_file, "majles-alhabd-bot");
if (!file_exists($admin_file)) file_put_contents($admin_file, "");

$current_admins = file_get_contents($admin_file);
$admin_list = !empty($current_admins) ? explode(',', $current_admins) : [];

foreach ($events['events'] as $event) {
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        
        $replyToken = $event['replyToken'];
        $userId = $event['source']['userId']; 
        $chatId = isset($event['source']['groupId']) ? $event['source']['groupId'] : (isset($event['source']['roomId']) ? $event['source']['roomId'] : "");
        $userMessage = trim($event['message']['text']);
        $responseText = "";

        if (empty($admin_list)) {
            $admin_list[] = $userId;
            file_put_contents($admin_file, $userId);
        }

        // تنظيف وفحص الأمر بشكل مرن (مع نقطة أو بدون نقطة، كابيتال أو سمول)
        $lowerMessage = mb_strtolower($userMessage, 'UTF-8');
        $cleanCommand = ltrim($lowerMessage, '.');

        $baseCommand = "";
        if (strpos($cleanCommand, 'help') === 0) $baseCommand = 'help';
        elseif (strpos($cleanCommand, 'setadmin') === 0) $baseCommand = 'setadmin';
        elseif (strpos($cleanCommand, 'deladmin') === 0) $baseCommand = 'deladmin';
        elseif (strpos($cleanCommand, 'kick') === 0) $baseCommand = 'kick';
        elseif (strpos($cleanCommand, 'rname') === 0) $baseCommand = 'rname';
        elseif (strpos($cleanCommand, 'u') === 0) $baseCommand = 'u';
        elseif ($userMessage === '.') $baseCommand = 'dot';

        switch ($baseCommand) {
            case 'help':
                $responseText = "◈ 𝐌𝐞𝐧𝐮 𝐇𝐞𝐥𝐩 ◈\n\n" .
                               "𝐆𝐚𝐝𝐦𝐢𝐧:\n\n" .
                               " » 𝐤𝐢𝐜𝐤\n" .
                               " » 𝐮\n" .
                               " » 𝐫𝐧𝐚𝐦𝐞\n" .
                               " » 𝐬𝐞𝐭𝐚𝐝𝐦𝐢𝐧\n" .
                               " » 𝐝𝐞𝐥𝐚𝐝𝐦𝐢𝐧\n" .
                               " » 𝐡𝐞𝐥𝐩";
                break;

            case 'setadmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐍𝐨𝐭 𝐀𝐮𝐭𝐡𝐨𝐫𝐢𝐳𝐞𝐝: 𝐎𝐧𝐥𝐲 𝐀𝐝𝐦𝐢𝐧𝐬 𝐜𝐚𝐧 𝐮𝐬𝐞 𝐭𝐡𝐢𝐬 𝐜𝐨𝐦𝐦𝐚𝐧𝐝.";
                    break;
                }
                
                // طريقة مرنة ومجرّبة لجلب الـ userId من المنشن
                $targetUser = "";
                if (isset($event['message']['mention']['mentions'])) {
                    foreach ($event['message']['mention']['mentions'] as $mention) {
                        if (isset($mention['userId'])) {
                            $targetUser = $mention['userId'];
                            break;
                        }
                    }
                }

                if (!empty($targetUser)) {
                    if (!in_array($targetUser, $admin_list)) {
                        $admin_list[] = $targetUser;
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "👑 𝐃𝐎𝐍𝐄 𝐒𝐄𝐓 𝐓𝐇block_𝐈𝐒 𝐔𝐒block_𝐄block_𝐑 𝐀𝐒 𝐀block_𝐃𝐌𝐈𝐍";
                    } else {
                        $responseText = "𝐓𝐡𝐢𝐬 𝐮𝐬𝐞𝐫 𝐢𝐬 𝐚𝐥𝐫𝐞𝐚𝐝𝐲 𝐚𝐧 𝐚𝐝𝐦𝐢𝐧.";
                    }
                } else {
                    $responseText = "⚠️ 𝐔𝐬𝐚𝐠𝐞: .<b>𝐬𝐞𝐭𝐚𝐝𝐦𝐢𝐧</b> @𝐌𝐞𝐧𝐭𝐢𝐨𝐧";
                }
                break;

            case 'deladmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐍𝐨𝐭 𝐀𝐮𝐭𝐡𝐨𝐫𝐢𝐳𝐞𝐝: 𝐎𝐧𝐥𝐲 𝐀𝐝𝐦𝐢𝐧𝐬 𝐜𝐚𝐧 𝐮𝐬𝐞 𝐭𝐡𝐢𝐬 𝐜𝐨𝐦𝐦𝐚𝐧𝐝.";
                    break;
                }

                $targetUser = "";
                if (isset($event['message']['mention']['mentions'])) {
                    foreach ($event['message']['mention']['mentions'] as $mention) {
                        if (isset($mention['userId'])) {
                            $targetUser = $mention['userId'];
                            break;
                        }
                    }
                }

                if (!empty($targetUser)) {
                    if ($targetUser === $userId) {
                        $responseText = "❌ 𝐘𝐨𝐮 𝐜𝐚𝐧𝐧𝐨𝐭 𝐫𝐞𝐦𝐨𝐯𝐞 𝐲𝐨𝐮𝐫𝐬𝐞𝐥𝐟.";
                        break;
                    }
                    if (($key = array_search($targetUser, $admin_list)) !== false) {
                        unset($admin_list[$key]);
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "🗑️ 𝐃𝐎𝐍𝐄 𝐑𝐄𝐌block_𝐎𝐕𝐄𝐃 𝐓𝐇block_𝐈𝐒 𝐔𝐒block_𝐄𝐑 𝐅block_𝐑𝐎𝐌 𝐀𝐃𝐌block_𝐈𝐍𝐒";
                    } else {
                        $responseText = "𝐓𝐡𝐢𝐬 𝐮𝐬𝐞𝐫 𝐢𝐬 𝐧𝐨𝐭 𝐚𝐧 𝐚𝐝𝐦𝐢𝐧.";
                    }
                } else {
                    $responseText = "⚠️ 𝐔𝐬𝐚𝐠𝐞: .𝐝𝐞𝐥𝐚𝐝𝐦𝐢𝐧 @𝐌𝐞𝐧𝐭𝐢𝐨𝐧";
                }
                break;

            case 'kick':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐀𝐜𝐜𝐞𝐬𝐬 𝐃block_𝐞𝐧𝐢block_block_block_𝐞𝐝: 𝐘𝐨𝐮 𝐚𝐫𝐞 𝐧𝐨𝐭 𝐚𝐧 𝐀𝐝𝐦𝐢𝐧!";
                    break;
                }

                // إصلاح جذري: البحث الشامل داخل مصفوفة المنشن عن الـ userId الصحيح
                $targetUser = "";
                if (isset($event['message']['mention']['mentions']) && is_array($event['message']['mention']['mentions'])) {
                    foreach ($event['message']['mention']['mentions'] as $mention) {
                        if (isset($mention['userId']) && !empty($mention['userId'])) {
                            $targetUser = $mention['userId'];
                            break; // الخروج فور العثور على أول ID صحيح
                        }
                    }
                }

                if (!empty($targetUser)) {
                    if (in_array($targetUser, $admin_list)) {
                        $responseText = "🛡️ 𝐘𝐨𝐮 𝐜𝐚𝐧𝐧𝐨𝐭 𝐤𝐢𝐜𝐤 𝐚𝐧𝐨𝐭𝐡𝐞𝐫 𝐀𝐝𝐦𝐢𝐧.";
                        break;
                    }

                    // تنفيذ الطرد الفعلي عبر الـ API
                    $kickUrl = "https://api.line.me/v2/bot/group/{$chatId}/member/{$targetUser}/kick";
                    if (isset($event['source']['roomId'])) {
                        $kickUrl = "https://api.line.me/v2/bot/room/{$chatId}/member/{$targetUser}/kick";
                    }
                    
                    $kickCh = curl_init($kickUrl);
                    curl_setopt($kickCh, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($kickCh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($kickCh, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
                    curl_exec($kickCh);
                    curl_close($kickCh);

                    $responseText = "⚡ 𝐃𝐎𝐍𝐄 𝐊𝐈𝐂𝐊𝐄𝐃 𝐓𝐇𝐈𝐒 𝐌𝐄𝐌𝐁block_𝐄𝐑";
                } else {
                    $responseText = "⚠️ 𝐔𝐬𝐚𝐠𝐞: .𝐤𝐢𝐜𝐤 @𝐌e𝐧𝐭𝐢o𝐧";
                }
                break;

            case 'u':
                $targetUser = "";
                if (isset($event['message']['mention']['mentions'])) {
                    foreach ($event['message']['mention']['mentions'] as $mention) {
                        if (isset($mention['userId'])) {
                            $targetUser = $mention['userId'];
                            break;
                        }
                    }
                }
                $checkUser = !empty($targetUser) ? $targetUser : $userId;

                if (in_array($checkUser, $admin_list)) {
                    $responseText = "🛡️ 𝐔𝐬𝐞𝐫 𝐑𝐚𝐧𝐤: 𝐀𝐃𝐌𝐈𝐍 / 𝐀𝐜𝐭𝐢𝐯𝐞.";
                } else {
                    $responseText = "👤 𝐔𝐬𝐞𝐫 𝐑𝐚𝐧𝐤: 𝐌𝐞𝐦𝐛𝐞𝐫.";
                }
                break;

            case 'rname':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐀𝐜𝐜block_𝐞𝐬𝐬 𝐃block_𝐞𝐧block_𝐢block_𝐞block_𝐝.";
                    break;
                }
                $newName = trim(preg_replace('/^\.?rname/i', '', $userMessage));
                if (!empty($newName)) {
                    file_put_contents($name_file, $newName);
                    $responseText = "⚙️ 𝐁𝐨𝐭 𝐧𝐚𝐦𝐞 𝐜𝐡𝐚𝐧𝐠𝐞𝐝.";
                } else {
                    $current_name = file_get_contents($name_file);
                    $responseText = "🤖 𝐁𝐨𝐭 𝐂𝐮𝐫𝐫𝐞𝐧𝐭 𝐍𝐚𝐦𝐞: " . $current_name;
                }
                break;

            case 'dot':
                $responseText = "الشاي مشروب العظماء ☕";
                break;
        }

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
