<?php
session_start();
include_once("password.php");

// Funzione per pulire i tentativi di una specifica email
function clearAttemptsForEmail($myDB, $email) {
    try {
        $clearStmt = $myDB->prepare("DELETE FROM login_attempts WHERE email = ?");
        if ($clearStmt) {
            $clearStmt->bind_param("s", $email);
            $clearStmt->execute();
            $deletedRows = $clearStmt->affected_rows;
            $clearStmt->close();
            
            // Log del risultato
            if ($deletedRows > 0) {
                error_log("Timer expired - Cleared login_attempts for email $email: eliminati $deletedRows record");
            }
            
            return $deletedRows;
        }
    } catch (Exception $e) {
        error_log("Errore in clearAttemptsForEmail (timer): " . $e->getMessage());
    }
    return 0;
}

// Gestione richiesta AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'clear_attempts') {
    header('Content-Type: application/json');
    
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Email non valida']);
        exit;
    }
    
    try {
        $deletedRows = clearAttemptsForEmail($myDB, $email);
        echo json_encode([
            'success' => true, 
            'deleted_rows' => $deletedRows,
            'message' => "Tentativi eliminati per email: $email"
        ]);
    } catch (Exception $e) {
        error_log("Errore nella pulizia tentativi via AJAX: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Errore interno del server']);
    }
} else {
    // Richiesta non valida
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Richiesta non valida']);
}
?>
