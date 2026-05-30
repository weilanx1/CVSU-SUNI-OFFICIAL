<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['temp_email'])) {
    header('Location: sign-in.php');
    exit();
}

$email = $_SESSION['temp_email'];
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($first_name === '' || $last_name === '' || $college === '' || $password === '' || $confirmPassword === '') {
        $error_msg = 'Please fill out all required fields.';
    } elseif (strlen($password) < 8) {
        $error_msg = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error_msg = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_msg = 'This CvSU account already exists. Please sign in instead.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (first_name, last_name, email, college, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, "cvsu", NOW())');
            $stmt->bind_param('sssss', $first_name, $last_name, $email, $college, $password_hash);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['email'] = $email;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['role'] = 'cvsu';
                header('Location: index.php');
                exit();
            } else {
                $error_msg = 'Unable to create account. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Sign Up</title>
     <link rel="stylesheet" href="css/navbar.css">
     <link rel="stylesheet" href="css/sign-up.css">
</head>

<body class="login-bg">
     <nav>
          <a href="">
          <img src="images/logo.png" alt="Suni Logo">
          </a>
          <ul style="display:none;">
               <li><a href="#" class="active">CvSU Events</a></li>
               <li><button class="nav-btn" onclick="window.location.href='sign-in.php'">Sign in</button></li>
          </ul>
     </nav>
     
 <div class="signup-container">
    <div class="signup-whitebox">

        <button type="button" class="back-btn" onclick="window.history.back()" aria-label="Go back">
            <svg xmlns="http://www.w3.org" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        </button>
        
  
        <div class="signupform-header">
            <img src="images/person1.png" class="person1" alt="Header Avatar">
            <h3>Create Account</h3>
            <p>Join SUNI and be part of campus events and activities.</p>
        </div>

     
        <form class="signup-form" id="registrationForm" method="POST" action="sign-up.php">
            <?php if (!empty($error_msg)): ?>
                <div class="form-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
            </div>

            <div class="form-row-2col">
                <div class="form-group">
                    <label>First Name*</label>
                    <input type="text" name="first_name" placeholder="First name" required>
                </div>
                <div class="form-group">
                    <label>Last Name*</label>
                    <input type="text" name="last_name" placeholder="Last name" required>
                </div>
            </div>
                
            <div class="form-group">
                <label>College</label>
                <div class="custom-dropdown" id="deptDropdown">
                    <div class="dropdown-header">
                        <span>Select your College</span>
                        <svg class="arrow-icon" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                    <ul class="dropdown-list">
                        <li data-value="CAFENR">College of Agriculture, Food, Environment, and Natural Resources</li>
                        <li data-value="CAS">College of Arts and Sciences</li>
                        <li data-value="CCJ">College of Criminal Justice</li>
                        <li data-value="CEd">College of Education</li>
                        <li data-value="CEIT">College of Engineering and Information Technology</li>                        
                        <li data-value="CEMDS">College of Economics, Management, and Development Studies</li>
                        <li data-value="CON">College of Nursing</li>  
                        <li data-value="CTHM">College of Tourism and Hospitality Management</li>
                        <li data-value="CSPEAR">College of Sports, Physical Education, and Recreation</li>
                        <li data-value="CVMBS">College of Veterinary Medicine and Biomedical Sciences</li>                                                                
                    </ul>
                    <input type="hidden" name="college" id="deptInput" required>
                </div>
            </div>

            <div class="form-group" id="passGroup">
                <label>Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="password" name="password" placeholder="●●●●●●●●" required>
                    <button type="button" class="toggle-password-btn" data-target="password">
                        <svg class="eye-icon" xmlns="http://www.w3.org" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <div class="password-hint" id="lengthHint">Passwor must be at least 8 characters</div>
            </div>

            <div class="form-group" id="confirmGroup">
                <label>Confirm Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="●●●●●●●●" required>
                    <button type="button" class="toggle-password-btn" data-target="confirmPassword">
                        <svg class="eye-icon" xmlns="http://www.w3.org" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <div class="password-hint" id="matchHint" style="display: none;">Passwords do not match</div>
            </div>
            <button type="submit" class="submit-btn">
                <img src="images/person2.png" class="btn-icon" alt=""> Create Account
            </button>
        </form>
    </div>
</div>

<script src="js/sign-up.js"></script>

</body>
</html>
