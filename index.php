<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
|--------------------------------------------------------------------------
| ССЫЛКИ
|--------------------------------------------------------------------------
*/

$csvUrl =
"https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();

$scriptUrl =
"https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

/*
|--------------------------------------------------------------------------
| СОХРАНЕНИЕ ИМЕНИ
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ch = curl_init($scriptUrl);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS,
        http_build_query($_POST));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_exec($ch);

    curl_close($ch);

    sleep(1);

    header("Location: /");

    exit;
}

/*
|--------------------------------------------------------------------------
| ЗАГРУЗКА CSV
|--------------------------------------------------------------------------
*/

$ch = curl_init($csvUrl);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$data = curl_exec($ch);

curl_close($ch);

if (!$data || trim($data) == "") {
    $rows = [];
} else {

    $rows = array_map(
        'str_getcsv',
        preg_split("/\r\n|\n|\r/", trim($data))
    );
}

/*
|--------------------------------------------------------------------------
| ПК
|--------------------------------------------------------------------------
*/

$newDevices = [];

$knownDevices = [];

$usedIps = [];

/*
|--------------------------------------------------------------------------
| ЧТЕНИЕ CSV
|--------------------------------------------------------------------------
*/

foreach ($rows as $index => $row) {

    if (count($row) < 3)
        continue;

    $ip =
        isset($row[1]) ? trim($row[1]) : '';

    // пропуск заголовков

    if ($ip == "ip")
        continue;

    // защита от дублей

    if (isset($usedIps[$ip]))
        continue;

    $usedIps[$ip] = true;

    $time =
        isset($row[0]) ? trim($row[0]) : '';

    $status =
        isset($row[2]) ? trim($row[2]) : 'offline';

    $name =
        isset($row[3]) ? trim($row[3]) : '';

    $device = [
        'time'   => $time,
        'status' => $status,
        'name'   => $name
    ];

    // новые ПК

    if (empty($name)) {

        $newDevices[$ip] = $device;

    } else {

        $knownDevices[$ip] = $device;
    }
}

?>

<!DOCTYPE html>

<html lang="ru">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>PC Panel</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#020617;
    color:white;
    font-family:Arial;
}

.container{
    max-width:1200px;
}

.title{
    font-size:32px;
    font-weight:bold;
    margin-bottom:30px;
}

.section-title{
    font-size:24px;
    margin-top:40px;
    margin-bottom:20px;
}

.device-card{

    background:#0f172a;

    border:1px solid #1e293b;

    border-radius:18px;

    padding:20px;

    transition:0.2s;
}

.device-card:hover{

    transform:translateY(-3px);

    border-color:#3b82f6;
}

.device-name{

    font-size:22px;

    font-weight:bold;

    margin-bottom:15px;
}

.device-info{

    color:#cbd5e1;

    margin-bottom:8px;
}

.online{

    color:#22c55e;

    font-weight:bold;
}

.offline{

    color:#ef4444;

    font-weight:bold;
}

.custom-input{

    background:#111827 !important;

    color:white !important;

    border:1px solid #334155 !important;

    border-radius:10px !important;

    padding:12px !important;
}

.custom-input::placeholder{

    color:#94a3b8 !important;
}

.custom-input:focus{

    background:#111827 !important;

    color:white !important;

    border-color:#3b82f6 !important;

    box-shadow:none !important;
}

.btn-save{

    background:#2563eb;

    border:none;

    border-radius:10px;

    padding:10px;

    width:100%;

    color:white;

    font-weight:bold;
}

.btn-save:hover{

    background:#1d4ed8;
}

.btn-delete{

    background:#dc2626;

    border:none;

    border-radius:10px;

    padding:10px;

    width:100%;

    color:white;

    font-weight:bold;
}

.btn-delete:hover{

    background:#b91c1c;
}

.empty-box{

    background:#111827;

    border-radius:14px;

    padding:20px;

    color:#94a3b8;
}

</style>

</head>

<body>

<div class="container py-5">

<div class="title">
🖥 PC Control Panel
</div>

<!-- НОВЫЕ ПК -->

<div class="section-title">
🆕 Новые ПК
</div>

<?php if(empty($newDevices)): ?>

<div class="empty-box">
Нет новых устройств
</div>

<?php else: ?>

<div class="row g-4">

<?php foreach($newDevices as $ip => $d): ?>

<div class="col-md-4">

<div class="device-card">

<div class="device-name">
⚠️ Новый ПК
</div>

<div class="device-info">
<b>IP:</b>
<?= htmlspecialchars($ip) ?>
</div>

<div class="device-info">
<b>Статус:</b>

<span class="<?= $d['status'] == 'online'
? 'online'
: 'offline' ?>">

<?= htmlspecialchars($d['status']) ?>

</span>

</div>

<form method="POST" class="mt-4">

<input
type="hidden"
name="update_name"
value="1">

<input
type="hidden"
name="ip"
value="<?= htmlspecialchars($ip) ?>">

<input
type="text"
name="name"
class="form-control custom-input mb-3"
placeholder="Введите имя ПК"
required>

<button class="btn-save">
Сохранить
</button>

</form>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<!-- ИЗВЕСТНЫЕ ПК -->

<div class="section-title">
💻 Известные ПК
</div>

<?php if(empty($knownDevices)): ?>

<div class="empty-box">
Нет известных устройств
</div>

<?php else: ?>

<div class="row g-4">

<?php foreach($knownDevices as $ip => $d): ?>

<div class="col-md-4">

<div class="device-card">

<div class="device-name">
<?= htmlspecialchars($d['name']) ?>
</div>

<div class="device-info">
<b>IP:</b>
<?= htmlspecialchars($ip) ?>
</div>

<div class="device-info">

<b>Статус:</b>

<span class="<?= $d['status'] == 'online'
? 'online'
: 'offline' ?>">

<?= $d['status'] == 'online'
? '🟢 Online'
: '🔴 Offline' ?>

</span>

</div>

<div class="device-info">
<b>Последний сигнал:</b><br>
<?= htmlspecialchars($d['time']) ?>
</div>

<form method="POST" class="mt-4">

<input
type="hidden"
name="delete_name"
value="1">

<input
type="hidden"
name="ip"
value="<?= htmlspecialchars($ip) ?>">

<button class="btn-delete">
Удалить имя
</button>

</form>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>

</body>

</html>
