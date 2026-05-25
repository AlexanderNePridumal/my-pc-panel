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
        return [
            "error" => curl_error($ch)
        ];
    }

    curl_close($ch);

    $json = json_decode($res, true);

    if (!is_array($json)) {
        return [
            "error" => "API returned invalid JSON",
            "raw" => $res
        ];
    }

    return $json;
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $ip = trim($_POST["ip"] ?? "");
    $name = trim($_POST["name"] ?? "");

    if (!$ip) {

        $message = "IP/HWID отсутствует";
        $messageType = "error";

    } else {

        $postData = http_build_query([
            "action" => "set_name",
            "ip" => $ip,
            "name" => $name
        ]);

        $ch = curl_init($api);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {

            $message = "Ошибка отправки: " . curl_error($ch);
            $messageType = "error";

        } else {

            $message = "Имя успешно отправлено";
            $messageType = "success";

        }

        curl_close($ch);
    }
}

$devices = getData($api);

?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<title>PC Panel</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

*{
box-sizing:border-box;
}

body{
margin:0;
background:#0b1220;
color:white;
font-family:system-ui;
}

.topbar{
padding:20px;
border-bottom:1px solid #1f2937;
display:flex;
justify-content:space-between;
align-items:center;
}

.title{
font-size:24px;
font-weight:bold;
}

.refresh{
color:#9ca3af;
font-size:13px;
}

.container{
padding:20px;
display:grid;
grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
gap:20px;
}

.card{
background:linear-gradient(145deg,#111827,#0f172a);
border:1px solid #1f2937;
border-radius:16px;
padding:18px;
transition:.2s;
box-shadow:0 10px 25px rgba(0,0,0,.35);
}

.card:hover{
transform:translateY(-3px);
border-color:#374151;
}

.name{
font-size:20px;
font-weight:700;
margin-bottom:10px;
}

.row{
margin-top:6px;
word-break:break-all;
}

.label{
color:#9ca3af;
font-size:13px;
}

.value{
margin-top:2px;
}

.online{
color:#22c55e;
font-weight:bold;
}

.offline{
color:#ef4444;
font-weight:bold;
}

input{
width:100%;
margin-top:15px;
padding:10px;
background:#0b1220;
border:1px solid #374151;
border-radius:10px;
color:white;
outline:none;
}

button{
width:100%;
margin-top:10px;
padding:10px;
border:none;
border-radius:10px;
background:#2563eb;
color:white;
cursor:pointer;
font-weight:bold;
transition:.2s;
}

button:hover{
background:#1d4ed8;
}

.message{
margin:20px;
padding:14px;
border-radius:10px;
font-weight:bold;
}

.success{
background:#14532d;
border:1px solid #22c55e;
}

.error{
background:#450a0a;
border:1px solid #ef4444;
}

.loading{
display:none;
margin-top:10px;
text-align:center;
color:#93c5fd;
font-size:13px;
}

.errorBox{
margin:20px;
padding:15px;
background:#450a0a;
border:1px solid #ef4444;
border-radius:12px;
white-space:pre-wrap;
}

</style>

</head>

<body>

<div class="topbar">

<div class="title">
PC Panel
</div>

<div class="refresh">
Auto refresh: 30s
</div>

</div>

<?php if($message): ?>

<div class="message <?=$messageType?>">
<?=$message?>
</div>

<?php endif; ?>

<?php if(isset($devices["error"])): ?>

<div class="errorBox">

<b>Ошибка API:</b>

<?=htmlspecialchars($devices["error"])?>

<?php if(isset($devices["raw"])): ?>

<hr>

<?=htmlspecialchars($devices["raw"])?>

<?php endif; ?>

</div>

<?php else: ?>

<div class="container">

<?php foreach($devices as $d): ?>

<div class="card">

<div class="name">
<?=htmlspecialchars($d["name"] ?? "No name")?>
</div>

<div class="row">
<div class="label">HWID / IP</div>
<div class="value">
<?=htmlspecialchars($d["ip"] ?? "-")?>
</div>
</div>

<div class="row">
<div class="label">Status</div>
<div class="value online">
<?=htmlspecialchars($d["status"] ?? "offline")?>
</div>
</div>

<div class="row">
<div class="label">Last Seen</div>
<div class="value">
<?=htmlspecialchars($d["time"] ?? "-")?>
</div>
</div>

<form method="POST" onsubmit="return sendForm(this)">

<input
type="hidden"
name="ip"
value="<?=htmlspecialchars($d["ip"] ?? "")?>"
>

<input
type="text"
name="name"
placeholder="Введите имя ПК"
required
>

<button type="submit">
Сохранить имя
</button>

<div class="loading">
Отправка...
</div>

</form>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<script>

function sendForm(form)
{
    const button = form.querySelector("button");
    const loading = form.querySelector(".loading");

    button.disabled = true;
    button.innerText = "Отправка...";

    loading.style.display = "block";

    return true;
}

setTimeout(() => {
    location.reload();
}, 30000);

</script>

</body>
</html>
