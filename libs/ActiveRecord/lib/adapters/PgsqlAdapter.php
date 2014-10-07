<?php

/**
 * @package ActiveRecord
 */

namespace ActiveRecord;

/**
 * Adapter for Postgres (not completed yet)
 *
 * @package ActiveRecord
 */
class PgsqlAdapter extends Connection
{

    static $QUOTE_CHARACTER = '"';
    static $DEFAULT_PORT    = 5432;

    public function get_sequence_name($table, $column_name) {
        return "{$table}_{$column_name}_seq";
    }

    public function limit($sql, $offset, $limit) {
        return $sql . ' LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);
    }

    public function native_database_types() {
        return array(
            'primary_key'      => 'serial primary key',
            'string'           => array('name' => 'character varying', 'length' => 255),
            'text'             => array('name' => 'text'),
            'integer'          => array('name' => 'integer'),
            'float'            => array('name' => 'float'),
            'datetime'         => array('name' => 'datetime'),
            'timestamp'        => array('name' => 'timestamp'),
            'time'             => array('name' => 'time'),
            'date'             => array('name' => 'date'),
            'binary'           => array('name' => 'binary'),
            'boolean'          => array('name' => 'boolean'),
            'bigint'           => array('name' => 'integer'),
            'smallint'         => array('name' => 'integer'),
            'real'             => array('name' => 'float'),
            'double precision' => array('name' => 'float'),
            'numeric'          => array('name' => 'float'),
            'decimal'          => array('name' => 'float')
        );
    }

    public function next_sequence_value($sequence_name) {
        return "nextval('" . str_replace("'", "\\'", $sequence_name) . "')";
    }

    public function query_column_info($table) {
        $sql = <<<SQL
SELECT
      a.attname AS field,
      a.attlen,
      REPLACE(pg_catalog.format_type(a.atttypid, a.atttypmod), 'character varying', 'varchar') AS type,
      a.attnotnull AS not_nullable,
      (SELECT 't'
        FROM pg_index
        WHERE c.oid = pg_index.indrelid
        AND a.attnum = ANY (pg_index.indkey)
        AND pg_index.indisprimary = 't'
      ) IS NOT NULL AS pk,      
      (SELECT pg_get_expr(pg_attrdef.adbin, pg_attrdef.adrelid)
        FROM pg_attrdef
        WHERE c.oid = pg_attrdef.adrelid
        AND pg_attrdef.adnum=a.attnum
      ) AS default
FROM pg_attribute a, pg_class c, pg_type t
WHERE c.relname = ?
      AND a.attnum > 0
      AND a.attrelid = c.oid
      AND a.atttypid = t.oid
ORDER BY a.attnum
SQL;
        $values = array($table);
        return $this->query($sql, $values);
    }

    public function query_for_tables() {
        return $this->query("SELECT tablename FROM pg_tables WHERE schemaname NOT IN('information_schema','pg_catalog')");
    }

    public function set_encoding($charset) {
        $this->query("SET NAMES '$charset'");
    }

    public function supports_sequences() {
        return true;
    }

    public function create_column(&$column) {
        $c = new Column();
        $c->inflected_name = Inflector::instance()->variablize($column['field']);
        $c->name = $column['field'];
        $c->nullable = ($column['not_nullable'] ? false : true);
        $c->pk = ($column['pk'] ? true : false);
        $c->auto_increment = false;

        if(substr($column['type'], 0, 9) == 'timestamp') {
            $c->raw_type = 'datetime';
            $c->length = 19;
        }
        elseif($column['type'] == 'date') {
            $c->raw_type = 'date';
            $c->length = 10;
        }
        else {
            preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/', $column['type'], $matches);

            $c->raw_type = (count($matches) > 0 ? $matches[1] : $column['type']);
            $c->length = count($matches) >= 4 ? intval($matches[3]) : intval($column['attlen']);

            if($c->length < 0) {
                $c->length = null;
            }
        }

        $c->map_raw_type();

        if(!is_null($column['default'])) {
            // extract the default value
            $default = preg_replace("/^'(.*(?=':))'::[a-z_ ]+$/", "$1", $column['default']);

            // check for a sequence
            preg_match("/^nextval\('(.*)'::[a-z_ ]+\)$/", $default, $matches);

            if(count($matches) == 2) {
                $c->sequence = $matches[1];
            }
            else {
                // unescape single quotes in a default value
                $default = preg_replace("/''/", "'", $default);
                $c->default = $c->cast($default, $this);
            }
        }
        return $c;
    }

    public function boolean_to_string($value) {
        if(!$value || in_array(strtolower($value), array('f', 'false', 'n', 'no', 'off'))) {
            return "0";
        }
        else {
            return "1";
        }
    }
}

?>