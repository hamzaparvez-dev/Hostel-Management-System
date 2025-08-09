# Nav Purush Boys Hostel Management System

A comprehensive hostel management system built with PHP and Oracle Database for efficient management of student accommodations, fees, visitors, and mess activities.

## Features

### Core Modules
- **Student Management**: Complete student registration, profile management, and status tracking
- **Room Management**: Room allocation, availability tracking, and floor plan visualization
- **Fee Management**: Payment tracking, receipt generation, and financial reporting
- **Visitor Management**: Entry/exit logging, security tracking, and visitor history
- **Mess Management**: Menu planning, activity tracking, and cost management
- **Admin Management**: Role-based access control and administrative functions

### Advanced Features
- **Oracle Database Integration**: Robust data storage with Oracle 19c+
- **Real-time Reporting**: Comprehensive dashboards and analytics
- **Security Features**: Role-based access, audit logging, and data protection
- **API Support**: RESTful API for external integrations
- **Mobile Responsive**: Modern UI that works on all devices

## System Requirements

### Server Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0+ with OCI8 extension
- **Oracle Database**: 19c or higher
- **Memory**: Minimum 4GB RAM
- **Storage**: 50GB+ available space

### PHP Extensions Required
- OCI8 (Oracle Database connectivity)
- PDO
- JSON
- cURL
- GD (for image processing)
- mbstring
- openssl

## Installation Guide

### Step 1: Oracle Database Setup

1. **Install Oracle Database 19c or higher**
   ```bash
   # Download from Oracle website
   # Follow installation wizard
   # Note down service name, port, and credentials
   ```

2. **Create Database User**
   ```sql
   -- Connect as SYSDBA
   CREATE USER hostel_admin IDENTIFIED BY hostel123;
   GRANT CONNECT, RESOURCE, DBA TO hostel_admin;
   GRANT CREATE SESSION TO hostel_admin;
   GRANT UNLIMITED TABLESPACE TO hostel_admin;
   ```

3. **Execute Database Schema**
   ```bash
   # Connect to Oracle using SQL*Plus
   sqlplus hostel_admin/hostel123@localhost:1521/XE
   
   # Execute the schema file
   @Database/oracle_schema.sql
   ```

### Step 2: Web Server Setup

1. **Install Apache and PHP**
   ```bash
   # Ubuntu/Debian
   sudo apt update
   sudo apt install apache2 php php-oci8 php-pdo php-json php-curl php-gd php-mbstring php-openssl
   
   # CentOS/RHEL
   sudo yum install httpd php php-oci8 php-pdo php-json php-curl php-gd php-mbstring php-openssl
   ```

2. **Configure PHP OCI8**
   ```bash
   # Edit php.ini
   sudo nano /etc/php/8.0/apache2/php.ini
   
   # Add/update these lines:
   extension=oci8
   extension=pdo_oci
   ```

3. **Install Oracle Instant Client**
   ```bash
   # Download from Oracle website
   # Extract to /opt/oracle/instantclient
   # Add to PATH
   export PATH=/opt/oracle/instantclient:$PATH
   ```

### Step 3: Application Deployment

1. **Clone/Download the Application**
   ```bash
   cd /var/www/html
   git clone <repository-url> hostel-management
   cd hostel-management
   ```

2. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/hostel-management
   sudo chmod -R 755 /var/www/html/hostel-management
   sudo chmod -R 777 /var/www/html/hostel-management/uploads
   ```

3. **Configure Database Connection**
   ```bash
   # Edit includes/dbconn.php
   # Update connection parameters:
   $host = "your-oracle-host";
   $port = "1521";
   $service_name = "your-service-name";
   $username = "hostel_admin";
   $password = "hostel123";
   ```

4. **Create Virtual Host (Optional)**
   ```apache
   <VirtualHost *:80>
       ServerName hostel.local
       DocumentRoot /var/www/html/hostel-management
       <Directory /var/www/html/hostel-management>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

### Step 4: Initial Setup

1. **Access the Application**
   ```
   http://your-server-ip/hostel-management/
   ```

2. **Default Admin Credentials**
   - **Username**: admin
   - **Email**: admin@navpurushhostel.com
   - **Password**: admin123

