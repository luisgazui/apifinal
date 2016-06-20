<?php 
include_once('../conexion/conexion.php'); 
?>


<?php
$paisycodigo = explode(',', $_POST["pais"]);

if ($_POST["file"] != NULL){
	$datosfile = 'http://localhost/comunicacionsApis/whatsapp/upload/files/'.$_POST["file"];
}else{
	$datosfile = '';
}

if ($_POST["pais"] == ""){
	echo json_encode(array('mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El Campo Pais es Requerido</div></div>'));
		//echo "El Campo Pais es Obligatorio";
}elseif($_POST["message"] == ""){
	echo json_encode(array(
		'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El Campo <b>Mensaje</b> ES Obligatorio</div></div>'));
}else{
		if (substr($_FILES['excelnum']['name'],-3)=="csv")
		{
				$fecha		= date("Y-m-d");
				$carpeta 	= "../tmp_excel/";
				$excel  	= $fecha."-".$_FILES['excelnum']['name'];

				move_uploaded_file($_FILES['excelnum']['tmp_name'], "$carpeta$excel");
				
				$row = 1;

				$fp = fopen ("$carpeta$excel","r");

				while ($data = fgetcsv ($fp, 1000,','))
				{	
					if ($row!=1)
					{	
						$wauser = "WAC33258"; //Usuario de Whappend / Whappend username
						$wapass	 = "111473"; //Clave de Whappend / Whappend passwrod
						$destination = $paisycodigo[1].$data[0]; // Número de destino (pueden ser varios serparados por coma) / Destination number (You can add many destinations separated by comma)
						$wamessage  =  $datosfile.' '.$_POST["message"]; // Texto del mensaje o URL multimedia / Text of message or Multimedia URL
						$waresponse = file_get_contents("http://private.whappend.com/wa_send.asp?API=1&TOS=". urlencode($destination) ."&TEXTO=". urlencode($wamessage) ."&USUARIO=". urlencode($wauser) ."&CLAVE=". urlencode($wapass) );

						$sql = "INSERT INTO whatsappapi (pais, telefono, mensaje, fecha_create, estado) VALUES 
							('".$paisycodigo[0]."', '".$paisycodigo[1].$data[0]."', '".NULL.' '.$_POST["message"]."', '".date("Y-m-d h:i:s")."', '".$waresponse."');";

							if (mysqli_query($conexion, $sql)) {
									//echo 'Correcto';
							}else{
								//echo "Error al Insertar";
							}
						}
						$row++;	
			}
				echo json_encode(array('success' => true, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-success alert-dismissible" role="alert">
		  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Mensaje: OK</div></div>'));
		}else{
			echo json_encode(array('mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
  				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Seleccione un Archivo para enviar</div></div>'));
		}	

}	
?>
<?php 

?>