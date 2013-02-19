<?php

/**
 * CFirebirdColumnSchema class file.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 * @updated by Edgard messias <edgardmessias@gmail.com>
 */

/**
 * CFirebirdColumnSchema class describes the column meta data of a Firebird table.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 * @updated by Edgard messias <edgardmessias@gmail.com>
 */
class CFirebirdColumnSchema extends CDbColumnSchema
{
    /**
     * Extracts the PHP type from DB type.
     * @param string DB type
     */
    protected function extractType($dbType)
    {
        // @todo Need to handle more data types here.
        //Types timestamp, date, time, text, blob are string in PHP
        if (stripos($dbType, 'int') !== false || stripos($dbType, 'quad') !== false) {
            $this->type = 'integer';
        } elseif (stripos($dbType, 'bool') !== false) {
            $this->type = 'boolean';
        } elseif (preg_match('/(numeric|decimal|floa|doub)/i', $dbType)) {
            $this->type = 'double';
        } else {
            $this->type = 'string';
        }
    }

    /**
     * Extracts the default value for the column.
     * The value is typecasted to correct PHP type.
     * @param mixed the default value obtained from metadata
     */
    protected function extractDefault($defaultValue)
    {
        /*
         * handle CURRENT_DATE/TIME/TIMESTAMP with optional precision
         * @todo handle context variable 'NOW'
         * ref. http://www.firebirdsql.org/refdocs/langrefupd25-variables.html
         */
        if (preg_match('/(CURRENT_|NULL|TODAY|TOMORROW|YESTERDAY)/i', $defaultValue)) {
            $this->defaultValue = null;
        } elseif ($defaultValue == "''") {
            $this->defaultValue = '';
        } else {
            parent::extractDefault($defaultValue);
        }
    }

}
