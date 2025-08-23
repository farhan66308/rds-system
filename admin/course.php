<?php
// Course.php
class Course {
    private $syllabus;
    private $assignments;
    private $modules;
    private $announcements;
    private $files;
    private $people;
    private $grades;
    private $discussion;

    // Public setter methods for each section
    public function setSyllabus($syllabus) {
        $this->syllabus = $syllabus;
    }

    public function setAssignments($assignments) {
        $this->assignments = $assignments;
    }
    
    public function setModules($modules) {
        $this->modules = $modules;
    }

    public function setAnnouncements($announcements) {
        $this->announcements = $announcements;
    }

    public function setFiles($files) {
        $this->files = $files;
    }

    public function setPeople($people) {
        $this->people = $people;
    }

    public function setGrades($grades) {
        $this->grades = $grades;
    }

    public function setDiscussion($discussion) {
        $this->discussion = $discussion;
    }
    
    // Getter methods to retrieve the state of each section
    public function getSyllabus() {
        return $this->syllabus;
    }

    public function getAssignments() {
        return $this->assignments;
    }

    public function getModules() {
        return $this->modules;
    }

    public function getAnnouncements() {
        return $this->announcements;
    }

    public function getFiles() {
        return $this->files;
    }

    public function getPeople() {
        return $this->people;
    }

    public function getGrades() {
        return $this->grades;
    }

    public function getDiscussion() {
        return $this->discussion;
    }

    public function displayCourseInfo() {
        echo "<h2>Course Structure</h2>";
        if ($this->syllabus) echo "<li>Syllabus</li>";
        if ($this->assignments) echo "<li>Assignments</li>";
        if ($this->modules) echo "<li>Modules</li>";
        if ($this->announcements) echo "<li>Announcements</li>";
        if ($this->files) echo "<li>Files</li>";
        if ($this->people) echo "<li>People</li>";
        if ($this->grades) echo "<li>Grades</li>";
        if ($this->discussion) echo "<li>Discussion</li>";
    }
}