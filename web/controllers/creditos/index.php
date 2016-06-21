<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/creditos/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 

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
		'Moneda', 
		'Simbolo',
        'Credito', 
    );
    
    $table_columns_type = array(
		'bigint(20)', 
		'string(255)',
        'string(255)', 
		'decimal(17,2)', 

    );    
    $table_columns1 = array(
        'a.id', 
        'b.Moneda', 
        'b.Simbolo',
        'a.Credito', 
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
                                                b.moneda AS Moneda,
                                                b.simbolo AS Simbolo,
                                                a.credito AS Credito
                                                FROM
                                                creditos AS a
                                                INNER JOIN monedas AS b 
                                                ON a.moneda_id = b.id
                                                " . $whereClause)->rowCount();
    
    $find_sql = "SELECT
                                                a.id AS id,
                                                b.moneda AS Moneda,
                                                b.simbolo AS Simbolo,
                                                a.credito AS Credito
                                                FROM
                                                creditos AS a
                                                INNER JOIN monedas AS b 
                                                ON a.moneda_id = b.id
                                                ". $whereClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/creditos/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
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
    
    $find_sql = "SELECT " . $fieldname . " FROM " . creditos . " WHERE ".$idfldname." = ?";
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



$app->match('/creditos', function () use ($app) {
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
        'Moneda', 
        'Simbolo',
        'Credito', 
    );

    $primary_key = "id";	

    return $app['twig']->render('creditos/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('creditos_list');



$app->match('/creditos/create', function () use ($app) {

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
		'moneda_id' => '', 
		'credito' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

    $find_sql = "SELECT `id`, CONCAT(`moneda`, ' ', `simbolo`) monedas FROM `monedas`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['monedas'];
    }

    $form = $form->add('moneda_id', 'choice', array('required' => true,
        "choices" => $datos,
        'label' => 'Moneda' 
        ));

	//$form = $form->add('moneda_id', 'text', array('required' => true));

	$form = $form->add('credito', 'text', array('required' => true,
        'label' => 'Credito'));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `creditos` (`moneda_id`, `credito`) VALUES (?, ?)";
            $app['db']->executeUpdate($update_query, array($data['moneda_id'], $data['credito']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Registro Guardado!',
                )
            );
            return $app->redirect($app['url_generator']->generate('creditos_list'));

        }
    }

    return $app['twig']->render('creditos/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('creditos_create');



$app->match('/creditos/edit/{id}', function ($id) use ($app) {

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
    $find_sql = "SELECT * FROM `creditos` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('creditos_list'));
    }

    
    $initial_data = array(
		'moneda_id' => $row_sql['moneda_id'], 
		'credito' => $row_sql['credito'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);
    $find_sql = "SELECT `id`, CONCAT(`moneda`, ' ', `simbolo`) monedas FROM `monedas`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['id']] = $value['monedas'];
    }

    $form = $form->add('moneda_id', 'choice', array('required' => true,
        "choices" => $datos,
        'label' => 'Moneda'  
        ));

    //$form = $form->add('moneda_id', 'text', array('required' => true));
	$form = $form->add('credito', 'text', array('required' => true,
        'label' => 'Creditos' ));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `creditos` SET `moneda_id` = ?, `credito` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['moneda_id'], $data['credito'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Registro Guardado!',
                )
            );
            return $app->redirect($app['url_generator']->generate('creditos_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('creditos/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('creditos_edit');



$app->match('/creditos/delete/{id}', function ($id) use ($app) {

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
    $find_sql = "SELECT * FROM `creditos` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `creditos` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'Registro Eliminado!',
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

    return $app->redirect($app['url_generator']->generate('creditos_list'));

})
->bind('creditos_delete');






