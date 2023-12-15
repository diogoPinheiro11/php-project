<?php
require_once __DIR__ . '/../../repositories/expense.php';
require_once __DIR__ . '/../../repositories/user.php';
@require_once __DIR__ . '/../../validations/session.php';
@require_once __DIR__ . '/../../validations/expenses/validate-expense.php';

if (isset($_POST['user'])) {
    $action = $_POST['user'];

    if ($action == 'add') {
        eadd($_POST);
    } elseif ($action == 'edit') {
        $expenseId = $_POST['expense_id'];
        eedit($expenseId, $_POST);
    } elseif ($action == 'delete') {
        $expenseId = $_POST['expense_id'];
        edelete($expenseId);
    } elseif ($action == 'share') {
        $expenseId = $_POST['expense_id'];
        $email = $_POST['email'];
        eshare($expenseId, $email);
    } elseif ($action == 'remove-shared') {
        $expenseId = $_POST['expense_id'];
        seremove($expenseId);
    }
}

function eadd($postData)
{
    if (!isset($_SESSION['id'])) {
        $_SESSION['errors'][] = 'User ID not set in the session.';
        $params = '?' . http_build_query($postData);
        header('location: /php-project/pages/secure/expense.php' . $params);
    }

    $validationResult = isExpenseValid($postData);

    if (isset($validationResult['invalid'])) {
        $_SESSION['errors'] = $validationResult['invalid'];
        $params = '?' . http_build_query($postData);
        header('location: /php-project/pages/secure/expense.php' . $params);
    }

    if (is_array($validationResult)) {
        $user = [
            'id' => $_SESSION['id'],
        ];

        if (isset($_FILES['receipt_img']) && $_FILES['receipt_img']['error'] === UPLOAD_ERR_OK) {
            $receiptImageData = file_get_contents($_FILES['receipt_img']['tmp_name']);
            $receiptImageEncoded = base64_encode($receiptImageData);
            $validationResult['receipt_img'] = $receiptImageEncoded;
        } else {
            $validationResult['receipt_img'] = null;
        }

        $expenseData = [
            'category_id' => $validationResult['category'],
            'description' => $validationResult['description'],
            'amount' => $validationResult['amount'],
            'date' => $validationResult['date'],
            'receipt_img' => $validationResult['receipt_img'],
            'note' => $validationResult['note'],
            'user_id' => $user['id'],
        ];

        $expenseData['payed'] = isset($validationResult['payed']) ? ($validationResult['payed'] ? 1 : 0) : 0;

        $expenseData['payment_id'] = $expenseData['payed'] ? $validationResult['method'] : getMethodByDescription('None')['id'];

        $result = createExpense($expenseData);

        if ($result) {
            $_SESSION['success'] = 'Expense created successfully.';
        } else {
            error_log("Error creating expense: " . implode(" - ", $result->errorInfo()));
        }

        $params = '?' . http_build_query($postData);
        header('location: /php-project/pages/secure/expense.php' . $params);
    }
}

function edelete($expenseId)
{
    if (!isset($_SESSION['id'])) {
        $_SESSION['errors'][] = 'User ID not set in the session.';
        header('location: /php-project/pages/secure/expense.php');
        exit();
    }

    $deleteSuccess = softDeleteExpense($expenseId);

    if ($deleteSuccess) {
        $_SESSION['success'] = 'Expense deleted successfully.';
    } else {
        $_SESSION['errors'][] = 'Error deleting expense.';
        error_log("Error deleting expense with ID $expenseId: " . implode(" - ", $GLOBALS['pdo']->errorInfo()));
    }

    header('location: /php-project/pages/secure/expense.php');
    exit();
}

