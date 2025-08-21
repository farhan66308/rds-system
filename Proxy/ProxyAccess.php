<?php
require_once "ProxyInterface.php";
require_once "AccessDenied.php";
require_once("ProxyReal.php");
require_once '../conn.php';

class ProxyAccess implements UserProxy
{
    private $UserID;
    private $UserFlag;
    private $ProxyReal;

    public function __construct($UserID)
    {
        $db = new Database();
        $conn = $db->getConnection();

        // Check if 2FA secret already exists
        $stmt = $conn->prepare("SELECT UserID, UserFlag FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $UserID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $this->UserFlag = $row['UserFlag'] ?? null;
        $stmt->close();
        $this->ProxyReal = new ProxyReal();
        $this->UserID = $UserID;
    }
    public function ManageUsers($UserID)
    {
        if ($this->UserFlag == 3) {
            return $this->ProxyReal->ManageUsers($UserID);
        }
    }

    public function ManageTransaction($UserID)
    {
        if ($this->UserFlag == 4) {
            return $this->ProxyReal->ManageTransaction($UserID);
        }
    }

    public function ManageCourse($UserID)
    {
        if ($this->UserFlag == 2) {
            return $this->ProxyReal->ManageCourse($UserID);
        }
    }

    public function ViewCourse($UserID)
    {
        $msg = "Error! You probably were not not allowed to access this feature or page";
        if ($this->UserFlag == 1) {
            return $this->ProxyReal->ViewCourse($UserID);
        }
        else {
            displayMessage($msg);
        }
    }
}
