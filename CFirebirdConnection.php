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
    public $driverMap = array(
        'firebird' => 'CFirebirdSchema', // Informix driver
    );
    
    /**
	  * Overritde to force use of Firebird PDO Adapter.
    * Finally call parent init.
    */    
    public function init()
    {
      $this->pdoClass = "CFirebirdPdoAdapter";
      parent::init();
    }        
}