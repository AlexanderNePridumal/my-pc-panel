<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$api = "https://script.google.com/macros/s/AKfycbxii7c1LApf-QkOCjg9aN7hgygQBa9Pjt0aAwO-y_r--wzunh0jMS6VoS1rA5gWiW2r/exec";

function getData($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        return ["error" => curl_error($ch)];
    }

    curl_close($ch);

    $json = json_decode($res, true);

    if (!is_array($json)) {
        return ["error" => "Invalid JSON", "raw" => $res];
    }

    return $json;
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $ip = trim($_POST["ip"] ?? "");

    function send($api, $data) {
        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_exec($ch);
        curl_close($ch);
    }

    if ($action === "set_name") {

        send($api, [
            "action" => "set_name",
            "ip" => $ip,
            "name" => $_POST["name"] ?? ""
        ]);

        $message = "Имя обновлено";
        $messageType = "success";
    }

    if ($action === "delete") {

        send($api, [
            "action" => "delete",
            "ip" => $ip
        ]);

        $message = "Устройство удалено";
        $messageType = "danger";
    }

    if ($action === "shutdown") {

        send($api, [
            "action" => "shutdown",
            "ip" => $ip
        ]);

        $message = "Команда выключения отправлена";
        $messageType = "warning";
    }
}

$devices = getData($api);

function statusColor($status)
{
    return $status === "online" ? "green" : "gray";
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PC Control Panel</title>

<style>

body{
margin:0;
font-family:system-ui;
background:#0a0f1a;
color:#e5e7eb;
}

.header{
padding:18px 25px;
display:flex;
justify-content:space-between;
align-items:center;
border-bottom:1px solid #1f2937;
background:#0f172a;
position:sticky;
top:0;
}

.header h1{
font-size:18px;
margin:0;
}

.badge{
font-size:12px;
color:#9ca3af;
}

.container{
padding:25px;
display:grid;
grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
gap:18px;
}

.card{
background:linear-gradient(145deg,#111827,#0b1220);
border:1px solid #1f2937;
border-radius:14px;
padding:16px;
transition:.2s;
}

.card:hover{
transform:translateY(-3px);
border-color:#334155;
}

.top{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:12px;
}

.name{
font-size:16px;
font-weight:700;
}

.status{
font-size:12px;
padding:4px 10px;
border-radius:999px;
}

.green{background:#14532d;color:#4ade80;}
.gray{background:#1f2937;color:#9ca3af;}

.row{
margin-top:10px;
font-size:13px;
color:#cbd5e1;
}

.label{
color:#64748b;
font-size:11px;
}

input{
width:100%;
padding:10px;
margin-top:10px;
border-radius:10px;
border:1px solid #334155;
background:#0b1220;
color:white;
outline:none;
}

button{
width:100%;
padding:9px;
margin-top:8px;
border:none;
border-radius:10px;
cursor:pointer;
font-weight:600;
transition:.2s;
}

.primary{background:#2563eb;color:white;}
.primary:hover{background:#1d4ed8;}

.danger{background:#dc2626;color:white;}
.danger:hover{background:#b91c1c;}

.warning{background:#f59e0b;color:black;}
.warning:hover{background:#d97706;}

.message{
margin:15px 25px;
padding:12px;
border-radius:10px;
font-weight:600;
}

.success{background:#14532d;}
.dangerMsg{background:#450a0a;}
.warningMsg{background:#3b2f0a;}

.small{
font-size:11px;
color:#94a3b8;
margin-top:6px;
word-break:break-word;
}

.apps{
font-size:11px;
color:#a5b4fc;
max-height:60px;
overflow:hidden;
}

</style>
</head>

<body>

<div class="header">
    <h1>PC Control Dashboard</h1>
    <div class="badge">Live monitoring</div>
</div>

<?php if($message): ?>
<div class="message <?= $messageType === "success" ? "success" : ($messageType === "warning" ? "warningMsg" : "dangerMsg") ?>">
    <?=htmlspecialchars($message)?>
</div>
<?php endif; ?>

<div class="container">

<?php if(isset($devices["error"])): ?>

<div style="padding:20px;color:red;">
    API ERROR: <?=htmlspecialchars($devices["error"])?>
</div>

<?php else: ?>

<?php foreach($devices as $d): ?>

<?php
$status = $d["status"] ?? "offline";
$color = statusColor($status);
$apps = json_decode($d["apps"] ?? "[]", true);
?>

<div class="card">

<div class="top">
    <div class="name"><?=htmlspecialchars($d["name"] ?? "Unknown PC")?></div>
    <div class="status <?=$color?>"><?=$status?></div>
</div>

<div class="row">
    <div class="label">IP</div>
    <?=htmlspecialchars($d["ip"] ?? "-")?>
</div>

<div class="row">
    <div class="label">Last seen</div>
    <?=htmlspecialchars($d["time"] ?? "-")?>
</div>

<div class="row">
    <div class="label">Programs</div>
    <div class="apps">
        <?=is_array($apps) ? htmlspecialchars(implode(", ", array_slice($apps,0,10))) : "-"?>
    </div>
</div>

<form method="POST">
    <input type="hidden" name="action" value="set_name">
    <input type="hidden" name="ip" value="<?=htmlspecialchars($d["ip"] ?? "")?>">
    <input type="text" name="name" placeholder="Set name" required>
    <button class="primary">Save</button>
</form>

<form method="POST">
    <input type="hidden" name="action" value="shutdown">
    <input type="hidden" name="ip" value="<?=htmlspecialchars($d["ip"] ?? "")?>">
    <button class="warning" onclick="return confirm('Shutdown this PC?')">Shutdown</button>
</form>

<form method="POST">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="ip" value="<?=htmlspecialchars($d["ip"] ?? "")?>">
    <button class="danger" onclick="return confirm('Delete device?')">Delete</button>
</form>

<div class="small">HWID/IP: <?=htmlspecialchars($d["ip"] ?? "")?></div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

</body>
</html>
