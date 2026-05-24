<?php
$csvUrl = "ССЫЛКА_НА_CSV_ИЗ_GOOGLE" . "&t=" . time(); // Замени на свою
$scriptUrl = "ССЫЛКА_НА_ВАШ_SCRIPT_EXEC"; // Замени на ссылку из Apps Script

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
    <title>Панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #0f172a; color: white; }</style>
</head>
<body class="p-4">
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
</body>
</html>
