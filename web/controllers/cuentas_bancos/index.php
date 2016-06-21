<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/cuentas_bancos/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
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
		'Cuenta', 
		'Banco_id', 
		'Moneda_id', 

    );
    
    $table_columns_type = array(
		'bigint(20)', 
		'varchar(255)', 
		'bigint(20)', 
		'bigint(20)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `cuentas_bancos`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `cuentas_bancos`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/cuentas_bancos/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
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
    
    $find_sql = "SELECT " . $fieldname . " FROM " . cuentas_bancos . " WHERE ".$idfldname." = ?";
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



$app->match('/cuentas_bancos', function () use ($app) {
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
		'id', 
		'Cuenta', 
		'Banco_id', 
		'Moneda_id', 

    );

    $primary_key = "id";	

    return $app['twig']->render('cuentas_bancos/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('cuentas_bancos_list');



$app->match('/cuentas_bancos/create', function () use ($app) {
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
		'Cuenta' => '', 
		'Banco_id' => '', 
		'Moneda_id' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('Cuenta', 'text', array('required' => true,
        'label' => 'Nro. Cuenta' ));
   $find_sql = "SELECT id, banco FROM `bancos`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['banco'];
    }

    $form = $form->add('Banco_id', 'choice', array('required' => true,
        "choices" => $datos,
        'label' => 'Banco'  
        ));

    $find_sql = "SELECT id, CONCAT(moneda,  ' ', simbolo) Nombre  FROM `monedas`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['Nombre'];
    }

    $form = $form->add('Moneda_id', 'choice', array('required' => true,
        "choices" => $datos,
        'label' => 'Moneda'  
        ));



    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
          try {
            $data = $form->getData();

            $update_query = "INSERT INTO `cuentas_bancos` (`Cuenta`, `Banco_id`, `Moneda_id`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['Cuenta'], 
                                                            $data['Banco_id'], 
                                                            $data['Moneda_id']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Registro Guardado!',
                )
            );
            }
            catch (Exception $e) {
                $app['session']->getFlashBag()->add(
                    'danger',
                    array(
                        'message' => 'Revise sus datos!',
                    )
                );
                return $app->redirect($app['url_generator']->generate('cuentas_bancos_create'));
            }            
            return $app->redirect($app['url_generator']->generate('cuentas_bancos_list'));

        }
    }

    return $app['twig']->render('cuentas_bancos/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('cuentas_bancos_create');



$app->match('/cuentas_bancos/edit/{id}', function ($id) use ($app) {
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
    $find_sql = "SELECT * FROM `cuentas_bancos` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('cuentas_bancos_list'));
    }

    
    $initial_data = array(
		'Cuenta' => $row_sql['Cuenta'], 
		'Banco_id' => $row_sql['Banco_id'], 
		'Moneda_id' => $row_sql['Moneda_id'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


$form = $form->add('Cuenta', 'text', array('required' => true,
        'label' => 'Nro. Cuenta' ));
   $find_sql = "SELECT id, banco FROM `bancos`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['banco'];
    }

    $form = $form->add('Banco_id', 'choice', array('required' => true,
        "choices" => $datos,
        'label' => 'Banco'  
        ));

    $find_sql = "SELECT id, CONCAT(moneda,  ' ', simbolo) Nombre  FROM `monedas`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['Nombre'];
    }

    $form = $form->add('Moneda_id', 'choice', array('required' => true,
        "choices" => $datos,
        'label' => 'Moneda'  
        ));




    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
          try{
                      $data = $form->getData();
          
                      $update_query = "UPDATE `cuentas_bancos` SET `Cuenta` = ?, `Banco_id` = ?, `Moneda_id` = ? WHERE `id` = ?";
                      $app['db']->executeUpdate($update_query, array($data['Cuenta'], $data['Banco_id'], $data['Moneda_id'], $id));            
          
          
                      $app['session']->getFlashBag()->add(
                          'success',
                          array(
                              'message' => 'Registro Guardado!',
                          )
                      );}
          catch (Exception $e) {
                $app['session']->getFlashBag()->add(
                    'danger',
                    array(
                        'message' => 'Revise sus datos!',
                    )
                );
            }
            return $app->redirect($app['url_generator']->generate('cuentas_bancos_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('cuentas_bancos/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('cuentas_bancos_edit');



$app->match('/cuentas_bancos/delete/{id}', function ($id) use ($app) {
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
    $find_sql = "SELECT * FROM `cuentas_bancos` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `cuentas_bancos` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'Registro Borrado!',
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

    return $app->redirect($app['url_generator']->generate('cuentas_bancos_list'));

})
->bind('cuentas_bancos_delete');






