<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/__TABLENAME__/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $qsStart = (int)($request->query->all()["start"]);
    $search = ($request->query->all()["search"]);
    $order = ($request->query->all()["order"]);
    $columns = ($request->query->all()["columns"]);
    $qsLength = (int)($request->query->all()["length"]);
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search[value];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue[column]][data] . " " . $orderValue[dir];
    }
    
    $table_columns = array(
__TABLECOLUMNS_ARRAY__
    );
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " or"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `__TABLENAME__`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `__TABLENAME__`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    $queryData = new queryData();
    $queryData->draw = $draw;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows_sql;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});

$app->match('/__TABLENAME__', function () use ($app) {
    
	$table_columns = array(
__TABLECOLUMNS_ARRAY__
    );

    $primary_key = "__TABLE_PRIMARYKEY__";	

    return $app['twig']->render('__TABLENAME__/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('__TABLENAME___list');



$app->match('/__TABLENAME__/create', function () use ($app) {
    
    $initial_data = array(
__TABLECOLUMNS_INITIALDATA_EMPTY_ARRAY__
    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

__EXTERNALSFIELDS_FOR_FORM__

__FIELDS_FOR_FORM__

    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `__TABLENAME__` (__INSERT_QUERY_FIELDS__) VALUES (__INSERT_QUERY_VALUES__)";
            $app['db']->executeUpdate($update_query, array(__INSERT_EXECUTE_FIELDS__));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => '__TABLENAME__ created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('__TABLENAME___list'));

        }
    }

    return $app['twig']->render('__TABLENAME__/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('__TABLENAME___create');



$app->match('/__TABLENAME__/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `__TABLENAME__` WHERE `__TABLE_PRIMARYKEY__` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('__TABLENAME___list'));
    }

    
    $initial_data = array(
__TABLECOLUMNS_INITIALDATA_ARRAY__
    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

__EXTERNALSFIELDS_FOR_FORM__
__FIELDS_FOR_FORM__

    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `__TABLENAME__` SET __UPDATE_QUERY_FIELDS__ WHERE `__TABLE_PRIMARYKEY__` = ?";
            $app['db']->executeUpdate($update_query, array(__UPDATE_EXECUTE_FIELDS__, $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => '__TABLENAME__ edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('__TABLENAME___edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('__TABLENAME__/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('__TABLENAME___edit');



$app->match('/__TABLENAME__/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `__TABLENAME__` WHERE `__TABLE_PRIMARYKEY__` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `__TABLENAME__` WHERE `__TABLE_PRIMARYKEY__` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => '__TABLENAME__ deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('__TABLENAME___list'));

})
->bind('__TABLENAME___delete');






