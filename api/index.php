<?php
// Даем права на запись, если файл не существует
if (!file_exists('data.txt')) {
    file_put_contents('data.txt', 'none');
    chmod('data.txt', 0777); 
}

<?php
$dataFile = __DIR__ . '/data.txt';
if (!file_exists($dataFile)) file_put_contents($dataFile, 'none');

if (isset($_GET['get_cmd'])) { echo file_get_contents($dataFile); exit; }
if (isset($_GET['clear_cmd'])) { file_put_contents($dataFile, 'none'); echo "ok"; exit; }
if (isset($_POST['set_cmd'])) {
    file_put_contents($dataFile, $_POST['set_cmd']);
    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Управление ПК</h1>
    <p>Команда: <b><?php echo file_get_contents($dataFile); ?></b></p>
    <form method="POST"><button name="set_cmd" value="screen">Сделать скриншот</button></form>
</body>
</html>
