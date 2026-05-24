<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$csvUrl =
"https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv" . time();

$scriptUrl =
"https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ch = curl_init($scriptUrl);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS,
        http_build_query($_POST));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);

    curl_close($ch);

    sleep(1);

    header("Location: /");

    exit;
}

$data = @file_get_contents($csvUrl);

if (!$data) {
    die("CSV пуст");
}

$rows = array_map(
    'str_getcsv',
    preg_split("/\r\n|\n|\r/", trim($data))
);

$newDevices = [];
$knownDevices = [];

foreach ($rows as $index => $row) {

    if ($index == 0)
        continue;

    if (count($row) < 4)
        continue;

    $ip = trim($row[1]);

    $device = [
        'time' => $row[0],
        'status' => $row[2],
        'name' => trim($row[3])
    ];

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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#0f172a;
    color:white;
}

.table{
    color:white;
}

</style>

</head>

<body class="p-4">

<div class="container">

<h2 class="mb-4">Новые ПК</h2>

<table class="table table-dark table-bordered">

<tr>
<th>IP</th>
<th>Статус</th>
<th>Имя</th>
</tr>

<?php foreach($newDevices as $ip => $d): ?>

<tr>

<td><?= htmlspecialchars($ip) ?></td>

<td><?= htmlspecialchars($d['status']) ?></td>

<td>

<form method="POST">

<input type="hidden"
name="update_name"
value="1">

<input type="hidden"
name="ip"
value="<?= htmlspecialchars($ip) ?>">

<input type="text"
name="name"
class="form-control mb-2"
placeholder="Имя ПК">

<button class="btn btn-success btn-sm">
Сохранить
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</table>

<h2 class="mt-5 mb-4">Известные ПК</h2>

<table class="table table-dark table-bordered">

<tr>
<th>Имя</th>
<th>IP</th>
<th>Статус</th>
<th>Последний сигнал</th>
</tr>

<?php foreach($knownDevices as $ip => $d): ?>

<tr>

<td><?= htmlspecialchars($d['name']) ?></td>

<td><?= htmlspecialchars($ip) ?></td>

<td>

<?php
echo $d['status'] == 'online'
    ? '🟢 Online'
    : '🔴 Offline';
?>

</td>

<td><?= htmlspecialchars($d['time']) ?></td>

</tr>

<?php endforeach; ?>

</table>

</div>

</body>
</html>
