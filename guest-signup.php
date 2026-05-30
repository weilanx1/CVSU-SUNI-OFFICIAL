<?php
session_start();
require_once 'db.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($first_name === '' || $last_name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error_msg = 'Please complete all guest signup fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
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
            $error_msg = 'This email is already registered. Please login instead.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, "guest", NOW())');
            $stmt->bind_param('ssss', $first_name, $last_name, $email, $password_hash);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['email'] = $email;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['role'] = 'guest';
                header('Location: index.php');
                exit();
            } else {
                $error_msg = 'Unable to create guest account. Please try again later.';
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
     <link rel="stylesheet" href="css/guest-signup.css">
</head>

<body class="login-bg">
     <nav>
          <a href="">
          <img src="images/logo.png" alt="Suni Logo">
          </a>
          <ul style="display:none;">
               <li><a href="#">+ Create Event</a></li>
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
            <p>For non-CvSU students and visitors to access campus events.</p>
        </div>

        <form class="signup-form" id="registrationForm" method="POST" action="guest-signup.php">
            <?php if (!empty($error_msg)): ?>
                <div class="form-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="your.email@example.com" required>
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
                
            <div class="form-group" id="passGroup">
                <label>Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="password" name="password" placeholder="●●●●●●●●" required>
                    <button type="button" class="toggle-password-btn" data-target="password">
                        <svg class="eye-icon" xmlns="http://www.w3.org" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <div class="password-hint" id="lengthHint">Password must be at least 8 characters</div>
            </div>

            <div class="form-group" id="confirmGroup">
                <label>Confirm Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="●●●●●●●●" required>
                    <button type="button" class="toggle-password-btn" data-target="confirmPassword">
                        <svg class="eye-icon" xmlns="http://www.w3.org" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <div class="password-hint" id="matchHint" style="display: none;">? Passwords do not match</div>
            </div>
            <button type="submit" class="submit-btn">
                <img src="images/person2.png" class="btn-icon" alt=""> Create Account as Guest
            </button>
        </form>
    </div>
</div>

<script src="js/sign-up.js"></script>

</body>
</html>
