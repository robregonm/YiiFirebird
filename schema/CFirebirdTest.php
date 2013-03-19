<?php

$dir = dirname(__FILE__).'/..';
$alias = md5($dir);
Yii::setPathOfAlias($alias, $dir);
Yii::import($alias . '.CFirebirdConnection');

class CFirebirdTest extends CTestCase
{
	public function setUp()
	{
		if(!extension_loaded('pdo') || !extension_loaded('pdo_firebird'))
			$this->markTestSkipped('PDO and Firebird extensions are required.');
	}

	public function tearDown()
	{
	}
}