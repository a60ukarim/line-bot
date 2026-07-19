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

// قراءة قائمة المشرفين والمحظورين أولاً لتبسيط العمليات
$current_admins = file_get_contents($admin_file);
$admin_list = !empty($current_admins) ? explode(',', $current_admins) : [];

$current_bans = file_get_contents($ban_file);
$ban_list = !empty($current_bans) ? explode(',', $current_bans) : [];

foreach ($events['events'] as $event) {
    
    // [ميزة تلقائية]: كشف محاولات الطرد من غير الأدمنز
    if ($event['type'] == 'memberLeft') {
        $chatId = isset($event['source']['groupId']) ? $event['source']['groupId'] : (isset($event['source']['roomId']) ? $event['source']['roomId'] : "");
        
        // التحقق من الشخص الذي غادر أو طُرد (إذا توفر بالحدث)
        if (isset($event['left']['members'][0]['userId'])) {
            $leftUser = $event['left']['members'][0]['userId'];
            
            // ملاحظة: LINE API في المجموعات العادية لا يرسل دائماً معرّف الفاعل (Actor) مباشرة لأسباب أمنية، 
            // ولكن في حال توفر أي معرّف مشبوه خارج قائمة الأدمنز يتم حظره فوراً.
            if (!in_array($leftUser, $admin_list) && !empty($leftUser)) {
                // إضافة العضو المشبوه أو المخرب للقائمة تلقائياً
                if (!in_array($leftUser, $ban_list)) {
                    $ban_list[] = $leftUser;
                    file_put_contents($ban_file, implode(',', $ban_list));
                }
            }
        }
    }

    // معالجة الرسائل والأوامر النصية
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        
        $replyToken = $event['replyToken'];
        $userId = $event['source']['userId']; 
        $chatId = isset($event['source']['groupId']) ? $event['source']['groupId'] : (isset($event['source']['roomId']) ? $event['source']['roomId'] : "");
        $userMessage = trim($event['message']['text']);
        $responseText = "";

        // تعيين المرسل الأول كأدمن أساسي (أنت) لو الملف فارغ تماماً
        if (empty($admin_list)) {
            $admin_list[] = $userId;
            file_put_contents($admin_file, $userId);
        }

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

        switch ($baseCommand) {
            case 'help':
                $responseText = "◈ 𝐌𝐞𝐧𝐮 𝐇𝐞𝐥𝐩 ◈\n\n" .
                               "𝐆𝐚𝐝𝐦𝐢𝐧:\n\n" .
                               " » 𝐜\n" .
                               " » 𝐤𝐢𝐜𝐤𝐛𝐚𝐧𝐬\n" .
                               " » 𝐮\n" .
                               " » 𝐫𝐧𝐚𝐦𝐞\n" .
                               " » 𝐬𝐞𝐭𝐚𝐝𝐦𝐢𝐧\n" .
                               " » 𝐝e<b>𝐥𝐚𝐝𝐦𝐢𝐧\n" .
                               " » 𝐡𝐞𝐥𝐩";
                break;

            case 'setadmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐍𝐨𝐭 𝐀𝐮𝐭𝐡𝐨𝐫𝐢𝐳𝐞𝐝: 𝐎𝐧𝐥𝐲 𝐀𝐝𝐦𝐢𝐧𝐬 𝐜𝐚𝐧 𝐮𝐬𝐞 𝐭𝐡𝐢𝐬 𝐜𝐨𝐦𝐦𝐚𝐧𝐝.";
                    break;
                }

                $targetUser = "";
                if (isset($event['message']['mention']['mentions'][0]['userId'])) {
                    $targetUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (!empty($targetUser)) {
                    if (!in_array($targetUser, $admin_list)) {
                        $admin_list[] = $targetUser;
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "👑 𝐃𝐎𝐍𝐄 𝐒block_𝐄𝐓 𝐓𝐇𝐈𝐒 𝐔𝐒𝐄𝐑 𝐀𝐒 𝐀𝐃𝐌𝐈block_𝐍";
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
                        $responseText = "❌ 𝐘𝐨𝐮 𝐜𝐚𝐧𝐧𝐨𝐭 𝐫𝐞𝐦𝐨𝐯𝐞 𝐲𝐨𝐮𝐫𝐬𝐞𝐥𝐟 𝐟𝐫𝐨𝐦 𝐚𝐝𝐦𝐢𝐧 𝐥𝐢𝐬𝐭.";
                        break;
                    }

                    if (($key = array_search($targetUser, $admin_list)) !== false) {
                        unset($admin_list[$key]);
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "🗑️ 𝐃𝐎𝐍𝐄 𝐑block_𝐄𝐌𝐎𝐕block_𝐄𝐃 𝐓𝐇block_𝐈𝐒 𝐔𝐒block_𝐄𝐑 𝐅𝐑block_𝐎𝐌 𝐀𝐃𝐌𝐈𝐍𝐒";
                    } else {
                        $responseText = "𝐓𝐡𝐢𝐬 𝐮𝐬𝐞𝐫 𝐢𝐬 𝐧𝐨𝐭 𝐚𝐧 𝐚𝐝𝐦𝐢𝐧.";
                    }
                } else {
                    $responseText = "⚠️ 𝐔𝐬𝐚𝐠𝐞: .𝐝e𝐥𝐚𝐝𝐦𝐢𝐧 @𝐌e𝐧𝐭𝐢o𝐧";
                }
                break;

            case 'c':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐀𝐜𝐜𝐞𝐬𝐬 𝐃block_𝐞𝐧𝐢block_block_𝐞𝐝.";
                    break;
                }
                $deleted_count = count($ban_list);
                file_put_contents($ban_file, ""); 
                $ban_list = [];
                // الـ deleted_count يطبع عادي بخط صغير طبيعي
                $responseText = "𝐃block_𝐎𝐍block_𝐄 𝐂𝐋𝐄block_𝐀𝐑 " . $deleted_count . " 𝐔𝐒block_𝐄𝐑'𝐒 𝐅block_𝐑𝐎𝐌 𝐁block_𝐀𝐍.";
                break;

            case 'u':
                $checkUser = $userId;
                if (isset($event['message']['mention']['mentions'][0]['userId'])) {
                    $checkUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (in_array($checkUser, $admin_list)) {
                    $responseText = "🛡️ 𝐔𝐬𝐞𝐫 𝐑block_𝐚𝐧𝐤: 𝐀𝐃block_𝐌block_𝐈𝐍 / 𝐀𝐜𝐭𝐢𝐯block_𝐞.";
                } else {
                    $responseText = "👤 𝐔𝐬𝐞𝐫 𝐑block_𝐚𝐧𝐤: 𝐌block_𝐞𝐦𝐛block_𝐞𝐫 / 𝐍block_𝐨𝐭 𝐁block_𝐚𝐧𝐧block_𝐞𝐝.";
                }
                break;

            case 'rname':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐀𝐜𝐜block_𝐞𝐬𝐬 𝐃block_𝐞𝐧𝐢block_block_block_𝐞𝐝.";
                    break;
                }
                $newName = trim(preg_replace('/^\.?rname/i', '', $userMessage));
                
                if (!empty($newName)) {
                    file_put_contents($name_file, $newName);
                    $responseText = "⚙️ 𝐁block_𝐨𝐭 𝐧block_𝐚𝐦block_𝐞 𝐜𝐡block_𝐚𝐧𝐠block_𝐞𝐝 𝐭block_𝐨: " . $newName;
                } else {
                    $current_name = file_get_contents($name_file);
                    $responseText = "🤖 𝐁block_𝐨𝐭 𝐂block_𝐮𝐫𝐫block_block_𝐞𝐧𝐭 𝐍block_𝐚𝐦block_𝐞: " . $current_name;
                }
                break;

            case 'kickbans':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "❌ 𝐍block_𝐨𝐭 𝐀block_𝐮𝐭𝐡block_𝐨𝐫𝐢𝐳block_𝐞𝐝.";
                    break;
                }

                if (empty($ban_list)) {
                    $responseText = "⚙️ 𝐍block_𝐨 𝐛block_𝐚𝐧𝐧block_block_𝐞𝐝 𝐮𝐬block_block_block_𝐞𝐫𝐬 𝐭block_𝐨 𝐤block_𝐢𝐜𝐤.";
                } else {
                    $responseText = "⚡ 𝐒𝐭block_𝐚𝐫𝐭𝐢block_𝐧𝐠 𝐤block_𝐢𝐜𝐤𝐛block_𝐚𝐧𝐬 𝐩𝐫block_𝐨𝐜block_block_𝐞𝐬𝐬...";
                    
                    // تنفيذ الطرد الفعلي لكل مستخدم في قائمة الحظر داخل الجروب الحالي
                    foreach ($ban_list as $bannedUser) {
                        if (!empty($bannedUser) && !empty($chatId)) {
                            $kickUrl = "https://api.line.me/v2/bot/group/{$chatId}/member/{$bannedUser}/kick";
                            if (isset($event['source']['roomId'])) {
                                $kickUrl = "https://api.line.me/v2/bot/room/{$chatId}/member/{$bannedUser}/kick";
                            }
                            
                            $kickCh = curl_init($kickUrl);
                            curl_setopt($kickCh, CURLOPT_CUSTOMREQUEST, "POST");
                            curl_setopt($kickCh, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($kickCh, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
                            curl_exec($kickCh);
                            curl_close($kickCh);
                        }
                    }
                    // تفريغ الملف بعد الانتهاء من طردهم جميعاً
                    file_put_contents($ban_file, "");
                    $ban_list = [];
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
