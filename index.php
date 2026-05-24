<?php
$url = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv";
$data = file_get_contents($url);

if ($data === false) {
    echo "ХОСТИНГ НЕ ВИДИТ GOOGLE! (ошибка file_get_contents)";
} else {
    echo "СВЯЗЬ ЕСТЬ! Вот что пришло: " . htmlspecialchars($data);
}
?>
