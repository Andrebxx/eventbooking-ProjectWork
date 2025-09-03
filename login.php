<?php
session_start(); // Avvia la sessione prima di tutto

include_once("password.php");

// Mostra messaggio se sessione scaduta
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Sessione scaduta per inattività. Effettua nuovamente il login.";
}

// Funzione per verificare i tentativi di login
function checkLoginAttempts($myDB, $ip, $email = null) {
    try {
        // Rimuovi i tentativi più vecchi di 30 secondi
        if ($email) {
            $cleanupStmt = $myDB->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND email = ? AND attempt_time < DATE_SUB(NOW(), INTERVAL 30 SECOND)");
            $cleanupStmt->bind_param("ss", $ip, $email);
        } else {
            $cleanupStmt = $myDB->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND attempt_time < DATE_SUB(NOW(), INTERVAL 30 SECOND)");
            $cleanupStmt->bind_param("s", $ip);
        }
        
        if ($cleanupStmt) {
            $cleanupStmt->execute();
            $cleanupStmt->close();
        }
        
        // Conta i tentativi negli ultimi 30 secondi per questa combinazione IP + Email
        if ($email) {
            $countStmt = $myDB->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND email = ? AND attempt_time >= DATE_SUB(NOW(), INTERVAL 30 SECOND)");
            $countStmt->bind_param("ss", $ip, $email);
        } else {
            $countStmt = $myDB->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time >= DATE_SUB(NOW(), INTERVAL 30 SECOND)");
            $countStmt->bind_param("s", $ip);
        }
        
        if ($countStmt) {
            $countStmt->execute();
            $result = $countStmt->get_result();
            $row = $result->fetch_assoc();
            $countStmt->close();
            return (int)$row['attempts'];
        }
    } catch (Exception $e) {
        error_log("Errore in checkLoginAttempts: " . $e->getMessage());
    }
    return 0;
}

// Funzione per ottenere il tempo rimanente prima del prossimo tentativo
function getTimeUntilNextAttempt($myDB, $ip, $email = null) {
    try {
        if ($email) {
            $stmt = $myDB->prepare("SELECT attempt_time FROM login_attempts WHERE ip_address = ? AND email = ? AND attempt_time >= DATE_SUB(NOW(), INTERVAL 30 SECOND) ORDER BY attempt_time ASC LIMIT 1");
            $stmt->bind_param("ss", $ip, $email);
        } else {
            $stmt = $myDB->prepare("SELECT attempt_time FROM login_attempts WHERE ip_address = ? AND attempt_time >= DATE_SUB(NOW(), INTERVAL 30 SECOND) ORDER BY attempt_time ASC LIMIT 1");
            $stmt->bind_param("s", $ip);
        }
        
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $firstAttemptTime = new DateTime($row['attempt_time']);
                $unlockTime = clone $firstAttemptTime;
                $unlockTime->add(new DateInterval('PT30S'));
                
                $now = new DateTime();
                if ($now < $unlockTime) {
                    $diff = $now->diff($unlockTime);
                    return [
                        'minutes' => $diff->i,
                        'seconds' => $diff->s,
                        'total_seconds' => ($diff->i * 60) + $diff->s,
                        'unlock_time' => $unlockTime->format('Y-m-d H:i:s')
                    ];
                }
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Errore in getTimeUntilNextAttempt: " . $e->getMessage());
    }
    return null;
}

// Funzione per registrare un tentativo fallito
function recordFailedAttempt($myDB, $ip, $email) {
    try {
        $insertStmt = $myDB->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
        if ($insertStmt) {
            $insertStmt->bind_param("ss", $ip, $email);
            $insertStmt->execute();
            $insertStmt->close();
            return true;
        }
    } catch (Exception $e) {
        error_log("Errore in recordFailedAttempt: " . $e->getMessage());
    }
    return false;
}

// Funzione per pulire periodicamente i vecchi tentativi
function cleanupOldAttempts($myDB) {
    try {
        // Elimina tentativi più vecchi di 1 giorno
        $cleanupStmt = $myDB->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        if ($cleanupStmt) {
            $cleanupStmt->execute();
            $deletedRows = $cleanupStmt->affected_rows;
            $cleanupStmt->close();
            
            // Log del risultato
            if ($deletedRows > 0) {
                error_log("Cleanup login_attempts: eliminati $deletedRows record vecchi");
            }
            
            return $deletedRows;
        }
    } catch (Exception $e) {
        error_log("Errore in cleanupOldAttempts: " . $e->getMessage());
    }
    return 0;
}

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
                error_log("Cleared login_attempts for email $email: eliminati $deletedRows record");
            }
            
            return $deletedRows;
        }
    } catch (Exception $e) {
        error_log("Errore in clearAttemptsForEmail: " . $e->getMessage());
    }
    return 0;
}

