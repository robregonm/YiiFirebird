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
        'firebird' => 'CFirebirdConnection', // Informix driver
    );
}