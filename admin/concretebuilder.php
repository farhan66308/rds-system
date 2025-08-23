<?php
require_once '../conn.php';
require_once 'Course.php';
require_once 'CourseBuilder.php';

class FullCourseBuilder implements CourseBuilder {
    private $course;

    public function __construct() {
        $this->course = new Course();
        $db = new Database();
        $conn = $db->getConnection();
    }

    public function addSyllabus() {
        $this->course->setSyllabus(true);
        return $this; // Returns the builder for method chaining
    }

    public function addAssignments() {
        $this->course->setAssignments(true);
        return $this;
    }

    public function addModules() {
        $this->course->setModules(true);
        return $this;
    }
    
    public function addAnnouncements() {
        $this->course->setAnnouncements(true);
        return $this;
    }

    public function addFiles() {
        $this->course->setFiles(true);
        return $this;
    }

    public function addPeople() {
        $this->course->setPeople(true);
        return $this;
    }

    public function addGrades() {
        $this->course->setGrades(true);
        return $this;
    }

    public function addDiscussion() {
        $this->course->setDiscussion(true);
        return $this;
    }

    public function getCourse(): Course {
        return $this->course;
    }

    public function saveToDatabase(mysqli $conn, string $courseId): bool {
        // Generate a unique StructureID
        $structureId = 'STR_' . uniqid(); 

        // Convert boolean properties to 1 or 0 for TinyInt columns
        $syllabus = $this->course->getSyllabus() ? 1 : 0;
        $assignments = $this->course->getAssignments() ? 1 : 0;
        $modules = $this->course->getModules() ? 1 : 0;
        $announcements = $this->course->getAnnouncements() ? 1 : 0;
        $files = $this->course->getFiles() ? 1 : 0;
        $people = $this->course->getPeople() ? 1 : 0;
        $grades = $this->course->getGrades() ? 1 : 0;
        $discussions = $this->course->getDiscussion() ? 1 : 0;

        // Assuming Section is always 1 for this structure, or based on specific logic
        $sectionNumber = 1; 

        // Prepare the SQL statement for insertion
        $stmt = $conn->prepare("INSERT INTO CourseStructure (StructureID, Section, CourseID, Syllabus, Assignments, Modules, Annoucements, Files, People, Grades, Discussions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Bind parameters
        $stmt->bind_param("sisiiiiiiii", 
            $structureId, 
            $sectionNumber, 
            $courseId, 
            $syllabus, 
            $assignments, 
            $modules, 
            $announcements, 
            $files, 
            $people, 
            $grades, 
            $discussions
        );

        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}