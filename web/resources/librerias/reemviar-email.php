<?php 
include_once('../conexion/conexion.php'); 
$emails = NULL;
foreach ($_POST['email'] as $r){
	 $emails.=$r.",";
}
$emails = substr($emails,0,strlen($emails)-1);
$para = $emails;
$titulo = $_POST['asunto'];
$mensaje = '
<html>
<head>
  <title>'.$_POST['asunto'].'</title>
</head>
<body>
  '.$_POST['message'].'
</body>
</html>
';
$cabeceras = 'MIME-Version: 1.0' . "\r\n";
$cabeceras .= 'Content-type: text/html; charset=utf-8' . "\r\n";
$cabeceras .= 'From: '.$_POST['de'].'>';
$enviado = mail($para, $titulo, $mensaje, $cabeceras);

if($enviado){

		$sql = "UPDATE emailsalida set para='$emails',de='$_POST[de]',asunto='$_POST[asunto]' WHERE id='$_POST[id]'";		

		if (mysqli_query($conexion, $sql)) {

		echo json_encode(array('success' => true, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-success alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Email Reenviado Correctamente</div></div>'));
		}else{
			echo 'error al insertar';
		}
}else{
	echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error no se pudo Enviar Intentelo de Nuevo</div></div>'));
}

?>