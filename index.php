<?php
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv" . "&cache=" . uniqid();
$scriptUrl = "https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

// Обработка смены имени
if (isset($_GET['action']) && $_GET['action'] == 'set_name') {
    $postData = http_build_query(['update_name' => '1', 'ip' => $_POST['ip'], 'name' => $_POST['name']]);
    $ch = curl_init($scriptUrl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    header("Location: /"); exit;
}

// Загрузка данных
$ch = curl_init($csvUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
curl_close($ch);

$rows = array_map('str_getcsv', explode("\n", trim($data)));
$named = [];   // ПК с именами
$unnamed = []; // ПК без имен

foreach ($rows as $row) {
    if(count($row) >= 3) {
        $ip = $row[1];
        $name = $row[3] ?? 'Неизвестный';
        $dataItem = ['ip' => $ip, 'time' => $row[0], 'status' => $row[2], 'name' => $name];
        
        if ($name === 'Неизвестный' || $name === '0' || empty($name)) {
            $unnamed[$ip] = $dataItem;
        } else {
            $named[$ip] = $dataItem;
        }
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
        <?php if (!empty($unnamed)): ?>
            <h2 class="text-danger">Требуется настройка:</h2>
            <table class="table table-dark mb-5">
                <tr><th>IP</th><th>Последний сигнал</th><th>Действие</th></tr>
                <?php foreach($unnamed as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['ip']) ?></td>
                    <td><?= htmlspecialchars($d['time']) ?></td>
                    <td>
                        <form method="POST" action="?action=set_name" class="d-flex">
                            <input type="hidden" name="ip" value="<?= $d['ip'] ?>">
                            <input type="text" name="name" class="form-control form-control-sm me-2" placeholder="Введите имя...">
                            <button class="btn btn-sm btn-primary">Присвоить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h2 class="text-success">Активные устройства:</h2>
        <table class="table table-dark">
            <tr><th>Имя</th><th>IP</th><th>Последний сигнал</th></tr>
            <?php foreach($named as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['name']) ?></td>
                <td><?= htmlspecialchars($d['ip']) ?></td>
                <td><?= htmlspecialchars($d['time']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
