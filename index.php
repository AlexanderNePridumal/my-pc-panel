<?php
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();
$scriptUrl = "https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";
$namesFile = 'names.json';
$names = file_exists($namesFile) ? json_decode(file_get_contents($namesFile), true) : [];

// Обработка действий
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'set_name') {
        $names[$_POST['ip']] = $_POST['name'];
        file_put_contents($namesFile, json_encode($names));
        $postData = http_build_query(['update_name' => '1', 'ip' => $_POST['ip'], 'name' => $_POST['name']]);
    } elseif ($_GET['action'] == 'delete_name') {
        unset($names[$_POST['ip']]);
        file_put_contents($namesFile, json_encode($names));
        $postData = http_build_query(['delete_name' => '1', 'ip' => $_POST['ip']]);
    }
    
    $ch = curl_init($scriptUrl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_exec($ch);
    curl_close($ch);
    header("Location: /"); exit;
}

// Загрузка
$ch = curl_init($csvUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$data = curl_exec($ch);
curl_close($ch);

$rows = array_map('str_getcsv', explode("\n", trim($data)));
$named = []; $unnamed = [];
foreach ($rows as $row) {
    if(count($row) >= 3) {
        $ip = $row[1];
        $name = isset($names[$ip]) ? $names[$ip] : ($row[3] ?? 'Неизвестный');
        if ($name === 'Неизвестный' || $name === '0' || empty($name)) { $unnamed[$ip] = ['ip' => $ip, 'time' => $row[0], 'name' => $name]; }
        else { $named[$ip] = ['ip' => $ip, 'time' => $row[0], 'name' => $name]; }
    }
}
?>

<!DOCTYPE html>
<html lang="ru"><head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#0f172a;color:white;}</style></head>
<body class="p-4">
    <div class="container">
        <?php if (!empty($unnamed)): ?>
            <h2 class="text-danger">Требуется настройка:</h2>
            <table class="table table-dark">
                <?php foreach($unnamed as $d): ?>
                <tr><td><?= $d['ip'] ?></td><td>
                    <form method="POST" action="?action=set_name" class="d-flex"><input type="hidden" name="ip" value="<?= $d['ip'] ?>"><input type="text" name="name" class="form-control form-control-sm" placeholder="Имя"><button class="btn btn-sm btn-primary">OK</button></form>
                </td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h2 class="text-success">Активные устройства:</h2>
        <table class="table table-dark">
            <?php foreach($named as $d): ?>
            <tr><td><?= $d['name'] ?></td><td><?= $d['ip'] ?></td>
            <td><form method="POST" action="?action=delete_name"><input type="hidden" name="ip" value="<?= $d['ip'] ?>"><button class="btn btn-sm btn-danger">Удалить имя</button></form></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</body></html>
