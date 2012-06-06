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

Unpack the adapter to framework/db/schema/firebird


Changes required to yii
=================

In YiiBase.php
---------------------

Make sure to add this:

		'CFirebirdColumnSchema' => '/db/schema/firebird/CFirebirdColumnSchema.php',
		'CFirebirdCommandBuilder' => '/db/schema/firebird/CFirebirdCommandBuilder.php',
		'CFirebirdPdoAdapter' => '/db/schema/firebird/CFirebirdPdoAdapter.php',
		'CFirebirdSchema' => '/db/schema/firebird/CFirebirdSchema.php',
		'CFirebirdTableSchema' => '/db/schema/firebird/CFirebirdTableSchema.php',

after this line (695 in yii-1.1.10.r3566) :

		'CDbTableSchema' => '/db/schema/CDbTableSchema.php',


In framework/db/CDbConnection.php
-----------------------------------------------------

createPdoInstance() should look like this:

	protected function createPdoInstance()
	{
		$pdoClass='PDO';
		if(($pos=strpos($this->connectionString,':'))!==false)
		{
			$driver=strtolower(substr($this->connectionString,0,$pos));
			if($driver==='mssql' || $driver==='dblib')
				$pdoClass='CMssqlPdoAdapter';
			if($driver==='firebird')
				$pdoClass='CFirebirdPdoAdapter';
		}
		return new $pdoClass($this->connectionString,$this->username,
									$this->password,$this->_attributes);
	}

in the $driverMap variable should look like this (line 236 in yii-1.1.10.r3566):

	public $driverMap=array(
		'pgsql'=>'CPgsqlSchema',    // PostgreSQL
		'mysqli'=>'CMysqlSchema',   // MySQL
		'mysql'=>'CMysqlSchema',    // MySQL
		'sqlite'=>'CSqliteSchema',  // sqlite 3
		'sqlite2'=>'CSqliteSchema', // sqlite 2
		'mssql'=>'CMssqlSchema',    // Mssql driver on windows hosts
		'dblib'=>'CMssqlSchema',    // dblib drivers on linux (and maybe others os) hosts
		'sqlsrv'=>'CMssqlSchema',   // Mssql
		'oci'=>'COciSchema',        // Oracle driver
		'firebird'=>'CFirebirdSchema',//Firebird driver
	)
