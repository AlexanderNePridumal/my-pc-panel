<?php
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();
$scriptUrl = "https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

if (isset($_GET['action'])) {
    $ch = curl_init($scriptUrl);
    curl_setopt($ch, CURLOPT_POST, true); // ВАЖНО: Явно указываем POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Безопасность
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    if(curl_errno($ch)) { echo "cURL Error: " . curl_error($ch); exit; }
    curl_close($ch);
    
    sleep(2); // Ждем записи в таблицу
    header("Location: /"); exit;
}

$data = @file_get_contents($csvUrl);
$rows = array_map('str_getcsv', explode("\n", trim($data)));
$devices = [];
foreach ($rows as $row) {
    if(count($row) >= 2) {
        $ip = trim($row[1]);
        $name = (!empty($row[3]) && $row[3] != "0") ? trim($row[3]) : "Неизвестный";
        $devices[$ip] = ['ip' => $ip, 'time' => $row[0], 'status' => $row[2], 'name' => $name];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#0f172a;color:white;}</style></head>
<body class="p-4"><div class="container">
    <table class="table table-dark">
        <thead><tr><th>Имя</th><th>IP</th><th>Сигнал</th><th>Действие</th></tr></thead>
        <tbody>
            <?php foreach($devices as $d): ?>
            <tr><td><?= htmlspecialchars($d['name']) ?></td><td><?= htmlspecialchars($d['ip']) ?></td><td><?= htmlspecialchars($d['time']) ?></td>
            <td>
                <?php if ($d['name'] == "Неизвестный"): ?>
                    <form method="POST" action="?action=set" class="d-flex"><input type="hidden" name="update_name" value="1"><input type="hidden" name="ip" value="<?= $d['ip'] ?>"><input type="text" name="name" class="form-control form-control-sm me-2" placeholder="Имя"><button class="btn btn-sm btn-primary">ОК</button></form>
                <?php else: ?>
                    <form method="POST" action="?action=del"><input type="hidden" name="delete_name" value="1"><input type="hidden" name="ip" value="<?= $d['ip'] ?>"><button class="btn btn-sm btn-danger">Удалить</button></form>
                <?php endif; ?>
            </td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div></body></html>
