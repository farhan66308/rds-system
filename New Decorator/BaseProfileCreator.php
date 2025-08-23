<?php
// BaseProfileCreator.php

require_once 'ProfileCreator.php';
require_once '../conn.php';

class BaseProfileCreator implements ProfileCreator {
    private $conn;
    private $errors = [];
    private $successMessage = '';

    public function __construct(Database $db) {
        $this->conn = $db->getConnection();
    }

    public function createProfile(array $data): bool {
        // 1. Validate Base Faculty Info
        if (empty($data['faculty_code'])) $this->errors[] = "Faculty Code is required.";
        if (empty($data['user_id'])) $this->errors[] = "User ID is required.";
        if (empty($data['name'])) $this->errors[] = "Name is required.";
        if (empty($data['department'])) $this->errors[] = "Department is required.";
        if (empty($data['role'])) $this->errors[] = "Role is required.";
    
        // Check for existing FacultyCode or UserID
        if (empty($this->errors)) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM facultyinfo WHERE FacultyCode = ? OR UserID = ?");
            $stmt->bind_param("ss", $data['faculty_code'], $data['user_id']);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            if ($count > 0) {
                $this->errors[] = "A faculty member with this Faculty Code or User ID already exists.";
            }
        }

        // 2. Insert Base Faculty Info
        if (empty($this->errors)) {
$stmt = $this->conn->prepare("
    INSERT INTO facultyinfo 
        (FacultyCode, UserID, Name, Avatar, Bio, Department, Role, Office, Website, EducationInfo, SkillSet) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    throw new RuntimeException("Prepare failed: ({$this->conn->errno}) {$this->conn->error}");
}

$stmt->bind_param("sssssssssss", 
    $data['faculty_code'], 
    $data['user_id'], 
    $data['name'], 
    $data['avatar'], 
    $data['bio'], 
    $data['department'], 
    $data['role'], 
    $data['office'], 
    $data['website_url'], 
    $data['education_institution'], 
    $data['skill_description']
);

            if ($stmt->execute()) {
                $stmt->close();
                $this->successMessage = "Base faculty profile created successfully!";
                return true;
            } else {
                $this->errors[] = "Failed to create base faculty profile: " . $this->conn->error;
                $stmt->close();
                return false;
            }
        }
        return false;
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
    
    public function getSuccessMessage(): string {
        return $this->successMessage;
    }
}
?>