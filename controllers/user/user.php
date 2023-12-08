<?php

require_once __DIR__ . '/../../repositories/userRepository.php';
require_once __DIR__ . '/../../validations/admin/validate-user.php';
@require_once __DIR__ . '/../../validations/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user = [
        'id' => $_SESSION['id'], 
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'country' => $_POST['country'],
        'birthdate' => $_POST['birthdate'],
        'updated_at' => $_POST['NOW()'],
    ];

    /* $validatedUser = validatedUser($user);

    if (isset($validatedUser['invalid'])) {
        echo 'Validation failed for user data. Errors: ' . implode(', ', $validatedUser['invalid']);
        exit();
    } */

    $updateSuccess = updateUser($user);

    if ($updateSuccess) {
        header('Location: ../../pages/secure/profile.php');
        exit();
    } else {
        echo 'Error updating user profile.';
    }
} else {
    echo 'Invalid request method.';
}

?>