// Inizializzazione e configurazione database
try {
    // Crea la tabella dei tentativi di login se non esiste
    $createTableQuery = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        email VARCHAR(255) NOT NULL,
        attempt_time DATETIME NOT NULL,
        INDEX idx_ip_email_time (ip_address, email, attempt_time),
        INDEX idx_attempt_time (attempt_time)
    )";
    $myDB->query($createTableQuery);

    // Aggiungi la colonna email se la tabella esiste già senza di essa (migrazione)
    $checkColumnQuery = "SHOW COLUMNS FROM login_attempts LIKE 'email'";
    $result = $myDB->query($checkColumnQuery);
    if ($result && $result->num_rows == 0) {
        $addColumnQuery = "ALTER TABLE login_attempts ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT '' AFTER ip_address";
        $myDB->query($addColumnQuery);
        
        // Aggiorna gli indici
        $myDB->query("DROP INDEX IF EXISTS idx_ip_time ON login_attempts");
        $myDB->query("CREATE INDEX idx_ip_email_time ON login_attempts (ip_address, email, attempt_time)");
        $myDB->query("CREATE INDEX idx_attempt_time ON login_attempts (attempt_time)");
    }

    // Pulizia periodica più aggressiva (5% di probabilità)
    if (rand(1, 20) == 1) {
        cleanupOldAttempts($myDB);
        
        // Log per monitoraggio
        error_log("Login attempts cleanup eseguito - " . date('Y-m-d H:i:s'));
    }
} catch (Exception $e) {
    error_log("Errore nell'inizializzazione database: " . $e->getMessage());
}

// Gestione del login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip = $_SERVER['REMOTE_ADDR'];
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"] ?? '';
    
    // Validazione input
    if (empty($email) || empty($password)) {
        $error = "Email e password sono obbligatori.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido.";
    } else {
        // Verifica i tentativi di login per questa combinazione IP + Email
        $attempts = checkLoginAttempts($myDB, $ip, $email);
        
        if ($attempts >= 3) {
            $timeRemaining = getTimeUntilNextAttempt($myDB, $ip, $email);
            if ($timeRemaining) {
                $minutes = $timeRemaining['minutes'];
                $seconds = sprintf('%02d', $timeRemaining['seconds']);
                $error = "Troppi tentativi di login falliti per questa email. Riprova tra <span id='countdown'>{$minutes}:{$seconds}</span>";
            } else {
                $error = "Troppi tentativi di login falliti per questa email. Riprova tra 30 secondi.";
            }
        } else {
            try {
                $stmt = $myDB->prepare("SELECT id, nome, password FROM utenti WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($id, $nome, $hash);
                    $stmt->fetch();

                    if (password_verify($password, $hash)) {
                        // Login riuscito: pulisci i tentativi per questa email
                        clearAttemptsForEmail($myDB, $email);
                        
                        // Salva info sessione
                        session_regenerate_id(true); // Sicurezza: rigenera session ID
                        $_SESSION["id"] = $id;
                        $_SESSION["nome"] = $nome;
                        $_SESSION["login_time"] = time();

                        // Reindirizza a pagina salvata o alla home
                        if (!empty($_SESSION['redirect_after_login'])) {
                            $redirect = $_SESSION['redirect_after_login'];
                            unset($_SESSION['redirect_after_login']);
                            header("Location: $redirect");
                        } else {
                            header("Location: index.php");
                        }
                        exit;
                    } else {
                        // Password errata - registra tentativo fallito
                        recordFailedAttempt($myDB, $ip, $email);
                        $remainingAttempts = 3 - ($attempts + 1);
                        if ($remainingAttempts > 0) {
                            $error = "Password errata. Tentativi rimanenti per questa email: $remainingAttempts";
                        } else {
                            $timeRemaining = getTimeUntilNextAttempt($myDB, $ip, $email);
                            if ($timeRemaining) {
                                $minutes = $timeRemaining['minutes'];
                                $seconds = sprintf('%02d', $timeRemaining['seconds']);
                                $error = "Password errata. Hai esaurito i tentativi per questa email. Riprova tra <span id='countdown'>{$minutes}:{$seconds}</span>";
                            } else {
                                $error = "Password errata. Hai esaurito i tentativi per questa email. Riprova tra 30 secondi.";
                            }
                        }
                    }
                } else {
                    // Email non trovata - registra tentativo fallito
                    recordFailedAttempt($myDB, $ip, $email);
                    $remainingAttempts = 3 - ($attempts + 1);
                    if ($remainingAttempts > 0) {
                        $error = "Email non trovata. Tentativi rimanenti per questa email: $remainingAttempts";
                    } else {
                        $timeRemaining = getTimeUntilNextAttempt($myDB, $ip, $email);
                        if ($timeRemaining) {
                            $minutes = $timeRemaining['minutes'];
                            $seconds = sprintf('%02d', $timeRemaining['seconds']);
                            $error = "Email non trovata. Hai esaurito i tentativi per questa email. Riprova tra <span id='countdown'>{$minutes}:{$seconds}</span>";
                        } else {
                            $error = "Email non trovata. Hai esaurito i tentativi per questa email. Riprova tra 30 secondi.";
                        }
                    }
                }
                $stmt->close();
            } catch (Exception $e) {
                error_log("Errore durante il login: " . $e->getMessage());
                $error = "Errore interno del server. Riprova più tardi.";
            }
        }
    }
    
    // Redirect dopo POST per evitare riinvio form al refresh
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Salva l'errore in sessione se presente
        if (!empty($error)) {
            $_SESSION['login_error'] = $error;
            $_SESSION['login_email'] = $email ?? '';
            $_SESSION['login_attempts'] = $currentAttempts;
            $_SESSION['login_blocked_email'] = $blockedEmail;
            $_SESSION['login_time_remaining'] = $timeRemaining;
        }
        
        // Redirect alla stessa pagina con GET
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Recupera errore dalla sessione se presente
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    $email = $_SESSION['login_email'] ?? '';
    $currentAttempts = $_SESSION['login_attempts'] ?? 0;
    $blockedEmail = $_SESSION['login_blocked_email'] ?? null;
    $timeRemaining = $_SESSION['login_time_remaining'] ?? null;
    
    // Pulisci la sessione
    unset($_SESSION['login_error']);
    unset($_SESSION['login_email']);
    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_blocked_email']);
    unset($_SESSION['login_time_remaining']);
}

