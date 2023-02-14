# laravel-sqlsrv
Before install make sure install sqlsrv and pdo_sqlsrv for php
SQLSRV DB driver for Laravel
The Microsoft drivers is required to enable SQL server for PHP applications

**Install package by composer:**
````composer log
 composer require julfiker/laravel-sqlsrv
````
**Post install:**  
Configure the service into the applicaton, Please add following into the config/app.php under the providers  
`Julfiker\SqlSrv\SqlSrvServiceProvider::class`

###Instruction to use into the laravel application to execute and procedure
````php

       $status_code = sprintf("%4000s", "");
       $status_message = sprintf("%4000s", "");
       $params = [
            "p_user_id" => 1, //OUTPUT parameter
            "o_status_code" => &$status_code, //OUT parameter
            "o_status_message" => &$status_message, // OUT parameter
        ];
       /** @var PDOStatement $sth */
       $sth = DB::executeProcedure('{SCHEMA_NAME}.{PROCEDURE_NAME}', $params);

       // If you have return sql statement from procedure you can use statement object $sth fetching data like as below
       $result = $sth->fetchAll();
       print_r($result);
       
````
You can use out parameters as you need that would bind from procedure end.

Also you can define parameter type and length in parameter like

````php
     $params = [
            "p_user_id" => ['value' => 1, 'length' => 400],'type' => PDO::PARAM_INPUT_OUTPUT] //OUTPUT parameter
     ];
     
     //Note: If you want to assign base64 content with the procedure param then you can keep null into the type and length, otherwise you might got error.
````

#####Any Help?
You can contact me through following access  
email: _mail.julfiker@gmail.com_  
skype: _eng.jewel_


### you are welcome to contribute on it further improvement/update or extended usability for all. Just make a pull request.  
Thank you
 