function eedit($expenseId, $postData)
{
    if (!isset($_SESSION['id'])) {
        $_SESSION['errors'][] = 'User ID not set in the session.';
        $params = '?' . http_build_query($postData);
        header('location: /php-project/pages/secure/expense.php' . $params);
    }

    $validationResult = isExpenseValid($postData);

    if (isset($validationResult['invalid'])) {
        $_SESSION['errors'] = $validationResult['invalid'];
        $params = '?' . http_build_query($postData);
        header('location: /php-project/pages/secure/expense.php' . $params);
    }

    if (is_array($validationResult)) {
        $user = [
            'id' => $_SESSION['id'],
        ];

        if (isset($_FILES['receipt_img']) && $_FILES['receipt_img']['error'] === UPLOAD_ERR_OK) {
            $receiptImageData = file_get_contents($_FILES['receipt_img']['tmp_name']);
            $receiptImageEncoded = base64_encode($receiptImageData);
            $validationResult['receipt_img'] = $receiptImageEncoded;
        } else {
            $validationResult['receipt_img'] = null;
        }

        $expenseData = [
            'category_id' => $validationResult['category'],
            'description' => $validationResult['description'],
            'amount' => $validationResult['amount'],
            'date' => $validationResult['date'],
            'receipt_img' => $validationResult['receipt_img'],
            'note' => $validationResult['note'],
            'user_id' => $user['id'],
        ];

        $expenseData['payed'] = isset($validationResult['payed']) ? ($validationResult['payed'] ? 1 : 0) : 0;

        if ($expenseData['payed']) {
            $expenseData['payment_id'] = $validationResult['method'];
        } else {
            $noneMethod = getMethodByDescription('None');
            $expenseData['payment_id'] = $noneMethod['id'];
        }

        /* echo '<pre>';
        var_dump($expenseData);
        echo '</pre>'; */

        if (empty($_SESSION['errors']) && updateExpense($expenseId, $expenseData)) {
            $_SESSION['success'] = 'Expense updated successfully.';
        } else {
            $_SESSION['errors'][] = 'Error updating expense. Please try again.';
        }

        $params = '?' . http_build_query($postData);
        header('location: /php-project/pages/secure/expense.php' . $params);
    }
}

function eshare($expenseId, $email) 
{
    try {
        $receiverUserId = getIdByEmail($email);

        if (!$receiverUserId) {
            $_SESSION['errors'][] = 'User with the email "' . $email . '" not found.';
            header('location: /php-project/pages/secure/expense.php');
            exit();
        }

        $sharerUserId = $_SESSION['id'];

        $shareSuccess = shareExpense($expenseId, $sharerUserId, $receiverUserId);

        if ($shareSuccess) {
            $_SESSION['success'] = 'Expense shared successfully.';
        } else {
            $_SESSION['errors'][] = 'Error sharing expense.';
            error_log("Error sharing expense with ID $expenseId: " . implode(" - ", $GLOBALS['pdo']->errorInfo()));
        }

        header('location: /php-project/pages/secure/expense.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['errors'][] = 'Error: ' . $e->getMessage();
        header('location: /php-project/pages/secure/expense.php');
        exit();
    }
}

function shareExpense($expenseId, $sharerUserId, $receiverUserId) 
{
    try {
        $isAlreadyShared = isExpenseShared($expenseId, $sharerUserId, $receiverUserId);

        if ($isAlreadyShared) {
            $_SESSION['errors'][] = 'Expense is already shared with the specified user.';
            return false;
        }

        $sharedExpense = [
            'receiver_user_id' => $receiverUserId,
            'sharer_user_id' => $sharerUserId,
            'expense_id' => $expenseId,
        ];

        return createSharedExpense($sharedExpense);
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
        return false;
    }
}

function seremove($expenseId)
{
    if (!isset($_SESSION['id'])) {
        $_SESSION['errors'][] = 'User ID not set in the session.';
        header('location: /php-project/pages/secure/expense.php');
        exit();
    }

    $UserId = $_SESSION['id'];

    $success = removeSharedExpense($expenseId, $UserId);

    if ($success) {
        $_SESSION['success'] = 'Shared expense removed successfully.';
    } else {
        $_SESSION['errors'][] = 'Error removing shared expense.';
        error_log("Error removing shared expense for user ID $UserId and expense ID $expenseId.");
    }

    header('location: /php-project/pages/secure/shared_expense.php');
    exit();
}

?>