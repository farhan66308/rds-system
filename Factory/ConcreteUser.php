<?php
require_once "UserAction.php";

// concrete prduct each of them accoridng tot slides
class StudentAction implements UserAction
{
    public function execute()
    {
        header("Location: ./Dash.php");
        exit();
    }
}

class FacultyAction implements UserAction
{
    public function execute()
    {
        header("Location: /RDS/Faculty/dashboard.php");
        exit();
    }
}

class AdminAction implements UserAction
{
    public function execute()
    {
        header("Location: /RDS/admin/admin.php");
        exit();
    }
}

class AccountantAction implements UserAction
{
    public function execute()
    {
        header("Location: ../account/account.php");
        exit();
    }
}
