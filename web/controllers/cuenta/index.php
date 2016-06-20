<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/cuenta/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'Ingresos', 
		'Egresos', 
		'Correo',
    );

    $table_columns1 = array(
        'a.ingreso', 
        'a.egreso', 
        'b.email',
    );
    
    $table_columns_type = array(
		'bigint(20)', 
		'decimal(17,2)', 
		'decimal(17,2)', 
		'decimal(17,2)', 
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
                                                a.id AS id,
                                                a.ingreso AS Ingresos,
                                                a.egreso AS Egresos,
                                                b.email AS `Correo`
                                                FROM
                                                cuenta AS a
                                                INNER JOIN usuarios AS b ON a.usuario_id = b.id"
                                                 . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT
                a.id AS id,
                a.ingreso AS Ingresos,
                a.egreso AS Egresos,
                b.email AS `Correo`
                FROM
                cuenta AS a
                INNER JOIN usuarios AS b ON 
                a.usuario_id = b.id". 
                $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/cuenta/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
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
    
    $find_sql = "SELECT " . $fieldname . " FROM " . cuenta . " WHERE ".$idfldname." = ?";
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



$app->match('/cuenta', function () use ($app) {
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
        'Ingresos', 
        'Egresos', 
        'Correo',
    );

    $primary_key = "id";	

    return $app['twig']->render('cuenta/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('cuenta_list');



$app->match('/cuenta/create', function () use ($app) {
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
		'ingreso' => '', 
		'egreso' => '', 
		'total' => '', 
		'usuario_id' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('ingreso', 'text', array('required' => false));
	$form = $form->add('egreso', 'text', array('required' => false));
	$form = $form->add('total', 'text', array('required' => false));

    $find_sql = "SELECT id, email FROM `usuarios`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['email'];
    }

    $form = $form->add('usuario_id', 'choice', array('required' => true,
        "choices" => $datos 
        ));
    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `cuenta` (`id`, `ingreso`, `egreso`, `total`, `usuario_id`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['ingreso'], $data['egreso'], $data['total'], $data['usuario_id']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cuenta created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cuenta_list'));

        }
    }

    return $app['twig']->render('cuenta/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('cuenta_create');



$app->match('/cuenta/edit/{id}', function ($id) use ($app) {
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
    $find_sql = "SELECT * FROM `cuenta` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('cuenta_list'));
    }

    
    $initial_data = array(
		'id' => $row_sql['id'], 
		'ingreso' => $row_sql['ingreso'], 
		'egreso' => $row_sql['egreso'], 
		'total' => $row_sql['total'], 
		'usuario_id' => $row_sql['usuario_id'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('id', 'text', array('required' => true));
	$form = $form->add('ingreso', 'text', array('required' => false));
	$form = $form->add('egreso', 'text', array('required' => false));
	$form = $form->add('total', 'text', array('required' => false));
    $find_sql = "SELECT id, email FROM `usuarios`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['email'];
    }

    $form = $form->add('usuario_id', 'choice', array('required' => true,
        "choices" => $datos 
        ));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `cuenta` SET `id` = ?, `ingreso` = ?, `egreso` = ?, `total` = ?, `usuario_id` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['ingreso'], $data['egreso'], $data['total'], $data['usuario_id'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cuenta edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cuenta_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('cuenta/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('cuenta_edit');



$app->match('/cuenta/delete/{id}', function ($id) use ($app) {
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
    $find_sql = "SELECT * FROM `cuenta` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `cuenta` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'cuenta deleted!',
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

    return $app->redirect($app['url_generator']->generate('cuenta_list'));

})
->bind('cuenta_delete');






