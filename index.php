<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?gid=0&single=true&output=csv";

$data = @file_get_contents($csvUrl);
if ($data === false) { 
    die("Ошибка загрузки данных."); 
}

// Разбиваем таблицу на строки
$lines = explode("\n", $data);
$pcs = [];

foreach ($lines as $line) {
    // Пробуем разделить строку и запятыми, и точками с запятой
    $row = str_getcsv($line, ",");
    if (count($row) <= 1) {
        $row = str_getcsv($line, ";");
    }
    
    $row = array_map('trim', $row);
    
    // Ищем, в какой ячейке лежит имя ПК (например, содержит "PC-")
    foreach ($row as $index => $cell) {
        if (stripos($cell, 'PC-') === 0) { // Если ячейка начинается с "PC-"
            $pc_id = $cell;
            
            // Статусом будет следующая ячейка. Если она пустая — напишем "online"
            $status = (isset($row[$index + 1]) && !empty($row[$index + 1])) ? $row[$index + 1] : "online";
            
            $pcs[$pc_id] = $status;
            break; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления ПК</title>
    <style>
        body { background: #0f172a; color: white; font-family: system-ui, -apple-system, sans-serif; padding: 30px; }
        h1 { font-size: 24px; margin-bottom: 20px; color: #f8fafc; }
        .grid { display: flex; flex-wrap: wrap; gap: 15px; }
        .card { background: #1e293b; padding: 20px; border-radius: 12px; width: 220px; border: 1px solid #334155; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card h3 { margin: 0 0 10px 0; color: #94a3b8; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; }
        .status { font-size: 18px; font-weight: bold; color: #10b981; display: flex; align-items: center; gap: 8px; }
        .status::before { content: ""; display: inline-block; width: 10px; height: 10px; background: #10b981; border-radius: 50px; }
        .empty { color: #64748b; }
    </style>
</head>
<body>

    <h1>Мониторинг систем</h1>

    <div class="grid">
        <?php if (empty($pcs)): ?>
            <p class="empty">Система ожидает подключения устройств...</p>
        <?php else: ?>
            <?php foreach($pcs as $id => $status): ?>
                <div class='card'>
                    <h3>Устройство</h3>
                    <div style="font-size: 20px; font-weight: 700; margin-bottom: 15px;"><?= htmlspecialchars($id) ?></div>
                    <div class='status'><?= htmlspecialchars($status) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