3. **First Time Setup**
   - Login as admin
   - Navigate to Admin Panel
   - Configure system settings
   - Add initial data (courses, states, rooms)

## Database Schema

### Core Tables
- `admin` - Administrator accounts
- `student_registration` - Student information
- `rooms` - Room details and availability
- `fee_payments` - Payment records
- `visitor_log` - Visitor tracking
- `mess_activities` - Mess management
- `courses` - Academic courses
- `states` - Geographic data

### Key Features
- **Foreign Key Relationships**: Maintains data integrity
- **Indexes**: Optimized for performance
- **Sequences**: Auto-incrementing IDs
- **Audit Logging**: Tracks all activities

## Usage Guide

### For Administrators

1. **Dashboard Access**
   - Login with admin credentials
   - View system overview and statistics
   - Access all management modules

2. **Student Management**
   - Register new students
   - Update student information
   - Track student status and room allocation

3. **Room Management**
   - Add/edit room details
   - Monitor room availability
   - Generate floor plans

4. **Fee Management**
   - Record payments
   - Generate receipts
   - View pending fees reports

5. **Visitor Management**
   - Log visitor entries/exits
   - Track visitor history
   - Generate security reports

6. **Mess Management**
   - Plan weekly menus
   - Track mess activities
   - Manage food preferences

### For Students

1. **Student Portal**
   - Login with student credentials
   - View personal information
   - Check fee status
   - View room details

2. **Profile Management**
   - Update contact information
   - Change password
   - View payment history

## API Documentation

### Authentication
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "student@example.com",
    "password": "password123"
}
```

### Student Endpoints
```http
GET /api/students/{id}
GET /api/students/{id}/fees
GET /api/students/{id}/visitors
```

### Admin Endpoints
```http
GET /api/admin/dashboard
POST /api/admin/students
PUT /api/admin/students/{id}
DELETE /api/admin/students/{id}
```

## Security Features

### Authentication & Authorization
- Role-based access control
- Session management
- Password hashing (MD5)
- IP-based login tracking

### Data Protection
- SQL injection prevention
- XSS protection
- Input validation
- Audit logging

### Backup & Recovery
- Automated database backups
- Point-in-time recovery
- Data export functionality

## Performance Optimization

### Database Optimization
- Indexed queries for faster retrieval
- Stored procedures for complex operations
- Connection pooling
- Query optimization

### Application Optimization
- Caching mechanisms
- Compressed assets
- CDN integration
- Load balancing ready

## Troubleshooting

### Common Issues

1. **Oracle Connection Error**
   ```bash
   # Check OCI8 extension
   php -m | grep oci
   
   # Verify Oracle client installation
   which sqlplus
   ```

2. **Permission Errors**
   ```bash
   # Fix file permissions
   sudo chown -R www-data:www-data /var/www/html/hostel-management
   sudo chmod -R 755 /var/www/html/hostel-management
   ```

3. **Database Schema Issues**
   ```sql
   -- Check if tables exist
   SELECT table_name FROM user_tables;
   
   -- Check sequences
   SELECT sequence_name FROM user_sequences;
   ```

### Log Files
- **Apache**: `/var/log/apache2/error.log`
- **PHP**: `/var/log/php_errors.log`
- **Application**: `logs/application.log`

## Development Guide

### Project Structure
```
hostel-management/
├── admin/              # Admin panel
├── student/            # Student portal
├── includes/           # Core classes and models
│   ├── models/        # Database models
│   └── dbconn.php     # Database connection
├── assets/            # Static assets
├── Database/          # Database scripts
└── uploads/           # File uploads
```

### Adding New Features
1. Create model class in `includes/models/`
2. Add controller logic
3. Create view files
4. Update database schema if needed
5. Test thoroughly

## Support & Maintenance

### Regular Maintenance
- **Daily**: Database backups
- **Weekly**: Log file cleanup
- **Monthly**: Performance monitoring
- **Quarterly**: Security updates

## License

This project is proprietary software developed for Nav Purush Boys Hostel. All rights reserved.

## Version History

- **v2.0.0** (Current): Oracle database integration, enhanced features
- **v1.0.0**: Initial MySQL-based version

---

**Developed for Nav Purush Boys Hostel**  
*Comprehensive Hostel Management Solution*
