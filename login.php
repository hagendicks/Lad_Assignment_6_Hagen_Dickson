<?php
include 'db_connect.php';
// ... (Keep the exact same PHP Logic from the previous response here) ...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (Paste the PHP logic from step 4 of the previous response) ...
    $email = $conn->real_escape_string($_POST['username']); 
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['first_name'];

            if ($row['role'] == 'Student') {
                header("Location: sDashboard.php");
            } else {
                header("Location: facDashboard.php");
            }
            exit();
        } else {
            $error = "Invalid Password";
        }
    } else {
        $error = "User not found";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h2>Attendance Management System Login</h2>
        <?php if(isset($error)) echo "<div class='error-msg'>$error</div>"; ?>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Email Address" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login</button>
        </form>
        <br>
        <p>Don't have an account? <a href="signup.php" style="color: #1976d2;">Sign Up</a></p>
    </div>
</body>
</html>