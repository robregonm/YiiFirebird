<?php

/**
 * CFirebirdCommandBuilder class file.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @modified by Ricardo Obregón <robregonm@gmail.com>
 */

/**
 * CFirebirdCommandBuilder provides basic methods to create query commands for tables of Firebird Servers.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @modified by Ricardo Obregón <robregonm@gmail.com>
 */
class CFirebirdCommandBuilder extends CDbCommandBuilder
{
    /**
     *
     * @var CDbCommand 
     */
    private $_command = null;

    /**
     * Returns the last insertion ID for the specified table.
     * @param mixed $table the table schema ({@link CDbTableSchema}) or the table name (string).
     * @return mixed last insertion id. Null is returned if no sequence name.
     */
    public function getLastInsertID($table)
    {
        if ($this->_command !== null) {
            $lastId = $this->_command->pdoStatement->fetchColumn();
            if ($lastId !== false) {
                return $lastId;
            }
        }
        return null;
    }

    /**
     * Alters the SQL to apply LIMIT and OFFSET.
     *
     * @param string SQL query string without LIMIT and OFFSET.
     * @param integer maximum number of rows, -1 to ignore limit.
     * @param integer row offset, -1 to ignore offset.
     * @return string SQL with LIMIT and OFFSET
     *
     * How to deal with passing -1 ?
     *
     * If both $limit and $offset are -1 then we don't need to
     * adjust the $sql.
     *
     * if $offset is -1 then it is ignored. If $limit is set to a
     * positive value then return first $limit rows.
     *
     * if $limit is -1 then it is ignored. If $offset has a value return
     * all in set starting from $offset. Firebird can't use
     * ROWS..TO.. syntax to do this so we use SKIP.
     *
     */
    public function applyLimit($sql, $limit, $offset)
    {

        $limit = $limit !== null ? intval($limit) : -1;
        $offset = $offset !== null ? intval($offset) : -1;

        // If ignoring both params then do nothing
        if ($offset < 0 && $limit < 0) {
            return $sql;
        }

        // If we are ignoring limit then return full result set starting
        // from $offset. In Firebird this can only be done with SKIP
        if ($offset >= 0 && $limit < 0) {
            $count = 1; //Only do it once
            $sql = preg_replace('/^SELECT /i', 'SELECT SKIP ' . (int) $offset . ' ', $sql, $count);
            return $sql;
        }

        // If we are ignoring $offset then return $limit rows.
        // ie, return the first $limit rows in the set.
        if ($offset < 0 && $limit >= 0) {
            $rows = $limit;
            $sql .= ' ROWS ' . (int) $rows;
            return $sql;
        }

        // Otherwise apply the params and return the amended sql.
        if ($offset >= 0 && $limit >= 0) {

            // calculate $rows for ROWS...
            $rows = $offset + 1;
            $sql .= ' ROWS ' . (int) $rows;

            // calculate $to for TO...
            $to = $offset + $limit;
            $sql .= ' TO ' . (int) $to;

            return $sql;
        }

        // If we have fallen through the cracks then just pass
        // the sql back.
        return $sql;
    }

    /**
     * Creates an INSERT command.
     * @param mixed $table the table schema ({@link CDbTableSchema}) or the table name (string).
     * @param array $data data to be inserted (column name=>column value). If a key is not a valid column name, the corresponding value will be ignored.
     * @return CDbCommand insert command
     */
    public function createInsertCommand($table, $data)
    {
        $this->ensureTable($table);
        $fields = array();
        $values = array();
        $placeholders = array();
        $i = 0;
        foreach ($data as $name => $value) {
            if (($column = $table->getColumn($name)) !== null && ($value !== null || $column->allowNull)) {
                $fields[] = $column->rawName;
                if ($value instanceof CDbExpression) {
                    $placeholders[] = $value->expression;
                    foreach ($value->params as $n => $v)
                        $values[$n] = $v;
                } else {
                    $placeholders[] = self::PARAM_PREFIX . $i;
                    $values[self::PARAM_PREFIX . $i] = $column->typecast($value);
                    $i++;
                }
            }
        }
        if ($fields === array()) {
            $pks = is_array($table->primaryKey) ? $table->primaryKey : array($table->primaryKey);
            foreach ($pks as $pk) {
                $fields[] = $table->getColumn($pk)->rawName;
                $placeholders[] = 'NULL';
            }
        }

        $sql = "INSERT INTO {$table->rawName} (" . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';

        if (is_string($table->primaryKey) && ($column = $table->getColumn($table->primaryKey)) !== null && $column->type !== 'string') {
            $sql.=' RETURNING ' . $column->rawName;
            $command = $this->getDbConnection()->createCommand($sql);
            $table->sequenceName = $column->rawName;
        } else {
            $command = $this->getDbConnection()->createCommand($sql);
        }

        foreach ($values as $name => $value) {
            $command->bindValue($name, $value);
        }

        $this->_command = $command;

        return $command;
    }

}
