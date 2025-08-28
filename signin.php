<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    
    if ($email === 'admin@nextgenspare.lk' && $password === 'admin123!@#') 
        {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        header("Location: admin_dashboard.php");
        exit();
    }
    
    
    $stmt = $conn->prepare("SELECT id, email, password_hash, full_name, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            
            $update_stmt = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            
            
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: home.html");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In | NextGenSpare.lk</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="chatbot.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Poppins&display=swap" rel="stylesheet">
    <style>
        .signin-container {
            display: flex;
            min-height: 80vh;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            padding: 65px 0;
        }

        .signin-box {
            display: flex;
            width: 65%;
            max-width: 960px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            overflow: hidden;
        }

        .signin-left {
            flex: 1;
            background: #bdbebe;
            color: #002f5f;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .signin-left img {
            max-width: 200px;
            margin-bottom: 0.5rem;
        }

        .signin-right {
            flex: 1;
            background: #fff;
            padding: 2rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .signin-right h2 {
            color: #323232;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .signin-right input {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .signin-right button.continue {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, #ee5757, #8a0202);
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .signin-right button.continue:hover {
            background: linear-gradient(135deg, #615f5f, #000000);
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #ef5350;
        }

        .admin-info {
            background: #e3f2fd;
            color: #1565c0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #42a5f5;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <section class="signin-container">
        <div class="signin-box">
            <div class="signin-left">
                <img src="newLogo.png" alt="logo" />
                <h2><span style="color:#8a0202;">NextGenSpare.lk</span><br><small style="font-size:1.5rem;">The Future
                        of Luxury Auto Parts</small></h2>
                <p>Sign in to access your account and manage your garage.</p>
            </div>
            <div class="signin-right">
                <h2>Sign In</h2>
                <p>Enter your credentials</p>
                
                <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="admin-info">
                    <strong>Admin Access:</strong> admin@nextgenspare.lk / admin123!@#
                </div>
                
                <form method="POST">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" class="continue">Sign In</button>
                </form>
                <p style="margin-top:1rem;">Don't have an account? <a href="signup.html">Sign Up</a></p>
            </div>
        </div>
    </section>
</body>
</html>