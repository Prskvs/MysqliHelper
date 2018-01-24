# mysqliHelper

**Version 0.1.0**

PHP Helper class for mysqli commands.

This is a database class which uses mysqli commands with friendly functions.

You can use it for building php applications

This project is still in Alpha version. Some commands are missing, early stage prepared statements need review.

## Contributors

- Prskvs

## Copyright

Copyright (C) Prskvs 2017
<hr>

## Documentation

* [Require Class](#require-class)<br>
* [Initialization](#initialization)<br>
* [Database Connection](#database-connection)<br>
* [Database Creation](#database-creation)<br>
* [Table Creation](#table-creation)<br>
* [Prepared Table Insertion](#prepared-table-insertion)<br>
* [Data Selection](#data-selection)<br>
* [Prepared Data Selection](#prepared-data-selection)<br>
* [Table Update](#table-update)<br>
* [Table Entry Deletion](#table-entry-deletion)<br>
* [Kill Connection](#kill-connection)<br>

### Require Class
```php
require 'MysqliHelper';
```

### Initialization
Object creation requires database host, username, password and databace name.
```php
$db = new MysqliHelper ($host, $user, $pass, $dbname);
```
Table prefix exists as optional parameter.
```php
$db = new MysqliHelper ($host, $user, $pass, $dbname, $prefix);
```

### Database Connection
connect function tries to establish database connection. If optional parameter is true, it will try to select the database.
```php
$db->connect (true);
```

### Database Creation
If Database does not exist you can create it with the CreateDatabase function. If it's created, it will select it automatically.
Optional parameter is for charset and collation.
```php
$db->createDatabace ($params);
```

### Table Creation
createTable function creates new table to the already selected database. Columns parameter accepts array with column names as indexes and column definitions as values. **If prefix was declared at the object creation, it will be used automatically.**
```php
$db->createTable ($table_name, $columns_array = ['id' => "INT NOT NULL PRIMARY KEY", 'name' => "VARCHAR(30) NOT NULL DEFAULT 'Person'"]);
```
Optional parameters are for engine, charset, collation etc.
```php
$db->createTable ($table_name, $columns_array, $params);
```

### Table Insertion
insert function adds new entries to the desired table. It uses similar decleration as table creation.
```php
$db->insert ($table_name, $columns_array = ['name' => "Harry", 'job' => Pphotographer"]);
```
Optional parameter if true returns last inserted id.
```php
$db->insert ($table_name, $columns_array, true);
```

### Prepared Table Insertion
insertPrepared function uses prepared statements to insert entries to the desired table. Decleration requires columns array as template and gets values from the values parameter.
```php
$db->insert ($table_name, $columns_array = ['name', 'job'], $values_array = ['Harry', 'Photographer']);
```
Multiple insertions can be done by making the values array as an array of arrays.
```php
$db->insert ($table_name, $columns_array, $values_array = array(['Harry', 'Photographer'], ['John', 'Writer']));
```

### Data Selection
select function fetches data from the desired table. Requires at least table name. Columns parameter accepts string values, if left empty, asterisk (*) for all columns will be used. 
```php
$db->select ($table_name, $columns = "`name`, `lastname`");
```
Optional condition parameter can be used as string value.
```php
$db->select ($table_name, $columns, $condition = "WHERE `name` = 'Harry' ORDER BY `age` DESC");
```
If prefix is inserted at the creation of the database object, it can be used by simply adding a # at the begining of the table name
```php
$db = new MysqliHelper ($host, $user, $pass, $dbname, $prefix = 'mydb_');
$db->select ($table_name = '#users', $columns);
```
Joining tables is currently supported only as INNER JOIN. Optional join parameter accepts join conditions as string and adds the connection with the plus (+) character. It is **not** needed at the begining of the join parameter.
```php
$db->select ($table_name, $columns = "`#users`.`name` AS `user`, `#jobs`.`title` AS `job`, `#tools`.`item` AS `equipment`", $condition, $join = "`#jobs` ON `#users`.`id` = `#jobs`.`worker_id` + `#tools` ON `#users`.`id` = `#tools`.`user_id`");
```

### Prepared Data Selection
select function fetches data from the desired table. Requires at least table name. Columns parameter accepts string values, if left empty, asterisk (*) for all columns will be used.
```php
$db->selectPrepared ($table_name, $columns_array = ['name', 'job'], $values_array = ['Harry', 'Photographer']);
```
Multiple selections can be done by making the values array as an array of arrays.
```php
$db->selectPrepared ($table_name, $columns_array, $values_array = array(['Harry', 'Photographer'], ['John', 'Writer']));
```
Conditions and joins work the same as the non prepared select function.

### Table Update
update function changes the already inserted table entries of the desired table. Table name and values are required though conditions can be optional.
```php
$db->update ($table_name, $values = ['name' => 'Mike', 'last_name' => 'johnson'], $condition = "WHERE `id` = 6");
```

### Table Entry Deletion
delete function deletes entries from the desired table. Table name and values are required though conditions can be optional.
```php
$db->delete ($table_name, $condition = "WHERE `name` = `Harry`");
```

### Kill Connection
By using the function close or by destroying the object, database connection closes.
```php
$db->close ();
```
