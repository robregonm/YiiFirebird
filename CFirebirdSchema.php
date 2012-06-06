<?php

/**
 * CFirebirdSchema class file.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 */

/**
 * CFirebirdSchema is the class for retrieving metadata information
 * from a Firebird server database.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @updated by Ricardo Obregón <robregonm@gmail.com>
 */
class CFirebirdSchema extends CDbSchema
{
    /**
     * @var array the abstract column types mapped to physical column types.
     * @since 1.1.11
     */
    public $columnTypes=array(
        'pk' => 'INTEGER NOT NULL PRIMARY KEY',
        'string' => 'VARCHAR(255)',
        'text' => 'BLOB SUB_TYPE TEXT',
        'integer' => 'INTEGER',
        'float' => 'float',
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

		if ($this->findColumns($table))
		{
			$this->findConstraints($table);
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

		if (isset($parts[1]))
		{
			$table->schemaName = $parts[0];
			$table->name = strtolower($parts[1]);
			$table->rawName = $this->quoteTableName($table->schemaName) . '.' . $this->quoteTableName($table->name);
		} else
		{
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

		try
		{
			$fkeys = $this->getDbConnection()->createCommand($sql)->queryAll();
		}
		catch (Exception $e)
		{
			return false;
		}


		foreach ($fkeys as $fkey) {
			// Zoggo - Added strtolower here to guarantee that values are
			// returned lower case. Otherwise gii generates wrong code.

			$key = strtolower(rtrim($fkey['lfield']));
			$table->foreignKeys[$key] = array(strtolower(rtrim($fkey['ftable'])), strtolower(rtrim($fkey['ffield'])));

			if (isset($table->columns[$key]))
				$table->columns[$key]->isForeignKey = true;
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
                    tps.rdb$type_name AS ftype,
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
          join rdb$types tps on fld.rdb$field_type=tps.rdb$type
        WHERE rel.rdb$relation_name=upper(\'' . $table->name . '\')
            AND tps.rdb$field_name=\'RDB$FIELD_TYPE\'
        ORDER BY rel.rdb$field_position;';
		try
		{
			$columns = $this->getDbConnection()->createCommand($sql)->queryAll();
		}
		catch (Exception $e)
		{
			return false;
		}
		$sql = 'SELECT
                    idx.rdb$field_name AS fname
                FROM
                    rdb$relation_constraints rc
                  join rdb$index_segments idx on idx.rdb$index_name=rc.rdb$index_name
                WHERE rc.rdb$constraint_type=\'PRIMARY KEY\'
					AND rc.rdb$relation_name=upper(\'' . $table->name . '\')';
		try
		{
			$primary = $this->getDbConnection()->createCommand($sql)->queryRow();
		}
		catch (Exception $e)
		{
			return false;
		}
		foreach ($columns as $key => $column) {
			$columns[$key]['fprimary'] = rtrim($column['fname']) == rtrim($primary['fname']) ? true : false;
		}

		foreach ($columns as $column) {
			$c = $this->createColumn($column);
			$table->columns[$c->name] = $c;
			if ($c->isPrimaryKey)
			{
				if ($table->primaryKey === null)
					$table->primaryKey = $c->name;
				else if (is_string($table->primaryKey))
					$table->primaryKey = array($table->primaryKey, $c->name);
				else
					$table->primaryKey[] = $c->name;
			}
		}

		return true;
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
        $c->autoIncrement = $column['fautoinc']==='1';
        $defaultValue = null;
        if(!empty($column['fdefault']))
		    $defaultValue = str_ireplace('DEFAULT ', '', trim($column['fdefault']));
        if($defaultValue===null)
            $defaultValue = $column['fdefault_value'];
        if($defaultValue=='CURRENT_TIMESTAMP')
            $defaultValue = null;
		$c->init(rtrim($column['ftype']), $defaultValue);

		return $c;
	}

	/**
	 * Returns all table names in the database.
	 * 
	 * @param string the schema of the tables. Defaults to empty string, meaning the current or default schema.
	 * @return array all table names in the database.
	 */
	protected function findTableNames($schema='')
	{
		$sql = 'SELECT
                    rdb$relation_name
                FROM
                    rdb$relations
                WHERE
                    (rdb$view_blr is null) AND
                    (rdb$system_flag is null OR rdb$system_flag=0)';
		try
		{
			$tables = $this->getDbConnection()->createCommand($sql)->queryColumn();
		}
		catch (Exception $e)
		{
			return false;
		}

		foreach ($tables as $key => $table) {
			$tables[$key] = rtrim($table);
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

}