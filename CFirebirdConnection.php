<?php

/**
 * CFirebirdConnection class file.
 *
 * @author Edgard Messias <edgardmessias@gmail.com>
 */

/**
 * CFirebirdConnection represents the connection for a Firebird database.
 *
 * @author Edgard Messias <edgardmessias@gmail.com>
 */
class CFirebirdConnection extends CDbConnection
{
    /**
     * @var array mapping between PDO driver and schema class name.
     * A schema class can be specified using path alias.
     * @since 1.1.6
     */
    public $driverMap = array(
        'firebird' => 'CFirebirdSchema', // Firebird driver
    );

    /**
     * @var string Custom PDO wrapper class.
     * @since 1.1.8
     */
    public $pdoClass = 'CFirebirdPdoAdapter';

}

/**
 * Auto Import
 * No need to add to main.php:
 * 'import'=>array(
 *  ...
 *  'ext.YiiFirebird.*',
 *  ...
 * ),
 */
$dir = dirname(__FILE__);
$alias = md5($dir);
Yii::setPathOfAlias($alias, $dir);
Yii::import($alias . '.*');
