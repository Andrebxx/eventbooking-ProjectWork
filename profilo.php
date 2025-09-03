<?php
session_start();
include_once('password.php');

if (!isset($_SESSION['id'])) {
    $_SESSION['redirect_after_login'] = 'profilo.php';
    header("Location: login.php");
    exit();
}

$utente_id = $_SESSION['id'];

// Annullamento evento futuro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annulla_evento_id'])) {
    $evento_id = intval($_POST['annulla_evento_id']);

    // Recupera i posti prenotati
    $stmt = $myDB->prepare("SELECT posti FROM prenotazioni WHERE evento_id = ? AND utente_id = ?");
    $stmt->bind_param("ii", $evento_id, $utente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $posti_da_liberare = $row['posti'] ?? 0;
    $stmt->close();

    if ($posti_da_liberare > 0) {
        // Elimina prenotazione
        $stmt = $myDB->prepare("DELETE FROM prenotazioni WHERE evento_id = ? AND utente_id = ?");
        $stmt->bind_param("ii", $evento_id, $utente_id);
        $stmt->execute();
        $stmt->close();

        // Aggiorna i posti disponibili
        $stmt = $myDB->prepare("UPDATE Eventi SET Posti = Posti + ? WHERE id = ?");
        $stmt->bind_param("ii", $posti_da_liberare, $evento_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['successo'] = "Prenotazione annullata con successo.";
    } else {
        $_SESSION['errore'] = "Prenotazione non trovata o già annullata.";
    }

    header("Location: profilo.php");
    exit();
}

// Aggiunta nuova carta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_carta'])) {
    $numero_carta = trim($_POST['numero_carta'] ?? '');
    $scadenza = trim($_POST['scadenza'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $titolare = trim($_POST['titolare'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');

    $valid_numero = preg_match('/^\d{16}$/', $numero_carta);
    $valid_scadenza = preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $scadenza);
    $valid_cvv = preg_match('/^\d{3,4}$/', $cvv);
    $valid_titolare = preg_match("/^[A-Za-zÀ-ÿ\s']{2,100}$/", $titolare);
    $valid_tipo = in_array($tipo, ['Visa', 'Mastercard', 'American Express', 'Altro']);
    
    // Validazione data di scadenza
    $valid_expiry_date = false;
    if ($valid_scadenza) {
        list($month, $year) = explode('/', $scadenza);
        $current_year = (int)date('y'); // Anno corrente in formato YY
        $current_month = (int)date('m'); // Mese corrente
        $card_year = (int)$year;
        $card_month = (int)$month;
        
        // Verifica che la carta non sia scaduta
        if ($card_year > $current_year || ($card_year == $current_year && $card_month >= $current_month)) {
            $valid_expiry_date = true;
        }
    }

    if ($valid_numero && $valid_scadenza && $valid_cvv && $valid_titolare && $valid_tipo && $valid_expiry_date) {
        $numero_mascherato = '**** **** **** ' . substr($numero_carta, -4);

        $stmt_check = $myDB->prepare("SELECT id FROM carte_credito WHERE utente_id = ? AND numero_mascherato = ?");
        $stmt_check->bind_param("is", $utente_id, $numero_mascherato);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['errore'] = "Questa carta è già registrata.";
        } else {
            $numero_hash = password_hash($numero_carta, PASSWORD_DEFAULT);
            $stmt = $myDB->prepare("INSERT INTO carte_credito (utente_id, numero_mascherato, numero_hash, scadenza, cvv, titolare, tipo, data_creazione) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssiss", $utente_id, $numero_mascherato, $numero_hash, $scadenza, $cvv, $titolare, $tipo);

            if ($stmt->execute()) {
                $_SESSION['successo'] = "Carta aggiunta con successo!";
            } else {
                $_SESSION['errore'] = "Errore durante l'aggiunta della carta: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        if (!$valid_expiry_date && $valid_scadenza) {
            $_SESSION['errore'] = "La carta di credito è scaduta. Inserisci una carta con data di scadenza valida.";
        } else {
            $_SESSION['errore'] = "Dati carta non validi.";
        }
    }

    header("Location: profilo.php");
    exit();
}

// Rimozione carta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rimuovi_carta'])) {
    $mascherato = $_POST['rimuovi_carta'];
    $stmt = $myDB->prepare("DELETE FROM carte_credito WHERE utente_id = ? AND numero_mascherato = ?");
    $stmt->bind_param("is", $utente_id, $mascherato);
    $stmt->execute();
    $stmt->close();

    $_SESSION['successo'] = "Carta rimossa con successo.";
    header("Location: profilo.php");
    exit();
}

// Aggiornamento profilo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['cognome'], $_POST['telefono'])) {
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $telefono = trim($_POST['telefono']);

    $valid_nome = preg_match("/^[A-Za-zÀ-ÿ\s']{2,30}$/", $nome);
    $valid_cognome = preg_match("/^[A-Za-zÀ-ÿ\s']{2,30}$/", $cognome);
    $valid_telefono = preg_match("/^\d{8,15}$/", $telefono);

    if ($valid_nome && $valid_cognome && $valid_telefono) {
        $stmt = $myDB->prepare("UPDATE utenti SET nome = ?, cognome = ?, telefono = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nome, $cognome, $telefono, $utente_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['successo'] = "Profilo aggiornato con successo.";
    } else {
        $_SESSION['errore'] = "Dati profilo non validi.";
    }

    header("Location: profilo.php");
    exit();
}

// Upload foto profilo
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['foto']['tmp_name'];
    $fileSize = $_FILES['foto']['size'];
    $fileName = $_FILES['foto']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Controlli di sicurezza
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($fileExt, $allowedExtensions)) {
        $_SESSION['errore'] = "Formato file non supportato. Usa JPG, PNG, GIF o WEBP.";
    } elseif ($fileSize > $maxFileSize) {
        $_SESSION['errore'] = "Il file è troppo grande. Dimensione massima: 5MB.";
    } else {
        // Verifica che sia effettivamente un'immagine
        $imageInfo = getimagesize($fileTmp);
        if ($imageInfo === false) {
            $_SESSION['errore'] = "Il file selezionato non è un'immagine valida.";
        } else {
            // Crea la directory se non esiste
            $uploadDir = 'Immagini/foto_utenti/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Genera un nome file unico
            $newFileName = 'profilo_' . $utente_id . '_' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $newFileName;
            
            // Elimina la vecchia foto se esiste e non è quella di default
            if (!empty($foto_profilo) && $foto_profilo !== 'default.jpg') {
                $oldPhotoPath = $uploadDir . basename($foto_profilo);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
            
            // Carica la nuova foto
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $stmt = $myDB->prepare("UPDATE utenti SET foto_profilo = ? WHERE id = ?");
                $stmt->bind_param("si", $newFileName, $utente_id);
                
                if ($stmt->execute()) {
                    $_SESSION['foto_profilo'] = $newFileName;
                    $_SESSION['successo'] = "Foto profilo aggiornata con successo!";
                } else {
                    $_SESSION['errore'] = "Errore durante l'aggiornamento del database.";
                    // Rimuovi il file caricato se il database non è stato aggiornato
                    unlink($uploadPath);
                }
                $stmt->close();
            } else {
                $_SESSION['errore'] = "Errore durante il caricamento del file.";
            }
        }
    }
    
    header("Location: profilo.php");
    exit();
}

