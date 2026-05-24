<?php
// Вывод ошибок, чтобы мы увидели, в чем проблема, если страница все еще белая
ini_set('display_errors', 1);
error_reporting(E_ALL);

$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();

// Загружаем данные
$data = @file_get_contents($csvUrl); // @ подавляет ошибку, если запрос к Google не удался

if ($data === false) {
    die("Ошибка: Не удалось подключиться к Google Таблице.");
}

$rows = array_map('str_getcsv', explode("\n", trim($data)));
?>

<!DOCTYPE html>
<html>
<head><title>Панель</title></head>
<body style="background:#0f172a; color:white;">
    <h1>Устройства:</h1>
    <pre>
    <?php 
    // Выводим данные в сыром виде, чтобы понять, приходят ли они вообще
    print_r($rows); 
    ?>
    </pre>
</body>
</html>
