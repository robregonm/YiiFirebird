<?php

/**
 * CFirebirdSchema class file.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 * @updated by Edgard messias <edgardmessias@gmail.com>
 */

/**
 * CFirebirdSchema is the class for retrieving metadata information
 * from a Firebird server database.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 * @updated by Edgard messias <edgardmessias@gmail.com>
 */
class CFirebirdSchema extends CDbSchema
{
    private $_sequences = array();

    /**
     * @var array the abstract column types mapped to physical column types.
     * @since 1.1.11
     */
    public $columnTypes = array(
        'pk' => 'INTEGER NOT NULL PRIMARY KEY',
        'string' => 'VARCHAR(255)',
        'text' => 'BLOB SUB_TYPE TEXT',
        'integer' => 'INTEGER',
        'float' => 'FLOAT',
        'decimal' => 'DECIMAL',
        'datetime' => 'TIMESTAMP',
        'timestamp' => 'TIMESTAMP',
        'time' => 'TIME',
        'date' => 'DATE',
        'binary' => 'BLOB',
        'boolean' => 'SMALLINT',
        'money' => 'DECIMAL(19,4)',
    );

    /**
     * Quotes a table name for use in a query.
     * We won't use quotes in Firebird.
     *
     * @param string table name
     * @return string the properly quoted table name
     */
    public function quoteTableName($name)
    {
        return $name;
    }

    /**
     * Quotes a column name for use in a query.
     * We won't use quotes in Firebird.
     *
     * @param string column name
     * @return string the properly quoted column name
     */
    public function quoteColumnName($name)
    {
        return $name;
    }

    /**
     * Creates a table instance representing the metadata for the named table.
     *
     * @return CFirebirdTableSchema driver dependent table metadata.
     * Null if the table does not exist.
     */
    protected function loadTable($name)
    {
        $table = new CFirebirdTableSchema;
        $this->resolveTableNames($table, $name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            if (is_string($table->primaryKey) && isset($this->_sequences[$table->rawName . '.' . $table->primaryKey])) {
                $table->sequenceName = $this->_sequences[$table->rawName . '.' . $table->primaryKey];
            } elseif (is_array($table->primaryKey)) {
                foreach ($table->primaryKey as $pk) {
                    if (isset($this->_sequences[$table->rawName . '.' . $pk])) {
                        $table->sequenceName = $this->_sequences[$table->rawName . '.' . $pk];
                        break;
                    }
                }
            }

            return $table;
        }

        return null;
    }

