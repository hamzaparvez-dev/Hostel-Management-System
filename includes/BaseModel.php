<?php
/**
 * Base Model Class for Oracle Database Operations
 * Nav Purush Boys Hostel Management System
 */

class BaseModel {
    protected $conn;
    protected $table;
    protected $primaryKey = 'id';
    protected $sequence;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all records from table
     */
    public function getAll($orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " FETCH FIRST $limit ROWS ONLY";
        }
        
        $stmt = executeQuery($this->conn, $sql);
        return fetchAll($stmt);
    }
    
    /**
     * Get record by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $params = array(':id' => $id);
        $stmt = executeQuery($this->conn, $sql, $params);
        return fetchRow($stmt);
    }
    
    /**
     * Insert new record
     */
    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = executeQuery($this->conn, $sql, $data);
        
        // Get the inserted ID from sequence
        if ($this->sequence) {
            return getLastInsertId($this->conn, $this->sequence);
        }
        
        return true;
    }
    
    /**
     * Update record by ID
     */
    public function update($id, $data) {
        $setClause = '';
        foreach (array_keys($data) as $column) {
            $setClause .= "$column = :$column, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id";
        $data[':id'] = $id;
        
        $stmt = executeQuery($this->conn, $sql, $data);
        return getRowCount($stmt) > 0;
    }
    
    /**
     * Delete record by ID
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $params = array(':id' => $id);
        $stmt = executeQuery($this->conn, $sql, $params);
        return getRowCount($stmt) > 0;
    }
    
    /**
     * Find records by conditions
     */
    public function find($conditions, $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $whereClause = '';
        
        foreach (array_keys($conditions) as $column) {
            $whereClause .= "$column = :$column AND ";
        }
        $whereClause = rtrim($whereClause, ' AND ');
        
        $sql .= $whereClause;
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " FETCH FIRST $limit ROWS ONLY";
        }
        
        $stmt = executeQuery($this->conn, $sql, $conditions);
        return fetchAll($stmt);
    }
    
    /**
     * Find single record by conditions
     */
    public function findOne($conditions) {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $whereClause = '';
        
        foreach (array_keys($conditions) as $column) {
            $whereClause .= "$column = :$column AND ";
        }
        $whereClause = rtrim($whereClause, ' AND ');
        
        $sql .= $whereClause . " FETCH FIRST 1 ROWS ONLY";
        
        $stmt = executeQuery($this->conn, $sql, $conditions);
        return fetchRow($stmt);
    }
    
    /**
     * Count records
     */
    public function count($conditions = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($conditions) {
            $sql .= " WHERE ";
            $whereClause = '';
            foreach (array_keys($conditions) as $column) {
                $whereClause .= "$column = :$column AND ";
            }
            $whereClause = rtrim($whereClause, ' AND ');
            $sql .= $whereClause;
        }
        
        $stmt = executeQuery($this->conn, $sql, $conditions ?: array());
        $row = fetchRow($stmt);
        return $row['COUNT'];
    }
    
    /**
     * Execute custom query
     */
    public function query($sql, $params = array()) {
        $stmt = executeQuery($this->conn, $sql, $params);
        return fetchAll($stmt);
    }
    
    /**
     * Execute custom query and return single row
     */
    public function queryOne($sql, $params = array()) {
        $stmt = executeQuery($this->conn, $sql, $params);
        return fetchRow($stmt);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        oci_execute(oci_parse($this->conn, "BEGIN"));
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        oci_commit($this->conn);
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        oci_rollback($this->conn);
    }
}
?> 