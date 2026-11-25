<!DOCTYPE html>
<html>

    <head>
            <style>
                .error {color: #ff0000;}
            </style>
            <link rel="stylesheet" href="style.css">
    </head>
        <body>

            <?php

                $studentIdErr = $firstnameErr = $lastnameErr = $projectTitleErr = $emailErr = $phoneNumberErr = $slot_idErr = "";
                $studentId = $firstname = $lastname = $projectTitle = $email = $phoneNumber = $slot_id = "";

                //Connecting to MySQL database
                //local host pass: jsdfkjef*49KJ)Ke*
                $servername = "project3db.cdsm06o6yrc7.us-east-2.rds.amazonaws.com";
                $username = "joshzuck523";
                $password = "kg84539fj2840f*a";
                $database = "project_3";

                $conn = new mysqli($servername, $username, $password, $database);

                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                
                //Loading timeslot information
                $sql = "SELECT
                            timeslots.slot_id,
                            timeslots.slot_date,
                            timeslots.start_time,
                            timeslots.end_time,
                            timeslots.capacity - COUNT(students.student_id) AS spotsLeft
                        
                        FROM timeslots

                        LEFT JOIN students
                            ON timeslots.slot_id = students.slot_id

                        GROUP BY timeslots.slot_id";
                
                $result = $conn->query($sql);
                

                $availableSlots = [];
                
                //Creates array of available time slots from the MySQL database info
                while ($row = $result->fetch_assoc()) {
                    $id = $row["slot_id"];

                    $availableSlots[$id] = [
                        "date" => $row["slot_date"],
                        "startTime" => $row["start_time"],
                        "endTime" => $row["end_time"],
                        "spotsLeft" => $row["spotsLeft"],
                    ];
                }

                //If the user changes there slot
                if ($_SERVER["REQUEST_METHOD"] == "POST" &&
                    isset($_POST["confirmChange"])) {

                    if ($_POST["confirmChange"] == "yes") {

                        $studentId = $_POST["studentId"];
                        $slot_id = (int)$_POST["slot_id"];

                        // Your UPDATE statement goes here
                        $stmt = $conn->prepare(
                            "UPDATE students
                            SET slot_id = ?
                            WHERE student_id = ?"
                        );

                        $stmt->bind_param("is", $slot_id, $studentId);

                        if ($stmt->execute()) {
                            header("Location: " . $_SERVER["PHP_SELF"] . "?changed=1");
                            exit();
                        }
                        else {
                            echo "<p>Slot change failed.</p>";
                        }

                        $stmt->close();
                    }
                    else {
                        echo "<p>Slot change cancelled.</p>";
                    }
                }
                
                if($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["confirmChange"])) {
                    if (empty($_POST["studentId"])) {
                        $studentIdErr = "Id is required";
                    }
                    else {
                        $studentId = test_input($_POST["studentId"]);
                        if (!preg_match("/^[0-9]{8}$/", $studentId)) {
                            $studentIdErr = "Must be 8 characters.";
                        }
                    }
                    if (empty($_POST["firstname"])) {
                        $firstnameErr = "First name is required";
                    }
                    else {
                        $firstname = test_input($_POST["firstname"]);
                        if (!preg_match("/^[a-zA-Z]+$/", $firstname)) {
                            $firstnameErr = "Only letters are allowed";
                        }
                    }
                    if (empty($_POST["lastname"])) {
                        $lastnameErr = "Last name is required";
                    }
                    else {
                        $lastname = test_input($_POST["lastname"]);
                        if (!preg_match("/^[a-zA-Z]+$/", $lastname)) {
                            $lastnameErr = "Only letters are allowed";
                        }
                    }
                    if (empty($_POST["projectTitle"])) {
                        $projectTitleErr = "Project Title is required";
                    }
                    else {
                        $projectTitle = test_input($_POST["projectTitle"]);
                    }
                    if (empty($_POST["email"])) {
                        $emailErr = "Email is required";
                    }
                    else {
                        $email = test_input($_POST["email"]);
                        if (!preg_match("/^[a-zA-Z0-9]+@[a-zA-Z0-9]{1,20}(\.[a-zA-Z0-9]{1,20})*$/", $email)) {
                            $emailErr = "Invalid email format";
                        }
                        else {
                            $domain = substr($email, strpos($email, "@") + 1);

                            if (strlen($domain) > 80) {
                                $emailErr = "Domain cannot exceed 80 characters";
                            }
                        }
                    }
                    if (empty($_POST["phoneNumber"])) {
                        $phoneNumberErr = "Phone number is required";
                    }
                    else {
                        $phoneNumber = test_input($_POST["phoneNumber"]);
                        if (!preg_match("/^\d{3}-\d{3}-\d{4}$/", $phoneNumber)) {
                            $phoneNumberErr = "Only 10 digits and 2 hyphens allowed. Ex: XXX-XXX-XXXX";
                        }
                    }
                    if (empty($_POST["slot_id"])) {
                        $slot_idErr = "Time slot is required";
                    }
                    else {
                        $slot_id = test_input($_POST["slot_id"]);

                        if (!array_key_exists($slot_id, $availableSlots)) {
                            $slot_idErr = "Invalid time slot selected";
                        }
                    }
                    
                    //Checking if student is already in registered
                    if (empty($studentIdErr) &&
                        empty($firstnameErr) &&
                        empty($lastnameErr) &&
                        empty($projectTitleErr) &&
                        empty($emailErr) &&
                        empty($phoneNumberErr) &&
                        empty($slot_idErr)) {

                        $stmt = $conn->prepare(
                            "SELECT student_id, slot_id FROM students WHERE student_id = ?"
                        );
                        $stmt->bind_param("s", $studentId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            //Students exists
                            $existingStudent = $result->fetch_assoc();
                            echo "Student already exists";
                            //Come back for changing time slot
                            if ($existingStudent["slot_id"] != $slot_id) {
                                if ($availableSlots[$slot_id]["spotsLeft"] <= 0) {
                                    $slot_idErr = "This time slot is fully booked";
                                }
                                else {
                                    // Student can change slots
                                    echo "<script>
                                            document.addEventListener('DOMContentLoaded', function() {

                                                if (confirm('You are already registered. Would you like to change your time slot?')) {
                                                    document.getElementById('confirmChange').value = 'yes';
                                                }
                                                else {
                                                    document.getElementById('confirmChange').value = 'no';
                                                }

                                                document.getElementById('confirmForm').submit();
                                            });
                                        </script>";
                                    
                                }
                            }
                        }
                        else {
                            if ($availableSlots[$slot_id]["spotsLeft"] <= 0) {
                                $slot_idErr = "This time slot is fully booked";
                            }
                            else {
                                $stmt = $conn->prepare(
                                    "INSERT INTO students
                                    (student_id, first_name, last_name, project_title, email_address, phone_number, slot_id)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                                );
                                $stmt->bind_param(
                                    "ssssssi",
                                    $studentId,
                                    $firstname,
                                    $lastname,
                                    $projectTitle,
                                    $email,
                                    $phoneNumber,
                                    $slot_id
                                );
                                if ($stmt->execute()) {
                                    header("Location: " . $_SERVER["PHP_SELF"] . "?success=1");
                                    exit();
                                }
                                else {
                                    echo "<p>Registration failed: " . htmlspecialchars($stmt->error) . "</p>";
                                }
                            }
                        }

                        $stmt->close();
                    }
                    
                }

                function test_input($data) {
                    $data = trim($data);
                    $data = stripslashes($data);
                    return $data;
                }
                

            ?>

            <?php
                if (isset($_GET["changed"])) {
                    echo "<p>Slot change successful!</p>";
                }
                else if (isset($_GET["success"])) {
                    echo "<p>Registration successful!</p>";
                }
            ?>
            <div class="container">
            <h2>Time Slot Registeration</h2>
            <a href="studentDisplay.php">View Register Students</a>
            <p><span class="error">* required field</span></p>
            <form method="POST" action="<?php 
                echo htmlspecialchars($_SERVER["PHP_SELF"]);
            ?>">
                Id : <span class="error">* <?php echo $studentIdErr;?></span>
                <input type="text" name="studentId" value="<?php echo htmlspecialchars($studentId);?>">
                <br><br>
                First Name: <span class="error">* <?php echo $firstnameErr;?></span>
                <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname);?>">
                <br><br>
                Last Name: <span class="error">* <?php echo $lastnameErr;?></span>
                <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname);?>">               
                <br><br>
                Project Title: <span class="error">* <?php echo $projectTitleErr;?></span>
                <input type="text" name="projectTitle" value="<?php echo htmlspecialchars($projectTitle);?>">                
                <br><br>
                E-mail: <span class="error">* <?php echo $emailErr;?></span>
                <input type="text" name="email" value="<?php echo htmlspecialchars($email);?>">               
                <br><br>
                Phone Number: <span class="error">* <?php echo $phoneNumberErr;?></span>
                <input type="text" name="phoneNumber" value="<?php echo htmlspecialchars($phoneNumber);?>">               
                <br><br>
                Project Date: <span class="error">* <?php echo $slot_idErr;?></span>
                <select name="slot_id">
                     <option value="">-- Select Time Slot --</option>

                        <?php
                            foreach ($availableSlots as $id => $slot) {

                                $disabled = ($slot["spotsLeft"] == 0) ? "disabled" : "";

                                $selected = ($slot_id == $id) ? "selected" : "";

                                echo "<option value='$id' $disabled $selected>";

                                echo $slot["date"] . " | ";
                                echo $slot["startTime"] . " - ";
                                echo $slot["endTime"] . " | ";
                                echo $slot["spotsLeft"] . " seats remaining";

                                echo "</option>";
                            }
                        ?>
                </select>
                <br><br>
                <input type="submit" name="submit" value="Submit">
            </form>
            <form method="POST" id="confirmForm">
                <input type="hidden" name="confirmChange" id="confirmChange">
                <input type="hidden" name="studentId" value="<?php echo htmlspecialchars($studentId); ?>">
                <input type="hidden" name="slot_id" value="<?php echo htmlspecialchars($slot_id); ?>">
            </form>
            </div>

            
            <?php 
                /*

                if ($_SERVER["REQUEST_METHOD"] == "POST" &&
                !isset($_POST["confirmChange"]) &&
                    empty($studentIdErr) &&
                    empty($firstnameErr) &&
                    empty($lastnameErr) &&
                    empty($projectTitleErr) &&
                    empty($emailErr) &&
                    empty($phoneNumberErr) &&
                    empty($slot_idErr)) {

                    echo "<h2>Your Input:</h2>";
                    echo $studentId;
                    echo "<br>";
                    echo $firstname;
                    echo "<br>";
                    echo $lastname;
                    echo "<br>";
                    echo $projectTitle;
                    echo "<br>";
                    echo $email;
                    echo "<br>";
                    echo $phoneNumber;
                    echo "<br>";
                }
                */
            ?>

        </body>
</html>
