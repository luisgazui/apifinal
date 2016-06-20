<?php 
include_once('../conexion/conexion.php'); 
$listaemail = '';
foreach ($_POST['email'] as $r){
$listaemail .= $r.',';
}
$$listaemail = substr($listaemail,0,strlen($listaemail)-1);
$titulo = $_POST['asunto'];
$mensaje = '
<html>
<head>
  <title>'.$_POST['asunto'].'</title>
</head>
<body style="background:#EEE; padding:30px;">
	<img width="100" src="http://bigsender.com.mx/comunicacionsApis/public/img/Logo.png"/>
	<h2 style="color:#767676;">Asunto: '.$_POST['asunto'].'</h2>
		<strong style="color:#0090C6;">Email: </strong>
		<span style="color:#767676;">'.$listaemail.'</span></p>
		<strong style="color:#0090C6;">Mensaje: </strong>
  			<span style="color:#767676;">'.$_POST['message'].'</span>
</body>
</html>
';
$cabeceras  = 'MIME-Version: 1.0' . "\r\n";
$cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
$cabeceras .= 'From: '.$_POST['de'].'' . "\r\n" .
				    'Reply-To: '.$_POST['de'].'' . "\r\n" .
				    'X-Mailer: PHP/' . phpversion();
$cabeceras .= "Content-Type: multipart/mixed; ";
$enviado = mail($listaemail, $titulo, $mensaje, $cabeceras);

if($enviado){

	$sql = "INSERT INTO emailsalida (para, de, asunto,mensaje, estado, fecha_create) VALUES 
		('$listaemail', '$_POST[de]', '$_POST[asunto]', '$_POST[message]','Enviado', '".date('Y-m-d h:i:s')."');";

		if (mysqli_query($conexion, $sql)) {

		echo json_encode(array('success' => true, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-success alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Email Enviado Correctamente</div></div>'));
		}else{
			echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>error al insertar</div></div>'));
		}
}else{
	echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error no se pudo Enviar Intentelo de Nuevo</div></div>'));
}

?>