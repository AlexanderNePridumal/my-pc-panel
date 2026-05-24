<?php
$csvUrl = "ССЫЛКА_НА_CSV_ИЗ_GOOGLE"; // ВСТАВЬ СВОЮ ССЫЛКУ!
$data = @file_get_contents($csvUrl);
$rows = $data ? array_map('str_getcsv', explode("\n", $data)) : [];
array_shift($rows);

if (isset($_POST['save_name'])) {
    file_get_contents("ССЫЛКА_НА_GOOGLE_SCRIPT/exec?update_name=1&ip=" . $_POST['ip'] . "&name=" . urlencode($_POST['new_name']));
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #0f172a; color: white; }</style>
</head>
<body class="p-4">
    <div class="row g-3">
        <?php foreach($new_pcs as $ip => $v): ?>
            <div class="col-md-3"><div class="card p-3 border-danger">
                <h5>⚠️ Новый ПК</h5><p><?= $ip ?></p>
                <form method="POST"><input type="hidden" name="ip" value="<?= $ip ?>">
                <input type="text" name="new_name" class="form-control mb-2" placeholder="Имя...">
                <button type="submit" name="save_name" class="btn btn-success btn-sm">Сохранить</button></form>
            </div></div>
        <?php endforeach; ?>
        <?php foreach($devices as $ip => $d): ?>
            <div class="col-md-3"><div class="card p-3"><h5><?= $d['name'] ?></h5><p><?= $d['status'] ?></p></div></div>
        <?php endforeach; ?>
    </div>
</body>
</html>
