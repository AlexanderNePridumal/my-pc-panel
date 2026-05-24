
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();
$scriptUrl ="https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

$csvUrl =
"https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();

$scriptUrl =
"https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

/*
|--------------------------------------------------------------------------
| СОХРАНЕНИЕ / УДАЛЕНИЕ ИМЕНИ
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

    $response = curl_exec($ch);

    curl_close($ch);

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

if (!$data) {
    die("Ошибка загрузки CSV");
}

/*
|--------------------------------------------------------------------------
| ПАРСИНГ CSV
|--------------------------------------------------------------------------
*/

$rows = array_map(
    'str_getcsv',
    preg_split("/\r\n|\n|\r/", trim($data))
);

$newDevices = [];

$knownDevices = [];

foreach ($rows as $index => $row) {

    // пропускаем заголовки
    if ($index == 0)
        continue;

    if (count($row) < 4)
        continue;

    $ip = trim($row[1]);

    $device = [
        'time'   => trim($row[0]),
        'status' => trim($row[2]),
        'name'   => trim($row[3])
    ];

    // новые ПК без имени

    if (empty($device['name'])) {

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

body{
    background:#0f172a;
    color:white;
}

.table{
    color:white;
}

.card{
    background:#1e293b;
    border:none;
    border-radius:12px;
}

input{
    background:#0f172a !important;
    color:white !important;
    border:1px solid #334155 !important;
}

input::placeholder{
    color:#94a3b8 !important;
}

</style>

</head>

<body class="p-4">

<div class="container">

<h2 class="mb-4">
🖥 Новые ПК
</h2>

<?php if(empty($newDevices)): ?>

<div class="alert alert-success">
Новых ПК нет
</div>

<?php else: ?>

<div class="row g-3">

<?php foreach($newDevices as $ip => $d): ?>

<div class="col-md-4">

<div class="card p-3">

<h5 class="mb-3">
⚠️ Новый ПК
</h5>

<p>
<b>IP:</b>
<?= htmlspecialchars($ip) ?>
</p>

<p>
<b>Статус:</b>
<?= htmlspecialchars($d['status']) ?>
</p>

<form method="POST">

<input type="hidden"
name="update_name"
value="1">

<input type="hidden"
name="ip"
value="<?= htmlspecialchars($ip) ?>">

<input
type="text"
name="name"
class="form-control mb-3"
placeholder="Введите имя ПК"
required>

<button
class="btn btn-success w-100">

Сохранить

</button>

</form>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

<h2 class="mt-5 mb-4">
✅ Известные ПК
</h2>

<?php if(empty($knownDevices)): ?>

<div class="alert alert-warning">
Нет известных ПК
</div>

<?php else: ?>

<div class="row g-3">

<?php foreach($knownDevices as $ip => $d): ?>

<div class="col-md-4">

<div class="card p-3">

<h4>
<?= htmlspecialchars($d['name']) ?>
</h4>

<p class="mb-1">
<b>IP:</b>
<?= htmlspecialchars($ip) ?>
</p>

<p class="mb-1">

<b>Статус:</b>

<?php
echo $d['status'] == 'online'
    ? '🟢 Online'
    : '🔴 Offline';
?>

</p>

<p>
<b>Последний сигнал:</b><br>
<?= htmlspecialchars($d['time']) ?>
</p>

<form method="POST">

<input type="hidden"
name="delete_name"
value="1">

<input type="hidden"
name="ip"
value="<?= htmlspecialchars($ip) ?>">

<button
class="btn btn-danger btn-sm">

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
