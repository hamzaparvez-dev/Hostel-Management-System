<?php
/**
 * Student Model Class
 * Nav Purush Boys Hostel Management System
 */

require_once 'BaseModel.php';

class StudentModel extends BaseModel {
    protected $table = 'student_registration';
    protected $sequence = 'student_reg_seq';
    
    public function __construct($conn) {
        parent::__construct($conn);
    }
    
    /**
     * Get student with related data
     */
    public function getStudentWithDetails($id) {
        $sql = "SELECT s.*, c.course_full_name, c.course_short_name, 
                       r.room_no, r.seater, r.fees_per_month, r.room_type,
                       st.state_name
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN states st ON s.state_id = st.id
                WHERE s.id = :id";
        
        $params = array(':id' => $id);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get all students with related data
     */
    public function getAllStudentsWithDetails($orderBy = 's.reg_date DESC') {
        $sql = "SELECT s.*, c.course_full_name, c.course_short_name, 
                       r.room_no, r.seater, r.fees_per_month, r.room_type,
                       st.state_name
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN states st ON s.state_id = st.id
                ORDER BY $orderBy";
        
        return $this->query($sql);
    }
    
    /**
     * Search students
     */
    public function searchStudents($searchTerm, $filters = array()) {
        $sql = "SELECT s.*, c.course_full_name, c.course_short_name, 
                       r.room_no, r.seater, r.fees_per_month, r.room_type,
                       st.state_name
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN states st ON s.state_id = st.id
                WHERE (s.first_name LIKE :search OR s.last_name LIKE :search 
                       OR s.email LIKE :search OR s.contact_no LIKE :search)";
        
        $params = array(':search' => '%' . $searchTerm . '%');
        
        // Add filters
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = :course_id";
            $params[':course_id'] = $filters['course_id'];
        }
        
        if (!empty($filters['room_id'])) {
            $sql .= " AND s.room_id = :room_id";
            $params[':room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $sql .= " ORDER BY s.reg_date DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get students by room
     */
    public function getStudentsByRoom($roomId) {
        $sql = "SELECT s.*, c.course_full_name, c.course_short_name
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                WHERE s.room_id = :room_id AND s.status = 'Active'
                ORDER BY s.reg_date";
        
        $params = array(':room_id' => $roomId);
        return $this->query($sql, $params);
    }
    
    /**
     * Get students by course
     */
    public function getStudentsByCourse($courseId) {
        $sql = "SELECT s.*, r.room_no, st.state_name
                FROM student_registration s
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN states st ON s.state_id = st.id
                WHERE s.course_id = :course_id AND s.status = 'Active'
                ORDER BY s.reg_date";
        
        $params = array(':course_id' => $courseId);
        return $this->query($sql, $params);
    }
    
    /**
     * Get student statistics
     */
    public function getStudentStatistics() {
        $stats = array();
        
        // Total students
        $sql = "SELECT COUNT(*) as total FROM student_registration WHERE status = 'Active'";
        $result = $this->queryOne($sql);
        $stats['total_students'] = $result['TOTAL'];
        
        // Students by course
        $sql = "SELECT c.course_full_name, COUNT(s.id) as count
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                WHERE s.status = 'Active'
                GROUP BY c.course_full_name, c.id
                ORDER BY count DESC";
        $stats['by_course'] = $this->query($sql);
        
        // Students by room type
        $sql = "SELECT r.room_type, COUNT(s.id) as count
                FROM student_registration s
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE s.status = 'Active'
                GROUP BY r.room_type
                ORDER BY count DESC";
        $stats['by_room_type'] = $this->query($sql);
        
        // Recent registrations
        $sql = "SELECT s.*, c.course_short_name, r.room_no
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE s.status = 'Active'
                ORDER BY s.reg_date DESC
                FETCH FIRST 10 ROWS ONLY";
        $stats['recent_registrations'] = $this->query($sql);
        
        return $stats;
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM student_registration WHERE email = :email";
        $params = array(':email' => $email);
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $result = $this->queryOne($sql, $params);
        return $result['COUNT'] > 0;
    }
    
    /**
     * Get available rooms for student
     */
    public function getAvailableRooms($seater = null) {
        $sql = "SELECT r.*, 
                       (r.seater - COALESCE(COUNT(s.id), 0)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE r.status = 'Available'
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status
                HAVING (r.seater - COALESCE(COUNT(s.id), 0)) > 0";
        
        if ($seater) {
            $sql .= " AND r.seater = :seater";
            $params = array(':seater' => $seater);
        } else {
            $params = array();
        }
        
        $sql .= " ORDER BY r.room_no";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Update student status
     */
    public function updateStatus($id, $status) {
        return $this->update($id, array('status' => $status));
    }
    
    /**
     * Get students with pending fees
     */
    public function getStudentsWithPendingFees() {
        $sql = "SELECT s.*, c.course_short_name, r.room_no, r.fees_per_month,
                       COALESCE(SUM(f.amount), 0) as total_paid,
                       (r.fees_per_month - COALESCE(SUM(f.amount), 0)) as pending_amount
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN fee_payments f ON s.id = f.student_id AND f.status = 'Paid'
                WHERE s.status = 'Active'
                GROUP BY s.id, s.first_name, s.last_name, s.email, c.course_short_name, r.room_no, r.fees_per_month
                HAVING (r.fees_per_month - COALESCE(SUM(f.amount), 0)) > 0
                ORDER BY pending_amount DESC";
        
        return $this->query($sql);
    }
}
?> 