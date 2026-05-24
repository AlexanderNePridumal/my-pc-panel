<?php
$dataFile = 'data.txt';
$cmdFile = 'cmd.txt'; // Храним команду отдельно для каждого ID
$imgFile = 'screenshot.jpg';

if (isset($_GET['get_cmd'])) {
    $pc_id = $_GET['pc_id'] ?? 'default';
    echo file_exists("cmd_$pc_id.txt") ? file_get_contents("cmd_$pc_id.txt") : 'none';
    exit;
}

if (isset($_FILES['screen'])) {
    move_uploaded_file($_FILES['screen']['tmp_name'], $imgFile);
    echo "ok"; exit;
}

// Установка команды для конкретного ПК
if (isset($_POST['set_cmd'])) {
    $pc_id = $_POST['pc_id'];
    file_put_contents("cmd_$pc_id.txt", $_POST['set_cmd']);
    header("Location: /"); exit;
}
?>
<!DOCTYPE html>
<html>
<body style="background:#1a1a24; color:white; text-align:center;">
    <h1>Управление ПК</h1>
    <form method="POST">
        <input type="text" name="pc_id" placeholder="ID твоего ПК (напр. PC-01)" required>
        <button name="set_cmd" value="screen">Скриншот</button>
    </form>
    <?php if(file_exists($imgFile)): ?>
        <h3>Последний скриншот:</h3>
        <img src="screenshot.jpg?<?php echo time(); ?>" style="max-width:500px; border:2px solid #fff;">
    <?php endif; ?>
</body>
</html>
