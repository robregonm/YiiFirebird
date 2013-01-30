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
        if (stripos($dbType, 'int') !== false && stripos($dbType, 'unsigned int') === false) {
            $this->type = 'integer';
        } elseif (stripos($dbType, 'bool') !== false) {
            $this->type = 'boolean';
        } elseif (preg_match('/(numeric|decimal|floa|doub)/i', $dbType)) {
            $this->type = 'double';
        } elseif (stripos($dbType, 'timestamp') !== false) {
            $this->type = 'timestamp';
        } elseif (stripos($dbType, 'date') !== false) {
            $this->type = 'date';
        } elseif (stripos($dbType, 'time') !== false) {
            $this->type = 'time';
        } elseif (stripos($dbType, 'text') !== false) {
            $this->type = 'text';
        } elseif (stripos($dbType, 'blob') !== false) {
            $this->type = 'binary';
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
        $defaultValue = strtoupper($defaultValue);

        /*
         * remove values from date/time columns with context variable
         * as the DB should set these values when saving them
         */
        if($this->type === 'date' or $this->type === 'time') {

            /*
             * handle CURRENT_DATE/TIME/TIMESTAMP with optional precision
             * @todo handle context variable 'NOW'
             * ref. http://www.firebirdsql.org/refdocs/langrefupd25-variables.html
             */
            if(strpos($defaultValue, 'CURRENT_DATE') === 0 or
               strpos($defaultValue, 'CURRENT_TIME') === 0 or
               in_array($defaultValue, array('NULL', 'TODAY', 'TOMORROW', 'YESTERDAY'))) {
                $this->defaultValue = null;
            }

        } elseif ($defaultValue == "''") {
            $this->defaultValue = '';
        } else {
            parent::extractDefault($defaultValue);
        }
    }

}
