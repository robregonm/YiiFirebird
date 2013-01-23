<?php
/**
 * CFirebirdColumnSchema class file.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 */

/**
 * CFirebirdColumnSchema class describes the column meta data of a Firebird table.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 */
class CFirebirdColumnSchema extends CDbColumnSchema
{
    private $DEFAULTS_DATETIME = array(
        '\'CURRENT_DATE\'',
        '\'CURRENT_TIME\'',
        '\'CURRENT_TIMESTAMP\'',
        '\'NOW\'',
        '\'TODAY\'',
        '\'TOMORROW\'',
        '\'YESTERDAY\'',
    );

    /**
     * Extracts the PHP type from DB type.
     * @param string DB type
     */
    protected function extractType($dbType)
    {
        // @todo Need to handle more data types here.

        if(strpos($dbType, 'LONG')!==false || strpos($dbType,'SHORT')!==false)
            $this->type = 'integer';
        else
            $this->type = 'string';
    }

    /**
     * Extracts the default value for the column.
     * The value is typecasted to correct PHP type.
     * @param mixed the default value obtained from metadata
     */
    protected function extractDefault($defaultValue)
    {
        $defaultValue = strtoupper($defaultValue);

        if(in_array($this->dbType, array('DATE', 'TIME', 'TIMESTAMP')) &&
            (in_array($defaultValue, $this->DEFAULTS_DATETIME) ||
            in_array("'$defaultValue'", $this->DEFAULTS_DATETIME)))
            $this->defaultValue=null;
        elseif($defaultValue== "''")
            $this->defaultValue='';
        else
            parent::extractDefault($defaultValue);
    }
 
}