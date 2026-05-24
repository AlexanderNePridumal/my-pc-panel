<?php
// Твои данные уже вставлены
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv" . "&t=" . time();
$scriptUrl = "https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec"; // <--- ВСТАВЬ СЮДА СВОЮ ССЫЛКУ ИЗ GOOGLE APPS SCRIPT

if (isset($_GET['action']) && $_GET['action'] == 'set_name') {
    $postData = http_build_query(['update_name' => '1', 'ip' => $_POST['ip'], 'name' => $_POST['name']]);
    $ch = curl_init($scriptUrl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    header("Location: /"); exit;
}

$ch = curl_init($csvUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
curl_close($ch);

$rows = explode("\n", trim($data));
$devices = [];
foreach ($rows as $row) {
    $cols = str_getcsv($row);
    if(count($cols) >= 3) {
        $devices[$cols[1]] = ['ip' => $cols[1], 'time' => $cols[0], 'status' => $cols[2], 'name' => $cols[3] ?? 'Нет имени'];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель Управления</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #0f172a; color: white; }</style>
</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">Список устройств</h2>
        <table class="table table-dark">
            <tr><th>Имя</th><th>IP</th><th>Последний сигнал</th><th>Действие</th></tr>
            <?php foreach($devices as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['name']) ?></td>
                <td><?= htmlspecialchars($d['ip']) ?></td>
                <td><?= htmlspecialchars($d['time']) ?></td>
                <td>
                    <form method="POST" action="?action=set_name" class="d-flex">
                        <input type="hidden" name="ip" value="<?= $d['ip'] ?>">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Имя">
                        <button class="btn btn-sm btn-primary">OK</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
