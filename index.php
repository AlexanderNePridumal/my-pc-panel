<?php
// Добавляем случайный параметр, чтобы PHP не брал старый кэш
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();
$scriptUrl = "https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

if (isset($_GET['action'])) {
    $postData = http_build_query($_POST);
    $ch = curl_init($scriptUrl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    // Ждем 1 секунду, чтобы Google успел обработать запрос
    sleep(1);
    header("Location: /"); exit;
}

$data = file_get_contents($csvUrl);
$rows = array_map('str_getcsv', explode("\n", trim($data)));
$named = []; $unnamed = [];

foreach ($rows as $row) {
    if(count($row) >= 4) {
        $ip = $row[1];
        $name = $row[3]; // Имя теперь берем прямо из таблицы
        
        $item = ['ip' => $ip, 'time' => $row[0], 'name' => $name];
        if (empty($name) || $name == "0") { $unnamed[$ip] = $item; }
        else { $named[$ip] = $item; }
    }
}
?>
