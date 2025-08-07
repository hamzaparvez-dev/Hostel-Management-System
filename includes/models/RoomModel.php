<?php
/**
 * Room Model Class
 * Nav Purush Boys Hostel Management System
 */

require_once 'BaseModel.php';

class RoomModel extends BaseModel {
    protected $table = 'rooms';
    protected $sequence = 'rooms_seq';
    
    public function __construct($conn) {
        parent::__construct($conn);
    }
    
    /**
     * Get room with occupancy details
     */
    public function getRoomWithOccupancy($id) {
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants,
                       (r.seater - COUNT(s.id)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE r.id = :id
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date";
        
        $params = array(':id' => $id);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get all rooms with occupancy details
     */
    public function getAllRoomsWithOccupancy($filters = array()) {
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants,
                       (r.seater - COUNT(s.id)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE 1=1";
        
        $params = array();
        
        // Add filters
        if (!empty($filters['block_name'])) {
            $sql .= " AND r.block_name = :block_name";
            $params[':block_name'] = $filters['block_name'];
        }
        
        if (!empty($filters['room_type'])) {
            $sql .= " AND r.room_type = :room_type";
            $params[':room_type'] = $filters['room_type'];
        }
        
        if (!empty($filters['floor_number'])) {
            $sql .= " AND r.floor_number = :floor_number";
            $params[':floor_number'] = $filters['floor_number'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['available_only'])) {
            $sql .= " HAVING (r.seater - COUNT(s.id)) > 0";
        }
        
        $sql .= " GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date
                  ORDER BY r.block_name, r.room_no";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get available rooms
     */
    public function getAvailableRooms($seater = null, $roomType = null) {
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants,
                       (r.seater - COUNT(s.id)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE r.status = 'Available'
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date
                HAVING (r.seater - COUNT(s.id)) > 0";
        
        $params = array();
        
        if ($seater) {
            $sql .= " AND r.seater = :seater";
            $params[':seater'] = $seater;
        }
        
        if ($roomType) {
            $sql .= " AND r.room_type = :room_type";
            $params[':room_type'] = $roomType;
        }
        
        $sql .= " ORDER BY r.room_no";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get room statistics
     */
    public function getRoomStatistics() {
        $stats = array();
        
        // Total rooms
        $sql = "SELECT COUNT(*) as total FROM rooms";
        $result = $this->queryOne($sql);
        $stats['total_rooms'] = $result['TOTAL'];
        
        // Available rooms
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants,
                       (r.seater - COUNT(s.id)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date
                HAVING (r.seater - COUNT(s.id)) > 0";
        $availableRooms = $this->query($sql);
        $stats['available_rooms'] = count($availableRooms);
        
        // Rooms by type
        $sql = "SELECT r.room_type, COUNT(*) as count, AVG(r.fees_per_month) as avg_fees
                FROM rooms r
                GROUP BY r.room_type
                ORDER BY count DESC";
        $stats['by_type'] = $this->query($sql);
        
        // Rooms by block
        $sql = "SELECT r.block_name, COUNT(*) as count
                FROM rooms r
                GROUP BY r.block_name
                ORDER BY r.block_name";
        $stats['by_block'] = $this->query($sql);
        
        // Occupancy rate
        $sql = "SELECT 
                       SUM(r.seater) as total_capacity,
                       COUNT(s.id) as total_occupied,
                       ROUND((COUNT(s.id) * 100.0 / SUM(r.seater)), 2) as occupancy_rate
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'";
        $result = $this->queryOne($sql);
        $stats['occupancy'] = $result;
        
        return $stats;
    }
    
    /**
     * Get floor plan
     */
    public function getFloorPlan($floorNumber = null) {
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants,
                       (r.seater - COUNT(s.id)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE 1=1";
        
        $params = array();
        
        if ($floorNumber) {
            $sql .= " AND r.floor_number = :floor_number";
            $params[':floor_number'] = $floorNumber;
        }
        
        $sql .= " GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date
                  ORDER BY r.block_name, r.room_no";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get rooms by block
     */
    public function getRoomsByBlock($blockName) {
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants,
                       (r.seater - COUNT(s.id)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE r.block_name = :block_name
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date
                ORDER BY r.room_no";
        
        $params = array(':block_name' => $blockName);
        return $this->query($sql, $params);
    }
    
    /**
     * Get room allocation history
     */
    public function getRoomAllocationHistory($roomId) {
        $sql = "SELECT s.*, c.course_short_name,
                       f.payment_date, f.amount, f.status as payment_status
                FROM student_registration s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN fee_payments f ON s.id = f.student_id
                WHERE s.room_id = :room_id
                ORDER BY s.reg_date DESC";
        
        $params = array(':room_id' => $roomId);
        return $this->query($sql, $params);
    }
    
    /**
     * Update room status
     */
    public function updateRoomStatus($id, $status) {
        return $this->update($id, array('status' => $status));
    }
    
    /**
     * Get room maintenance status
     */
    public function getRoomMaintenanceStatus($roomId) {
        $sql = "SELECT r.*, 
                       CASE 
                           WHEN COUNT(s.id) = 0 THEN 'Vacant'
                           WHEN COUNT(s.id) < r.seater THEN 'Partially Occupied'
                           ELSE 'Fully Occupied'
                       END as occupancy_status
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE r.id = :room_id
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date";
        
        $params = array(':room_id' => $roomId);
        return $this->queryOne($sql, $params);
    }
    
    /**
     * Get rooms requiring maintenance
     */
    public function getRoomsRequiringMaintenance() {
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE r.status = 'Maintenance'
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date
                ORDER BY r.room_no";
        
        return $this->query($sql);
    }
    
    /**
     * Search rooms
     */
    public function searchRooms($searchTerm) {
        $sql = "SELECT r.*, 
                       COUNT(s.id) as current_occupants,
                       (r.seater - COUNT(s.id)) as available_seats
                FROM rooms r
                LEFT JOIN student_registration s ON r.id = s.room_id AND s.status = 'Active'
                WHERE r.room_no LIKE :search OR r.block_name LIKE :search OR r.room_type LIKE :search
                GROUP BY r.id, r.room_no, r.seater, r.fees_per_month, r.room_type, r.floor_number, r.block_name, r.status, r.posting_date
                ORDER BY r.room_no";
        
        $params = array(':search' => '%' . $searchTerm . '%');
        return $this->query($sql, $params);
    }
}
?> 