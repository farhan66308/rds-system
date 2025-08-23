<?php
class AnnouncementSubject implements Subject {
    private $observers = [];

    // Attach a student observer
    public function attach(Observer $observer) {
        $this->observers[$observer->getUserID()] = $observer;
    }

    // Detach a student observer
    public function detach(Observer $observer) {
        unset($this->observers[$observer->getUserID()]);
    }

    // Notify all observers about new announcement
    public function notify($announcement) {
        foreach ($this->observers as $observer) {
            $observer->update($announcement);
        }
    }

    // Load observers from DB for a course
    public function loadObserversByCourse($courseID, $conn) {
        $this->observers = []; // reset
        $sql = "SELECT UserID FROM subscriptions WHERE CourseID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $courseID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $this->attach(new StudentObserver($row['UserID']));
        }
        $stmt->close();
    }
}

class StudentObserver implements Observer {
    private $userID;

    public function __construct($userID) {
        $this->userID = $userID;
    }

    public function getUserID() {
        return $this->userID;
    }

    // Define what happens when an announcement is pushed
    public function update($announcement) {

        global $conn; // assuming $conn is accessible globally or pass as param

        $stmt = $conn->prepare("INSERT INTO notifications (UserID, AnnouncementID, IsRead) VALUES (?, ?, 0)");
        $stmt->bind_param("ii", $this->userID, $announcement['id']);
        $stmt->execute();
        $stmt->close();
    }
}
?>