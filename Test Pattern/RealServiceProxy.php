<?php
require_once "PortalService.php";
require_once "RealPortalService.php";

class PortalServiceProxy implements PortalService {
    private $realService;
    private $role;
    private $userId;

    public function __construct($role, $userId) {
        $this->realService = new RealPortalService();
        $this->role = $role;
        $this->userId = $userId; 
    }

    public function viewGrades($studentId) {
        // Students can only view their own grades
        if ($this->role === "student" && $this->userId !== $studentId) {
            return "Access Denied: Students can only view their own grades.";
        }
        return $this->realService->viewGrades($studentId);
    }

    public function manageCourses() {
        if ($this->role === "teacher" || $this->role === "admin") {
            return $this->realService->manageCourses();
        }
        return "Access Denied: Only teachers and admins can manage courses.";
    }

    public function manageUsers() {
        if ($this->role === "admin") {
            return $this->realService->manageUsers();
        }
        return "Access Denied: Only admins can manage users.";
    }
}
?>
