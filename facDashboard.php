<?php
include 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Faculty' && $_SESSION['role'] != 'FI')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// --- PHP LOGIC (Keep the same logic as previous response) ---
if (isset($_POST['create_course'])) {
    $cName = $_POST['cName'];
    $cCode = $_POST['cCode'];
    $conn->query("INSERT INTO courses (course_name, course_code, created_by) VALUES ('$cName', '$cCode', '$user_id')");
    $message = "Course Created!";
}
// ... (Include the rest of the logic for assignments, grading, etc. here) ...
if (isset($_POST['create_assignment'])) {
    $courseID = $_POST['course_id'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $deadline = $_POST['deadline'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["file"]["name"]);
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    $conn->query("INSERT INTO assignments (course_id, title, description, file_path, deadline, created_by) VALUES ('$courseID', '$title', '$desc', '$target_file', '$deadline', '$user_id')");
    $message = "Assignment Posted!";
}
if (isset($_POST['create_attendance'])) {
    $courseID = $_POST['course_id'];
    $date = $_POST['date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $code = strtoupper(substr(md5(time()), 0, 6)); 
    $conn->query("INSERT INTO attendance_sessions (course_id, session_date, start_time, end_time, access_code, created_by) VALUES ('$courseID', '$date', '$start', '$end', '$code', '$user_id')");
    $message = "Attendance Active! Code: $code";
}
if (isset($_POST['grade_submission'])) {
    $subID = $_POST['submission_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];
    $conn->query("UPDATE submissions SET grade='$grade', feedback='$feedback' WHERE submission_id='$subID'");
    $message = "Graded successfully.";
}

$courses = $conn->query("SELECT * FROM courses WHERE created_by='$user_id'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h3>LMS Faculty</h3>
            <div class="user-info">
                Logged in as: <br> <strong><?php echo $_SESSION['name']; ?></strong>
            </div>
            <a href="#courses">Create Course</a>
            <a href="#assignments">Assignments</a>
            <a href="#attendance">Attendance</a>
            <a href="#grading">Grading</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        
        <div class="main-content">
            <h2>Dashboard Overview</h2>
            <br>
            <?php if($message) echo "<div class='success-msg'>$message</div>"; ?>

            <div class="card" id="courses">
                <h4>Create New Course</h4>
                <form method="POST">
                    <input type="text" name="cName" placeholder="Course Name" required>
                    <input type="text" name="cCode" placeholder="Course Code" required>
                    <button type="submit" name="create_course">Create Course</button>
                </form>
            </div>

            <div class="card" id="assignments">
                <h4>Upload Assignment</h4>
                <form method="POST" enctype="multipart/form-data">
                    <select name="course_id" required>
                        <option value="">Select Course</option>
                        <?php 
                        $courses->data_seek(0); 
                        while($row = $courses->fetch_assoc()) { 
                            echo "<option value='".$row['course_id']."'>".$row['course_name']."</option>"; 
                        } 
                        ?>
                    </select>
                    <input type="text" name="title" placeholder="Assignment Title" required>
                    <textarea name="description" placeholder="Instructions/Description" rows="3"></textarea>
                    <input type="datetime-local" name="deadline" required>
                    <p style="margin-bottom:5px; font-size:0.9em; color:#666;">Attach Material:</p>
                    <input type="file" name="file" style="background:white;">
                    <button type="submit" name="create_assignment">Post Assignment</button>
                </form>
            </div>

            <div class="card" id="attendance">
                <h4>Generate Attendance Code</h4>
                <form method="POST">
                    <select name="course_id" required>
                        <option value="">Select Course</option>
                        <?php 
                        $courses->data_seek(0); 
                        while($row = $courses->fetch_assoc()) { 
                            echo "<option value='".$row['course_id']."'>".$row['course_name']."</option>"; 
                        } 
                        ?>
                    </select>
                    <input type="date" name="date" required>
                    <div style="display:flex; gap:10px;">
                        <input type="time" name="start_time" required>
                        <input type="time" name="end_time" required>
                    </div>
                    <button type="submit" name="create_attendance">Generate Code</button>
                </form>
            </div>

            <div class="card" id="grading">
                <h4>Pending Submissions</h4>
                <table>
                    <tr>
                        <th>Student</th>
                        <th>Assignment</th>
                        <th>File</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $sql_subs = "SELECT s.submission_id, u.first_name, u.last_name, a.title, s.file_path, s.grade 
                                 FROM submissions s 
                                 JOIN users u ON s.student_id = u.user_id 
                                 JOIN assignments a ON s.assignment_id = a.assignment_id 
                                 WHERE a.created_by = '$user_id' AND s.grade IS NULL";
                    $subs = $conn->query($sql_subs);
                    if($subs->num_rows > 0) {
                        while($sub = $subs->fetch_assoc()) {
                            echo "<tr>
                                <td>".$sub['first_name']." ".$sub['last_name']."</td>
                                <td>".$sub['title']."</td>
                                <td><a href='".$sub['file_path']."' download style='color:#1976d2;'>Download</a></td>
                                <td>
                                    <form method='POST' style='display:flex; gap:5px; margin:0;'>
                                        <input type='hidden' name='submission_id' value='".$sub['submission_id']."'>
                                        <input type='text' name='grade' placeholder='Grade' style='width:60px; margin:0;'>
                                        <input type='text' name='feedback' placeholder='Feedback' style='margin:0;'>
                                        <button type='submit' name='grade_submission' style='width:auto; padding:5px 10px; font-size:0.8em;'>Save</button>
                                    </form>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;'>No pending submissions.</td></tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>