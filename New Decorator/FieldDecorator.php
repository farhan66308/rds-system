<?php
// FieldDataDecorator.php

require_once 'ProfileCreator.php';
require_once '../libs/functions.php';

abstract class ProfileCreatorDecorator implements ProfileCreator {
    protected $profileCreator;
    protected $errors = []; // Changed from private to protected
    protected $successMessage = ''; // Changed from private to protected

    public function __construct(ProfileCreator $profileCreator) {
        $this->profileCreator = $profileCreator;
    }
    
    public function createProfile(array $data): bool {
        return $this->profileCreator->createProfile($data);
    }
    
    public function getErrors(): array {
        // Correctly merges errors from the wrapped object and the decorator
        return array_merge($this->profileCreator->getErrors(), $this->errors);
    }

    public function getSuccessMessage(): string {
        // Correctly concatenates success messages
        return $this->profileCreator->getSuccessMessage() . " " . $this->successMessage;
    }
}

class FieldDataDecorator extends ProfileCreatorDecorator {
    private $conn;
    
    public function __construct(ProfileCreator $profileCreator, Database $db) {
        parent::__construct($profileCreator);
        $this->conn = $db->getConnection();
    }
    
    public function createProfile(array $data): bool {
        if (!$this->profileCreator->createProfile($data)) {
            $this->errors = $this->profileCreator->getErrors();
            $this->successMessage = $this->profileCreator->getSuccessMessage();
            return false;
        }

        $userID = $data['user_id'];
        $fieldErrorsOccurred = false;

        // Insert dynamic custom fields
        if (!empty($data['field_name'])) {
            $stmt = $this->conn->prepare("INSERT INTO field (FieldID, UserID, FieldName, FieldSubTitle, SetField1, SetField2, SetField3, Description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['field_name'] as $index => $fieldName) {
                $currentFieldName = trim($fieldName);
                if (!empty($currentFieldName) || !empty($data['field_description'][$index])) {
                    // Assign all values to variables before binding
                    $currentFieldSubTitle = trim($data['field_subtitle'][$index] ?? '');
                    $currentSetField1 = trim($data['set_field1'][$index] ?? '');
                    $currentSetField2 = trim($data['set_field2'][$index] ?? '');
                    $currentSetField3 = trim($data['set_field3'][$index] ?? '');
                    $currentFieldDescription = trim($data['field_description'][$index] ?? '');
                    $FieldID = UserIDGen();

                    $stmt->bind_param("ssssssss", 
                        $FieldID,
                        $userID, 
                        $currentFieldName, 
                        $currentFieldSubTitle, 
                        $currentSetField1, 
                        $currentSetField2, 
                        $currentSetField3, 
                        $currentFieldDescription
                    );
                    if (!$stmt->execute()) {
                        $this->errors[] = "Failed to add custom field '{$currentFieldName}': " . $this->conn->error;
                        $fieldErrorsOccurred = true;
                    }
                }
            }
            $stmt->close();
        }

        if ($fieldErrorsOccurred) {
            $this->successMessage = "Faculty profile created, but some custom fields could not be added.";
            return false;
        } else {
            $this->successMessage = "All additional and custom fields added successfully!";
            return true;
        }
    }
}