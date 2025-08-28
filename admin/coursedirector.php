<?php
// CourseDirector.php
require_once 'Course.php';
require_once 'CourseBuilder.php';
require_once 'concretebuilder.php'; // Path to your FullCourseBuilder.php

class CourseDirector {

    public function buildFullCourse(CourseBuilder $builder): Course {
        return $builder
            ->addSyllabus()
            ->addAssignments()
            ->addModules()
            ->addAnnouncements()
            ->addFiles()
            ->addPeople()
            ->addGrades()
            ->addDiscussion()
            ->getCourse();
    }
    
    public function buildSeminarCourse(CourseBuilder $builder): Course {
        return $builder
            ->addSyllabus()
            ->addAnnouncements()
            ->addDiscussion()
            ->getCourse();
    }
    public function loadCourseFromDatabase(CourseBuilder $builder, mysqli $conn, string $courseId, int $sectionNumber): Course {
        $stmt = $conn->prepare("SELECT Syllabus, Assignments, Modules, Annoucements, Files, People, Grades, Discussions FROM CourseStructure WHERE CourseID = ? AND Section = ? LIMIT 1");
        $stmt->bind_param("si", $courseId, $sectionNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $courseData = $result->fetch_assoc();
        $stmt->close();

        if (!$courseData) {
            throw new Exception("Course structure not found for Course ID: {$courseId}, Section: {$sectionNumber}.");
        }

        // Use the retrieved data to call the builder's methods
        // Only add sections that are marked as 1 in the database
        if ($courseData['Syllabus']) $builder->addSyllabus();
        if ($courseData['Assignments']) $builder->addAssignments();
        if ($courseData['Modules']) $builder->addModules();
        if ($courseData['Annoucements']) $builder->addAnnouncements();
        if ($courseData['Files']) $builder->addFiles();
        if ($courseData['People']) $builder->addPeople();
        if ($courseData['Grades']) $builder->addGrades();
        if ($courseData['Discussions']) $builder->addDiscussion();

        return $builder->getCourse();
    }
}