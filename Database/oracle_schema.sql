-- Oracle Database Schema for Hostel Management System
-- Nav Purush Boys Hostel

-- Drop existing tables if they exist
BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE user_log CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE visitor_log CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE fee_payments CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE mess_activities CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE student_registration CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE rooms CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE courses CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE states CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE admin CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE admin_log CASCADE CONSTRAINTS';
EXCEPTION
   WHEN OTHERS THEN NULL;
END;
/

-- Create sequences for auto-incrementing IDs
CREATE SEQUENCE admin_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE admin_log_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE courses_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE states_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE rooms_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE student_reg_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE fee_payments_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE visitor_log_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE user_log_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE mess_activities_seq START WITH 1 INCREMENT BY 1;

-- Admin table
CREATE TABLE admin (
    id NUMBER PRIMARY KEY,
    username VARCHAR2(255) NOT NULL UNIQUE,
    email VARCHAR2(255) NOT NULL UNIQUE,
    password VARCHAR2(300) NOT NULL,
    role VARCHAR2(50) DEFAULT 'admin',
    status NUMBER(1) DEFAULT 1,
    reg_date TIMESTAMP DEFAULT SYSTIMESTAMP,
    updation_date DATE DEFAULT SYSDATE
);

-- Admin log table
CREATE TABLE admin_log (
    id NUMBER PRIMARY KEY,
    admin_id NUMBER NOT NULL,
    ip VARCHAR2(45),
    login_time TIMESTAMP DEFAULT SYSTIMESTAMP,
    logout_time TIMESTAMP,
    session_duration NUMBER,
    FOREIGN KEY (admin_id) REFERENCES admin(id)
);

