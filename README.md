YiiFirebird
===========

Firebird Adapter for Yii 1.1.x
******************************

This is an updated version of the adapter originally posted by
idlesign. It has been enhanced and tested with gii from yii 1.1.9.

php 5.3.10 is required. Previous versions of PDO_Firebird do
not return the number of rows affected by a dml statement.

This version is marked 0.8 


Summary of new features and fixes
==============================================

FirebirdSchema
o Forced all metadata objects to lowercase for compatibility with gii
o Changed system table queries to use JOINS instead of WHERE conditions.
   This is partly a matter of style but WHERE joins are deprecated.

FirebirdCommandBuilder
o Added public $returnID for use with INSERT ... RETURNING ...
o Added getLastInsertID
o Added createInsertCommand
o Added createUpdateCommand
o Fixed applyLImit to correctly handle all variations of $limit and $offset


Installation
========

* Unpack the adapter to `protected/extensions`
* In your `protected/config/main.php`, add the following:

```php
<?php
...
  'import'=>array(
    ...
    'ext.YiiFirebird.*',
    ...
	),
...
  'components' => array(
  ...
    'db' => array(
      'connectionString'=>'firebird:dbname=localhost:C:\DataBase\NetSchool\DB\MAIN30.GDB',
      'class' => 'ext.YiiFirebird.CFirebirdConnection',
    ),
    ...
  ),
...
```
