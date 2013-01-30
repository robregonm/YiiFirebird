YiiFirebird
===========

Firebird Adapter for Yii 1.1.x
******************************

This is an updated version of the adapter originally posted by
idlesign. It has been enhanced and tested with gii from yii 1.1.9.

php 5.3.10 is required. Previous versions of PDO_Firebird do
not return the number of rows affected by a dml statement.

This version is marked 1.0

Requirements
============

* PHP 5.3.10 (or above)
* PDO_Firebird extension enabled.
* Firebird 2.5 (not tested on previous versions)
* Yii 1.1.9


Summary of new features and fixes
==============================================

* Simplified installation process
* Added support for transactions
* Added support for date & time data types handling.
* Improved BLOB support.
* Fixed type code for INT64.


FirebirdSchema
* Forced all metadata objects to lowercase for compatibility with gii
* Changed system table queries to use JOINS instead of WHERE conditions.
   This is partly a matter of style but WHERE joins are deprecated.
* Support of composite primary keys
* Fixed 'find Columns' do 'CFirebirdSchema' that always returns true, even if the table does not exist.


FirebirdCommandBuilder
* Added public $returnID for use with INSERT ... RETURNING ...
* Added getLastInsertID
* Added createInsertCommand
* Added createUpdateCommand
* Fixed applyLImit to correctly handle all variations of $limit and $offset


Installation
========

* Unpack the adapter to `protected/extensions`
* In your `protected/config/main.php`, add the following:

```php
<?php
...
  'components' => array(
  ...
    'db' => array(
      'connectionString'=>'firebird:dbname=localhost:C:\Path\To\Db\MyDB.GDB',
      'class' => 'ext.YiiFirebird.CFirebirdConnection',
    ),
    ...
  ),
...
```

Thanks to
=========

@idlesign, @robregonm, @edgardmessias, @mr-rfh, @mlorentz75
