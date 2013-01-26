<?php

/**
 * CFirebirdPdo class file
 *
 * @author idle sign <idlesign@yandex.ru>
 */

/**
 * This is an extension of default PDO class for Firebird driver only
 * It provides some missing functionalities of pdo driver
 * 
 * @author idle sign <idlesign@yandex.ru>
 */
class CFirebirdPdoAdapter extends PDO
{
    private $inTransaction = false;

    /**
     * Do some basic setup for Firebird.
     * o Force use of exceptions on error.
     * o Force all metadata to lower case.
     * 	 Yii will behave in unpredicatable ways if
     *   metadata is not lowercase.
     * o Ensure that table names are not prefixed to
     *    fieldnames when returning metadata.
     * Finally call parent constructor.
     *
     */
    function __construct($dsn, $username, $password, $driver_options = array())
    {
        // Windows OS paths with backslashes should be changed
        $dsn = str_replace("\\", "/", $dsn);
        // apply error mode
        $driver_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        // lower case column names in results are necessary for Yii ActiveRecord proper functioning
        $driver_options[PDO::ATTR_CASE] = PDO::CASE_LOWER;
        // ensure we only receive fieldname not tablename.fieldname.
        $driver_options[PDO::ATTR_FETCH_TABLE_NAMES] = FALSE;
        parent::__construct($dsn, $username, $password, $driver_options);
    }

    /**
     * Initiates a transaction
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function beginTransaction()
    {
        $this->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        $r = $this->exec("SET TRANSACTION");
        $success = ($r !== false);
        if ($success) {
            $this->inTransaction = true;
        }
        return ($success);
    }

    /**
     * Commits a transaction
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function commit()
    {
        $r = $this->exec("COMMIT");
        $this->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        $success = ($r !== false);
        if ($success) {
            $this->inTransaction = false;
        }
        return ($success);
    }

    /**
     * Rolls back a transaction
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function rollBack()
    {
        $r = $this->exec("ROLLBACK");
        $this->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        $success = ($r !== false);
        if ($success) {
            $this->inTransaction = false;
        }
        return ($success);
    }

	/**
	 * Checks if inside a transaction
	 * @return bool <b>TRUE</b> if a transaction is currently active, and <b>FALSE</b> if not.
	 */
    public function inTransaction()
    {
        return $this->inTransaction;
    }

}
