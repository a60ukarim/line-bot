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

        // تعيين أول مستخدم كأدمن أساسي لو الملف فارغ
        if (empty($admin_list)) {
            $admin_list[] = $userId;
            file_put_contents($admin_file, $userId);
        }

        switch ($baseCommand) {
            case 'help':
                $responseText = "Menu Help:\n\n" .
                               "Gadmin:\n\n" .
                               " » c\n" .
                               " » kickbans\n" .
                               " » u\n" .
                               " » rname\n" .
                               " » setadmin\n" .
                               " » deladmin\n" .
                               " » help";
                break;

            case 'setadmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "Not Authorized: Only Admins can use this command.";
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
                        $responseText = "DONE SET THIS USER AS ADMIN";
                    } else {
                        $responseText = "This user is already an admin.";
                    }
                } else {
                    $responseText = "Usage: .setadmin @Mention";
                }
                break;

            case 'deladmin':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "Not Authorized: Only Admins can use this command.";
                    break;
                }

                $targetUser = "";
                if (isset($event['message']['mention']['mentions'][0]['userId'])) {
                    $targetUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (!empty($targetUser)) {
                    if ($targetUser === $userId) {
                        $responseText = "You cannot remove yourself from admin list.";
                        break;
                    }

                    if (($key = array_search($targetUser, $admin_list)) !== false) {
                        unset($admin_list[$key]);
                        file_put_contents($admin_file, implode(',', $admin_list));
                        $responseText = "DONE REMOVED THIS USER FROM ADMINS";
                    } else {
                        $responseText = "This user is not an admin.";
                    }
                } else {
                    $responseText = "Usage: .deladmin @Mention";
                }
                break;

            case 'c':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "Access Denied.";
                    break;
                }
                $current_bans = trim(file_get_contents($ban_file));
                $deleted_count = empty($current_bans) ? 0 : count(explode(',', $current_bans));
                file_put_contents($ban_file, ""); 
                $responseText = "DONE CLEAR " . $deleted_count . " USER'S FROM BAN.";
                break;

            case 'u':
                $checkUser = $userId;
                if (isset($event['message']['mention']['mentions'][0]['userId'])) {
                    $checkUser = $event['message']['mention']['mentions'][0]['userId'];
                }

                if (in_array($checkUser, $admin_list)) {
                    $responseText = "User Rank: ADMIN / Active.";
                } else {
                    $responseText = "User Rank: Member / Not Banned.";
                }
                break;

            case 'rname':
                if (!in_array($userId, $admin_list)) {
                    $responseText = "Access Denied.";
                    break;
                }
                $newName = trim(preg_replace('/^\.?rname/i', '', $userMessage));
                
                if (!empty($newName)) {
                    file_put_contents($name_file, $newName);
                    $responseText = "Bot name changed to: " . $newName;
                } else {
                    $current_name = file_get_contents($name_file);
                    $responseText = "Bot Current Name: " . $current_name;
                }
                break;

            case 'kickbans':
                $current_bans = trim(file_get_contents($ban_file));
                if (empty($current_bans)) {
                    $responseText = "No banned users to kick.";
                } else {
                    $responseText = "Starting kickbans process...";
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
