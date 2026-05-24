<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();

$scriptUrl = "https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ch = curl_init($scriptUrl);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS,
        http_build_query($_POST));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);

    curl_close($ch);

    header("Location: /");

    exit;
}

$data = @file_get_contents($csvUrl);

if (!$data) {
    die("Ошибка загрузки CSV");
}

$rows = array_map(
    'str_getcsv',
    preg_split("/\r\n|\n|\r/", trim($data))
);

$devices = [];

foreach ($rows as $row) {

    if(count($row) >= 4) {

        $ip = trim($row[1]);

        $devices[$ip] = [
            'time' => $row[0],
            'status' => $row[2],
            'name' => trim($row[3]),
            'img' => isset($row[4]) ? trim($row[4]) : ''
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>

<meta charset="UTF-8">

<title>Панель ПК</title>

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

<h2 class="mb-4">Устройства</h2>

<table class="table table-dark table-bordered">

<thead>

<tr>
<th>Имя</th>
<th>IP</th>
<th>Статус</th>
<th>Время</th>
<th>Скрин</th>
<th>Действие</th>
</tr>

</thead>

<tbody>

<?php foreach($devices as $ip => $d): ?>

<tr>

<td>
<?= htmlspecialchars(
$d['name'] ?: 'Неизвестный'
) ?>
</td>

<td><?= htmlspecialchars($ip) ?></td>

<td><?= htmlspecialchars($d['status']) ?></td>

<td><?= htmlspecialchars($d['time']) ?></td>

<td>
<?= !empty($d['img']) ? "📸 Есть" : "—" ?>
</td>

<td>

<?php if(empty($d['name'])): ?>

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
placeholder="Имя">

<button class="btn btn-success btn-sm">
Сохранить
</button>

</form>

<?php else: ?>

<form method="POST">

<input type="hidden"
name="delete_name"
value="1">

<input type="hidden"
name="ip"
value="<?= htmlspecialchars($ip) ?>">

<button class="btn btn-danger btn-sm">
Удалить
</button>

</form>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</body>

</html>
