<?php namespace SearchReplace;

use SearchReplace\SearchReplaceDatabaseInterface;
use SearchReplace\SearchReplaceException as Exception;

/**
 * Class SearchReplaceDatabase
 * @package SearchReplace
 * @author Jarrett Barnett <hello@jarrettbarnett.com>
 */
class SearchReplaceDatabase implements SearchReplaceDatabaseInterface
{
    protected $db, $host, $username, $password, $database;

    public function __construct($host, $username, $password, $database)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;

        return $this->db();
    }
    
    /**
     * Return db instance
     *
     * @return mixed
     * @throws SearchReplaceException
     */
    public function db()
    {
        $this->db = new \mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->db->connect_errno) {
            throw new Exception("Failed to connect to MySQL: (" . $this->db->connect_errno . ") " . $this->db->connect_error);
        }

        return $this;
    }
    
    public function getDatabase()
    {
        return $this->db;
    }
    
    public function useDatabase()
    {
        if (empty($this->database))
        {
            return false;
        }
        
        ($this->db)::select_db($this->database);
        
        return $this;
    }
    
    /**
     * Get All Tables
     * @return mixed
     */
    public function getAllTables()
    {
        $result = $this->db->query('SHOW TABLES');
        
        return $result->toArray();
    }
}
