<?php
/**
 * Mess Management Model Class
 * Nav Purush Boys Hostel Management System
 */

require_once 'BaseModel.php';

class MessModel extends BaseModel {
    protected $table = 'mess_activities';
    protected $sequence = 'mess_activities_seq';
    
    public function __construct($conn) {
        parent::__construct($conn);
    }
    
    /**
     * Get mess activity with details
     */
    public function getMessActivityWithDetails($id) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE m.id = :id";
        
        $params = array(':id' => $id);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get all mess activities with details
     */
    public function getAllMessActivitiesWithDetails($filters = array()) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE 1=1";
        
        $params = array();
        
        // Add filters
        if (!empty($filters['activity_type'])) {
            $sql .= " AND m.activity_type = :activity_type";
            $params[':activity_type'] = $filters['activity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND m.activity_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND m.activity_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['created_by'])) {
            $sql .= " AND m.created_by = :created_by";
            $params[':created_by'] = $filters['created_by'];
        }
        
        $sql .= " ORDER BY m.activity_date DESC, m.created_at DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get mess activities by date
     */
    public function getMessActivitiesByDate($date) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE m.activity_date = :activity_date
                ORDER BY m.activity_type, m.created_at";
        
        $params = array(':activity_date' => $date);
        return $this->query($sql, $params);
    }
    
    /**
     * Get weekly menu
     */
    public function getWeeklyMenu($startDate) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE m.activity_type = 'Menu'
                   AND m.activity_date BETWEEN :start_date AND :start_date + 6
                ORDER BY m.activity_date, m.created_at";
        
