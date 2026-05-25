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
            "error" => "Invalid JSON",
            "raw" => $res
        ];
    }

    return $json;
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $ip = trim($_POST["ip"] ?? "");

    if ($action === "set_name") {

        $name = trim($_POST["name"] ?? "");

        $postData = http_build_query([
            "action" => "set_name",
            "ip" => $ip,
            "name" => $name
        ]);

        $ch = curl_init($api);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        curl_exec($ch);

        curl_close($ch);

        $message = "Имя отправлено";
        $messageType = "success";
    }

    if ($action === "delete") {

        $postData = http_build_query([
            "action" => "delete",
            "ip" => $ip
        ]);

        $ch = curl_init($api);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        curl_exec($ch);

        curl_close($ch);

        $message = "Устройство удалено";
        $messageType = "success";
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
font-size:13px;
color:#9ca3af;
}

.container{
padding:20px;
display:grid;
grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
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
margin-bottom:15px;
}

.row{
margin-top:10px;
word-break:break-all;
}

.label{
font-size:13px;
color:#9ca3af;
}

.value{
margin-top:3px;
}

.online{
color:#22c55e;
font-weight:bold;
}

input{
width:100%;
padding:10px;
margin-top:15px;
background:#0b1220;
border:1px solid #374151;
border-radius:10px;
color:white;
outline:none;
}

button{
width:100%;
padding:10px;
margin-top:10px;
border:none;
border-radius:10px;
cursor:pointer;
font-weight:bold;
transition:.2s;
}

.saveBtn{
background:#2563eb;
color:white;
}

.saveBtn:hover{
background:#1d4ed8;
}

.deleteBtn{
background:#dc2626;
color:white;
}

.deleteBtn:hover{
background:#b91c1c;
}

.message{
margin:20px;
padding:15px;
border-radius:12px;
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
font-size:13px;
color:#93c5fd;
}

.errorBox{
margin:20px;
padding:15px;
border-radius:12px;
background:#450a0a;
border:1px solid #ef4444;
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
Live updating...
</div>

</div>

<?php if($message): ?>

<div class="message <?=$messageType?>">
<?=$message?>
</div>

<?php endif; ?>

<?php if(isset($devices["error"])): ?>

<div class="errorBox">

<b>API ERROR</b>

<br><br>

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
name="action"
value="set_name"
>

<input
type="hidden"
name="ip"
value="<?=htmlspecialchars($d["ip"] ?? "")?>"
>

<input
type="text"
name="name"
placeholder="Введите имя"
required
>

<button
type="submit"
class="saveBtn"
>
Сохранить имя
</button>

<div class="loading">
Отправка...
</div>

</form>

<form method="POST">

<input
type="hidden"
name="action"
value="delete"
>

<input
type="hidden"
name="ip"
value="<?=htmlspecialchars($d["ip"] ?? "")?>"
>

<button
type="submit"
class="deleteBtn"
onclick="return confirm('Удалить устройство?')"
>
Удалить
</button>

</form>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<script>

async function loadDevices()
{
    try
    {
        const response =
            await fetch(window.location.href + "?t=" + Date.now());

        const html =
            await response.text();

        const parser =
            new DOMParser();

        const doc =
            parser.parseFromString(
                html,
                "text/html"
            );

        const newContainer =
            doc.querySelector(".container");

        const currentContainer =
            document.querySelector(".container");

        if(newContainer && currentContainer)
        {
            currentContainer.innerHTML =
                newContainer.innerHTML;

            currentContainer.style.opacity = "0.7";

            setTimeout(() => {
                currentContainer.style.opacity = "1";
            }, 200);
        }

        const refresh =
            document.querySelector(".refresh");

        if(refresh)
        {
            refresh.innerText =
                "Updated: " +
                new Date().toLocaleTimeString();
        }
    }
    catch(err)
    {
        console.log(err);
    }
}

function sendForm(form)
{
    const button =
        form.querySelector("button");

    const loading =
        form.querySelector(".loading");

    button.disabled = true;

    button.innerText = "Отправка...";

    loading.style.display = "block";

    return true;
}

loadDevices();

setInterval(loadDevices, 5000);

</script>

</body>
</html>
