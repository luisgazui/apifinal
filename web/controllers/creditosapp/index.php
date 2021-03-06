<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/creditosapp/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
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
		'Creditos', 
		'Aplicacion', 

    );
    $table_columns1 = array(
        'b.id', 
        'b.ncreditos', 
        'a.nombre', 

    );
    $table_columns_type = array(
		'bigint(20)', 
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
                                                b.id AS id,
                                                b.ncreditos AS `Creditos`,
                                                a.nombre AS Aplicacion
                                                FROM
                                                app AS a
                                                INNER JOIN creditosapp AS b ON b.app_id = a.id" 
                                                . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT
                    b.id AS id,
                    b.ncreditos AS `Creditos`,
                    a.nombre AS Aplicacion
                    FROM
                    app AS a
                    INNER JOIN creditosapp AS b ON b.app_id = a.id". 
                    $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'app_id'){
			    $findexternal_sql = 'SELECT `id` FROM `app` WHERE `id` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['id'];
			}
			else{
			    $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
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
$app->match('/creditosapp/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
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
    
    $find_sql = "SELECT " . $fieldname . " FROM " . creditosapp . " WHERE ".$idfldname." = ?";
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



$app->match('/creditosapp', function () use ($app) {

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
        'Creditos', 
        'Aplicacion', 

    );
    $primary_key = "id";	

    return $app['twig']->render('creditosapp/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('creditosapp_list');



$app->match('/creditosapp/create', function () use ($app) {

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
		'ncreditos' => '', 
		'app_id' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `id`, `nombre` FROM `app`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['id']] = $findexternal_row['nombre'];
	}
	if(count($options) > 0){
	    $form = $form->add('app_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options)),
            'label' => 'Aplicacion' 
	    ));
	}
	else{
	    $form = $form->add('app_id', 'text', array('required' => true,
            'label' => 'Aplicacion' ));
	}


	$form = $form->add('ncreditos', 'text', array('required' => true,
        'label' => 'Creditos' ));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `creditosapp` (`id`, `ncreditos`, `app_id`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['ncreditos'], $data['app_id']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Registro Guardado!',
                )
            );
            return $app->redirect($app['url_generator']->generate('creditosapp_list'));

        }
    }

    return $app['twig']->render('creditosapp/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('creditosapp_create');



$app->match('/creditosapp/edit/{id}', function ($id) use ($app) {

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
    $find_sql = "SELECT * FROM `creditosapp` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('creditosapp_list'));
    }

    
    $initial_data = array(
		'ncreditos' => $row_sql['ncreditos'], 
		'app_id' => $row_sql['app_id'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `id`, `nombre` FROM `app`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['id']] = $findexternal_row['nombre'];
	}
	if(count($options) > 0){
	    $form = $form->add('app_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
            'label' => 'Aplicacion', 
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('app_id', 'text', array('required' => true,
            'label' => 'Aplicacion' ));
	}


	$form = $form->add('ncreditos', 'text', array('required' => true,
        'label' => 'Creditos' ));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `creditosapp` SET `id` = ?, `ncreditos` = ?, `app_id` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['ncreditos'], $data['app_id'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Registro Guardado!',
                )
            );
            return $app->redirect($app['url_generator']->generate('creditosapp_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('creditosapp/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('creditosapp_edit');



$app->match('/creditosapp/delete/{id}', function ($id) use ($app) {

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
    $find_sql = "SELECT * FROM `creditosapp` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `creditosapp` WHERE `id` = ?";
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

    return $app->redirect($app['url_generator']->generate('creditosapp_list'));

})
->bind('creditosapp_delete');






