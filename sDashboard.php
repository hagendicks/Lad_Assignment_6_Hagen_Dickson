<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = "";

// --- PHP LOGIC ---
if (isset($_POST['submit_assignment'])) {
    $assignID = $_POST['assignment_id'];
    $target_dir = "uploads/";
    $target_file = $target_dir . "sub_" . $student_id . "_" . basename($_FILES["file"]["name"]);
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    $conn->query("INSERT INTO submissions (assignment_id, student_id, file_path) VALUES ('$assignID', '$student_id', '$target_file')");
    $message = "Assignment Submitted!";
}

if (isset($_POST['mark_attendance'])) {
    $code = $_POST['access_code'];
    $now = date('H:i:s');
    $today = date('Y-m-d');

    $sql = "SELECT session_id, end_time FROM attendance_sessions WHERE access_code='$code' AND session_date='$today' AND '$now' BETWEEN start_time AND end_time";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $session = $result->fetch_assoc();
        $session_id = $session['session_id'];
        $check = $conn->query("SELECT * FROM attendance_records WHERE session_id='$session_id' AND student_id='$student_id'");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO attendance_records (session_id, student_id, status) VALUES ('$session_id', '$student_id', 'Present')");
            $message = "Attendance Marked Present!";
        } else {
            $message = "You have already marked attendance.";
        }
    } else {
        $message = "Invalid or Expired Code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h3>LMS Student</h3>
            <div class="user-info">
                Logged in as: <br> <strong><?php echo $_SESSION['name']; ?></strong>
            </div>
            <a href="#attendance">Mark Attendance</a>
            <a href="#assignments">Assignments</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div class="main-content">
            <h2>Student Dashboard</h2>
            <br>
            <?php if($message) echo "<div class='success-msg'>$message</div>"; ?>

            <div class="card" id="attendance">
                <h4>Mark Attendance</h4>
                <form method="POST">
                    <input type="text" name="access_code" placeholder="Enter 6-digit Class Code" required>
                    <button type="submit" name="mark_attendance">Check In</button>
                </form>
            </div>

            <div class="card" id="assignments">
                <h4>Available Assignments</h4>
                <table>
                    <tr>
                        <th>Course</th>
                        <th>Title</th>
                        <th>Material</th>
                        <th>Grade</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $sql_assign = "SELECT a.assignment_id, c.course_name, a.title, a.file_path, 
                                   (SELECT grade FROM submissions WHERE assignment_id=a.assignment_id AND student_id='$student_id') as grade
                                   FROM assignments a 
                                   JOIN courses c ON a.course_id = c.course_id";
                    $assigns = $conn->query($sql_assign);
                    
                    if ($assigns->num_rows > 0) {
                        while($row = $assigns->fetch_assoc()) {
                            $gradeDisplay = $row['grade'] ? "<span style='color:green; font-weight:bold;'>".$row['grade']."</span>" : "<span style='color:orange;'>Pending</span>";
                            echo "<tr>
                                <td>".$row['course_name']."</td>
                                <td>".$row['title']."</td>
                                <td><a href='".$row['file_path']."' download style='color:#1976d2;'>Download</a></td>
                                <td>".$gradeDisplay."</td>
                                <td>";
                            if(!$row['grade']) {
                                 echo "<form method='POST' enctype='multipart/form-data' style='display:flex; margin:0; gap:5px;'>
                                        <input type='hidden' name='assignment_id' value='".$row['assignment_id']."'>
                                        <input type='file' name='file' required style='margin:0; width:180px; font-size:0.8em;'>
                                        <button type='submit' name='submit_assignment' style='width:auto; padding:5px 10px; font-size:0.8em;'>Submit</button>
                                      </form>";
                            } else {
                                echo "Submitted";
                            }
                            echo "</td></tr>";
                        }
                    } else {
                         echo "<tr><td colspan='5' style='text-align:center;'>No assignments available.</td></tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>