<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['temp_email'])) {
    header('Location: sign-in.php');
    exit();
}

$email = $_SESSION['temp_email'];
$displayName = $_SESSION['temp_name'] ?? '';
$error_msg = '';
$passwordClass = '';
$passwordError = '';

// Fetch user's profile picture from the database for the initial display avatar
$profile_picture = 'images/person3.png'; // Fallback default image
$avatar_stmt = $conn->prepare('SELECT profile_picture FROM users WHERE email = ? AND account_type = "cvsu" LIMIT 1');
$avatar_stmt->bind_param('s', $email);
$avatar_stmt->execute();
$avatar_result = $avatar_stmt->get_result();
if ($avatar_user = $avatar_result->fetch_assoc()) {
    if (!empty($avatar_user['profile_picture'])) {
        $profile_picture = htmlspecialchars($avatar_user['profile_picture']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        $passwordError = 'Please enter your password.';
        $passwordClass = 'input-error-border';
    } else {
        $stmt = $conn->prepare('SELECT id, first_name, password, account_type, profile_picture FROM users WHERE email = ? AND account_type = "cvsu" LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = 'cvsu';
            
            if (!empty($user['profile_picture'])) {
                $_SESSION['profile_picture'] = $user['profile_picture'];
            }
            
            // Check if user is a main admin or organization admin/moderator
            $_SESSION['is_admin'] = false;
            $_SESSION['admin_role'] = null;
            
            $admin_check = $conn->prepare('SELECT id FROM organizations WHERE main_admin_id = ? LIMIT 1');
            $admin_check->bind_param('i', $user['id']);
            $admin_check->execute();
            $admin_check->store_result();
            
            if ($admin_check->num_rows > 0) {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_role'] = 'main_admin';
            } else {
                $org_admin_check = $conn->prepare('SELECT role FROM organization_admins WHERE user_id = ? LIMIT 1');
                $org_admin_check->bind_param('i', $user['id']);
                $org_admin_check->execute();
                $org_result = $org_admin_check->get_result();
                
                if ($org_result->num_rows > 0) {
                    $org_admin = $org_result->fetch_assoc();
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_role'] = $org_admin['role'];
                }
            }
            
            header('Location: index.php');
            exit();
        }

        $passwordError = 'Wrong email or password. Please try again.';
        $passwordClass = 'input-error-border';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cvsu Login Page</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/cvsu-login.css">
    
    <style>
        .form-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 24px;
        }

        .avatar-container {
            width: 90px;
            height: 90px;
            margin-bottom: 16px;
            position: relative;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(135deg, #6da06f 0%, #4c8a59 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .avatar-container:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(76, 138, 89, 0.3);
        }

        .person3-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #fff;
            display: block;
        }

        .form-header h3 {
            font-size: 22px;
            color: #222;
            margin-bottom: 8px;
            font-weight: 600;
        }
    </style>
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
            
            <button type="button" class="back-btn" onclick="window.location.href='sign-in.php'" aria-label="Go back">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
            
            <div class="form-header">
                <div class="avatar-container">
                    <img src="<?php echo $profile_picture; ?>" class="person3-avatar" alt="User avatar">
                </div>
                <h3>Welcome Back<?php echo $displayName ? ', ' . htmlspecialchars($displayName) : ''; ?></h3>
            
                <div class="form-mail">
                    <img src="images/mail.png" class="mail" alt="Mail icon">
                    <p><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>

            <form class="login-form" method="POST" action="cvsu-login.php">
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
            </form>
        </div>
    </div>
</body>
</html>