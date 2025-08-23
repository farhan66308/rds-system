<?php
// ProfileCreator.php

interface ProfileCreator {
    public function createProfile(array $data): bool;
    public function getErrors(): array;
    public function getSuccessMessage(): string;
}
?>