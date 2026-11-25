<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>

    <?php
        //local host pass: jsdfkjef*49KJ)Ke*
        
        $servername = "project3db.cdsm06o6yrc7.us-east-2.rds.amazonaws.com";
        $username = "joshzuck523";
        $password = "kg84539fj2840f*a";
        $database = "project_3";
        $conn = new mysqli($servername, $username, $password, $database);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT
                    students.student_id,
                    students.first_name,
                    students.last_name,
                    students.project_title,
                    students.email_address,
                    students.phone_number,
                    timeslots.slot_date,
                    timeslots.start_time,
                    timeslots.end_time
                FROM students
                JOIN timeslots ON students.slot_id = timeslots.slot_id
                ORDER BY timeslots.slot_id, students.last_name;";

        $result = $conn->query($sql);

    ?>

    <div class="container">
    <h2>Registered Students</h2>
    <a href="index.php">Registration Page</a>
    <table border="1">
        <tr>
            <th>Student ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Project Title</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
        </tr>

        <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["student_id"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["first_name"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["last_name"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["project_title"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["email_address"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["phone_number"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["slot_date"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["start_time"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["end_time"]) . "</td>";
                    echo "</tr>";
                }
            }
            else {
                echo "<tr><td colspan='9'>No students registered.</td></tr>";
            }
        ?>
    </table>
</div>

    </body>
</html>