        $params = array(':start_date' => $startDate);
        return $this->query($sql, $params);
    }
    
    /**
     * Get monthly mess activities
     */
    public function getMonthlyMessActivities($year, $month) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE EXTRACT(YEAR FROM m.activity_date) = :year
                   AND EXTRACT(MONTH FROM m.activity_date) = :month
                ORDER BY m.activity_date, m.activity_type";
        
        $params = array(
            ':year' => $year,
            ':month' => $month
        );
        return $this->query($sql, $params);
    }
    
    /**
     * Get mess statistics
     */
    public function getMessStatistics($dateFrom = null, $dateTo = null) {
        $stats = array();
        
        // Total activities
        $sql = "SELECT COUNT(*) as total_activities FROM mess_activities";
        $params = array();
        
        if ($dateFrom) {
            $sql .= " WHERE activity_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= $dateFrom ? " AND" : " WHERE";
            $sql .= " activity_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $result = $this->queryOne($sql, $params);
        $stats['total_activities'] = $result['TOTAL_ACTIVITIES'] ?: 0;
        
        // Activities by type
        $sql = "SELECT activity_type, COUNT(*) as count
                FROM mess_activities";
        
        if ($dateFrom) {
            $sql .= " WHERE activity_date >= :date_from";
        }
        if ($dateTo) {
            $sql .= $dateFrom ? " AND" : " WHERE";
            $sql .= " activity_date <= :date_to";
        }
        
        $sql .= " GROUP BY activity_type ORDER BY count DESC";
        $stats['by_type'] = $this->query($sql, $params);
        
        // Cost analysis
        $sql = "SELECT activity_type,
                       COUNT(*) as activity_count,
                       SUM(total_cost) as total_cost,
                       AVG(total_cost) as avg_cost
                FROM mess_activities
                WHERE total_cost IS NOT NULL";
        
        if ($dateFrom) {
            $sql .= " AND activity_date >= :date_from";
        }
        if ($dateTo) {
            $sql .= " AND activity_date <= :date_to";
        }
        
        $sql .= " GROUP BY activity_type ORDER BY total_cost DESC";
        $stats['cost_analysis'] = $this->query($sql, $params);
        
        // Monthly trend
        $sql = "SELECT TO_CHAR(activity_date, 'YYYY-MM') as month,
                       COUNT(*) as activity_count,
                       SUM(total_cost) as total_cost
                FROM mess_activities";
        
        if ($dateFrom) {
            $sql .= " WHERE activity_date >= :date_from";
        }
        if ($dateTo) {
            $sql .= $dateFrom ? " AND" : " WHERE";
            $sql .= " activity_date <= :date_to";
        }
        
        $sql .= " GROUP BY TO_CHAR(activity_date, 'YYYY-MM')
                  ORDER BY month DESC";
        $stats['monthly_trend'] = $this->query($sql, $params);
        
        return $stats;
    }
    
    /**
     * Get students with food status
     */
    public function getStudentsWithFoodStatus() {
        $sql = "SELECT s.*, c.course_short_name, r.room_no,
                       CASE WHEN s.food_status = 1 THEN 'Yes' ELSE 'No' END as food_opted
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                WHERE s.status = 'Active'
                ORDER BY s.food_status DESC, s.first_name";
        
        return $this->query($sql);
    }
    
    /**
     * Get food preference statistics
     */
    public function getFoodPreferenceStatistics() {
        $sql = "SELECT 
                       COUNT(*) as total_students,
                       SUM(food_status) as food_opted_count,
                       (COUNT(*) - SUM(food_status)) as food_not_opted_count,
                       ROUND((SUM(food_status) * 100.0 / COUNT(*)), 2) as food_opted_percentage
                FROM student_registration
                WHERE status = 'Active'";
        
        return $this->queryOne($sql);
    }
    
    /**
     * Get mess cost per student
     */
    public function getMessCostPerStudent($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                       AVG(total_cost / NULLIF(total_students, 0)) as avg_cost_per_student,
                       MIN(total_cost / NULLIF(total_students, 0)) as min_cost_per_student,
                       MAX(total_cost / NULLIF(total_students, 0)) as max_cost_per_student
                FROM mess_activities
                WHERE total_cost IS NOT NULL AND total_students IS NOT NULL";
        
        $params = array();
        if ($dateFrom) {
            $sql .= " AND activity_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND activity_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Record mess activity
     */
    public function recordMessActivity($data) {
        // Set default values
        if (empty($data['activity_date'])) {
            $data['activity_date'] = date('Y-m-d');
        }
        
        if (empty($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->insert($data);
    }
    
    /**
     * Get menu items by date
     */
    public function getMenuItemsByDate($date) {
        $sql = "SELECT menu_items, cost_per_meal, total_students, total_cost
                FROM mess_activities
                WHERE activity_type = 'Menu' AND activity_date = :activity_date
                ORDER BY created_at DESC";
        
        $params = array(':activity_date' => $date);
        return $this->query($sql, $params);
    }
    
    /**
     * Get special events
     */
    public function getSpecialEvents($dateFrom = null, $dateTo = null) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE m.activity_type = 'Special Event'";
        
        $params = array();
        if ($dateFrom) {
            $sql .= " AND m.activity_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND m.activity_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $sql .= " ORDER BY m.activity_date DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get mess maintenance activities
     */
    public function getMessMaintenanceActivities($dateFrom = null, $dateTo = null) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE m.activity_type = 'Maintenance'";
        
        $params = array();
        if ($dateFrom) {
            $sql .= " AND m.activity_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND m.activity_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $sql .= " ORDER BY m.activity_date DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Search mess activities
     */
    public function searchMessActivities($searchTerm) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE m.activity_type LIKE :search 
                   OR m.menu_items LIKE :search 
                   OR m.remarks LIKE :search
                   OR a.username LIKE :search
                ORDER BY m.activity_date DESC, m.created_at DESC";
        
        $params = array(':search' => '%' . $searchTerm . '%');
        return $this->query($sql, $params);
    }
    
    /**
     * Get mess activity by type and date
     */
    public function getMessActivityByTypeAndDate($activityType, $date) {
        $sql = "SELECT m.*, a.username as created_by_name
                FROM mess_activities m
                LEFT JOIN admin a ON m.created_by = a.id
                WHERE m.activity_type = :activity_type 
                   AND m.activity_date = :activity_date
                ORDER BY m.created_at DESC";
        
        $params = array(
            ':activity_type' => $activityType,
            ':activity_date' => $date
        );
        return $this->query($sql, $params);
    }
    
    /**
     * Update mess activity
     */
    public function updateMessActivity($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Get mess activity summary by month
     */
    public function getMessActivitySummaryByMonth($year, $month) {
        $sql = "SELECT 
                       activity_type,
                       COUNT(*) as activity_count,
                       SUM(total_cost) as total_cost,
                       AVG(cost_per_meal) as avg_cost_per_meal,
                       SUM(total_students) as total_students_served
                FROM mess_activities
                WHERE EXTRACT(YEAR FROM activity_date) = :year
                   AND EXTRACT(MONTH FROM activity_date) = :month
                GROUP BY activity_type
                ORDER BY total_cost DESC";
        
        $params = array(
            ':year' => $year,
            ':month' => $month
        );
        return $this->query($sql, $params);
    }
}
?> 