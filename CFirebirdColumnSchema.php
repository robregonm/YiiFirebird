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
        if (stripos($dbType, 'int') !== false && stripos($dbType, 'unsigned int') === false) {
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
        $defaultValue = strtoupper($defaultValue);

        if (in_array($this->dbType, array('DATE', 'TIME', 'TIMESTAMP')) &&
                (in_array($defaultValue, $this->DEFAULTS_DATETIME) ||
                in_array("'$defaultValue'", $this->DEFAULTS_DATETIME))) {
            $this->defaultValue = null;
        } elseif ($defaultValue == "''") {
            $this->defaultValue = '';
        } else {
            parent::extractDefault($defaultValue);
        }
    }

}
