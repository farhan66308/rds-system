<?php
    interface UserProxy {
        public function ManageUsers($UserID);
        public function ManageTransaction($UserID);
        public function ManageCourse($UserID);
        public function ViewCourse($UserID);
    }
?>