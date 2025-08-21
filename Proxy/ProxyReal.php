<?php
    require_once "ProxyInterface.php";
    
    Class ProxyReal implements UserProxy {

        // amdin panel
        public function ManageUsers($UserID)
        {
            header("Location: ../admin/admin.php");
            exit();
        }

        //Accountant to manage transaction 
        public function ManageTransaction($UserID)
        {
            header("Location: ../account/account.php");
            exit();
        }
    
        //Faculty members to maanage course stuff
        public function ManageCourse($UserID)
        {
            header("Location: ../courses/managecourses.php");
            exit();
        }

        //Students UI perspective of courses tab
        public function ViewCourse($UserID)
        {
            header("Location: ../courses/courses.php");
            exit();
        }
    }
?>