<?php
$url = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv";

// Используем cURL для получения данных
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
curl_close($ch);

// Парсим CSV строки
$rows = explode("\n", trim($data));
$devices = [];

// Обрабатываем строки (берем последние 10 записей)
foreach ($rows as $row) {
    $cols = str_getcsv($row);
    if(count($cols) >= 3) {
        $devices[$cols[1]] = [ // Ключ — это IP, чтобы не дублировать
            'ip' => $cols[1],
            'time' => $cols[0],
            'status' => $cols[2]
        ];
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
        <h2 class="mb-4">Список активных устройств</h2>
        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th>IP Адрес</th>
                    <th>Последний сигнал</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($devices as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['ip']) ?></td>
                    <td><?= htmlspecialchars($d['time']) ?></td>
                    <td><span class="badge bg-success"><?= htmlspecialchars($d['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
