<?php
require_once "PortalServiceProxy.php";

// Example: Student (ID 10)
$student = new PortalServiceProxy("student", 10);
echo $student->viewGrades(10);   // ✅ Allowed
echo "<br>";
echo $student->viewGrades(12);   // ❌ Denied
echo "<br>";
echo $student->manageCourses();  // ❌ Denied

echo "<hr>";

// Example: Teacher (ID 50)
$teacher = new PortalServiceProxy("teacher", 50);
echo $teacher->viewGrades(12);   // ✅ Allowed
echo "<br>";
echo $teacher->manageCourses();  // ✅ Allowed
echo "<br>";
echo $teacher->manageUsers();    // ❌ Denied

echo "<hr>";

// Example: Admin (ID 1)
$admin = new PortalServiceProxy("admin", 1);
echo $admin->viewGrades(12);     // ✅ Allowed
echo "<br>";
echo $admin->manageCourses();    // ✅ Allowed
echo "<br>";
echo $admin->manageUsers();      // ✅ Allowed
?>
