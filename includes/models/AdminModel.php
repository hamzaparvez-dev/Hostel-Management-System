<?php
/**
 * Admin Model Class
 * Nav Purush Boys Hostel Management System
 */

require_once 'BaseModel.php';

class AdminModel extends BaseModel {
    protected $table = 'admin';
    protected $sequence = 'admin_seq';
    
    public function __construct($conn) {
        parent::__construct($conn);
    }
    
    /**
     * Authenticate admin user
     */
    public function authenticate($email, $password) {
        $sql = "SELECT * FROM admin WHERE email = :email AND password = :password AND status = 1";
        $params = array(
            ':email' => $email,
            ':password' => $password
        );
        
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get admin by email
     */
    public function getAdminByEmail($email) {
        $sql = "SELECT * FROM admin WHERE email = :email";
        $params = array(':email' => $email);
        
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get admin by username
     */
    public function getAdminByUsername($username) {
        $sql = "SELECT * FROM admin WHERE username = :username";
        $params = array(':username' => $username);
        
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get all admins with roles
     */
    public function getAllAdminsWithRoles() {
        $sql = "SELECT id, username, email, role, status, reg_date, updation_date
                FROM admin
                ORDER BY reg_date DESC";
        
        return $this->query($sql);
    }
    
    /**
     * Get admin with login history
     */
    public function getAdminWithLoginHistory($adminId) {
        $sql = "SELECT a.*, 
                       COUNT(al.id) as login_count,
                       MAX(al.login_time) as last_login
                FROM admin a
                LEFT JOIN admin_log al ON a.id = al.admin_id
                WHERE a.id = :admin_id
                GROUP BY a.id, a.username, a.email, a.password, a.role, a.status, a.reg_date, a.updation_date";
        
        $params = array(':admin_id' => $adminId);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Record admin login
     */
    public function recordAdminLogin($adminId, $ip = null) {
        $sql = "INSERT INTO admin_log (id, admin_id, ip, login_time) 
                VALUES (admin_log_seq.NEXTVAL, :admin_id, :ip, SYSTIMESTAMP)";
        
        $params = array(
            ':admin_id' => $adminId,
            ':ip' => $ip ?: $_SERVER['REMOTE_ADDR']
        );
        
        return executeQuery($this->conn, $sql, $params);
    }
    
    /**
     * Record admin logout
     */
    public function recordAdminLogout($adminId) {
        $sql = "UPDATE admin_log 
                SET logout_time = SYSTIMESTAMP,
                    session_duration = EXTRACT(MINUTE FROM (SYSTIMESTAMP - login_time))
                WHERE admin_id = :admin_id 
                   AND logout_time IS NULL
                   AND login_time = (
                       SELECT MAX(login_time) 
                       FROM admin_log 
                       WHERE admin_id = :admin_id2
                   )";
        
        $params = array(
            ':admin_id' => $adminId,
            ':admin_id2' => $adminId
        );
        
        return executeQuery($this->conn, $sql, $params);
    }
    
    /**
     * Get admin login history
     */
    public function getAdminLoginHistory($adminId, $limit = 10) {
        $sql = "SELECT * FROM admin_log 
                WHERE admin_id = :admin_id
                ORDER BY login_time DESC
                FETCH FIRST $limit ROWS ONLY";
        
        $params = array(':admin_id' => $adminId);
        return $this->query($sql, $params);
    }
    
    /**
     * Get admin statistics
     */
    public function getAdminStatistics() {
        $stats = array();
        
        // Total admins
        $sql = "SELECT COUNT(*) as total_admins FROM admin";
        $result = $this->queryOne($sql);
        $stats['total_admins'] = $result['TOTAL_ADMINS'] ?: 0;
        
        // Active admins
        $sql = "SELECT COUNT(*) as active_admins FROM admin WHERE status = 1";
        $result = $this->queryOne($sql);
        $stats['active_admins'] = $result['ACTIVE_ADMINS'] ?: 0;
        
        // Admins by role
        $sql = "SELECT role, COUNT(*) as count
                FROM admin
                GROUP BY role
                ORDER BY count DESC";
        $stats['by_role'] = $this->query($sql);
        
        // Recent logins
        $sql = "SELECT a.username, a.email, al.login_time, al.ip
                FROM admin_log al
                LEFT JOIN admin a ON al.admin_id = a.id
                ORDER BY al.login_time DESC
                FETCH FIRST 10 ROWS ONLY";
        $stats['recent_logins'] = $this->query($sql);
        
        return $stats;
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM admin WHERE email = :email";
        $params = array(':email' => $email);
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $result = $this->queryOne($sql, $params);
        return $result['COUNT'] > 0;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM admin WHERE username = :username";
        $params = array(':username' => $username);
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $result = $this->queryOne($sql, $params);
        return $result['COUNT'] > 0;
    }
    
    /**
     * Update admin status
     */
    public function updateAdminStatus($id, $status) {
        return $this->update($id, array('status' => $status));
    }
    
    /**
     * Update admin role
     */
    public function updateAdminRole($id, $role) {
        return $this->update($id, array('role' => $role));
    }
    
    /**
     * Change admin password
     */
    public function changePassword($id, $newPassword) {
        return $this->update($id, array('password' => $newPassword));
    }
    
    /**
     * Get admin session info
     */
    public function getAdminSessionInfo($adminId) {
        $sql = "SELECT a.*, al.login_time, al.ip
                FROM admin a
                LEFT JOIN admin_log al ON a.id = al.admin_id
                WHERE a.id = :admin_id
                   AND al.login_time = (
                       SELECT MAX(login_time) 
                       FROM admin_log 
                       WHERE admin_id = :admin_id2
                   )";
        
        $params = array(
            ':admin_id' => $adminId,
            ':admin_id2' => $adminId
        );
        
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get admin activity log
     */
    public function getAdminActivityLog($adminId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT al.*, a.username, a.email
                FROM admin_log al
                LEFT JOIN admin a ON al.admin_id = a.id
                WHERE 1=1";
        
        $params = array();
        
        if ($adminId) {
            $sql .= " AND al.admin_id = :admin_id";
            $params[':admin_id'] = $adminId;
        }
        
        if ($dateFrom) {
            $sql .= " AND TRUNC(al.login_time) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND TRUNC(al.login_time) <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $sql .= " ORDER BY al.login_time DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get admin by role
     */
    public function getAdminsByRole($role) {
        $sql = "SELECT * FROM admin WHERE role = :role AND status = 1 ORDER BY username";
        $params = array(':role' => $role);
        
        return $this->query($sql, $params);
    }
    
    /**
     * Search admins
     */
    public function searchAdmins($searchTerm) {
        $sql = "SELECT * FROM admin 
                WHERE username LIKE :search 
                   OR email LIKE :search 
                   OR role LIKE :search
                ORDER BY username";
        
        $params = array(':search' => '%' . $searchTerm . '%');
        return $this->query($sql, $params);
    }
    
    /**
     * Get admin dashboard statistics
     */
    public function getAdminDashboardStats() {
        $stats = array();
        
        // Total admins
        $sql = "SELECT COUNT(*) as total FROM admin";
        $result = $this->queryOne($sql);
        $stats['total_admins'] = $result['TOTAL'] ?: 0;
        
        // Active admins
        $sql = "SELECT COUNT(*) as active FROM admin WHERE status = 1";
        $result = $this->queryOne($sql);
        $stats['active_admins'] = $result['ACTIVE'] ?: 0;
        
        // Recent logins (last 24 hours)
        $sql = "SELECT COUNT(*) as recent_logins 
                FROM admin_log 
                WHERE login_time >= SYSTIMESTAMP - INTERVAL '1' DAY";
        $result = $this->queryOne($sql);
        $stats['recent_logins'] = $result['RECENT_LOGINS'] ?: 0;
        
        // Admin roles distribution
        $sql = "SELECT role, COUNT(*) as count
                FROM admin
                WHERE status = 1
                GROUP BY role
                ORDER BY count DESC";
        $stats['roles_distribution'] = $this->query($sql);
        
        return $stats;
    }
}
?> 