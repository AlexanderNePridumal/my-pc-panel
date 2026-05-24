<?php
// Ссылка на твою опубликованную таблицу в формате CSV
// В таблице: Файл -> Поделиться -> Опубликовать в интернете -> Выбрать "Значения, разделенные запятыми (.csv)"
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?gid=0&single=true&output=csv"; 

$data = file_get_contents($csvUrl);
$rows = array_map('str_getcsv', explode("\n", $data));
array_shift($rows); // Убираем заголовок таблицы

// Фильтруем данные, чтобы оставить только последнее состояние каждого ПК
$pcs = [];
foreach ($rows as $row) {
    if (count($row) >= 3) {
        $pcs[$row[1]] = $row[2]; // [1] - pc_id, [2] - status
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { background: #0f172a; color: white; font-family: sans-serif; padding: 20px; }
        .card { background: #1e293b; padding: 15px; margin: 10px; border-radius: 8px; display: inline-block; }
    </style>
</head>
<body>
    <h1>Панель компьютеров (Google Sheets)</h1>
    <?php foreach($pcs as $id => $status): ?>
        <div class='card'>
            <h3>PC: <?= htmlspecialchars($id) ?></h3>
            <p>Статус: <b><?= htmlspecialchars($status) ?></b></p>
        </div>
    <?php endforeach; ?>
</body>
</html>
