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
            $sql = preg_replace('/^SELECT /i', 'SELECT SKIP ' . (int)$offset . ' ', $sql, $count);
            return $sql;
        }

        // If we are ignoring $offset then return $limit rows.
        // ie, return the first $limit rows in the set.
        if ($offset < 0 && $limit >= 0) {
            $rows = $limit;
            $sql .= ' ROWS ' . (int)$rows;
            return $sql;
        }

        // Otherwise apply the params and return the amended sql.
        if ($offset >= 0 && $limit >= 0) {

            // calculate $rows for ROWS...
            $rows = $offset + 1;
            $sql .= ' ROWS ' . (int)$rows;

            // calculate $to for TO...
            $to = $offset + $limit;
            $sql .= ' TO ' . (int)$to;

            return $sql;
        }

        // If we have fallen through the cracks then just pass
        // the sql back.
        return $sql;
    }


}
