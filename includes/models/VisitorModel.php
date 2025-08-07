<?php
/**
 * Visitor Management Model Class
 * Nav Purush Boys Hostel Management System
 */

require_once 'BaseModel.php';

class VisitorModel extends BaseModel {
    protected $table = 'visitor_log';
    protected $sequence = 'visitor_log_seq';
    
    public function __construct($conn) {
        parent::__construct($conn);
    }
    
    /**
     * Get visitor log with student details
     */
    public function getVisitorLogWithDetails($id) {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE v.id = :id";
        
        $params = array(':id' => $id);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get all visitor logs with details
     */
    public function getAllVisitorLogsWithDetails($filters = array()) {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE 1=1";
        
        $params = array();
        
        // Add filters
        if (!empty($filters['student_id'])) {
            $sql .= " AND v.student_id = :student_id";
            $params[':student_id'] = $filters['student_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND v.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND TRUNC(v.entry_time) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND TRUNC(v.entry_time) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['visitor_name'])) {
            $sql .= " AND v.visitor_name LIKE :visitor_name";
            $params[':visitor_name'] = '%' . $filters['visitor_name'] . '%';
        }
        
        $sql .= " ORDER BY v.entry_time DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get current visitors (inside hostel)
     */
    public function getCurrentVisitors() {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE v.status = 'Inside'
                ORDER BY v.entry_time DESC";
        
        return $this->query($sql);
    }
    
    /**
     * Record visitor entry
     */
    public function recordVisitorEntry($data) {
        // Set default values
        if (empty($data['entry_time'])) {
            $data['entry_time'] = date('Y-m-d H:i:s');
        }
        
        if (empty($data['status'])) {
            $data['status'] = 'Inside';
        }
        
        return $this->insert($data);
    }
    
    /**
     * Record visitor exit
     */
    public function recordVisitorExit($id, $exitTime = null, $remarks = null) {
        if (!$exitTime) {
            $exitTime = date('Y-m-d H:i:s');
        }
        
        // Calculate duration
        $sql = "SELECT entry_time FROM visitor_log WHERE id = :id";
        $params = array(':id' => $id);
        $visitor = $this->queryOne($sql, $params);
        
        if ($visitor) {
            $entryTime = strtotime($visitor['ENTRY_TIME']);
            $exitTimeStamp = strtotime($exitTime);
            $durationMinutes = round(($exitTimeStamp - $entryTime) / 60);
            
            $updateData = array(
                'exit_time' => $exitTime,
                'duration_minutes' => $durationMinutes,
                'status' => 'Exited'
            );
            
            if ($remarks) {
                $updateData['security_remarks'] = $remarks;
            }
            
            return $this->update($id, $updateData);
        }
        
        return false;
    }
    
    /**
     * Get visitor statistics
     */
    public function getVisitorStatistics($dateFrom = null, $dateTo = null) {
        $stats = array();
        
        // Total visitors
        $sql = "SELECT COUNT(*) as total_visitors FROM visitor_log";
        $params = array();
        
        if ($dateFrom) {
            $sql .= " WHERE TRUNC(entry_time) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= $dateFrom ? " AND" : " WHERE";
            $sql .= " TRUNC(entry_time) <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $result = $this->queryOne($sql, $params);
        $stats['total_visitors'] = $result['TOTAL_VISITORS'] ?: 0;
        
        // Current visitors
        $sql = "SELECT COUNT(*) as current_visitors FROM visitor_log WHERE status = 'Inside'";
        $result = $this->queryOne($sql);
        $stats['current_visitors'] = $result['CURRENT_VISITORS'] ?: 0;
        
        // Visitors by purpose
        $sql = "SELECT purpose, COUNT(*) as count
                FROM visitor_log";
        
        if ($dateFrom) {
            $sql .= " WHERE TRUNC(entry_time) >= :date_from";
        }
        if ($dateTo) {
            $sql .= $dateFrom ? " AND" : " WHERE";
            $sql .= " TRUNC(entry_time) <= :date_to";
        }
        
        $sql .= " GROUP BY purpose ORDER BY count DESC";
        $stats['by_purpose'] = $this->query($sql, $params);
        
        // Daily visitor trend
        $sql = "SELECT TO_CHAR(entry_time, 'YYYY-MM-DD') as visit_date,
                       COUNT(*) as visitor_count
                FROM visitor_log";
        
        if ($dateFrom) {
            $sql .= " WHERE TRUNC(entry_time) >= :date_from";
        }
        if ($dateTo) {
            $sql .= $dateFrom ? " AND" : " WHERE";
            $sql .= " TRUNC(entry_time) <= :date_to";
        }
        
        $sql .= " GROUP BY TO_CHAR(entry_time, 'YYYY-MM-DD')
                  ORDER BY visit_date DESC";
        $stats['daily_trend'] = $this->query($sql, $params);
        
        // Average visit duration
        $sql = "SELECT AVG(duration_minutes) as avg_duration
                FROM visitor_log 
                WHERE duration_minutes IS NOT NULL";
        
        if ($dateFrom) {
            $sql .= " AND TRUNC(entry_time) >= :date_from";
        }
        if ($dateTo) {
            $sql .= " AND TRUNC(entry_time) <= :date_to";
        }
        
        $result = $this->queryOne($sql, $params);
        $stats['avg_duration'] = round($result['AVG_DURATION'] ?: 0, 2);
        
        return $stats;
    }
    
    /**
     * Get visitors by student
     */
    public function getVisitorsByStudent($studentId) {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                WHERE v.student_id = :student_id
                ORDER BY v.entry_time DESC";
        
        $params = array(':student_id' => $studentId);
        return $this->query($sql, $params);
    }
    
    /**
     * Search visitors
     */
    public function searchVisitors($searchTerm) {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE v.visitor_name LIKE :search 
                   OR v.visitor_phone LIKE :search 
                   OR v.visitor_id_number LIKE :search
                   OR s.first_name LIKE :search 
                   OR s.last_name LIKE :search
                ORDER BY v.entry_time DESC";
        
        $params = array(':search' => '%' . $searchTerm . '%');
        return $this->query($sql, $params);
    }
    
    /**
     * Get visitor entry by ID proof
     */
    public function getVisitorByIdProof($idProof, $idNumber) {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email, s.contact_no
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                WHERE v.visitor_id_proof = :id_proof 
                   AND v.visitor_id_number = :id_number
                ORDER BY v.entry_time DESC";
        
        $params = array(
            ':id_proof' => $idProof,
            ':id_number' => $idNumber
        );
        return $this->query($sql, $params);
    }
    
    /**
     * Get security alerts (long duration visits)
     */
    public function getSecurityAlerts($maxDurationHours = 4) {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no,
                       ROUND((SYSDATE - v.entry_time) * 24, 2) as hours_inside
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE v.status = 'Inside'
                   AND (SYSDATE - v.entry_time) * 24 > :max_duration
                ORDER BY hours_inside DESC";
        
        $params = array(':max_duration' => $maxDurationHours);
        return $this->query($sql, $params);
    }
    
    /**
     * Get visitor report by date range
     */
    public function getVisitorReportByDateRange($dateFrom, $dateTo) {
        $sql = "SELECT v.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no
                FROM visitor_log v
                LEFT JOIN student_registration s ON v.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE TRUNC(v.entry_time) BETWEEN :date_from AND :date_to
                ORDER BY v.entry_time DESC";
        
        $params = array(
            ':date_from' => $dateFrom,
            ':date_to' => $dateTo
        );
        return $this->query($sql, $params);
    }
    
    /**
     * Update visitor status
     */
    public function updateVisitorStatus($id, $status) {
        return $this->update($id, array('status' => $status));
    }
    
    /**
     * Get visitor count by time period
     */
    public function getVisitorCountByTimePeriod($period = 'daily') {
        switch ($period) {
            case 'hourly':
                $format = 'YYYY-MM-DD HH24';
                break;
            case 'daily':
                $format = 'YYYY-MM-DD';
                break;
            case 'monthly':
                $format = 'YYYY-MM';
                break;
            default:
                $format = 'YYYY-MM-DD';
        }
        
        $sql = "SELECT TO_CHAR(entry_time, '$format') as time_period,
                       COUNT(*) as visitor_count
                FROM visitor_log
                GROUP BY TO_CHAR(entry_time, '$format')
                ORDER BY time_period DESC";
        
        return $this->query($sql);
    }
    
    /**
     * Get frequent visitors
     */
    public function getFrequentVisitors($limit = 10) {
        $sql = "SELECT visitor_name, visitor_phone, COUNT(*) as visit_count
                FROM visitor_log
                GROUP BY visitor_name, visitor_phone
                ORDER BY visit_count DESC
                FETCH FIRST $limit ROWS ONLY";
        
        return $this->query($sql);
    }
}
?> 