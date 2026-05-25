<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ВСТАВЬТЕ ВАШ URL СЮДА:
$api = "https://script.google.com/macros/s/AKfycbxii7c1LApf-QkOCjg9aN7hgygQBa9Pjt0aAwO-y_r--wzunh0jMS6VoS1rA5gWiW2r/exec";

function getData($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $res = curl_exec($ch);

    if (curl_errno($ch))
    {
        return ["error" => curl_error($ch)];
    }

    curl_close($ch);

    $json = json_decode($res, true);

    if (!is_array($json))
    {
        return ["error" => "Invalid JSON", "raw" => $res];
    }

    return $json;
}

function send($api, $data)
{
    $ch = curl_init($api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_exec($ch);
    curl_close($ch);
}

function timeAgo($t)
{
    if (!$t) return "Never";
    $t = strtotime($t);
    $diff = time() - $t;

    if ($diff < 60) return $diff . " sec ago";
    if ($diff < 3600) return floor($diff/60) . " min ago";
    if ($diff < 86400) return floor($diff/3600) . " h ago";
    return floor($diff/86400) . " days ago";
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST")
{
    $action = $_POST["action"] ?? "";
    $device_id = trim($_POST["device_id"] ?? "");

    if ($action === "set_name")
    {
        send($api, [
            "action" => "set_name",
            "device_id" => $device_id,
            "name" => $_POST["name"] ?? ""
        ]);
    }

    if ($action === "shutdown" || $action === "stop_client" || $action === "delete")
    {
        send($api, [
            "action" => $action,
            "device_id" => $device_id
        ]);
    }
    
    // Обновляем страницу, чтобы избежать повторной отправки формы
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$devices = getData($api);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PC Dashboard</title>

<style>
body{ margin:0; font-family:system-ui; background:#0a0f1a; color:#e5e7eb; }
.header{ padding:16px 20px; background:#0f172a; border-bottom:1px solid #1f2937; position:sticky; top:0; display:flex; justify-content:space-between; }
.container{ padding:20px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:16px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:15px; }
.name{ font-weight:700; font-size:16px; }
.small{ font-size:11px; color:#94a3b8; }
.row{ margin-top:8px; font-size:13px; }
.apps{ font-size:11px; color:#a5b4fc; max-height:60px; overflow:hidden; }
input{ width:100%; margin-top:8px; padding:8px; border-radius:8px; border:1px solid #334155; background:#0b1220; color:white; box-sizing:border-box; }
button{ width:100%; margin-top:6px; padding:8px; border-radius:8px; border:none; cursor:pointer; }
.blue{ background:#2563eb; color:white; }
.red{ background:#dc2626; color:white; }
.orange{ background:#f59e0b; color:black; }
.status{ font-size:11px; padding:3px 8px; border-radius:999px; display:inline-block; }
.online{ background:#14532d; color:#4ade80; }
.offline{ background:#1f2937; color:#9ca3af; }
</style>
</head>

<body>

<div class="header">
    <div>PC Dashboard</div>
</div>

<div class="container">

<?php if(isset($devices["error"])): ?>
    <div style="color:red;"><?=htmlspecialchars($devices["error"])?></div>
<?php else: ?>

    <?php foreach($devices as $d): ?>
    <?php
        $last = strtotime($d["time"] ?? "");
        $diff = time() - $last;
        $status = ($diff < 60) ? "online" : "offline";
        $apps = json_decode($d["apps"] ?? "[]", true);
    ?>

    <div class="card">
        <div class="name">
            <?=htmlspecialchars($d["name"] ?? "Unknown")?>
        </div>

        <div class="status <?=$status?>">
            <?=$status?>
        </div>

        <div class="row">
            <b>IP:</b> <?=htmlspecialchars($d["ip"] ?? "")?>
        </div>

        <div class="row">
            <b>ID:</b> <span class="small"><?=htmlspecialchars($d["device_id"] ?? "")?></span>
        </div>

        <div class="row">
            <b>Last:</b> <?=timeAgo($d["time"] ?? "")?>
        </div>

        <div class="row apps">
            <?=is_array($apps) ? implode(", ", array_slice($apps,0,10)) : "-"?>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="set_name">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($d["device_id"] ?? "")?>">
            <input name="name" placeholder="New Name">
            <button class="blue">Save Name</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="stop_client">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($d["device_id"] ?? "")?>">
            <button class="orange" onclick="return confirm('Stop client process on this PC?')">Stop Client</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($d["device_id"] ?? "")?>">
            <button class="orange" onclick="return confirm('Shutdown this PC?')">Shutdown PC</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($d["device_id"] ?? "")?>">
            <button class="red" onclick="return confirm('Delete device from dashboard?')">Delete</button>
        </form>
    </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

</body>
</html>
