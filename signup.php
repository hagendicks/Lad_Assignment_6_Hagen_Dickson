<?php
include 'db_connect.php'; // Ensure this path is correct

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fName = $conn->real_escape_string($_POST['fName']);
    $lName = $conn->real_escape_string($_POST['lName']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $_POST['password']; 
    
    // 1. Ashesi Domain Security Check
    if (!preg_match("/@ashesi\.edu\.gh$/", $email)) {
        $error = "Only @ashesi.edu.gh emails are allowed for registration.";
    } else {
        // 2. Password Hashing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (first_name, last_name, email, password_hash, role) 
                VALUES ('$fName', '$lName', '$email', '$hashed_password', '$role')";

        if ($conn->query($sql) === TRUE) {
            // Success: Redirect to login
            header("Location: login.php");
            exit();
        } else {
            // Error handling (e.g., email already exists)
            $error = "Registration failed. Email may already be in use.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LMS Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h2>Register New Account</h2>
        <?php if(isset($error) && $error) echo "<div class='error-msg'>$error</div>"; ?>
        
        <form method="POST" action="signup.php">
            <input type="text" name="fName" placeholder="First Name" required />
            <input type="text" name="lName" placeholder="Last Name" required />
            <input type="email" name="email" placeholder="Ashesi Email (@ashesi.edu.gh)" required />
            <input type="password" name="password" placeholder="Password" required />
            
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="Student">Student</option>
                <option value="Faculty">Faculty</option>
                <option value="FI">FI (Faculty Intern)</option>
            </select>
            
            <button type="submit">Create Account</button>
        </form>
        <br>
        <p>Already have an account? <a href="login.php" style="color: var(--primary-light); font-weight: bold;">Login here</a></p>
    </div>
</body>
</html>