// Dati profilo utente
$stmt = $myDB->prepare("SELECT nome, cognome, email, telefono, foto_profilo FROM utenti WHERE id = ?");
$stmt->bind_param("i", $utente_id);
$stmt->execute();
$stmt->bind_result($nome, $cognome, $email, $telefono, $foto_profilo);
$stmt->fetch();
$stmt->close();

// Carte salvate
$carte = [];
$res = $myDB->prepare("SELECT numero_mascherato, tipo, scadenza, titolare FROM carte_credito WHERE utente_id = ?");
$res->bind_param("i", $utente_id);
$res->execute();
$result_carte = $res->get_result();
while ($row = $result_carte->fetch_assoc()) {
    $carte[] = $row;
}
$res->close();

// Eventi prenotati
$eventiPassati = [];
$eventiFuturi = [];

$query = "SELECT e.id AS evento_id, e.titoloE, e.data, e.ora, p.posti, p.pagamento 
          FROM prenotazioni p
          JOIN Eventi e ON p.evento_id = e.id
          WHERE p.utente_id = ?
          ORDER BY e.data DESC";
$stmt = $myDB->prepare($query);
$stmt->bind_param("i", $utente_id);
$stmt->execute();
$result_eventi = $stmt->get_result();

$oggi = date("Y-m-d");
while ($evento = $result_eventi->fetch_assoc()) {
    if ($evento['data'] < $oggi) {
        $eventiPassati[] = $evento;
    } else {
        $eventiFuturi[] = $evento;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il Tuo Profilo - EventBooking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="stili/stile_profilo.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
    <link rel="icon" type="image/x-icon" href="Immagini/logo.ico">

</head>
<body class="bg-dark text-white">
    <!-- Elementi decorativi di sfondo -->
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
    </div>
    
    <?php include 'navbar.php'; ?>
    
    <!-- Hero del Profilo -->
    <div class="container profile-container">
        
        <div class="profile-hero">
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <?php 
                    $fotoPath = "Immagini/foto_utenti/" . ($foto_profilo ?? 'default.jpg');
                    
                    // Verifica se il file esiste, altrimenti usa l'immagine di default
                    if (!file_exists($fotoPath) || empty($foto_profilo) || $foto_profilo === 'default.jpg') {
                        $fotoPath = "Immagini/foto_utenti/default.jpg";
                    }
                    
                    // Se non esiste nemmeno l'immagine di default, crea una placeholder
                    if (!file_exists($fotoPath)) {
                        // Crea la directory se non esiste
                        $foto_dir = "Immagini/foto_utenti/";
                        if (!file_exists($foto_dir)) {
                            mkdir($foto_dir, 0755, true);
                        }
                        
                        // Crea un'immagine di default se non esiste
                        $default_path = $foto_dir . "default.jpg";
                        if (!file_exists($default_path)) {
                            $width = 200;
                            $height = 200;
                            $image = imagecreate($width, $height);
                            
                            // Colori
                            $bg_color = imagecolorallocate($image, 108, 92, 231);
                            $text_color = imagecolorallocate($image, 255, 255, 255);
                            
                            // Icona utente stilizzata
                            imagefilledellipse($image, 100, 80, 60, 60, $text_color); // Testa
                            imagefilledellipse($image, 100, 150, 120, 80, $text_color); // Corpo
                            
                            // Salva l'immagine
                            imagejpeg($image, $default_path, 90);
                            imagedestroy($image);
                        }
                        $fotoPath = $default_path;
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($fotoPath); ?>" 
                         alt="Foto Profilo" class="profile-image mb-3"
                         onerror="this.src='Immagini/foto_utenti/default.jpg'">

                    <h3 class="mb-0"><?php echo htmlspecialchars($nome . ' ' . $cognome); ?></h3>
                    <p class="text-white"><?php echo htmlspecialchars($email); ?></p>
                </div>
                <div class="col-md-8">
                    <h1 class="section-title">
                        <i class="fa fa-user"></i>
                        Benvenuto nel tuo profilo!
                    </h1>
                    <p class="lead mb-4">Gestisci le tue informazioni, le tue carte e tieni traccia di tutti i tuoi eventi.</p>
                    
                    <!-- Statistiche rapide -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($eventiFuturi); ?></div>
                            <div class="stat-label">Eventi Futuri</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($eventiPassati); ?></div>
                            <div class="stat-label">Eventi Passati</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($carte); ?></div>
                            <div class="stat-label">Carte Salvate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sezione Informazioni Personali -->
    <div class="container">
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fa fa-id-card"></i>
                Informazioni Personali
            </h2>
            
            <div class="row align-items-stretch">
                <div class="col-md-6">
                    <div class="info-card">
                        <form action="profilo.php" method="POST">
                            <div class="info-item">
                                <i class="fa fa-user"></i>
                                <div class="w-100">
                                    <label class="form-label text-white"><strong>Nome</strong></label>
                                    <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($nome); ?>" 
                                    required pattern="[A-Za-zÀ-ÿ\s']{2,30}" title="Inserisci solo lettere, minimo 2 caratteri">
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-user"></i>
                                <div class="w-100">
                                    <label class="form-label text-white"><strong>Cognome</strong></label>
                                    <input type="text" name="cognome" class="form-control" value="<?php echo htmlspecialchars($cognome); ?>" 
                                    required pattern="[A-Za-zÀ-ÿ\s']{2,30}" title="Inserisci solo lettere, minimo 2 caratteri">
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-envelope"></i>
                                <div class="w-100">
                                    <label class="form-label text-white"><strong>Email</strong></label>
                                    <input type="email" class="form-control text-black" value="<?php echo htmlspecialchars($email); ?>" disabled>
                                    <small class="text-danger fw-bold">Email non modificabile</small>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-phone"></i>
                                <div class="w-100">
                                    <label class="form-label text-white"><strong>Telefono</strong></label>
                                    <input type="text" name="telefono" class="form-control" 
                                    value="<?php echo htmlspecialchars($telefono); ?>" 
                                    placeholder="Inserisci numero di telefono"
                                    pattern="\d{8,15}" title="Inserisci tra 8 e 15 cifre numeriche">
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-custom">
                                    <i class="fa fa-save me-2"></i>Aggiorna Dati
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="upload-area">
                        <i class="fa fa-camera fa-2x mb-3" style="color: #6c5ce7;"></i>
                        <h5 class="text-white">Cambia Foto Profilo</h5>
                        <p class="text-white mb-3">Carica una nuova foto per personalizzare il tuo profilo</p>
                        <form action="profilo.php" method="POST" enctype="multipart/form-data">
                            <input type="file" name="foto" class="form-control mb-3" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
                            <div class="form-text text-light mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Dimensione massima: 5MB. Formati supportati: JPG, PNG, GIF, WEBP
                            </div>
                            <button type="submit" class="btn btn-custom">
                                <i class="fa fa-upload me-2"></i>Carica Foto
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Sezione Carte di Credito -->
        <?php if (isset($_SESSION['successo'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                <?php echo htmlspecialchars($_SESSION['successo']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['successo']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['errore'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
                <?php echo htmlspecialchars($_SESSION['errore']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['errore']); ?>
        <?php endif; ?>

        <div class="profile-section mt-4">
            <h2 class="section-title">
                <i class="fa fa-credit-card"></i>
                Metodi di Pagamento
            </h2>

            <div class="row align-items-stretch">
                <div class="col-md-8">
                    <?php if (empty($carte)): ?>
                        <div class="card-item text-center py-4">
                            <i class="fa fa-credit-card fa-3x text-white mb-3"></i>
                            <h5 class="text-white">Nessuna carta registrata</h5>
                            <p class="text-white">Aggiungi la tua prima carta per pagamenti più veloci</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($carte as $carta): ?>
                            <div class="card-item d-flex align-items-center">
                                <!-- Icona del tipo di carta -->
                                <i class="fa fa-credit-card fa-2x me-3 text-secondary"></i>
                                
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong class="me-2 text-white"><?php echo htmlspecialchars($carta['tipo']); ?></strong>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($carta['numero_mascherato']); ?></span>
                                    </div>
                                    <div class="card-details">
                                        <small class="text-light">
                                            <i class="fa fa-user me-1"></i><?php echo htmlspecialchars($carta['titolare']); ?> |
                                            <i class="fa fa-calendar me-1"></i>Scade: <?php echo htmlspecialchars($carta['scadenza']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <form action="profilo.php" method="POST" class="ms-auto">
                                    <input type="hidden" name="rimuovi_carta" value="<?php echo htmlspecialchars($carta['numero_mascherato']); ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Sei sicuro di voler eliminare questa carta?');">
                                        <i class="fa fa-trash"></i> Rimuovi
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="add-card-form">
                        <h5 class="mb-3 text-white">
                            <i class="fa fa-plus-circle me-2"></i>Aggiungi Nuova Carta
                        </h5>
                        <form action="profilo.php" method="POST">
                            <!-- Tipo di carta -->
                            <div class="mb-3">
                                <label class="form-label text-white">Tipo di Carta</label>
                                <select name="tipo" class="form-select" required>
                                    <option value="">Seleziona tipo...</option>
                                    <option value="Visa">Visa</option>
                                    <option value="Mastercard">Mastercard</option>
                                    <option value="American Express">American Express</option>
                                    <option value="Altro">Altro</option>
                                </select>
                            </div>
                            
                            <!-- Numero carta -->
                            <div class="mb-3">
                                <label class="form-label text-white">Numero Carta</label>
                                <input type="text" name="numero_carta" class="form-control" 
                                       maxlength="16" pattern="\d{16}" 
                                       placeholder="1234567890123456" required>
                            </div>
                            
                            <!-- Nome titolare -->
                            <div class="mb-3">
                                <label class="form-label text-white">Nome Titolare</label>
                                <input type="text" name="titolare" class="form-control" 
                                       pattern="[A-Za-zÀ-ÿ\s']{2,100}" 
                                       placeholder="Nome Cognome" required>
                            </div>
                            
                            <!-- Scadenza e CVV -->
                            <div class="row">
                                <div class="col-7">
                                    <div class="mb-3">
                                        <label class="form-label text-white">Scadenza (MM/YY)</label>
                                        <input type="text" name="scadenza" class="form-control" 
                                               pattern="(0[1-9]|1[0-2])\/\d{2}" 
                                               placeholder="12/25" maxlength="5" required
                                               id="scadenza-input">
                                        <div class="form-text text-light">
                                            <i class="fas fa-info-circle me-1"></i>
                                            La carta non deve essere scaduta
                                        </div>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="mb-3">
                                        <label class="form-label text-white">CVV</label>
                                        <input type="text" name="cvv" class="form-control" 
                                               pattern="\d{3,4}" placeholder="123" 
                                               maxlength="4" required>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-custom w-100">
                                <i class="fa fa-save me-2"></i>Salva Carta
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


<!-- Sezione Eventi -->
<div class="container">
    <div class="profile-section">
        <h2 class="section-title">
            <i class="fa fa-calendar-alt"></i>
            I Tuoi Eventi
        </h2>
        
        <!-- Tab Navigation -->
        <ul class="nav nav-pills mb-4" id="eventTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active custom-tab" data-bs-toggle="pill" 
                        data-bs-target="#futuri" type="button">
                    <i class="fa fa-calendar-plus me-2"></i>Eventi Futuri
                    <span class="badge bg-light text-dark ms-2"><?php echo count($eventiFuturi); ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link custom-tab" data-bs-toggle="pill" 
                        data-bs-target="#passati" type="button">
                    <i class="fa fa-history me-2"></i>Eventi Passati
                    <span class="badge bg-light text-dark ms-2"><?php echo count($eventiPassati); ?></span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Eventi Futuri -->
            <div class="tab-pane fade show active" id="futuri">
                <?php if (empty($eventiFuturi)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-calendar-times fa-4x text-white mb-3"></i>
                        <h4 class="text-white">Nessun evento futuro</h4>
                        <p class="text-white">Non hai ancora prenotato eventi futuri.</p>
                        <a href="Eventi.php" class="btn btn-custom mt-3">
                            <i class="fa fa-search me-2"></i>Scopri gli Eventi
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table event-table">
                            <thead>
                                <tr>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-music me-2"></i>Evento</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-calendar me-2"></i>Data</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-clock me-2"></i>Ora</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-users me-2"></i>Posti</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-credit-card me-2"></i>Pagamento</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-times-circle me-2"></i>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventiFuturi as $e): ?>
                                    <tr>
                                        <td style="background-color: #5b3361ff;">
                                            <strong class="text-white"><?php echo htmlspecialchars($e['titoloE']); ?></strong>
                                        </td>
                                        <td style="background-color: #5b3361ff;" class="text-white"><?php echo date("d/m/Y", strtotime($e['data'])); ?></td>
                                        <td style="background-color: #5b3361ff;" class="text-white"><?php echo date("H:i", strtotime($e['ora'])); ?></td>
                                        <td style="background-color: #5b3361ff;">
                                            <span class="badge bg-primary"><?php echo $e['posti']; ?></span>
                                        </td>
                                        <td style="background-color: #5b3361ff;" class="text-white"><?php echo htmlspecialchars($e['pagamento']); ?></td>
                                        <td style="background-color: #5b3361ff;">
                                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                                                  onsubmit="return confirm('Sei sicuro di voler annullare questa prenotazione? Questa azione non può essere annullata.');">
                                                <input type="hidden" name="annulla_evento_id" value="<?php echo $e['evento_id']; ?>">
                                                <input type="hidden" name="posti_annullati" value="<?php echo $e['posti']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-times"></i> Annulla
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Eventi Passati -->
            <div class="tab-pane fade" id="passati">
                <?php if (empty($eventiPassati)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-history fa-4x text-white mb-3"></i>
                        <h4 class="text-white">Nessun evento passato</h4>
                        <p class="text-white">Non hai ancora partecipato a nessun evento.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table event-table">
                            <thead>
                                <tr>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-music me-2"></i>Evento</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-calendar me-2"></i>Data</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-clock me-2"></i>Ora</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-users me-2"></i>Posti</th>
                                    <th style="background-color: #5b3361ff;"><i class="fa fa-credit-card me-2"></i>Pagamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventiPassati as $e): ?>
                                    <tr>
                                        <td style="background-color: #5b3361ff;">
                                            <strong class="text-white"><?php echo htmlspecialchars($e['titoloE']); ?></strong>
                                        </td>
                                        <td style="background-color: #5b3361ff;" class="text-white"><?php echo date("d/m/Y", strtotime($e['data'])); ?></td>
                                        <td style="background-color: #5b3361ff;" class="text-white"><?php echo date("H:i", strtotime($e['ora'])); ?></td>
                                        <td style="background-color: #5b3361ff;">
                                            <span class="badge bg-secondary"><?php echo $e['posti']; ?></span>
                                        </td>
                                        <td style="background-color: #5b3361ff;" class="text-white"><?php echo htmlspecialchars($e['pagamento']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
    <br><br>
    
    <?php include 'footer.php'; 
    include 'cookie_banner.php';?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formattazione automatica per il numero di carta
        document.querySelector('input[name="numero_carta"]').addEventListener('input', function(e) {
            // Rimuove tutto ciò che non è un numero
            this.value = this.value.replace(/\D/g, '');
        });

        // Formattazione automatica per la scadenza con validazione
        document.querySelector('input[name="scadenza"]').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0,2) + '/' + value.substring(2,4);
            }
            this.value = value;
            
            // Validazione in tempo reale
            validateExpiryDate(this);
        });

        // Validazione anche quando l'utente esce dal campo
        document.querySelector('input[name="scadenza"]').addEventListener('blur', function(e) {
            validateExpiryDate(this);
        });

        function validateExpiryDate(input) {
            const value = input.value;
            const regex = /^(0[1-9]|1[0-2])\/\d{2}$/;
            
            if (regex.test(value)) {
                const [month, year] = value.split('/');
                const currentDate = new Date();
                const currentYear = currentDate.getFullYear() % 100; // Anno corrente in formato YY
                const currentMonth = currentDate.getMonth() + 1; // Mese corrente (1-12)
                
                const cardYear = parseInt(year);
                const cardMonth = parseInt(month);
                
                if (cardYear < currentYear || (cardYear === currentYear && cardMonth < currentMonth)) {
                    input.setCustomValidity('La carta è scaduta. Inserisci una data di scadenza valida.');
                    input.classList.add('is-invalid');
                    
                    // Mostra messaggio di errore
                    let errorDiv = input.parentNode.querySelector('.invalid-feedback');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        input.parentNode.appendChild(errorDiv);
                    }
                    errorDiv.textContent = 'La carta è scaduta. Inserisci una data di scadenza valida.';
                } else {
                    input.setCustomValidity('');
                    input.classList.remove('is-invalid');
                    
                    // Rimuovi messaggio di errore
                    const errorDiv = input.parentNode.querySelector('.invalid-feedback');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }
            } else if (value.length > 0) {
                input.setCustomValidity('Formato non valido. Usa MM/YY');
                input.classList.add('is-invalid');
            } else {
                input.setCustomValidity('');
                input.classList.remove('is-invalid');
            }
        }

        // Formattazione automatica per il CVV
        document.querySelector('input[name="cvv"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });

        // Formattazione automatica per il titolare (solo lettere e spazi)
        document.querySelector('input[name="titolare"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s']/g, '');
        });

        // Previeni invio del form se la data di scadenza non è valida
        document.querySelector('form').addEventListener('submit', function(e) {
            const scadenzaInput = document.querySelector('input[name="scadenza"]');
            validateExpiryDate(scadenzaInput);
            
            if (!scadenzaInput.checkValidity()) {
                e.preventDefault();
                scadenzaInput.focus();
                return false;
            }
        });
    </script>
</body>
</html>


