<?php
include_once("password.php");
session_start();

$step = isset($_POST['step']) ? $_POST['step'] : 1;
$error = "";
$message = "";

// Per tenere i dati tra i passaggi
$userData = [
    'nome' => '',
    'cognome' => '',
    'email' => '',
    'password' => ''
];

// Funzione per generare codice di verifica
function generateVerificationCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

// Funzione per inviare email di verifica
function sendVerificationEmail($email, $nome, $codice) {
    $to = $email;
    $subject = "Conferma la tua registrazione - EventBooking";
    
    $message = "
    <!DOCTYPE html>
    <html lang='it'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Conferma Registrazione</title>
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px; text-align: center; }
            .verification-code { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; font-size: 36px; font-weight: bold; letter-spacing: 8px; margin: 20px 0; display: inline-block; }
            .instructions { background: #f8f9ff; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #667eea; }
            .footer { background: #f7fafc; padding: 20px; text-align: center; color: #718096; font-size: 14px; }
            .warning { background: #fff5cd; padding: 15px; border-radius: 8px; border-left: 4px solid #f6e05e; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Benvenuto su EventBooking!</h1>
                <p>Conferma la tua registrazione</p>
            </div>
            
            <div class='content'>
                <p>Ciao <strong>$nome</strong>,</p>
                <p>Grazie per esserti registrato su EventBooking! Per completare la registrazione, inserisci il seguente codice di verifica:</p>
                
                <div class='verification-code'>$codice</div>
                
                <div class='instructions'>
                    <h3 style='margin-top: 0; color: #667eea;'>📋 Istruzioni:</h3>
                    <ol style='text-align: left; margin: 0;'>
                        <li>Torna alla pagina di registrazione</li>
                        <li>Inserisci il codice sopra riportato</li>
                        <li>Clicca su 'Verifica e Completa'</li>
                    </ol>
                </div>
                
                <div class='warning'>
                    <strong>⏰ Importante:</strong><br>
                    Questo codice è valido per 15 minuti. Se non completi la verifica entro questo tempo, dovrai registrarti nuovamente.
                </div>
                
                <p>Se non hai richiesto questa registrazione, puoi ignorare questa email.</p>
            </div>
            
            <div class='footer'>
                <p>Questa email è stata generata automaticamente da EventBooking.<br>
                Non rispondere a questa email.</p>
                <p style='margin-top: 15px;'>
                    <strong>EventBooking</strong> - Via Piemonte, 6, 40024 Osteria Grande BO<br>
                    © " . date('Y') . " EventBooking. Tutti i diritti riservati.
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: EventBooking <noreply@eventbooking.com>" . "\r\n";
    $headers .= "Reply-To: marco.bedeschi@itsolivetti.it" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($step == 1 && isset($_POST['next'])) {
        // Valida i dati del primo step
        $userData['nome'] = trim($_POST["nome"]);
        $userData['cognome'] = trim($_POST["cognome"]);
        $userData['email'] = trim($_POST["email"]);
        
        // Controlla se l'email esiste già
        $stmt = $myDB->prepare("SELECT id FROM utenti WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $userData['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email già registrata. Utilizza un'altra email.";
                $step = 1;
            } else {
                $step = 2; // Passa al secondo step
            }
            $stmt->close();
        } else {
            $error = "Errore del database. Riprova più tardi.";
            $step = 1;
        }
    } elseif ($step == 2 && isset($_POST['send_verification'])) {
        // Recupera i dati e invia il codice di verifica
        $nome = trim($_POST["nome"]);
        $cognome = trim($_POST["cognome"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $telefono = !empty($_POST["telefono"]) ? trim($_POST["telefono"]) : "";
        
        // Genera codice di verifica
        $verification_code = generateVerificationCode();
        $expiry_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Salva i dati temporaneamente nella sessione
        $_SESSION['temp_user'] = [
            'nome' => $nome,
            'cognome' => $cognome,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'telefono' => $telefono,
            'verification_code' => $verification_code,
            'expiry_time' => $expiry_time
        ];
        
        // Invia email di verifica
        if (sendVerificationEmail($email, $nome, $verification_code)) {
            $step = 3;
            $message = "Codice di verifica inviato! Controlla la tua email.";
        } else {
            $error = "Errore nell'invio dell'email di verifica. Riprova.";
            $step = 2;
        }
    } elseif ($step == 3 && isset($_POST['verify'])) {
        // Verifica il codice
        $entered_code = trim($_POST["verification_code"]);
        
        if (!isset($_SESSION['temp_user'])) {
            $error = "Sessione scaduta. Riprova la registrazione.";
            $step = 1;
        } else {
            $temp_user = $_SESSION['temp_user'];
            
            // Controlla se il codice è scaduto
            if (strtotime($temp_user['expiry_time']) < time()) {
                $error = "Codice di verifica scaduto. Riprova la registrazione.";
                unset($_SESSION['temp_user']);
                $step = 1;
            } elseif ($entered_code !== $temp_user['verification_code']) {
                $error = "Codice di verifica non corretto. Riprova.";
                $step = 3;
            } else {
                // Codice corretto, completa la registrazione
                $foto_profilo = "default.jpg";
                
                $stmt = $myDB->prepare("INSERT INTO utenti (nome, cognome, email, password, telefono, foto_profilo, is_admin, email_verified) VALUES (?, ?, ?, ?, ?, ?, 0, 1)");
                if ($stmt) {
                    $stmt->bind_param("ssssss", 
                        $temp_user['nome'], 
                        $temp_user['cognome'], 
                        $temp_user['email'], 
                        $temp_user['password'], 
                        $temp_user['telefono'], 
                        $foto_profilo
                    );
                    
                    if ($stmt->execute()) {
                        $message = "Registrazione completata con successo! Ora puoi accedere.";
                        unset($_SESSION['temp_user']);
                        header("refresh:3;url=login.php");
                    } else {
                        $error = "Errore durante la registrazione: " . $stmt->error;
                        $step = 3;
                    }
                    $stmt->close();
                } else {
                    $error = "Errore del database durante la registrazione: " . $myDB->error;
                    $step = 3;
                }
            }
        }
    } elseif ($step == 3 && isset($_POST['resend_code'])) {
        // Reinvia il codice
        if (isset($_SESSION['temp_user'])) {
            $temp_user = $_SESSION['temp_user'];
            $new_code = generateVerificationCode();
            $new_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $_SESSION['temp_user']['verification_code'] = $new_code;
            $_SESSION['temp_user']['expiry_time'] = $new_expiry;
            
            if (sendVerificationEmail($temp_user['email'], $temp_user['nome'], $new_code)) {
                $message = "Nuovo codice di verifica inviato!";
            } else {
                $error = "Errore nell'invio del nuovo codice.";
            }
            $step = 3;
        } else {
            $error = "Sessione scaduta. Riprova la registrazione.";
            $step = 1;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBooking - Registrazione</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- CSS Files -->
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="stili/stile_register.css">
    <link rel="stylesheet" href="stili/stile_navfoo.css">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
    <link rel="stylesheet" href="stili/stile_autofill_fix.css">
    <link rel="icon" type="image/x-icon" href="Immagini/logo.ico">
</head>
<body>
    <!-- Elementi decorativi di sfondo avanzati -->
    <div class="background-elements">
        <!-- Forme geometriche fluttuanti -->
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-shape shape-5"></div>
        
        <!-- Particelle luminose -->
        <div class="floating-particle particle-1"></div>
        <div class="floating-particle particle-2"></div>
        <div class="floating-particle particle-3"></div>
        <div class="floating-particle particle-4"></div>
        <div class="floating-particle particle-5"></div>
        <div class="floating-particle particle-6"></div>
        
        <!-- Onde di luce -->
        <div class="light-wave wave-1"></div>
        <div class="light-wave wave-2"></div>
        <div class="light-wave wave-3"></div>
        
        <!-- Cerchi pulsanti -->
        <div class="pulse-circle circle-1"></div>
        <div class="pulse-circle circle-2"></div>
        <div class="pulse-circle circle-3"></div>
        
        <!-- Stelle scintillanti -->
        <div class="twinkling-star star-1"></div>
        <div class="twinkling-star star-2"></div>
        <div class="twinkling-star star-3"></div>
        <div class="twinkling-star star-4"></div>
        <div class="twinkling-star star-5"></div>
        
        <!-- Note musicali fluttuanti -->
        <div class="musical-note note-1">♪</div>
        <div class="musical-note note-2">♫</div>
        <div class="musical-note note-3">♪</div>
        <div class="musical-note note-4">♬</div>
        <div class="musical-note note-5">♫</div>
        <div class="musical-note note-6">♪</div>
        <div class="musical-note note-7">♭</div>
        <div class="musical-note note-8">♯</div>
        
        <!-- Icone eventi musicali -->
        <div class="music-icon icon-1"><i class="fas fa-music"></i></div>
        <div class="music-icon icon-2"><i class="fas fa-guitar"></i></div>
        <div class="music-icon icon-3"><i class="fas fa-microphone"></i></div>
        <div class="music-icon icon-4"><i class="fas fa-drum"></i></div>
        <div class="music-icon icon-5"><i class="fas fa-piano"></i></div>
        <div class="music-icon icon-6"><i class="fas fa-headphones"></i></div>
        
        <!-- Pentagrammi decorativi -->
        <div class="staff staff-1">
            <div class="staff-line"></div>
            <div class="staff-line"></div>
            <div class="staff-line"></div>
            <div class="staff-line"></div>
            <div class="staff-line"></div>
        </div>
        <div class="staff staff-2">
            <div class="staff-line"></div>
            <div class="staff-line"></div>
            <div class="staff-line"></div>
            <div class="staff-line"></div>
            <div class="staff-line"></div>
        </div>
        
        <!-- Onde sonore -->
        <div class="sound-wave wave-sound-1"></div>
        <div class="sound-wave wave-sound-2"></div>
        <div class="sound-wave wave-sound-3"></div>
    </div>
    
    <?php include 'navbar.php'; ?>
    
    <div class="container register-container">
        <div class="register-card">
            <div class="step-indicator">
                <div class="step <?php echo ($step == 1) ? 'active' : 'completed'; ?>">1</div>
                <div class="step-connector"></div>
                <div class="step <?php echo ($step == 2) ? 'active' : (($step > 2) ? 'completed' : ''); ?>">2</div>
                <div class="step-connector"></div>
                <div class="step <?php echo ($step == 3) ? 'active' : ''; ?>">3</div>
            </div>
            
            <h2 class="text-center mb-4">
                <i class="fa fa-user-plus me-2"></i>
                <?php 
                if ($step == 1) echo 'Crea un account';
                elseif ($step == 2) echo 'Completa la registrazione';
                else echo 'Verifica la tua email';
                ?>
            </h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <?php if (strpos($message, 'completata') !== false): ?>
                    <div class="mt-2 text-center">
                        <div class="spinner-border spinner-border-sm text-light me-2" role="status"></div>
                        Reindirizzamento al login...
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($message) || strpos($message, 'completata') === false): ?>
            
                <?php if ($step == 1): ?>
                <!-- Step 1: Informazioni di base -->
                <form method="post" class="mt-4">
                    <input type="hidden" name="step" value="1">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" required value="<?php echo htmlspecialchars($userData['nome']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="cognome" class="form-label">Cognome</label>
                        <input type="text" id="cognome" name="cognome" class="form-control" required value="<?php echo htmlspecialchars($userData['cognome']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($userData['email']); ?>">
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" name="next" class="btn btn-hero">Continua</button>
                        <a href="login.php" class="btn btn-outline-light">Ho già un account</a>
                    </div>
                </form>
                
                <?php elseif ($step == 2): ?>
                <!-- Step 2: Password e dettagli opzionali -->
                <form method="post" class="mt-4">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($_POST['nome']); ?>">
                    <input type="hidden" name="cognome" value="<?php echo htmlspecialchars($_POST['cognome']); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email']); ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               oninput="checkPasswordMatch()">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Conferma Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                               oninput="checkPasswordMatch()">
                        <div id="password-feedback" class="invalid-feedback" style="display: none;">Le password non corrispondono</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="telefono" class="form-label">Telefono (opzionale)</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control"
                               pattern="\d{8,15}" title="Inserisci tra 8 e 15 cifre numeriche">
                        <div class="form-text text-light opacity-75">Questo campo è opzionale e può essere compilato anche in seguito</div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" name="send_verification" class="btn btn-hero" id="register-btn">
                            <i class="fas fa-envelope me-2"></i>Invia Codice di Verifica
                        </button>
                        <button type="button" class="btn btn-outline-light" onclick="history.back()">Indietro</button>
                    </div>
                </form>
                
                <?php elseif ($step == 3): ?>
                <!-- Step 3: Verifica codice -->
                <div class="verification-step">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text fa-3x text-primary mb-3"></i>
                        <h4 style="color: white;">Controlla la tua email</h4>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 1rem; line-height: 1.5;">
                            Abbiamo inviato un codice di verifica a:<br>
                            <strong style="color: #ff79c6; font-weight: 700; text-shadow: 0 0 10px rgba(255, 121, 198, 0.3);">
                                <?php echo isset($_SESSION['temp_user']) ? htmlspecialchars($_SESSION['temp_user']['email']) : ''; ?>
                            </strong>
                        </p>
                    </div>
                    
                    <form method="post" class="mt-4">
                        <input type="hidden" name="step" value="3">
                        
                        <div class="mb-4">
                            <label for="verification_code" class="form-label" style="color: white; font-weight: 600;">Codice di Verifica</label>
                            <input type="text" id="verification_code" name="verification_code" class="form-control text-center" 
                                   style="font-size: 1.5rem; letter-spacing: 0.5rem; font-weight: bold; background: rgba(255, 255, 255, 0.1); border: 2px solid rgba(255, 255, 255, 0.2); color: white;" 
                                   placeholder="000000" maxlength="6" pattern="\d{6}" required>
                            <div class="form-text" style="color: rgba(255, 255, 255, 0.8); font-weight: 500;">
                                <i class="fas fa-clock me-1"></i>
                                Il codice scade tra <span id="countdown" style="color: #ff79c6; font-weight: bold;">15:00</span> minuti
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="verify" class="btn btn-hero">
                                <i class="fas fa-check-circle me-2"></i>Verifica e Completa
                            </button>
                            <button type="submit" name="resend_code" class="btn btn-outline-light">
                                <i class="fas fa-redo me-2"></i>Invia nuovo codice
                            </button>
                            <a href="register.php" class="btn btn-secondary">Ricomincia</a>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Non trovi l'email?</strong><br>
                        Controlla la cartella spam/posta indesiderata. Se il problema persiste, 
                        clicca su "Invia nuovo codice".
                    </div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; 
    include 'cookie_banner.php';?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const registerBtn = document.getElementById('register-btn');
            const confirmInput = document.getElementById('confirm_password');
            const feedback = document.getElementById('password-feedback');
            
            if (confirmPassword === '') {
                confirmInput.setCustomValidity('');
                confirmInput.classList.remove('is-invalid');
                feedback.style.display = 'none';
                registerBtn.disabled = false;
                return;
            }
            
            if (password !== confirmPassword) {
                confirmInput.setCustomValidity('Le password non corrispondono');
                confirmInput.classList.add('is-invalid');
                feedback.style.display = 'block';
                registerBtn.disabled = true;
            } else {
                confirmInput.setCustomValidity('');
                confirmInput.classList.remove('is-invalid');
                feedback.style.display = 'none';
                registerBtn.disabled = false;
            }
        }
        
        // Countdown timer per il codice di verifica
        <?php if ($step == 3 && isset($_SESSION['temp_user'])): ?>
        const expiryTime = new Date('<?php echo $_SESSION['temp_user']['expiry_time']; ?>').getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const timeLeft = expiryTime - now;
            
            if (timeLeft > 0) {
                const minutes = Math.floor(timeLeft / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                document.getElementById('countdown').textContent = 
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            } else {
                document.getElementById('countdown').textContent = 'SCADUTO';
                document.getElementById('countdown').style.color = '#dc3545';
            }
        }
        
        // Aggiorna ogni secondo
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Chiamata iniziale
        <?php endif; ?>
        
        // Auto-format del codice di verifica
        document.addEventListener('DOMContentLoaded', function() {
            const verificationInput = document.getElementById('verification_code');
            if (verificationInput) {
                verificationInput.addEventListener('input', function() {
                    // Permetti solo numeri
                    this.value = this.value.replace(/\D/g, '');
                });
            }
            
            const registerBtn = document.getElementById('register-btn');
            if (registerBtn) {
                registerBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
