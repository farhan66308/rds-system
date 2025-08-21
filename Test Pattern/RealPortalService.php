<?php
require_once "PortalService.php";

class RealPortalService implements PortalService {
    public function viewGrades($studentId) {
        // Normally you’d fetch from DB
        return "Grades for student $studentId: A, B, C";
    }

    public function manageCourses() {
        return "Course management panel opened.";
    }

    public function manageUsers() {
        return "User management panel opened.";
    }
}
?>
