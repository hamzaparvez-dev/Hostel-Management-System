<?php
/**
 * Fee Management Model Class
 * Nav Purush Boys Hostel Management System
 */

require_once 'BaseModel.php';

class FeeModel extends BaseModel {
    protected $table = 'fee_payments';
    protected $sequence = 'fee_payments_seq';
    
    public function __construct($conn) {
        parent::__construct($conn);
    }
    
    /**
     * Get fee payment with student details
     */
    public function getFeePaymentWithDetails($id) {
        $sql = "SELECT f.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no, r.fees_per_month
                FROM fee_payments f
                LEFT JOIN student_registration s ON f.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE f.id = :id";
        
        $params = array(':id' => $id);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get all fee payments with details
     */
    public function getAllFeePaymentsWithDetails($filters = array()) {
        $sql = "SELECT f.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no, r.fees_per_month
                FROM fee_payments f
                LEFT JOIN student_registration s ON f.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE 1=1";
        
        $params = array();
        
        // Add filters
        if (!empty($filters['student_id'])) {
            $sql .= " AND f.student_id = :student_id";
            $params[':student_id'] = $filters['student_id'];
        }
        
        if (!empty($filters['payment_type'])) {
            $sql .= " AND f.payment_type = :payment_type";
            $params[':payment_type'] = $filters['payment_type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND f.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND f.payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND f.payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY f.payment_date DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get student fee summary
     */
    public function getStudentFeeSummary($studentId) {
        $sql = "SELECT s.*, c.course_short_name, r.room_no, r.fees_per_month,
                       COALESCE(SUM(f.amount), 0) as total_paid,
                       (r.fees_per_month - COALESCE(SUM(f.amount), 0)) as pending_amount,
                       COUNT(f.id) as payment_count
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN fee_payments f ON s.id = f.student_id AND f.status = 'Paid'
                WHERE s.id = :student_id
                GROUP BY s.id, s.first_name, s.last_name, s.email, c.course_short_name, r.room_no, r.fees_per_month";
        
        $params = array(':student_id' => $studentId);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get student payment history
     */
    public function getStudentPaymentHistory($studentId) {
        $sql = "SELECT f.*, s.first_name, s.last_name, s.email
                FROM fee_payments f
                LEFT JOIN student_registration s ON f.student_id = s.id
                WHERE f.student_id = :student_id
                ORDER BY f.payment_date DESC";
        
        $params = array(':student_id' => $studentId);
        return $this->query($sql, $params);
    }
    
    /**
     * Get pending fees report
     */
    public function getPendingFeesReport($filters = array()) {
        $sql = "SELECT s.*, c.course_short_name, r.room_no, r.fees_per_month,
                       COALESCE(SUM(f.amount), 0) as total_paid,
                       (r.fees_per_month - COALESCE(SUM(f.amount), 0)) as pending_amount,
                       CASE 
                           WHEN (r.fees_per_month - COALESCE(SUM(f.amount), 0)) > 0 THEN 'Pending'
                           ELSE 'Paid'
                       END as payment_status
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN fee_payments f ON s.id = f.student_id AND f.status = 'Paid'
                WHERE s.status = 'Active'";
        
        $params = array();
        
        // Add filters
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = :course_id";
            $params[':course_id'] = $filters['course_id'];
        }
        
        if (!empty($filters['room_id'])) {
            $sql .= " AND s.room_id = :room_id";
            $params[':room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['payment_status'])) {
            if ($filters['payment_status'] == 'Pending') {
                $sql .= " HAVING (r.fees_per_month - COALESCE(SUM(f.amount), 0)) > 0";
            } else {
                $sql .= " HAVING (r.fees_per_month - COALESCE(SUM(f.amount), 0)) <= 0";
            }
        }
        
        $sql .= " GROUP BY s.id, s.first_name, s.last_name, s.email, c.course_short_name, r.room_no, r.fees_per_month
                  ORDER BY pending_amount DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get fee collection statistics
     */
    public function getFeeCollectionStatistics($dateFrom = null, $dateTo = null) {
        $stats = array();
        
        // Total collection
        $sql = "SELECT SUM(amount) as total_collection, COUNT(*) as total_payments
                FROM fee_payments 
                WHERE status = 'Paid'";
        
        $params = array();
        if ($dateFrom) {
            $sql .= " AND payment_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND payment_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $result = $this->queryOne($sql, $params);
        $stats['total_collection'] = $result['TOTAL_COLLECTION'] ?: 0;
        $stats['total_payments'] = $result['TOTAL_PAYMENTS'] ?: 0;
        
        // Collection by payment type
        $sql = "SELECT payment_type, SUM(amount) as total, COUNT(*) as count
                FROM fee_payments 
                WHERE status = 'Paid'";
        
        if ($dateFrom) {
            $sql .= " AND payment_date >= :date_from";
        }
        if ($dateTo) {
            $sql .= " AND payment_date <= :date_to";
        }
        
        $sql .= " GROUP BY payment_type ORDER BY total DESC";
        $stats['by_payment_type'] = $this->query($sql, $params);
        
        // Monthly collection trend
        $sql = "SELECT TO_CHAR(payment_date, 'YYYY-MM') as month,
                       SUM(amount) as total_collection,
                       COUNT(*) as payment_count
                FROM fee_payments 
                WHERE status = 'Paid'";
        
        if ($dateFrom) {
            $sql .= " AND payment_date >= :date_from";
        }
        if ($dateTo) {
            $sql .= " AND payment_date <= :date_to";
        }
        
        $sql .= " GROUP BY TO_CHAR(payment_date, 'YYYY-MM')
                  ORDER BY month DESC";
        $stats['monthly_trend'] = $this->query($sql, $params);
        
        // Pending fees summary
        $sql = "SELECT COUNT(*) as pending_students,
                       SUM(pending_amount) as total_pending
                FROM (
                    SELECT s.id, (r.fees_per_month - COALESCE(SUM(f.amount), 0)) as pending_amount
                    FROM student_registration s
                    LEFT JOIN rooms r ON s.room_id = r.id
                    LEFT JOIN fee_payments f ON s.id = f.student_id AND f.status = 'Paid'
                    WHERE s.status = 'Active'
                    GROUP BY s.id, r.fees_per_month
                    HAVING (r.fees_per_month - COALESCE(SUM(f.amount), 0)) > 0
                )";
        
        $result = $this->queryOne($sql);
        $stats['pending_students'] = $result['PENDING_STUDENTS'] ?: 0;
        $stats['total_pending'] = $result['TOTAL_PENDING'] ?: 0;
        
        return $stats;
    }
    
    /**
     * Generate receipt number
     */
    public function generateReceiptNumber() {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM fee_payments 
                WHERE receipt_no LIKE :receipt_pattern";
        $params = array(':receipt_pattern' => "RCPT$date%");
        
        $result = $this->queryOne($sql, $params);
        $count = $result['COUNT'] + 1;
        
        return "RCPT$date" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Record fee payment
     */
    public function recordPayment($data) {
        // Generate receipt number if not provided
        if (empty($data['receipt_no'])) {
            $data['receipt_no'] = $this->generateReceiptNumber();
        }
        
        // Set default values
        if (empty($data['payment_date'])) {
            $data['payment_date'] = date('Y-m-d');
        }
        
        if (empty($data['status'])) {
            $data['status'] = 'Paid';
        }
        
        return $this->insert($data);
    }
    
    /**
     * Get overdue payments
     */
    public function getOverduePayments($daysOverdue = 30) {
        $sql = "SELECT s.*, c.course_short_name, r.room_no, r.fees_per_month,
                       COALESCE(SUM(f.amount), 0) as total_paid,
                       (r.fees_per_month - COALESCE(SUM(f.amount), 0)) as pending_amount,
                       TRUNC(SYSDATE - s.stay_from) as days_staying
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN fee_payments f ON s.id = f.student_id AND f.status = 'Paid'
                WHERE s.status = 'Active'
                GROUP BY s.id, s.first_name, s.last_name, s.email, s.stay_from, c.course_short_name, r.room_no, r.fees_per_month
                HAVING (r.fees_per_month - COALESCE(SUM(f.amount), 0)) > 0
                   AND TRUNC(SYSDATE - s.stay_from) > :days_overdue
                ORDER BY pending_amount DESC";
        
        $params = array(':days_overdue' => $daysOverdue);
        return $this->query($sql, $params);
    }
    
    /**
     * Get fee payment by receipt number
     */
    public function getPaymentByReceipt($receiptNo) {
        $sql = "SELECT f.*, s.first_name, s.last_name, s.email, s.contact_no,
                       c.course_short_name, r.room_no, r.fees_per_month
                FROM fee_payments f
                LEFT JOIN student_registration s ON f.student_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE f.receipt_no = :receipt_no";
        
        $params = array(':receipt_no' => $receiptNo);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $status) {
        return $this->update($id, array('status' => $status));
    }
    
    /**
     * Get payment methods statistics
     */
    public function getPaymentMethodsStatistics() {
        $sql = "SELECT payment_method, 
                       COUNT(*) as count,
                       SUM(amount) as total_amount,
                       AVG(amount) as avg_amount
                FROM fee_payments 
                WHERE status = 'Paid'
                GROUP BY payment_method
                ORDER BY total_amount DESC";
        
        return $this->query($sql);
    }
}
?> 