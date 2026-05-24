<?php
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();
$data = @file_get_contents($csvUrl);
$rows = array_map('str_getcsv', explode("\n", trim($data)));

// Фильтруем уникальные IP (берем последнее состояние каждого)
$devices = [];
foreach ($rows as $row) {
    if(count($row) >= 2) {
        $ip = $row[1];
        // Если имени в 4-й колонке нет, ставим "Неизвестный"
        $name = isset($row[3]) && !empty($row[3]) ? $row[3] : "Неизвестный";
        $devices[$ip] = ['ip' => $ip, 'time' => $row[0], 'status' => $row[2], 'name' => $name];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#0f172a; color:white;}</style>
</head>
<body class="p-4">
    <div class="container">
        <table class="table table-dark">
            <thead><tr><th>Имя</th><th>IP</th><th>Последний сигнал</th><th>Статус</th></tr></thead>
            <tbody>
                <?php foreach($devices as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['ip']) ?></td>
                    <td><?= htmlspecialchars($d['time']) ?></td>
                    <td><?= htmlspecialchars($d['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
