<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/bandeja_ent/list/{buscar}', function ($buscar, Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
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
		'Destitatario', 
		'Mensaje', 
		'Fecha', 
		'Estado', 
    );
        
    $table_columns1 = array(
        'id',
        'destinatario', 
        'mensaje', 
        'fecha_envio', 
        'estado', 
    );
    $table_columns_type = array(
		'varchar(255)', 
		'longtext', 
		'datetime', 
		'varchar(255)', 
    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns1 as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE (usuario_id = '".$_SESSION['id'].
                          "' AND app_id = $buscar) AND (";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    $whereClause= $whereClause . ")";
    $recordsTotal = $app['db']->executeQuery("SELECT
                                            a.id AS id,
                                            a.destinatario AS Destitatario,
                                            a.mensaje AS Mensaje,
                                            date_format(a.fecha_envio,'%d/%m/%Y') AS Fecha,
                                            a.estado AS Estado
                                            FROM
                                            bandeja_ent AS a" 
                                            . $whereClause 
                                            . $orderClause)->rowCount();
    
    $find_sql = "SELECT
                a.id AS id,
                a.destinatario AS Destitatario,
                a.mensaje AS Mensaje,
                date_format(a.fecha_envio,'%d/%m/%Y') AS Fecha,
                a.estado AS Estado
                FROM
                bandeja_ent AS a" 
                . $whereClause 
                . $orderClause . 
                " LIMIT ". $index . "," 
                . $rowsPerPage;
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
$app->match('/bandeja_ent/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . bandeja_ent . " WHERE ".$idfldname." = ?";
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



$app->match('/bandeja_ent/{id}', function ($id) use ($app) {
    
    $table_columns = array(
        'Destitatario', 
        'Mensaje', 
        'Fecha', 
        'Estado', 
    );

    $primary_key = "id";

    return $app['twig']->render('bandeja_ent/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key,
        "buscar" => $id
    ));
        
})
->bind('bandeja_ent_list');



/*$app->match('/bandeja_ent/create', function () use ($app) {
    
    $initial_data = array(
		'id' => '', 
		'remitente' => '', 
		'destinatario' => '', 
		'estado' => '', 
		'fecha_envio' => '', 
		'mensaje' => '', 
		'recurso' => '', 
		'app_id' => '', 
		'usuario_id' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `id`, `id` FROM `app`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['id']] = $findexternal_row['id'];
	}
	if(count($options) > 0){
	    $form = $form->add('app_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options)),
            'label' => 'Aplicación'
	    ));
	}
	else{
	    $form = $form->add('app_id', 'text', array('required' => true,
                                                'label' => 'Aplicación'));
	}



	$form = $form->add('id', 'text', array('required' => true));
	$form = $form->add('remitente', 'text', array('required' => true));
	$form = $form->add('destinatario', 'text', array('required' => true));
	$form = $form->add('estado', 'text', array('required' => true));
	$form = $form->add('fecha_envio', 'text', array('required' => true));
	$form = $form->add('mensaje', 'textarea', array('required' => false));
	$form = $form->add('recurso', 'text', array('required' => false));
	$form = $form->add('usuario_id', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `bandeja_ent` (`id`, `remitente`, `destinatario`, `estado`, `fecha_envio`, `mensaje`, `recurso`, `app_id`, `usuario_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['remitente'], $data['destinatario'], $data['estado'], $data['fecha_envio'], $data['mensaje'], $data['recurso'], $data['app_id'], $data['usuario_id']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'bandeja_ent created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('bandeja_ent_list'));

        }
    }

    return $app['twig']->render('bandeja_ent/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('bandeja_ent_create');



$app->match('/bandeja_ent/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `bandeja_ent` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('bandeja_ent_list'));
    }

    
    $initial_data = array(
		'id' => $row_sql['id'], 
		'remitente' => $row_sql['remitente'], 
		'destinatario' => $row_sql['destinatario'], 
		'estado' => $row_sql['estado'], 
		'fecha_envio' => $row_sql['fecha_envio'], 
		'mensaje' => $row_sql['mensaje'], 
		'recurso' => $row_sql['recurso'], 
		'app_id' => $row_sql['app_id'], 
		'usuario_id' => $row_sql['usuario_id'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `id`, `id` FROM `app`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['id']] = $findexternal_row['id'];
	}
	if(count($options) > 0){
	    $form = $form->add('app_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('app_id', 'text', array('required' => true));
	}


	$form = $form->add('id', 'text', array('required' => true));
	$form = $form->add('remitente', 'text', array('required' => true));
	$form = $form->add('destinatario', 'text', array('required' => true));
	$form = $form->add('estado', 'text', array('required' => true));
	$form = $form->add('fecha_envio', 'text', array('required' => true));
	$form = $form->add('mensaje', 'textarea', array('required' => false));
	$form = $form->add('recurso', 'text', array('required' => false));
	$form = $form->add('usuario_id', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `bandeja_ent` SET `id` = ?, `remitente` = ?, `destinatario` = ?, `estado` = ?, `fecha_envio` = ?, `mensaje` = ?, `recurso` = ?, `app_id` = ?, `usuario_id` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['remitente'], $data['destinatario'], $data['estado'], $data['fecha_envio'], $data['mensaje'], $data['recurso'], $data['app_id'], $data['usuario_id'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'bandeja_ent edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('bandeja_ent_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('bandeja_ent/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('bandeja_ent_edit');
*/


$app->match('/bandeja_ent/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `bandeja_ent` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `bandeja_ent` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'bandeja_ent deleted!',
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

    return $app->redirect($app['url_generator']->generate('bandeja_ent_list'));

})
->bind('bandeja_ent_delete');






