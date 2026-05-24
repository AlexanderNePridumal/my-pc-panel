<?php
$api="https://script.google.com/macros/s/AKfycbxii7c1LApf-QkOCjg9aN7hgygQBa9Pjt0aAwO-y_r--wzunh0jMS6VoS1rA5gWiW2r/exec";

function getData($url){
$ch=curl_init($url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($ch,CURLOPT_TIMEOUT,10);
$res=curl_exec($ch);
curl_close($ch);
return json_decode($res,true);
}

if($_SERVER["REQUEST_METHOD"]==="POST"){
$ip=$_POST["ip"]??"";
$name=$_POST["name"]??"";

$data=http_build_query([
"action"=>"set_name",
"ip"=>$ip,
"name"=>$name
]);

$ch=curl_init($api);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
curl_exec($ch);
curl_close($ch);

header("Location: /");
exit;
}

$devices=getData($api);
if(!is_array($devices))$devices=[];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PC Panel</title>

<style>
body{
margin:0;
font-family:system-ui;
background:#0b1220;
color:#e5e7eb;
}

h2{
padding:20px;
margin:0;
border-bottom:1px solid #1f2937;
}

.container{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
gap:15px;
padding:20px;
}

.card{
background:linear-gradient(145deg,#111827,#0f172a);
border:1px solid #1f2937;
padding:15px;
border-radius:14px;
box-shadow:0 10px 30px rgba(0,0,0,0.3);
transition:0.2s;
}

.card:hover{
transform:translateY(-3px);
}

.ip{color:#93c5fd;font-size:12px}
.status{color:#34d399;margin-top:5px}
.time{color:#9ca3af;font-size:12px;margin-top:5px}

input{
width:100%;
padding:8px;
margin-top:10px;
border-radius:8px;
border:1px solid #374151;
background:#0b1220;
color:white;
}

button{
width:100%;
margin-top:10px;
padding:8px;
border:none;
border-radius:8px;
background:#3b82f6;
color:white;
cursor:pointer;
}

button:hover{
background:#2563eb;
}
</style>

</head>
<body>

<h2>PC Panel</h2>

<div class="container">

<?php foreach($devices as $d): ?>
<div class="card">

<div><b><?=htmlspecialchars($d["name"] ?: "No name")?></b></div>
<div class="ip"><?=htmlspecialchars($d["ip"])?></div>

<div class="status"><?=htmlspecialchars($d["status"])?></div>
<div class="time"><?=htmlspecialchars($d["time"])?></div>

<form method="POST">
<input type="hidden" name="ip" value="<?=htmlspecialchars($d["ip"])?>">
<input name="name" placeholder="Имя ПК">
<button>Сохранить</button>
</form>

</div>
<?php endforeach; ?>

</div>

</body>
</html>