    /**
     * Generates various kinds of table names.
     *
     * @param CFirebirdTableSchema the table instance
     * @param string the unquoted table name
     *
     * All objects are forced to lower case for compatibility
     * with gii etc.but it means we have to call
     * upper($table->name) when we do metadata lookups.
     * This can be confusing.
     */
    protected function resolveTableNames($table, $name)
    {
        $parts = explode('.', str_replace('"', '', $name));

        if (isset($parts[1])) {
            $table->schemaName = $parts[0];
            $table->name = strtolower($parts[1]);
            $table->rawName = $this->quoteTableName($table->schemaName) . '.' . $this->quoteTableName($table->name);
        } else {
            $table->name = strtolower($parts[0]);
            $table->rawName = $this->quoteTableName($table->name);
        }
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param CFirebirdTableSchema the table metadata
     */
    protected function findConstraints($table)
    {
        // Zoggo - Converted sql to use join syntax
        $sql = 'SELECT
                    c.rdb$relation_name AS ftable,
                    d.rdb$field_name AS ffield,
                    e.rdb$field_name AS lfield
                FROM
                    rdb$ref_constraints b
                    join rdb$relation_constraints a on a.rdb$constraint_name=b.rdb$constraint_name
                    join rdb$relation_constraints c on b.rdb$const_name_uq=c.rdb$constraint_name
                    join rdb$index_segments d on c.rdb$index_name=d.rdb$index_name
                    join rdb$index_segments e on a.rdb$index_name=e.rdb$index_name
                WHERE
                    a.rdb$constraint_type=\'FOREIGN KEY\' AND
                    a.rdb$relation_name=upper(\'' . $table->name . '\') ';

        try {
            $fkeys = $this->getDbConnection()->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            return false;
        }


        foreach ($fkeys as $fkey) {
            // Zoggo - Added strtolower here to guarantee that values are
            // returned lower case. Otherwise gii generates wrong code.

            $key = strtolower(rtrim($fkey['lfield']));
            $table->foreignKeys[$key] = array(strtolower(rtrim($fkey['ftable'])), strtolower(rtrim($fkey['ffield'])));

            if (isset($table->columns[$key])) {
                $table->columns[$key]->isForeignKey = true;
            }
        }
    }

    /**
     * Collects the table column metadata.
     *
     * @param CFirebirdTableSchema the table metadata
     * @return boolean whether the table exists in the database
     */
    protected function findColumns($table)
    {
        // Zoggo - Converted sql to use join syntax
        // robregonm - Added isAutoInc
        $sql = 'SELECT
                    rel.rdb$field_name AS fname,
                    rel.rdb$default_source AS fdefault,
                    fld.rdb$field_type AS fcodtype,
                    fld.rdb$field_sub_type AS fcodsubtype,
                    fld.rdb$field_length AS flength,
                    fld.rdb$field_scale AS fscale,
                    fld.rdb$field_precision AS fprecision,
                    rel.rdb$null_flag AS fnull,
                    fld.rdb$default_value AS fdefault_value,
                    (SELECT 1 FROM RDB$TRIGGERS
                        WHERE RDB$SYSTEM_FLAG = 0
                        AND RDB$RELATION_NAME=upper(\'' . $table->name . '\')
                        AND RDB$TRIGGER_TYPE = 1
                        AND RDB$TRIGGER_INACTIVE = 0
                        AND (UPPER(REPLACE(RDB$TRIGGER_SOURCE,\' \',\'\')) LIKE \'%NEW.\'||TRIM(rel.rdb$field_name)||\'=GEN_ID%\'
                            OR UPPER(REPLACE(RDB$TRIGGER_SOURCE,\' \',\'\')) LIKE \'%NEW.\'||TRIM(rel.rdb$field_name)||\'=NEXTVALUEFOR%\'))
                    AS fautoinc
                FROM
                    rdb$relation_fields rel
                    join rdb$fields fld on rel.rdb$field_source=fld.rdb$field_name
                WHERE
                    rel.rdb$relation_name=upper(\'' . $table->name . '\')
                ORDER BY
                    rel.rdb$field_position;';
        try {
            $columns = $this->getDbConnection()->createCommand($sql)->queryAll();
            if (!$columns) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        $sql = 'SELECT
                    idx.rdb$field_name AS fname
                FROM
                    rdb$relation_constraints rc
                    join rdb$index_segments idx on idx.rdb$index_name=rc.rdb$index_name
                WHERE rc.rdb$constraint_type=\'PRIMARY KEY\'
					AND rc.rdb$relation_name=upper(\'' . $table->name . '\')';
        try {
            $pkeys = $this->getDbConnection()->createCommand($sql)->queryColumn();
        } catch (Exception $e) {
            return false;
        }
        $pkeys = array_map("rtrim", $pkeys);
        foreach ($columns as $key => $column) {
            $columns[$key]['fprimary'] = in_array(rtrim($column['fname']), $pkeys);
        }

        foreach ($columns as $column) {
            $c = $this->createColumn($column);
            if ($c->autoIncrement) {
                $this->_sequences[$table->rawName . '.' . $c->name] = $table->rawName . '.' . $c->name;
            }
            $table->columns[$c->name] = $c;
            if ($c->isPrimaryKey) {
                if ($table->primaryKey === null)
                    $table->primaryKey = $c->name;
                else if (is_string($table->primaryKey))
                    $table->primaryKey = array($table->primaryKey, $c->name);
                else
                    $table->primaryKey[] = $c->name;
            }
        }
        return (count($table->columns) > 0);
    }

    /**
     * Creates a table column.
     *
     * @param array column metadata
     * @return CFirebirdColumnSchema normalized column metadata
     */
    protected function createColumn($column)
    {
        $c = new CFirebirdColumnSchema;

        $c->name = strtolower(rtrim($column['fname']));
        $c->rawName = $this->quoteColumnName($c->name);
        $c->allowNull = $column['fnull'] !== '1';
        $c->isPrimaryKey = $column['fprimary'];
        $c->isForeignKey = false;
        $c->size = (int) $column['flength'];
        $c->scale = (int) $column['fscale'];
        $c->precision = (int) $column['fprecision'];
        $c->autoIncrement = $column['fautoinc'] === '1';
        $defaultValue = null;
        if (!empty($column['fdefault'])) {

            // remove whitespace, 'DEFAULT ' prefix and surrounding single quotes; all optional
            if (preg_match("/\s*(DEFAULT\s+){0,1}('(.*)'|(.*))\s*/i", $column['fdefault'], $parts)) {
                $defaultValue = array_pop($parts);
            }

            // handle escaped single quotes like in "funny''quoted''string"
            $defaultValue = str_replace('\'\'', '\'', $defaultValue);
        }
        if ($defaultValue === null) {
            $defaultValue = $column['fdefault_value'];
        }

        $type = "";

        $baseTypes = array(
            7 => 'SMALLINT',
            8 => 'INTEGER',
            16 => 'INT64',
            9 => 'QUAD',
            10 => 'FLOAT',
            11 => 'D_FLOAT',
            17 => 'BOOLEAN',
            27 => 'DOUBLE',
            12 => 'DATE',
            13 => 'TIME',
            35 => 'TIMESTAMP',
            261 => 'BLOB',
            37 => 'VARCHAR',
            14 => 'CHAR',
            40 => 'CSTRING',
            45 => 'BLOB_ID',
        );

        if (array_key_exists((int) $column['fcodtype'], $baseTypes)) {
            $type = $baseTypes[(int) $column['fcodtype']];
        }

        switch ((int) $column['fcodtype']) {
            case 7:
            case 8:
                switch ((int) $column['fcodsubtype']) {
                    case 1:
                        $type = 'NUMERIC';
                        break;
                    case 2:
                        $type = 'DECIMAL';
                        break;
                }
                break;
            case 16:
                switch ((int) $column['fcodsubtype']) {
                    case 1:
                        $type = 'NUMERIC';
                        break;
                    case 2:
                        $type = 'DECIMAL';
                        break;
                    default :
                        $type = 'BIGINT';
                        break;
                }
                break;
            case 261:
                switch ((int) $column['fcodsubtype']) {
                    case 1:
                        $type = 'TEXT';
                        break;
                }
                break;
        }

        $c->init(rtrim($type), $defaultValue);

        return $c;
    }

    /**
     * Returns all table names in the database.
     * 
     * @param string the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @return array all table names in the database.
     */
    protected function findTableNames($schema = '')
    {
        $sql = 'SELECT
                    rdb$relation_name
                FROM
                    rdb$relations
                WHERE
                    (rdb$view_blr is null) AND
                    (rdb$system_flag is null OR rdb$system_flag=0)';
        try {
            $tables = $this->getDbConnection()->createCommand($sql)->queryColumn();
        } catch (Exception $e) {
            return false;
        }

        foreach ($tables as $key => $table) {
            $tables[$key] = strtolower(rtrim($table));
        }

        return $tables;
    }

    /**
     * Creates custom command builder for the database.
     *
     * @return CFirebirdCommandBuilder command builder instance
     */
    protected function createCommandBuilder()
    {
        return new CFirebirdCommandBuilder($this);
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     * @since 1.1.13
     */
    public function renameTable($table, $newName)
    {
        throw new CDbException('Renaming a DB table is not supported by Firebird.');
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     * @return string the SQL statement for truncating a DB table.
     * @since 1.1.6
     */
    public function truncateTable($table)
    {
        return "DELETE FROM " . $this->quoteTableName($table);
    }

    /**
     * Builds a SQL statement for dropping a DB column.
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a DB column.
     * @since 1.1.6
     */
    public function dropColumn($table, $column)
    {
        return "ALTER TABLE " . $this->quoteTableName($table)
                . " DROP " . $this->quoteColumnName($column);
    }

    /**
     * Builds a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $name the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn($table, $name, $newName)
    {
        return "ALTER TABLE " . $this->quoteTableName($table) .
                " ALTER " . $this->quoteColumnName($name)
                . " TO " . $this->quoteColumnName($newName);
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The {@link getColumnType} method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     * @since 1.1.6
     */
    public function alterColumn($table, $column, $type)
    {
        $tableSchema = $this->getTable($table);
        $columnSchema = $tableSchema->getColumn(strtolower(rtrim($column)));

        $allowNullNewType = !preg_match("/not +null/i", $type);

        $type = preg_replace("/ +(not)? *null/i", "", $type);

        $baseSql = 'ALTER TABLE ' . $this->quoteTableName($table)
                . ' ALTER ' . $this->quoteColumnName($column) . ' '
                . ' TYPE ' . $this->getColumnType($type);


        if ($columnSchema->allowNull == $allowNullNewType) {
            return $baseSql;
        } else {
            $sql = 'EXECUTE BLOCK AS BEGIN'
                    . ' EXECUTE STATEMENT \'' . trim($baseSql, ';') . '\';'
                    . ' UPDATE RDB$RELATION_FIELDS SET RDB$NULL_FLAG = ' . ($allowNullNewType ? 'NULL' : '1')
                    . ' WHERE RDB$FIELD_NAME = UPPER(\'' . $column . '\') AND RDB$RELATION_NAME = UPPER(\'' . $table . '\');';

            /**
             * In any case (whichever option you choose), make sure that the column doesn't have any NULLs.
             * Firebird will not check it for you. Later when you backup the database, everything is fine, 
             * but restore will fail as the NOT NULL column has NULLs in it. To be safe, each time you change from NULL to NOT NULL.
             */
            if (!$allowNullNewType) {
                $sql .= ' UPDATE ' . $this->quoteTableName($table) . ' SET ' . $this->quoteColumnName($column) . ' = 0'
                        . ' WHERE ' . $this->quoteColumnName($column) . ' IS NULL;';
            }
            $sql .= ' END';

            return $sql;
        }
    }

}
