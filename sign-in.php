<?php
// 1. Start a session so the server can temporarily remember the typed email across pages
session_start();

// 2. Link the database connection file we made earlier
require_once 'db.php'; 

$error_msg = "";

// 3. Check if a form button was actually clicked
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if they clicked "Continue with CvSU Email"
    if (isset($_POST['action_cvsu'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error_msg = "Please enter an email address.";
        } 
        // Rule: It MUST end with @cvsu.edu.ph
        elseif (!str_ends_with($email, '@cvsu.edu.ph')) {
            $error_msg = "Invalid email. Please use your official @cvsu.edu.ph account.";
        } else {
            // Check your database table to see if this email exists
            $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ? AND role != 'guest'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Account already exists! Save details to memory and go to Login screen
                $user = $result->fetch_assoc();
                $_SESSION['temp_email'] = $email;
                $_SESSION['temp_name'] = $user['first_name'];
                header("Location: cvsu-login.php");
                exit();
            } else {
                // New user! Save details to memory and go to registration Sign Up screen
                $_SESSION['temp_email'] = $email;
                header("Location: sign-up.php");
                exit();
            }
        }
    }

    // Check if they clicked "Login as Guest" instead
    if (isset($_POST['action_guest'])) {
        header("Location: guest-login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Login Page</title>
     <link rel="stylesheet" href="css/navbar.css">
     <link rel="stylesheet" href="css/sign-in.css?v=<?php echo time(); ?>">
</head>
<body class="login-bg">
     <nav>
          <a href="">
          <img src="images/logo.png" alt="Suni Logo">
          </a>
          <ul>
               <li><a href="#" class="active">CvSU Events</a></li>
               <li><button class="nav-btn" onclick="window.location.href='sign-in.php'">Sign in</button></li>
          </ul>
     </nav>
     <div class="login-container">
          <div class="white-box">
               <div class="form-header">
                    <img src="images/cvsulogo.png" alt="CvSU logo" class="cvsu-img">
                    <img src="images/logo.png" alt="SUNI logo" class="logo-img">
                    <p>Please Sign In or Sign Up Below.</p>
               </div>

               <form class="login-form" method="POST" action="sign-in.php">
               <div class="input-email">
                    <label for="email">CvSU Email</label>
                    
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="juan.delacruz@cvsu.edu.ph" 
                           required 
                           class="<?php echo !empty($error_msg) ? 'input-error-border' : ''; ?>"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    
                    <?php if (!empty($error_msg)): ?>
                        <span class="field-error"><?php echo $error_msg; ?></span>
                    <?php endif; ?>
               </div>
                    <button type="submit" name="action_cvsu" class="btn-primary">Continue with CvSU Email</button>
                    <button type="submit" name="action_guest" class="btn-secondary" formnovalidate>Login as Guest</button>
               </form>
          </div>
     </div>

     
</body>
</html>