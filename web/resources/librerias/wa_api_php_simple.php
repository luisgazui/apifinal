<?php 
include_once('../conexion/conexion.php'); 
?>


<?php

function validar_tel($tel) { 
    $tel = preg_replace("/[^0-9]/","",$tel); 

    if ((strlen($tel) == 11) || (strlen($tel) == 10)) 
        return TRUE; 
    else 
        return FALSE;  
}

$paisycodigo = explode(',', $_POST["pais"]);

if (strlen($_POST["cedular"]) == 11) {
	$telefonodata = substr(trim($_POST["cedular"]), 1);
}elseif(strlen($_POST["cedular"]) == 10){
	$telefonodata = $_POST["cedular"];
}

if ($_POST["file"] != NULL){
	$datosfile = 'http://localhost/comunicacionsApis/whatsapp/upload/files/'.$_POST["file"];
}else{
	$datosfile = '';
}

if ($_POST["pais"] == ""){
	echo json_encode(array('mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El Campo Pais es Requerido</div></div>'));
		//echo "El Campo Pais es Obligatorio";
}elseif(trim($_POST["cedular"]) == "") {
	echo json_encode(array('mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El Campo Telefono es Requerido</div></div>'));
}elseif(!validar_tel(trim($_POST["cedular"]))){
	echo json_encode(array(
			'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Ingrese un <b>Telefono</b> Valido<b></div></div>'));
}elseif($_POST["message"] == ""){
	echo json_encode(array(
		'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El Campo <b>Mensaje</b> ES Obligatorio</div></div>'));
}else{
		$wauser = "WAC33258"; //Usuario de Whappend / Whappend username
		$wapass	 = "111473"; //Clave de Whappend / Whappend passwrod
		$destination = @$paisycodigo[1].$telefonodata; // Número de destino (pueden ser varios serparados por coma) / Destination number (You can add many destinations separated by comma)
		$wamessage  =  $datosfile.' '.$_POST["message"]; // Texto del mensaje o URL multimedia / Text of message or Multimedia URL
		$waresponse = file_get_contents("http://private.whappend.com/wa_send.asp?API=1&TOS=". urlencode($destination) ."&TEXTO=". urlencode($wamessage) ."&USUARIO=". urlencode($wauser) ."&CLAVE=". urlencode($wapass) );


		$sql = "INSERT INTO whatsappapi (pais, telefono, mensaje, fecha_create, estado) VALUES 
		('".@$paisycodigo[0]."', '".@$paisycodigo[1].$telefonodata."', '".$datosfile.' '.$_POST["message"]."', '".date("Y-m-d h:i:s")."', '".$waresponse."');";

		if (mysqli_query($conexion, $sql)) {
			
		echo json_encode(array('success' => true, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-success alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Mensaje: '.$waresponse.'</div></div>'));
		}else{
			echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Mensaje: El Mensaje No se ha podido Enviar</div></div>'));
		}


}	

?>