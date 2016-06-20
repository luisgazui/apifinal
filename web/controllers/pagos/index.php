<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';
//require_once __DIR__.'/../../reglas/reglas_calculo.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/pagos/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'id', 
		'Fecha', 
		'Cantidad', 
		'Referencia',
        'Estado', 
		'Correo', 
        'Cuenta',
    );
    
    $table_columns1 = array(
        'a.id', 
        'a.created_at', 
        'a.cantidad', 
        'a.Referencia',
        'c.email', 
        'e.banco',
        'd.cuenta',
    );


    $table_columns_type = array(
		'bigint(20)', 
		'timestamp', 
		'decimal(16,2)', 
		'tinyint(4)', 
		'bigint(20)', 
		'bigint(20)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns1 as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT
                                                    a.id,
                                                    DATE_FORMAT(a.created_at, '%d/%m/%Y') Fecha,
                                                    a.cantidad Cantidad,
                                                    a.Referencia Referencia,
                                                    CASE a.aprobado
                                                WHEN 1 THEN
                                                    'Aprobado'
                                                ELSE
                                                    'Pendiente'
                                                END Estado,
                                                 c.email Correo,
                                                 CONCAT(e.banco, ' ', d.Cuenta) Cuenta
                                                FROM
                                                    pagos AS a
                                                INNER JOIN usuarios AS c ON a.usuario_id = c.id
                                                INNER JOIN cuentas_bancos as d ON a.cuenta_id = d.id
                                                INNER JOIN bancos AS e ON d.Banco_id = e.id" . 
                                                $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT
                    a.id,
                    DATE_FORMAT(a.created_at, '%d/%m/%Y') Fecha,
                    a.cantidad Cantidad,
                    a.Referencia Referencia,
                        CASE a.aprobado
                            WHEN 1 THEN
                                'Aprobado'
                            ELSE
                                'Pendiente'
                            END Estado,
                    c.email Correo,
                    CONCAT(e.banco, ' ', d.Cuenta) Cuenta
                    FROM
                        pagos AS a
                        INNER JOIN usuarios AS c ON a.usuario_id = c.id
                        INNER JOIN cuentas_bancos as d ON a.cuenta_id = d.id
                        INNER JOIN bancos AS e ON d.Banco_id = e.id"
                        . $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
		}

        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/pagos/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }     
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . pagos . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/pagos', function () use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }     
    $table_columns = array(
        'Fecha', 
        'Cantidad', 
        'Referencia',
        'Estado', 
        'Correo', 
        'Cuenta',
    );

    $primary_key = "id";	

    return $app['twig']->render('pagos/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('pagos_list');



$app->match('/pagos/create', function () use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }     
    $initial_data = array(
		'created_at' => '', 
		'cantidad' => 0,
        'Referencia' =>'', 
		'aprobado' => false, 
		'usuario_id' => '', 
        'cuenta_id' => '',

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);
	$form = $form->add('created_at', 'text', array('required' => true));
	$form = $form->add('cantidad', 'number', array('required' => true));
    $form = $form->add('Referencia', 'text', array('required' => true));
	$form = $form->add('aprobado', 'checkbox', array('required' => false));

    $find_sql = "SELECT id, email FROM `usuarios`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['email'];
    }

    $form = $form->add('usuario_id', 'choice', array('required' => true,
        "choices" => $datos 
        ));

    $find_sql = "SELECT
                b.id AS id,
                concat(a.banco, ' ', 
                b.Cuenta) AS cuenta
                FROM
                bancos AS a
                INNER JOIN cuentas_bancos AS b ON b.Banco_id = a.id";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['cuenta'];
    }

    $form = $form->add('cuenta_id', 'choice', array('required' => true,
        "choices" => $datos 
        ));

    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            try {
                $data = $form->getData();
                $date = date("Y-m-d", strtotime($data['created_at']));
                $update_query = "INSERT INTO `pagos` (`id`, 
                                                      `created_at`, 
                                                      `cantidad`,
                                                      `Referencia`,
                                                      `aprobado`,  
                                                      `usuario_id`,
                                                      `cuenta_id`) 
                                                      VALUES 
                                                      (?, ?, ?, ?, ?, ?, ?)";
                $app['db']->executeUpdate($update_query, array($data['id'], 
                                                               $date, 
                                                               $data['cantidad'],
                                                               $data['Referencia'], 
                                                               $data['aprobado'],  
                                                               $data['usuario_id'],
                                                               $data['cuenta_id']));
                $cuenta= $data['cuenta_id'];
                if ($data['aprobado']) {
                    $sqlcredito =  "SELECT
                                        a.credito
                                    FROM
                                        creditos AS a
                                    INNER JOIN cuentas_bancos AS b ON a.moneda_id = b.Moneda_id
                                       WHERE
                                        b.id = '$cuenta'" ;

                    $rows_sql = $app['db']->fetchAll($sqlcredito, array());
                    
                    foreach ($rows_sql as  $value) {
                       $calculo = $value['credito'] * $data['cantidad'];
                    } 
                    $update_query = "INSERT INTO `cuenta` (`ingreso`, 
                                                       `usuario_id`) 
                                                      VALUES 
                                                      (?, ?)";
                $app['db']->executeUpdate($update_query, array($calculo, 
                                                               $_SESSION['id'])); 
                }            


                $app['session']->getFlashBag()->add(
                    'success',
                    array(
                        'message' => 'Pago Registrado!',
                    )
                );
                //return $app->redirect($app['url_generator']->generate('pagos_list'));

            }
            catch (Exception $e) {
                $app['session']->getFlashBag()->add(
                    'danger',
                    array(
                        'message' => 'Revise sus datos!',
                    )
                );
            }
        } 
    }

    return $app['twig']->render('pagos/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('pagos_create');

$app->match('/pagos/registrar', function () use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }     
    $initial_data = array(
        'created_at' => '', 
        'cantidad' => 0,
        'Referencia' =>'',  
        'usuario_id' => '', 
        'cuenta_id' => '',

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);
   
    $form = $form->add('created_at', 'text', array('required' => true));
    $form = $form->add('cantidad', 'number', array('required' => true));
    $form = $form->add('Referencia', 'text', array('required' => true));


    $find_sql = "SELECT
                b.id AS id,
                concat(a.banco, ' ', 
                b.Cuenta) AS cuenta
                FROM
                bancos AS a
                INNER JOIN cuentas_bancos AS b ON b.Banco_id = a.id";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['cuenta'];
    }

    $form = $form->add('cuenta_id', 'choice', array('required' => true,
        "choices" => $datos 
        ));



    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            try {
                $data = $form->getData();
                $date = date("Y-m-d", strtotime($data['created_at']));
                $update_query = "INSERT INTO `pagos` (`id`, 
                                                      `created_at`, 
                                                      `cantidad`,
                                                      `Referencia`,
                                                      `aprobado`,  
                                                      `usuario_id`,
                                                      `cuenta_id`) 
                                                      VALUES 
                                                      (?, ?, ?, ?, ?, ?, ?)";
                $app['db']->executeUpdate($update_query, array($data['id'], 
                                                               $date, 
                                                               $data['cantidad'],
                                                               $data['Referencia'], 
                                                               false,  
                                                               $_SESSION['id'],
                                                               $data['cuenta_id']));            


                $app['session']->getFlashBag()->add(
                    'success',
                    array(
                        'message' => 'Su pago ha sido registrado!',
                    )
                );
                //return $app->redirect($app['url_generator']->generate('pagos_list'));

            }
            catch (Exception $e) {
                $app['session']->getFlashBag()->add(
                    'danger',
                    array(
                        'message' => 'Revise sus datos!',
                    )
                );
            }
        } 
    }

    return $app['twig']->render('pagos/regitrar.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('pagos_registrar');

$app->match('/pagos/edit/{id}', function ($id) use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    } 
    $find_sql = "SELECT * FROM `pagos` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('pagos_list'));
    }

    
    if ($row_sql['aprobado'] == 1) {
      $aprobado = true;
    }
    else {
      $aprobado = false;
    }

    $fecha = strtotime($row_sql['created_at']);
    $newformat = date('d-m-Y',$fecha);
    $initial_data = array(
		'id' => $row_sql['id'], 
		'created_at' => $newformat, 
		'cantidad' => $row_sql['cantidad'], 
        'Referencia' => $row_sql['Referencia'],
		'aprobado' => $aprobado,
		'usuario_id' => $row_sql['usuario_id'], 

    );
    $oldaprobado = $aprobado;

    $form = $app['form.factory']->createBuilder('form', $initial_data);
    $form = $form->add('created_at', 'text', array('required' => true));
    $form = $form->add('cantidad', 'number', array('required' => true));
    $form = $form->add('Referencia', 'text', array('required' => true));
    $form = $form->add('aprobado', 'checkbox', array('required' => false,
                                                     'disabled' => $aprobado));

    $find_sql = "SELECT id, email FROM `usuarios`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['email'];
    }

    $form = $form->add('usuario_id', 'choice', array('required' => true,
        "choices" => $datos 
        ));


    $find_sql = "SELECT
                b.id AS id,
                concat(a.banco, ' ', 
                b.Cuenta) AS cuenta
                FROM
                bancos AS a
                INNER JOIN cuentas_bancos AS b ON b.Banco_id = a.id";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['cuenta'];
    }

    $form = $form->add('cuenta_id', 'choice', array('required' => true,
        "choices" => $datos 
        ));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `pagos` SET 
                            `created_at` = ?, 
                            `cantidad` = ?,
                            `Referencia`= ?, 
                            `aprobado` = ?,  
                            `usuario_id` = ?,
                            `cuenta_id` = ?
                            WHERE 
                            `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['created_at'], 
                                                           $data['cantidad'], 
                                                           $data['Referencia'],
                                                           $data['aprobado'], 
                                                           $data['usuario_id'],
                                                           $data['cuenta_id'], 
                                                           $id));            

                if (($oldaprobado != $data['aprobado']) && ($data['aprobado'])) {
                    $cuenta= $data['cuenta_id'];
                    $sqlcredito =  "SELECT
                                        a.credito
                                    FROM
                                        creditos AS a
                                    INNER JOIN cuentas_bancos AS b ON a.moneda_id = b.Moneda_id
                                       WHERE
                                        b.id = '$cuenta'" ;

                    $rows_sql = $app['db']->fetchAll($sqlcredito, array());
                    
                    foreach ($rows_sql as  $value) {
                       $calculo = $value['credito'] * $data['cantidad'];
                    } 
                    $update_query = "INSERT INTO `cuenta` (`ingreso`, 
                                                       `usuario_id`) 
                                                      VALUES 
                                                      (?, ?)";
                $app['db']->executeUpdate($update_query, array($calculo, 
                                                               $_SESSION['id'])); 
                } 

            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Pago Actualizado!',
                )
            );
            return $app->redirect($app['url_generator']->generate('pagos_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('pagos/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('pagos_edit');



$app->match('/pagos/delete/{id}', function ($id) use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    } 
    $find_sql = "SELECT * FROM `pagos` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `pagos` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'pagos deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('pagos_list'));

})
->bind('pagos_delete');