<?php
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../../repositories/userRepository.php';
@require_once __DIR__ . '/../../validations/session.php';
$user = user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
       // Obtenha os dados da imagem
        $avatarData = file_get_contents($_FILES['avatar']['tmp_name']);
        $avatarEncoded = base64_encode($avatarData);

        // Atualize o avatar do usuário no banco de dados
        $success = updateUserAvatar($user['id'], $avatarEncoded);

        if ($success) {
            header('Location: ../../pages/secure/profile.php');
            exit();
        } else {
            echo 'Erro ao atualizar o avatar.';
        }
    }
}
