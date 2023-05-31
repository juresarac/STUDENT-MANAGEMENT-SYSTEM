<?php
$studentsFile = 'students.json';
error_reporting(E_ERROR | E_PARSE);


// Load student data from JSON file
$students = [];
$jsonData = file_get_contents('students.json');
if ($jsonData) {
    $students = json_decode($jsonData, true);
}

// Add student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registrationNumber = $_POST['registration_number'];
    $name = $_POST['name'];
    $grade = $_POST['grade'];
    $classroom = $_POST['classroom'];

    if (empty($registrationNumber) || empty($name) || empty($grade) || empty($classroom)) {
    } elseif (!is_numeric($grade)) {
        echo "Error: Grade must be a number!";
    } else {
        // Check if the registration number already exists
        $existingStudent = array_filter($students, function ($student) use ($registrationNumber) {
            return $student['registration_number'] === $registrationNumber;
        });

        if (empty($existingStudent)) {
            $newStudent = [
                "registration_number" => $registrationNumber,
                "name" => $name,
                "grade" => $grade,
                "classroom" => $classroom
            ];
            array_push($students, $newStudent);
            saveStudentsData($students, $studentsFile);
        } else {
            echo "Error: Duplicate registration number!";
        }
    }
}

// Delete student
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['registration_number'])) {
    $registrationNumber = $_GET['registration_number'];
    foreach ($students as $key => $student) {
        if ($student['registration_number'] === $registrationNumber) {
            unset($students[$key]);
            saveStudentsData($students, $studentsFile);
            break;
        }
    }
}

// Edit student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_registration_number'])) {
    $registrationNumber = $_POST['edit_registration_number'];
    $name = $_POST['edit_name'];
    $grade = $_POST['edit_grade'];
    $classroom = $_POST['edit_classroom'];

    if (empty($registrationNumber) || empty($name) || empty($grade) || empty($classroom)) {
        echo "Error: All fields are required!";
    } elseif (!is_numeric($grade)) {
        echo "Error: Grade must be a number!";
    } else {
        // Find the student to edit
        $index = -1;
        foreach ($students as $key => $student) {
            if ($student['registration_number'] === $registrationNumber) {
                $index = $key;
                break;
            }
        }

        if ($index !== -1) {
            // Update the student's information
            $students[$index]['name'] = $name;
            $students[$index]['grade'] = $grade;
            $students[$index]['classroom'] = $classroom;

            // Save the updated student data to the JSON file
            $jsonData = json_encode($students);
            if (file_put_contents('students.json', $jsonData)) {
                echo "<div class=\"success-message\">Student updated successfully!</div>";
            } else {
                echo "Error: Failed to update student data!";
            }
        } else {
            echo "Error: Student not found!";
        }
    }
}

function saveStudentsData($students, $file)
{
    $studentsData = json_encode($students, JSON_PRETTY_PRINT);
    file_put_contents($file, $studentsData);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management System</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<h1>Student Management System</h1>

<form method="post" action="index.php">
    <label for="registration_number">Registration Number:</label>
    <input type="text" id="registration_number" name="registration_number" required>

    <label for="name">Name:</label>
    <input type="text" id="name" name="name" required>

    <label for="grade">Grade:</label>
    <input type="number" id="grade" name="grade" min="0" max="10" required>

    <label for="classroom">Classroom:</label>
    <select id="classroom" name="classroom" required>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
    </select>

    <button type="submit" class="button">Add Student</button>
</form>

<table>
    <tr>
        <th>Registration Number</th>
        <th>Name</th>
        <th>Grade</th>
        <th>Classroom</th>
        <th>Actions</th>
    </tr>
    <?php
    // Code to display existing students
    foreach ($students as $student) {
        $registrationNumber = $student['registration_number'];
        $name = $student['name'];
        $grade = $student['grade'];
        $classroom = $student['classroom'];
        echo "<tr>";
        echo "<td>$registrationNumber</td>";
        echo "<td>$name</td>";
        echo "<td>$grade</td>";
        echo "<td>$classroom</td>";
        echo "<td>
                <button onclick=\"editStudent('$registrationNumber', '$name', '$grade', '$classroom')\" class=\"button\">Edit</button>
                <button onclick=\"deleteStudent('$registrationNumber')\" class=\"button\">Delete</button>
            </td>";
        echo "</tr>";
    }
    ?>
</table>

<div id="edit-form-container" style="display: none;">
    <!-- Edit student form -->
    <form id="edit-form" method="post" action="index.php" onsubmit="return validateEditForm()">
        <h2>Edit Student</h2>
        <input type="hidden" id="edit_registration_number" name="edit_registration_number">
        <label for="edit_name">Name:</label>
        <input type="text" id="edit_name" name="edit_name" required>
        <label for="edit_grade">Grade:</label>
        <input type="number" id="edit_grade" name="edit_grade" min="0" max="10" required>
        <label for="edit_classroom">Classroom:</label>
        <select id="edit_classroom" name="edit_classroom" required>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
        </select>
        <button type="submit" class="button">Save</button>
        <button type="button" onclick="cancelEdit()" class="button">Cancel</button>
    </form>
</div>

<script>
    function editStudent(registrationNumber, name, grade, classroom) {
        // Populate the edit form fields with student data
        document.getElementById("edit_registration_number").value = registrationNumber;
        document.getElementById("edit_name").value = name;
        document.getElementById("edit_grade").value = grade;
        document.getElementById("edit_classroom").value = classroom;

        // Show the edit form container
        document.getElementById("edit-form-container").style.display = "block";
    }

    function deleteStudent(registrationNumber) {
        if (confirm("Are you sure you want to delete this student?")) {
            window.location.href = "index.php?action=delete&registration_number=" + registrationNumber;
        }
    }

    function cancelEdit() {
        // Clear the edit form fields
        document.getElementById("edit_registration_number").value = "";
        document.getElementById("edit_name").value = "";
        document.getElementById("edit_grade").value = "";
        document.getElementById("edit_classroom").value = "";

        // Hide the edit form container
        document.getElementById("edit-form-container").style.display = "none";
    }

    function validateEditForm() {
        // Validate the edit form fields before submitting
        var name = document.getElementById("edit_name").value;
        var grade = document.getElementById("edit_grade").value;
        var classroom = document.getElementById("edit_classroom").value;

        if (name === "" || grade === "" || classroom === "") {
            alert("Error: All fields are required!");
            return false;
        }

        if (isNaN(grade) || grade < 0 || grade > 10) {
            alert("Error: Invalid grade value!");
            return false;
        }

        return true;
    }
</script>

</body>
</html>
