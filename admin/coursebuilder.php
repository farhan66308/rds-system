<?php
// CourseBuilder.php
interface CourseBuilder {
    public function addSyllabus();
    public function addAssignments();
    public function addModules();
    public function addAnnouncements();
    public function addFiles();
    public function addPeople();
    public function addGrades();
    public function addDiscussion();
    public function getCourse(): Course;
}
?>