-- Courses table
CREATE TABLE courses (
    id NUMBER PRIMARY KEY,
    course_code VARCHAR2(50) NOT NULL UNIQUE,
    course_short_name VARCHAR2(50) NOT NULL,
    course_full_name VARCHAR2(255) NOT NULL,
    duration_years NUMBER(1) DEFAULT 4,
    status NUMBER(1) DEFAULT 1,
    posting_date TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- States table
CREATE TABLE states (
    id NUMBER PRIMARY KEY,
    state_name VARCHAR2(255) NOT NULL,
    state_code VARCHAR2(10) NOT NULL UNIQUE,
    status NUMBER(1) DEFAULT 1,
    posting_date TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id NUMBER PRIMARY KEY,
    room_no VARCHAR2(20) NOT NULL UNIQUE,
    seater NUMBER(2) NOT NULL,
    fees_per_month NUMBER(10,2) NOT NULL,
    room_type VARCHAR2(50) DEFAULT 'Standard',
    floor_number NUMBER(2),
    block_name VARCHAR2(50),
    status VARCHAR2(20) DEFAULT 'Available',
    posting_date TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- Student registration table
CREATE TABLE student_registration (
    id NUMBER PRIMARY KEY,
    room_id NUMBER,
    course_id NUMBER,
    state_id NUMBER,
    first_name VARCHAR2(255) NOT NULL,
    last_name VARCHAR2(255) NOT NULL,
    email VARCHAR2(255) NOT NULL UNIQUE,
    password VARCHAR2(300) NOT NULL,
    gender VARCHAR2(10) NOT NULL,
    contact_no VARCHAR2(15) NOT NULL,
    emergency_contact VARCHAR2(15),
    address TEXT,
    city VARCHAR2(100),
    pincode VARCHAR2(10),
    guardian_name VARCHAR2(255),
    guardian_contact VARCHAR2(15),
    guardian_relation VARCHAR2(50),
    food_status NUMBER(1) DEFAULT 0,
    stay_from DATE NOT NULL,
    stay_to DATE,
    status VARCHAR2(20) DEFAULT 'Active',
    reg_date TIMESTAMP DEFAULT SYSTIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (state_id) REFERENCES states(id)
);

-- Fee payments table
CREATE TABLE fee_payments (
    id NUMBER PRIMARY KEY,
    student_id NUMBER NOT NULL,
    payment_type VARCHAR2(50) NOT NULL,
    amount NUMBER(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    due_date DATE,
    payment_method VARCHAR2(50),
    receipt_no VARCHAR2(50),
    remarks TEXT,
    status VARCHAR2(20) DEFAULT 'Paid',
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student_registration(id)
);

-- Visitor log table
CREATE TABLE visitor_log (
    id NUMBER PRIMARY KEY,
    visitor_name VARCHAR2(255) NOT NULL,
    visitor_phone VARCHAR2(15),
    visitor_id_proof VARCHAR2(50),
    visitor_id_number VARCHAR2(50),
    purpose VARCHAR2(255),
    student_id NUMBER,
    entry_time TIMESTAMP DEFAULT SYSTIMESTAMP,
    exit_time TIMESTAMP,
    duration_minutes NUMBER,
    security_remarks TEXT,
    status VARCHAR2(20) DEFAULT 'Inside',
    FOREIGN KEY (student_id) REFERENCES student_registration(id)
);

-- User log table
CREATE TABLE user_log (
    id NUMBER PRIMARY KEY,
    user_id NUMBER NOT NULL,
    user_email VARCHAR2(255) NOT NULL,
    user_ip VARCHAR2(45),
    city VARCHAR2(100),
    country VARCHAR2(100),
    login_time TIMESTAMP DEFAULT SYSTIMESTAMP,
    logout_time TIMESTAMP,
    session_duration NUMBER,
    FOREIGN KEY (user_id) REFERENCES student_registration(id)
);

-- Mess activities table
CREATE TABLE mess_activities (
    id NUMBER PRIMARY KEY,
    activity_type VARCHAR2(50) NOT NULL,
    activity_date DATE NOT NULL,
    menu_items TEXT,
    cost_per_meal NUMBER(8,2),
    total_students NUMBER,
    total_cost NUMBER(10,2),
    remarks TEXT,
    created_by NUMBER,
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id)
);

-- Create indexes for better performance
CREATE INDEX idx_student_email ON student_registration(email);
CREATE INDEX idx_student_room ON student_registration(room_id);
CREATE INDEX idx_fee_student ON fee_payments(student_id);
CREATE INDEX idx_fee_date ON fee_payments(payment_date);
CREATE INDEX idx_visitor_student ON visitor_log(student_id);
CREATE INDEX idx_visitor_date ON visitor_log(entry_time);
CREATE INDEX idx_room_status ON rooms(status);
CREATE INDEX idx_admin_email ON admin(email);

-- Insert default admin user
INSERT INTO admin (id, username, email, password, role) 
VALUES (admin_seq.NEXTVAL, 'admin', 'admin@navpurushhostel.com', 'D00F5D5217896FB7FD601412CB890830', 'super_admin');

-- Insert sample courses
INSERT INTO courses (id, course_code, course_short_name, course_full_name) VALUES
(courses_seq.NEXTVAL, 'BTH123', 'B.Tech', 'Bachelor of Technology'),
(courses_seq.NEXTVAL, 'BCOM18', 'B.Com', 'Bachelor of Commerce'),
(courses_seq.NEXTVAL, 'BSC296', 'BSC', 'Bachelor of Science'),
(courses_seq.NEXTVAL, 'BCOA55', 'BCA', 'Bachelor of Computer Application'),
(courses_seq.NEXTVAL, 'MCA001', 'MCA', 'Master of Computer Application'),
(courses_seq.NEXTVAL, 'MBA777', 'MBA', 'Master in Business Administration'),
(courses_seq.NEXTVAL, 'BE069', 'BE', 'Bachelor of Engineering'),
(courses_seq.NEXTVAL, 'BIT353', 'BIT', 'Bachelors in Information Technology'),
(courses_seq.NEXTVAL, 'MIT005', 'MIT', 'Master of Information Technology');

-- Insert sample states
INSERT INTO states (id, state_name, state_code) VALUES
(states_seq.NEXTVAL, 'Andhra Pradesh', 'AP'),
(states_seq.NEXTVAL, 'Arunachal Pradesh', 'AR'),
(states_seq.NEXTVAL, 'Assam', 'AS'),
(states_seq.NEXTVAL, 'Bihar', 'BR'),
(states_seq.NEXTVAL, 'Chhattisgarh', 'CG'),
(states_seq.NEXTVAL, 'Delhi', 'DL'),
(states_seq.NEXTVAL, 'Goa', 'GA'),
(states_seq.NEXTVAL, 'Gujarat', 'GJ'),
(states_seq.NEXTVAL, 'Haryana', 'HR'),
(states_seq.NEXTVAL, 'Himachal Pradesh', 'HP'),
(states_seq.NEXTVAL, 'Jammu and Kashmir', 'JK'),
(states_seq.NEXTVAL, 'Jharkhand', 'JH'),
(states_seq.NEXTVAL, 'Karnataka', 'KA'),
(states_seq.NEXTVAL, 'Kerala', 'KL'),
(states_seq.NEXTVAL, 'Madhya Pradesh', 'MP'),
(states_seq.NEXTVAL, 'Maharashtra', 'MH'),
(states_seq.NEXTVAL, 'Manipur', 'MN'),
(states_seq.NEXTVAL, 'Meghalaya', 'ML'),
(states_seq.NEXTVAL, 'Mizoram', 'MZ'),
(states_seq.NEXTVAL, 'Nagaland', 'NL'),
(states_seq.NEXTVAL, 'Odisha', 'OD'),
(states_seq.NEXTVAL, 'Punjab', 'PB'),
(states_seq.NEXTVAL, 'Rajasthan', 'RJ'),
(states_seq.NEXTVAL, 'Sikkim', 'SK'),
(states_seq.NEXTVAL, 'Tamil Nadu', 'TN'),
(states_seq.NEXTVAL, 'Telangana', 'TS'),
(states_seq.NEXTVAL, 'Tripura', 'TR'),
(states_seq.NEXTVAL, 'Uttar Pradesh', 'UP'),
(states_seq.NEXTVAL, 'Uttarakhand', 'UK'),
(states_seq.NEXTVAL, 'West Bengal', 'WB');

-- Insert sample rooms
INSERT INTO rooms (id, room_no, seater, fees_per_month, room_type, floor_number, block_name) VALUES
(rooms_seq.NEXTVAL, 'A101', 2, 8000.00, 'Standard', 1, 'Block A'),
(rooms_seq.NEXTVAL, 'A102', 2, 8000.00, 'Standard', 1, 'Block A'),
(rooms_seq.NEXTVAL, 'A103', 3, 7000.00, 'Standard', 1, 'Block A'),
(rooms_seq.NEXTVAL, 'A201', 2, 8500.00, 'Premium', 2, 'Block A'),
(rooms_seq.NEXTVAL, 'A202', 2, 8500.00, 'Premium', 2, 'Block A'),
(rooms_seq.NEXTVAL, 'B101', 2, 8000.00, 'Standard', 1, 'Block B'),
(rooms_seq.NEXTVAL, 'B102', 3, 7000.00, 'Standard', 1, 'Block B'),
(rooms_seq.NEXTVAL, 'B201', 2, 8500.00, 'Premium', 2, 'Block B'),
(rooms_seq.NEXTVAL, 'C101', 2, 9000.00, 'Deluxe', 1, 'Block C'),
(rooms_seq.NEXTVAL, 'C102', 2, 9000.00, 'Deluxe', 1, 'Block C');

COMMIT; 