// Se non c'è errore dalla sessione, inizializza le variabili
if (!isset($error)) {
    $currentIp = $_SERVER['REMOTE_ADDR'];
    $currentAttempts = 0;
    $timeRemaining = null;
    $blockedEmail = null;
    $error = '';
    $email = '';
}
// NON controllare blocchi globali per IP - ogni email è indipendente
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBooking - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- CSS Files -->
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="stili/stile_login.css">
    <link rel="stylesheet" href="stili/stile_navfoo.css">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
    <link rel="stylesheet" href="stili/stile_autofill_fix.css">
    
    <link rel="icon" type="image/x-icon" href="Immagini/logo.ico">
    
    <style>
        /* Styling per autofill del browser - FIX VISIBILITA' TESTO */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px rgba(255, 255, 255, 0.08) inset !important;
            -webkit-text-fill-color: #ffffff !important;
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            border-radius: 16px !important;
            font-size: 16px !important;
            font-weight: normal !important;
        }
        
        /* Fix per Firefox e altri browser */
        input:-moz-autofill,
        input:-moz-autofill-preview {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }
        
        /* Forza il colore del testo per tutti gli input */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus,
        .form-control:-webkit-autofill:active {
            -webkit-text-fill-color: #ffffff !important;
            color: #ffffff !important;
            caret-color: #ffffff !important;
        }
        
        /* Email in rosso e grassetto quando readonly */
        input[type="email"]:-webkit-autofill[readonly],
        input[type="email"][readonly] {
            -webkit-text-fill-color: #dc3545 !important;
            color: #dc3545 !important;
            font-weight: bold !important;
            background-color: #f8f9fa !important;
            border-color: #dc3545 !important;
        }
        
        .form-control[readonly] {
            background-color: #f8f9fa !important;
            border-color: #dc3545 !important;
            color: #dc3545 !important;
            font-weight: bold !important;
        }
        
        /* Fix per altri browser */
        input:autofill {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }
        
        /* Assicurati che il testo sia sempre visibile */
        .form-control {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            caret-color: #ffffff !important;
        }
        
        .form-control:focus {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.12) !important;
            caret-color: #ffffff !important;
        }
        
        /* Fix specifico per Chrome autofill - forza il colore del testo */
        input:-webkit-autofill::first-line {
            color: #ffffff !important;
            font-size: 16px !important;
            font-weight: normal !important;
        }
        
        /* Override completo per qualsiasi stato di autofill */
        input[type="email"]:-webkit-autofill,
        input[type="password"]:-webkit-autofill,
        input[type="text"]:-webkit-autofill {
            -webkit-text-fill-color: #ffffff !important;
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 0 0 30px rgba(255, 255, 255, 0.08) inset !important;
            transition: background-color 5000s ease-in-out 0s !important;
        }
        
        /* Stile per il countdown */
        #countdown {
            font-weight: bold;
            color: #ff6b6b !important;
            background: rgba(255, 107, 107, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 1.1em;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        /* Stile per messaggio di successo */
        .alert-success {
            border-left: 4px solid #28a745;
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        /* Animazioni smooth */
        .alert {
            transition: opacity 0.5s ease-in-out;
        }
        
        .form-control {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Elementi decorativi di sfondo -->
    <div class="login-decorations">
        <!-- Forme geometriche fluttuanti -->
        <div class="floating-login-shape login-shape-1"></div>
        <div class="floating-login-shape login-shape-2"></div>
        <div class="floating-login-shape login-shape-3"></div>
        
        <!-- Particelle luminose -->
        <div class="login-particle login-particle-1"></div>
        <div class="login-particle login-particle-2"></div>
        <div class="login-particle login-particle-3"></div>
        <div class="login-particle login-particle-4"></div>
    </div>

    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh; padding-top: 100px; padding-bottom: 50px;">
        <div class="login-container">
            <div class="login-title">
                <h2><i class="fas fa-sign-in-alt"></i>Accedi</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    
                    <?php if ($currentAttempts >= 3 && $blockedEmail && $blockedEmail === ($email ?? '') && !$timeRemaining): ?>
                        <!-- Fallback countdown element se non è stato creato nel messaggio di errore -->
                        <br><span>Riprova tra <span id='countdown'>0:30</span></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Inserisci la tua email"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           <?php if ($currentAttempts >= 3 && $blockedEmail && $blockedEmail === ($email ?? '')): ?>
                               readonly style="color: #dc3545 !important; font-weight: bold !important; background-color: #f8f9fa !important; border-color: #dc3545 !important;"
                           <?php endif; ?>
                           required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Inserisci la tua password"
                           <?php if ($currentAttempts >= 3 && $blockedEmail && $blockedEmail === ($email ?? '')) echo 'readonly'; ?>
                           required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
                
                <button type="submit" class="btn btn-login" <?php if ($currentAttempts >= 3 && $blockedEmail && $blockedEmail === ($email ?? '')) echo 'disabled'; ?>>
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <?php echo ($currentAttempts >= 3 && $blockedEmail && $blockedEmail === ($email ?? '')) ? 'Accesso Bloccato per questa Email' : 'Accedi'; ?>
                </button>
                
                <a href="register.php" class="register-link">
                    <i class="fas fa-user-plus me-2"></i>
                    Non hai un account? Registrati qui
                </a>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; 
    include 'cookie_banner.php';?>
    
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Debug: mostra informazioni sui tentativi
        console.log('Current attempts:', <?php echo $currentAttempts; ?>);
        console.log('Time remaining:', <?php echo $timeRemaining ? json_encode($timeRemaining) : 'null'; ?>);
        
        // Countdown timer per tentativi di login
        <?php if ($timeRemaining && isset($timeRemaining['total_seconds']) && $timeRemaining['total_seconds'] > 0): ?>
        let remainingSeconds = <?php echo $timeRemaining['total_seconds']; ?>;
        let countdownFinished = false; // Flag per evitare loop
        console.log('Starting countdown with:', remainingSeconds, 'seconds');
        
        function updateCountdown() {
            if (remainingSeconds <= 0 && !countdownFinished) {
                countdownFinished = true; // Impedisce esecuzioni multiple
                // Tempo scaduto: pulisci i tentativi nel database
                console.log('Countdown finished, clearing attempts and resetting form');
                
                // Chiama il server per pulire i tentativi
                const blockedEmail = '<?php echo addslashes($blockedEmail ?? ''); ?>';
                if (blockedEmail) {
                    fetch('clear_attempts.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=clear_attempts&email=' + encodeURIComponent(blockedEmail)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Clear attempts response:', data);
                        if (data.success) {
                            console.log('Tentativi eliminati con successo:', data.deleted_rows);
                        } else {
                            console.error('Errore nella pulizia tentativi:', data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Errore nella richiesta di pulizia:', error);
                    });
                }
                
                // Nascondi l'alert di errore
                const alertElement = document.querySelector('.alert-danger');
                if (alertElement) {
                    alertElement.style.transition = 'opacity 0.5s ease-out';
                    alertElement.style.opacity = '0';
                    setTimeout(() => {
                        alertElement.style.display = 'none';
                    }, 500);
                }
                
                // Riabilita i campi del form
                const emailInput = document.getElementById('email');
                const passwordInput = document.getElementById('password');
                const submitButton = document.querySelector('.btn-login');
                
                if (emailInput) {
                    emailInput.readOnly = false;
                    emailInput.disabled = false;
                    emailInput.style.color = '#ffffff';
                    emailInput.style.fontWeight = 'normal';
                    emailInput.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
                    emailInput.style.borderColor = '';
                    emailInput.value = ''; // Pulisci il campo email
                }
                
                if (passwordInput) {
                    passwordInput.readOnly = false;
                    passwordInput.disabled = false;
                    passwordInput.value = ''; // Pulisci il campo password
                }
                
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Accedi';
                }
                
                // Rimuovi gli event listeners di prevenzione
                if (emailInput) {
                    emailInput.removeEventListener('keydown', preventEdit);
                    emailInput.removeEventListener('paste', preventEdit);
                }
                
                if (passwordInput) {
                    passwordInput.removeEventListener('keydown', preventEdit);
                    passwordInput.removeEventListener('paste', preventEdit);
                }
                
                // Mostra messaggio di successo
                const loginContainer = document.querySelector('.login-container');
                if (loginContainer) {
                    const successMsg = document.createElement('div');
                    successMsg.className = 'alert alert-success';
                    successMsg.innerHTML = '<i class="fas fa-check-circle me-2"></i>Blocco rimosso! Puoi riprovare ad accedere.';
                    successMsg.style.opacity = '0';
                    successMsg.style.transition = 'opacity 0.5s ease-in';
                    
                    loginContainer.insertBefore(successMsg, loginContainer.querySelector('.login-form'));
                    
                    setTimeout(() => {
                        successMsg.style.opacity = '1';
                    }, 100);
                    
                    // Rimuovi il messaggio dopo 3 secondi
                    setTimeout(() => {
                        successMsg.style.opacity = '0';
                        setTimeout(() => {
                            if (successMsg.parentNode) {
                                successMsg.parentNode.removeChild(successMsg);
                            }
                        }, 500);
                    }, 3000);
                }
                
                // Ferma l'intervallo per evitare loop
                clearInterval(countdownInterval);
                return;
            }
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            const countdownElement = document.getElementById('countdown');
            
            console.log('Updating countdown:', minutes + ':' + seconds.toString().padStart(2, '0'));
            
            if (countdownElement) {
                countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                console.log('Countdown element updated');
            } else {
                console.log('Countdown element not found!');
            }
            
            remainingSeconds--;
        }
        
        // Aggiorna il countdown ogni secondo
        const countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown(); // Aggiorna immediatamente
        <?php else: ?>
        console.log('No countdown needed');
        <?php endif; ?>
        
        // Gestione speciale per campi bloccati - aggiunge stile e comportamento
        <?php if ($currentAttempts >= 3 && $blockedEmail && $blockedEmail === ($email ?? '')): ?>
        
        // Funzioni per prevenire la modifica dei campi
        function preventEdit(e) {
            e.preventDefault();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            if (emailInput && emailInput.readOnly) {
                // Forza lo stile dell'email in rosso e grassetto
                emailInput.style.color = '#dc3545';
                emailInput.style.fontWeight = 'bold';
                emailInput.style.backgroundColor = '#f8f9fa';
                emailInput.style.borderColor = '#dc3545';
                
                // Previeni qualsiasi tentativo di modifica
                emailInput.addEventListener('keydown', preventEdit);
                emailInput.addEventListener('paste', preventEdit);
            }
            
            if (passwordInput && passwordInput.readOnly) {
                passwordInput.addEventListener('keydown', preventEdit);
                passwordInput.addEventListener('paste', preventEdit);
            }
        });
        <?php endif; ?>
        
        // Effetto di focus migliorato per i campi input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Animazione del bottone durante il submit
        const loginForm = document.querySelector('.login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function() {
                const btn = this.querySelector('.btn-login');
                if (btn && !btn.disabled) {
                    btn.classList.add('loading');
                    btn.disabled = true;
                }
            });
        }

        // Effetto parallax leggero per gli elementi decorativi
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.floating-login-shape');
            const particles = document.querySelectorAll('.login-particle');
            
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.02;
                const x = (mouseX - 0.5) * speed * 100;
                const y = (mouseY - 0.5) * speed * 100;
                shape.style.transform = `translate(${x}px, ${y}px)`;
            });
            
            particles.forEach((particle, index) => {
                const speed = (index + 1) * 0.01;
                const x = (mouseX - 0.5) * speed * 50;
                const y = (mouseY - 0.5) * speed * 50;
                particle.style.transform = `translate(${x}px, ${y}px)`;
            });
        });
    </script>
</body>
</html>