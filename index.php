<?php
session_start();
include('includes/dbconn.php');
include('includes/models/AdminModel.php');
include('includes/models/StudentModel.php');

// Initialize models
$adminModel = new AdminModel($conn);
$studentModel = new StudentModel($conn);

if(isset($_POST['login']))
{
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password);
    
    // Try admin login first
    $admin = $adminModel->authenticate($email, $password);
    
    if($admin) {
        // Admin login successful
        $_SESSION['admin_id'] = $admin['ID'];
        $_SESSION['admin_login'] = $admin['EMAIL'];
        $_SESSION['admin_role'] = $admin['ROLE'];
        
        // Record admin login
        $adminModel->recordAdminLogin($admin['ID']);
        
        $uip = $_SERVER['REMOTE_ADDR'];
        $ldate = date('d/m/Y h:i:s', time());
        
        header("location:admin/dashboard.php");
        exit();
    } else {
        // Try student login
        $sql = "SELECT * FROM student_registration WHERE email = :email AND password = :password AND status = 'Active'";
        $params = array(':email' => $email, ':password' => $password);
        $stmt = executeQuery($conn, $sql, $params);
        $student = fetchRow($stmt);
        
        if($student) {
            // Student login successful
            $_SESSION['student_id'] = $student['ID'];
            $_SESSION['student_login'] = $student['EMAIL'];
            $_SESSION['student_name'] = $student['FIRST_NAME'] . ' ' . $student['LAST_NAME'];
            
            $uip = $_SERVER['REMOTE_ADDR'];
            $ldate = date('d/m/Y h:i:s', time());
            
            // Record user log
            $uid = $_SESSION['student_id'];
            $uemail = $_SESSION['student_login'];
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Get location info
            $geopluginURL = 'http://www.geoplugin.net/php.gp?ip=' . $ip;
            $addrDetailsArr = unserialize(file_get_contents($geopluginURL));
            $city = $addrDetailsArr['geoplugin_city'];
            $country = $addrDetailsArr['geoplugin_countryName'];
            
            $logSql = "INSERT INTO user_log (id, user_id, user_email, user_ip, city, country, login_time) 
                       VALUES (user_log_seq.NEXTVAL, :user_id, :user_email, :user_ip, :city, :country, SYSTIMESTAMP)";
            $logParams = array(
                ':user_id' => $uid,
                ':user_email' => $uemail,
                ':user_ip' => $ip,
                ':city' => $city,
                ':country' => $country
            );
            executeQuery($conn, $logSql, $logParams);
            
            header("location:student/dashboard.php");
            exit();
        } else {
            echo "<script>alert('Sorry, Invalid Username/Email or Password!');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon.png">
    <title>Nav Purush Boys Hostel - Management System</title>
    <!-- Custom CSS -->
    <link href="dist/css/style.min.css" rel="stylesheet">

    <script type="text/javascript">
    function valid() {
        if(document.registration.password.value != document.registration.cpassword.value) {
            alert("Password and Re-Type Password Field do not match !!");
            document.registration.cpassword.focus();
            return false;
        }
        return true;
    }
    </script>

</head>

<body>
    <div class="main-wrapper">
        <!-- ============================================================== -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- ============================================================== -->
        <div class="preloader">
            <div class="lds-ripple">
                <div class="lds-pos"></div>
                <div class="lds-pos"></div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center position-relative"
            style="background:url(../assets/images/big/auth-bg.jpg) no-repeat center center;">
            <div class="auth-box row">
                <div class="col-lg-7 col-md-5 modal-bg-img" style="background-image: url(assets/images/hostel-img.jpg);">
                </div>
                <div class="col-lg-5 col-md-7 bg-white">
                    <div class="p-3">
                        <div class="text-center">
                            <img src="assets/images/big/icon.png" alt="wrapkit">
                        </div>
                        <h2 class="mt-3 text-center">Nav Purush Boys Hostel</h2>
                        <h4 class="mt-3 text-center">Management System</h4>
                        
                        <form class="mt-4" method="POST">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="uname">Email</label>
                                        <input class="form-control" name="email" id="uname" type="email"
                                            placeholder="Enter your email" required>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="pwd">Password</label>
                                        <input class="form-control" name="password" id="pwd" type="password"
                                            placeholder="Enter your password" required>
                                    </div>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <button type="submit" name="login" class="btn btn-block btn-dark">LOGIN</button>
                                </div>
                                <div class="col-lg-12 text-center mt-5">
                                   <a href="admin/index.php" class="text-danger">Go to Admin Panel</a>
                                </div>
                                <div class="col-lg-12 text-center mt-2">
                                   <a href="student/registration.php" class="text-info">New Student Registration</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- Login box.scss -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- All Required js -->
    <!-- ============================================================== -->
    <script src="assets/libs/jquery/dist/jquery.min.js "></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="assets/libs/popper.js/dist/umd/popper.min.js "></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.min.js "></script>
    <!-- ============================================================== -->
    <!-- This page plugin js -->
    <!-- ============================================================== -->
    <script>
        $(".preloader ").fadeOut();
    </script>
</body>

</html>