<?php
session_start();
require_once 'db.php';

$error_msg = '';
$passwordClass = '';
$passwordError = '';
$emailClass = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error_msg = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, first_name, password_hash FROM users WHERE email = ? AND role = "guest" LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = 'guest';
            header('Location: index.php');
            exit();
        }

        $passwordError = 'Invalid guest login credentials.';
        $passwordClass = 'input-error-border';
        $emailClass = 'input-error-border';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Guest Login</title>
        <link rel="stylesheet" href="css/navbar.css">
        <link rel="stylesheet" href="css/guest-login.css">
</head>
<body class="login-bg"> 
    <nav>
        <a href="">
            <img src="images/logo.png" class="Suni logo" alt="Logo">
        </a>
        <ul>
            <li><a href="#" class="active">About CvSU SUNI</a></li>
            <li><button class="nav-btn" onclick="window.location.href='sign-in.php'">Sign in</button></li>
        </ul>
    </nav>

    <div class="login-container">
        <div class="white-box">
            
            <button type="button" class="back-btn" onclick="window.history.back()" aria-label="Go back">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
            
            <div class="form-header">
                <img src="images/cvsulogo.png" alt="CvSU logo" class="cvsu-logo"> 
                <h3>Welcome, Visitor!</h3>                     
                <p>Access public Cavite State University events and activities.</p>
            </div>
            
            <form class="login-form" method="POST" action="guest-login.php">
                <?php if (!empty($error_msg)): ?>
                    <div class="form-error"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="your.email@example.com" required class="<?php echo $emailClass; ?>" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required class="<?php echo $passwordClass; ?>">
                    <?php if (!empty($passwordError)): ?>
                        <span class="field-error"><?php echo htmlspecialchars($passwordError); ?></span>
                    <?php endif; ?>
                </div>
            
                <div class="options">
                    <label><input type="checkbox"> Remember Me</label>
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
            
                <button type="submit" class="login-btn">Login</button>

                <div class="signup">
                    Don’t have an account? <a href="guest-signup.php">Sign up as Guest</a>
                </div>
            </form>

        </div> 
    </div> 
</body>
</html>
    
    
        