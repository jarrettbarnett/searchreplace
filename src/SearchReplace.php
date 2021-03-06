<?php namespace SearchReplace;

use SearchReplace\SearchReplaceDatabase;
use SearchReplace\DatabaseTypes\MySQLi;
use SearchReplace\SearchReplaceException as Exception;

/**
 * Class SearchReplace
 *
 * @property bool include_all_tables
* @package SearchReplace
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Jarrett Barnett <hello@jarrettbarnett.com>
 */
class SearchReplace
{
    // DEFAULTS
    const DEFAULT_TABLE_ROW_OFFSET = 0;
    const DEFAULT_TABLE_ROW_LIMIT = null;
    const DEFAULT_TABLE_ROWS_PER_BATCH = 100;
    
    /**
     * Collection of search/replace properties
     */
    protected $search_property;
    protected $search_regex;
    protected $replace_property;
    protected $replace_regex;

    /**
     * Collection of tables to execute search on
     * @var (array) $tables
     */
    protected $tables = [];
    protected $tables_include_all = false;
    protected $tables_include = [];
    protected $tables_exclude = [];
    protected $table_offset;
    protected $table_limit;
    protected $table_row_offset = self::DEFAULT_TABLE_ROW_OFFSET;
    protected $table_row_limit = self::DEFAULT_TABLE_ROW_LIMIT;
    protected $table_rows_per_batch = self::DEFAULT_TABLE_ROWS_PER_BATCH;
    
    /**
     * @var $db
     */
    protected $db;

    /**
     * @var bool $exceptions - enable/disable exceptions
     */
    protected $exceptions = true;

    /**
     * @var array $errors - collection of errors before or during execution
     */
    protected $errors;

    /**
     * SearchReplace constructor.
     * @param $db_resource
     */
    public function __construct($db_resource = '')
    {
        if (!empty($db_resource))
        {
            $this->setDatabase($db_resource);
        }
    }

    public function db()
    {
        return $this->db;
    }
    
    /**
     * Search
     *
     * @param $search
     * @param bool $regex
     * @return $this
     */
    public function search($search, $regex = false)
    {
        $this->search_property = $search;
        $this->search_regex = $regex;
        return $this;
    }
    
    /**
     * Replace
     *
     * @param $replace
     * @param bool $regex
     * @return $this
     */
    public function replace($replace, $regex = false)
    {
        $this->replace_property = $replace;
        $this->replace_regex = $regex;
        return $this;
    }

    /**
     * Set Database
     * @param $resource_or_host
     * @param $username
     * @param $password
     * @param $database
     * @return mixed
     */
    public function setDatabase($resource_or_host = '', $username = '', $password = '', $database = '')
    {
        if (empty($resource_or_host)) return $this->throwError('setDatabase(): Database resource/hostname cannot be empty');

        if (is_object($resource_or_host)) {
            $this->db = $resource_or_host;

            return $this;
        }

        if (empty($username)) return $this->throwError('setDatabase(): Username parameter cannot be empty.');
        if (empty($database)) return $this->throwError('setDatabase(): Database name parameter cannot be empty.');

        // empty passwords are allowed
        $password = (empty($password)) ? '' : $password;

        $instance = new SearchReplaceDatabase($resource_or_host, $username, $password, $database);
        $this->db = $instance;

        return $this;
    }

    /**
     * Throw Error
     * @param $error_message
     * @param bool $return_value_if_disabled
     * @return bool
     * @throws Exception
     */
    public function throwError($error_message, $return_value_if_disabled = false)
    {
        if ($this->exceptions)
        {
            throw new Exception($error_message);
        }

        return $return_value_if_disabled;
    }

    /**
     * Enable Exceptions
     * @return $this
     */
    public function enableExceptions()
    {
        $this->exceptions = true;

        return $this;
    }

    /**
     * Disable Exceptions
     * @return $this
     */
    public function disableExceptions()
    {
        $this->exceptions = false;

        return $this;
    }

    /**
     * Include All Tables
     * @param $bool (bool)
     * @return mixed
     */
    public function includeAllTables($bool = true)
    {
        if (!is_bool($bool)) return $this->throwError('includeAllTables(): Non-boolean value supplied.');

        $this->tables_include_all = $bool;

        return $this;
    }

    /**
     * Include Tables
     * @param $tables
     * @param bool $override
     * @return $this
     */
    public function includeTables($tables, $override = false)
    {
        if ($override) {
            $this->tables_include = $tables;

            return $this;
        }

        $this->tables_include = array_merge($this->tables_include, $tables);

        return $this;
    }

    /**
     * Exclude Tables
     * @param $tables
     * @return $this
     */
    public function excludeTables($tables, $override = false)
    {
        if ($override) {
            $this->tables_exclude = $tables;

            return $this;
        }
        
        $this->tables_exclude = array_merge($this->tables, $tables);

        return $this;
    }

