<?php
// create_faculty_profile.php
session_start();

require_once 'ProfileCreator.php';
require_once 'BaseProfileCreator.php';
require_once 'FieldDecorator.php';
require_once '../conn.php';

$db = new Database();

$errors = [];
$success = '';
$formData = [];

// Initialize form fields for sticky form
$formData['faculty_code'] = $_POST['faculty_code'] ?? '';
$formData['user_id'] = $_POST['user_id'] ?? '';
$formData['name'] = $_POST['name'] ?? '';
$formData['avatar'] = $_POST['avatar'] ?? '';
$formData['bio'] = $_POST['bio'] ?? '';
$formData['department'] = $_POST['department'] ?? '';
$formData['role'] = $_POST['role'] ?? '';
$formData['office'] = $_POST['office'] ?? '';
$formData['website_url'] = $_POST['website_url'] ?? '';
$formData['education_institution'] = $_POST['education_institution'] ?? '';
$formData['skill_description'] = $_POST['skill_description'] ?? '';
$formData['field_name'] = $_POST['field_name'] ?? [];
$formData['field_subtitle'] = $_POST['field_subtitle'] ?? [];
$formData['field_description'] = $_POST['field_description'] ?? [];
$formData['set_field1'] = $_POST['set_field1'] ?? [];
$formData['set_field2'] = $_POST['set_field2'] ?? [];
$formData['set_field3'] = $_POST['set_field3'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Instantiate the base creator
    $baseCreator = new BaseProfileCreator($db);

    // Decorate the base creator with the field data functionality
    $profileCreator = new FieldDataDecorator($baseCreator, $db);

    // Call the single method to create the profile and fields
    if ($profileCreator->createProfile($formData)) {
        $success = $profileCreator->getSuccessMessage();
        // Clear form fields on success
        $formData = [];
    } else {
        $errors = $profileCreator->getErrors();
        $success = $profileCreator->getSuccessMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Faculty Profile - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../dash.css">
</head>

<body>
    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="../admin/admin.php"><i class="fa fa-home"></i> Home</a></li>
            <li><a href="create_course.php"><i class="fa fa-plus-circle"></i> Create Course</a></li>
            <li><a href="create_course_structure.php"><i class="fa fa-book"></i> Create Course Structure</a></li>
            <li><a href="view_course_layout.php"><i class="fa fa-eye"></i> View Course Layout</a></li>
            <li class="active"><a href="create_faculty_profile.php"><i class="fa fa-user-tie"></i> Create Faculty Profile</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-3xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Create New Faculty Profile</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error mb-4">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success mb-4">
                    <p><?= htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <div class="p-8 bg-white rounded-lg shadow-md">
                <form id="createProfileForm" method="POST">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">Base Faculty Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="faculty_code" class="block text-sm font-medium text-gray-700 mb-1">Faculty Code</label>
                            <input type="text" id="faculty_code" name="faculty_code" placeholder="e.g., FC101" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['faculty_code']); ?>" required />
                        </div>
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User ID</label>
                            <input type="text" id="user_id" name="user_id" placeholder="e.g., USER456" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['user_id']); ?>" required />
                        </div>
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="e.g., Dr. John Doe" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['name']); ?>" required />
                        </div>
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" id="department" name="department" placeholder="e.g., Computer Science" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['department']); ?>" required />
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <input type="text" id="role" name="role" placeholder="e.g., Professor" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['role']); ?>" required />
                        </div>
                        <div>
                            <label for="office" class="block text-sm font-medium text-gray-700 mb-1">Office</label>
                            <input type="text" id="office" name="office" placeholder="e.g., Building A, Room 101" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['office']); ?>" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-1">Avatar URL</label>
                            <input type="url" id="avatar" name="avatar" placeholder="e.g., https://example.com/avatar.jpg" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['avatar']); ?>" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Biography</label>
                            <textarea id="bio" name="bio" placeholder="A brief biography..." class="textarea textarea-bordered h-24 w-full"><?= htmlspecialchars($formData['bio']); ?></textarea>
                        </div>
                    </div>

                    <h2 class="text-xl font-semibold mb-4 border-b pb-2 mt-8">Additional Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="website_url" class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
                            <input type="url" id="website_url" name="website_url" placeholder="e.g., https://johndoe.com" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['website_url']); ?>" />
                        </div>
                        <div>
                            <label for="education_institution" class="block text-sm font-medium text-gray-700 mb-1">Education Institution</label>
                            <input type="text" id="education_institution" name="education_institution" placeholder="e.g., University of Technology" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['education_institution']); ?>" />
                        </div>

                        <div class="md:col-span-2">
                            <label for="skill_description" class="block text-sm font-medium text-gray-700 mb-1">Skill Description</label>
                            <textarea id="skill_description" name="skill_description" placeholder="e.g., PHP, Python, JavaScript" class="textarea textarea-bordered h-16 w-full"><?= htmlspecialchars($formData['skill_description']); ?></textarea>
                        </div>
                    </div>

                    <h2 class="text-xl font-semibold mb-4 border-b pb-2 mt-8">Custom Fields (Optional)</h2>
                    <div id="custom-fields-container" class="space-y-4 mb-6">
                        <?php 
                        if (!empty($formData['field_name'])) {
                            foreach ($formData['field_name'] as $index => $fieldName):
                        ?>
                            <div class="custom-field-group flex flex-col gap-2 p-4 border rounded-lg bg-gray-50 relative">
                                <button type="button" class="btn btn-sm btn-circle btn-error absolute top-2 right-2" onclick="removeField(this)">
                                    <i class="fa fa-times"></i>
                                </button>
                                <div>
                                    <label for="field_name_<?= $index; ?>" class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
                                    <input type="text" id="field_name_<?= $index; ?>" name="field_name[]" placeholder="e.g., Research Interests" class="input input-bordered w-full" required value="<?= htmlspecialchars($fieldName); ?>" />
                                </div>
                                <div>
                                    <label for="field_subtitle_<?= $index; ?>" class="block text-sm font-medium text-gray-700 mb-1">Field Subtitle</label>
                                    <input type="text" id="field_subtitle_<?= $index; ?>" name="field_subtitle[]" placeholder="Optional subtitle" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['field_subtitle'][$index] ?? ''); ?>" />
                                </div>
                                <div>
                                    <label for="set_field1_<?= $index; ?>" class="block text-sm font-medium text-gray-700 mb-1">Set Field 1</label>
                                    <input type="text" id="set_field1_<?= $index; ?>" name="set_field1[]" placeholder="Optional data field" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['set_field1'][$index] ?? ''); ?>" />
                                </div>
                                <div>
                                    <label for="set_field2_<?= $index; ?>" class="block text-sm font-medium text-gray-700 mb-1">Set Field 2</label>
                                    <input type="text" id="set_field2_<?= $index; ?>" name="set_field2[]" placeholder="Optional data field" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['set_field2'][$index] ?? ''); ?>" />
                                </div>
                                <div>
                                    <label for="set_field3_<?= $index; ?>" class="block text-sm font-medium text-gray-700 mb-1">Set Field 3</label>
                                    <input type="text" id="set_field3_<?= $index; ?>" name="set_field3[]" placeholder="Optional data field" class="input input-bordered w-full" value="<?= htmlspecialchars($formData['set_field3'][$index] ?? ''); ?>" />
                                </div>
                                <div>
                                    <label for="field_description_<?= $index; ?>" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea id="field_description_<?= $index; ?>" name="field_description[]" placeholder="Detailed description or URL..." class="textarea textarea-bordered h-16 w-full"><?= htmlspecialchars($formData['field_description'][$index] ?? ''); ?></textarea>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        } else {
                        ?>
                            <div class="custom-field-group flex flex-col gap-2 p-4 border rounded-lg bg-gray-50 bg-gray-50 relative">
                                <button type="button" class="btn btn-sm btn-circle btn-error absolute top-2 right-2 hidden" onclick="removeField(this)">
                                    <i class="fa fa-times"></i>
                                </button>
                                <div>
                                    <label for="field_name_0" class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
                                    <input type="text" id="field_name_0" name="field_name[]" placeholder="e.g., Research Interests" class="input input-bordered w-full" />
                                </div>
                                <div>
                                    <label for="field_subtitle_0" class="block text-sm font-medium text-gray-700 mb-1">Field Subtitle</label>
                                    <input type="text" id="field_subtitle_0" name="field_subtitle[]" placeholder="Optional subtitle" class="input input-bordered w-full" />
                                </div>
                                <div>
                                    <label for="set_field1_0" class="block text-sm font-medium text-gray-700 mb-1">Set Field 1</label>
                                    <input type="text" id="set_field1_0" name="set_field1[]" placeholder="Optional data field" class="input input-bordered w-full" />
                                </div>
                                <div>
                                    <label for="set_field2_0" class="block text-sm font-medium text-gray-700 mb-1">Set Field 2</label>
                                    <input type="text" id="set_field2_0" name="set_field2[]" placeholder="Optional data field" class="input input-bordered w-full" />
                                </div>
                                <div>
                                    <label for="set_field3_0" class="block text-sm font-medium text-gray-700 mb-1">Set Field 3</label>
                                    <input type="text" id="set_field3_0" name="set_field3[]" placeholder="Optional data field" class="input input-bordered w-full" />
                                </div>
                                <div>
                                    <label for="field_description_0" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea id="field_description_0" name="field_description[]" placeholder="Detailed description or URL..." class="textarea textarea-bordered h-16 w-full"></textarea>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <button type="button" class="btn btn-outline btn-primary w-full" onclick="addField()">
                        <i class="fa fa-plus"></i> Add Another Custom Field
                    </button>

                    <div class="mt-8">
                        <button type="button" onclick="showConfirmationModal()" class="btn btn-primary w-full">Create Faculty Profile</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <dialog id="confirmation_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Confirm Profile Creation</h3>
            <p class="py-4">Are you sure you want to create this faculty profile?</p>
            <div class="modal-action justify-end">
                <form method="dialog" class="flex gap-2">
                    <button class="btn">Cancel</button>
                    <button class="btn btn-primary" onclick="document.getElementById('createProfileForm').submit()">Yes, Create Profile</button>
                </form>
            </div>
        </div>
    </dialog>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
        }

        function showConfirmationModal() {
            document.getElementById('confirmation_modal').showModal();
        }

        let fieldCounter = <?= count($formData['field_name']) > 0 ? count($formData['field_name']) : 1; ?>;

        function addField() {
            const container = document.getElementById('custom-fields-container');
            const newFieldGroup = document.createElement('div');
            newFieldGroup.className = 'custom-field-group flex flex-col gap-2 p-4 border rounded-lg bg-gray-50 relative';
            newFieldGroup.innerHTML = `
                <button type="button" class="btn btn-sm btn-circle btn-error absolute top-2 right-2" onclick="removeField(this)">
                    <i class="fa fa-times"></i>
                </button>
                <div>
                    <label for="field_name_${fieldCounter}" class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
                    <input type="text" id="field_name_${fieldCounter}" name="field_name[]" placeholder="e.g., Research Interests" class="input input-bordered w-full" />
                </div>
                <div>
                    <label for="field_subtitle_${fieldCounter}" class="block text-sm font-medium text-gray-700 mb-1">Field Subtitle</label>
                    <input type="text" id="field_subtitle_${fieldCounter}" name="field_subtitle[]" placeholder="Optional subtitle" class="input input-bordered w-full" />
                </div>
                <div>
                    <label for="set_field1_${fieldCounter}" class="block text-sm font-medium text-gray-700 mb-1">Set Field 1</label>
                    <input type="text" id="set_field1_${fieldCounter}" name="set_field1[]" placeholder="Optional data field" class="input input-bordered w-full" />
                </div>
                <div>
                    <label for="set_field2_${fieldCounter}" class="block text-sm font-medium text-gray-700 mb-1">Set Field 2</label>
                    <input type="text" id="set_field2_${fieldCounter}" name="set_field2[]" placeholder="Optional data field" class="input input-bordered w-full" />
                </div>
                <div>
                    <label for="set_field3_${fieldCounter}" class="block text-sm font-medium text-gray-700 mb-1">Set Field 3</label>
                    <input type="text" id="set_field3_${fieldCounter}" name="set_field3[]" placeholder="Optional data field" class="input input-bordered w-full" />
                </div>
                <div>
                    <label for="field_description_${fieldCounter}" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="field_description_${fieldCounter}" name="field_description[]" placeholder="Detailed description or URL..." class="textarea textarea-bordered h-16 w-full"></textarea>
                </div>
            `;
            container.appendChild(newFieldGroup);
            fieldCounter++;

            updateRemoveButtonsVisibility();
        }

        function removeField(button) {
            button.closest('.custom-field-group').remove();
            updateRemoveButtonsVisibility();
        }

        function updateRemoveButtonsVisibility() {
            const fieldGroups = document.querySelectorAll('.custom-field-group');
            if (fieldGroups.length === 1) {
                fieldGroups[0].querySelector('.btn-circle').classList.add('hidden');
            } else {
                fieldGroups.forEach(group => {
                    group.querySelector('.btn-circle').classList.remove('hidden');
                });
            }
        }
        
        document.addEventListener('DOMContentLoaded', updateRemoveButtonsVisibility);
    </script>
</body>
</html>