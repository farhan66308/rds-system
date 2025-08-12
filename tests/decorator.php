<?php
// Interface for Course
interface ICourse {
    public function getDescription(): string;
}

// Concrete Course class
class Course implements ICourse {
    private string $courseName;

    public function __construct(string $name) {
        $this->courseName = $name;
    }

    public function getDescription(): string {
        return "Course: " . $this->courseName;
    }
}

// Base Decorator class
abstract class CourseDecorator implements ICourse {
    protected ICourse $wrappedCourse;

    public function __construct(ICourse $course) {
        $this->wrappedCourse = $course;
    }

    public function getDescription(): string {
        return $this->wrappedCourse->getDescription();
    }
}

// Announcement Decorator
class AnnouncementDecorator extends CourseDecorator {
    public function getDescription(): string {
        // In real scenario, you can fetch announcement count from DB here
        $announcements = " | Announcements: 5 new updates";
        return parent::getDescription() . $announcements;
    }
}

// Transaction Decorator
class TransactionDecorator extends CourseDecorator {
    public function getDescription(): string {
        // In real scenario, fetch transaction/payment status
        $paymentInfo = " | Payment Status: Completed";
        return parent::getDescription() . $paymentInfo;
    }
}

// Support Decorator
class SupportDecorator extends CourseDecorator {
    public function getDescription(): string {
        // In real scenario, fetch support ticket info from DB
        $supportInfo = " | Support Tickets: 2 open";
        return parent::getDescription() . $supportInfo;
    }
}

// Usage example
$basicCourse = new Course("Computer Science 101");

// Add announcement info dynamically
$courseWithAnnouncements = new AnnouncementDecorator($basicCourse);

// Add transaction info dynamically
$courseWithTransaction = new TransactionDecorator($courseWithAnnouncements);

// Add support info dynamically
$fullyDecoratedCourse = new SupportDecorator($courseWithTransaction);

echo $fullyDecoratedCourse->getDescription();

?>
