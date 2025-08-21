<?php 
interface AuthStrategy {
    public function authenticate($username, $password, $code = null): bool;
}
?>