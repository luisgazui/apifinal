<?php


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

/**
* clase que permite calcular el costo por 
* app segun id de app dada en la posicion
* asi como tambien el calculo de los ingresos para poder
* tener el control de cuentas de usuarios
*/
class reglas_calculo
{
	
	public function creditos_cuenta($cantidad, $moneda_id)
	{
		$sqlcredito = "SELECT
					    a.id id,
					    a.credito credito
				       FROM
					    creditos AS a
				       WHERE
					    a.moneda_id = '$moneda_id'";

    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    $datos = array();
    foreach ($rows_sql as  $value) {
       $calculo = $value['credito'] * $cantidad;
    }
    return($calculo);
	}
}