    /**
     * Get Tables
     * @return array
     */
    public function getTables()
    {
        $this->prepareTables();
        
        return $this->tables;
    }

    /**
     * Get Table Includes
     * @return array
     */
    public function getTableIncludes()
    {
        return $this->tables_include;
    }

    /**
     * Get Table Excludes
     * @return array
     */
    public function getTableExcludes()
    {
        return $this->tables_exclude;
    }

    /**
     * Reset Tables (Inclusions and Exclusions)
     * @return $this
     */
    public function resetTables()
    {
        $this->tables_include = [];
        $this->tables_exclude = [];

        return $this;
    }

    /**
     * Start table execution at a specific offset
     * Useful for executing search in batches
     * @param $offset
     * @return mixed
     */
    public function setTableOffset($offset)
    {
        if (!is_numeric($offset)) return $this->throwError('Table offset must be a number!');

        $this->table_offset = (int) $offset;

        return $this;
    }
    
    /**
     * Get Table Row Offset
     * @return int
     */
    public function getTableRowOffset()
    {
        return $this->table_row_offset;
    }
    
    /**
     * Get Table Row Limit
     * @return int
     */
    public function getTableRowLimit()
    {
        return $this->table_row_limit;
    }
    
    /**
     * Get Table Offset
     * @return int
     */
    public function getTableOffset()
    {
        return $this->table_offset;
    }

    /**
     * Set limit on table list
     * @param $limit
     * @return mixed
     */
    public function setTableLimit($limit)
    {
        if (!is_numeric($limit)) return $this->throwError('Table limit must be a number!');

        $this->table_limit = (int) $limit;

        return $this;
    }
    
    /**
     * Get Table Limit
     * @return int
     */
    public function getTableLimit()
    {
        return $this->table_limit;
    }

    /**
     * Set offset and limit on table list
     * @param $offset
     * @param $limit
     * @return $this
     */
    public function setTableRange($offset, $limit)
    {
        $this->setTableOffset($offset);
        $this->setTableLimit($limit);

        return $this;
    }

    /**
     * Start row search/replace at a specific row offset
     * Useful for executing search/replacements on a specific table in batches
     * @param $offset
     * @return $this
     */
    public function setTableRowOffset($offset)
    {
        $this->table_row_offset = (int) $offset;

        return $this;
    }

    /**
     * Limit how many rows are executed per table
     * Useful for executing search/replacements on a specific table in batches
     * @param $limit
     * @return $this
     */
    public function setTableRowLimit($limit)
    {
        $this->table_row_limit = (int) $limit;

        return $this;
    }

    /**
     * Set table row range (limit and offset)
     * @param $offset
     * @param $limit
     * @return $this
     */
    public function setTableRowRange($offset, $limit)
    {
        $this->setTableRowOffset($offset);
        $this->setTableRowLimit($limit);

        return $this;
    }

    /**
     * Set Table Rows Per Batch
     * @param $rows_per_batch
     * @return $this
     */
    public function setTableRowsPerBatch($rows_per_batch)
    {
        if (!is_numeric($rows_per_batch)) {
            $this->throwError('setTableRowsPerBatch(): Parameter must be a number');
        }
        
        $this->table_rows_per_batch = (int) $rows_per_batch;
        
        return $this;
    }

    /**
     * Verify Prerequisites
     *
     * @return array
     */
    public function verifyPrereqs()
    {
        if (empty($this->db))
        {
            $this->errors[] = 'Database connection not provided.';
        }

        return $this->errors;
    }

    /**
     * Reset The Request For Another Go-Round'
     * @return $this
     */
    public function reset()
    {
        $this->tables = [];
        $this->tables_include = [];
        $this->tables_exclude = [];
        $this->table_offset = [];
        $this->table_limit = [];
        $this->table_row_offset = [];
        $this->table_row_limit = [];
        
        return $this;
    }

    /**
     * Reset Errors
     * @return $this
     */
    protected function resetErrors()
    {
        $this->errors = [];

        return $this;
    }

    /**
     * Append Error
     * @param $error
     * @return $this
     */
    protected function appendError($error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Prepare
     * @return $this
     */
    private function prepare()
    {
        $this->prepareTables();
        
        return $this;
    }

    /**
     * Prepare Tables
     * @return $this
     */
    private function prepareTables()
    {
        if ($this->tables_include_all === true) {
            $this->tables = array_merge($this->tables, $this->db->getAllTables());
        }
        
        if (!empty($this->tables_include)) {
            $this->tables = array_merge($this->tables, $this->tables_include);
        }
        
        if (!empty($this->tables_exclude)) {
            $this->tables = array_diff($this->tables, $this->tables_exclude);
        }
        
        return $this;
    }

    /**
     * Execute!
     */
    public function execute()
    {
        $this->verifyPrereqs();
        $this->prepare();
    }
}
