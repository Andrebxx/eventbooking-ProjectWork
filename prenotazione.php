<?php
session_start();
include_once("password.php");

if (!isset($_SESSION['id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['id'];
$carteSalvate = [];

// Recupera carte salvate
$stmt = $myDB->prepare("SELECT id, numero_mascherato AS numero_carta_masked, tipo AS tipo_carta FROM carte_credito WHERE utente_id = ?");
$stmt->bind_param("i", $utente_id);
$stmt->execute();
$resultCarte = $stmt->get_result();
while ($carta = $resultCarte->fetch_assoc()) {
    $carteSalvate[] = $carta;
}
$stmt->close();

// Controllo evento
if (!isset($_GET['id'])) {
    die("Evento non specificato.");
}

$idEvento = intval($_GET['id']);
$sql = "SELECT * FROM Eventi WHERE id = $idEvento";
$result = $myDB->query($sql);
if (!$result || $result->num_rows == 0) {
    die("Evento non trovato.");
}

$evento = $result->fetch_assoc();
if ($evento['Posti'] <= 0) {
    die("Spiacenti, non ci sono posti disponibili per questo evento.");
}

// Aggiungi colonna nominativi se non esiste
try {
    $checkColumnQuery = "SHOW COLUMNS FROM prenotazioni LIKE 'nominativi'";
    $result = $myDB->query($checkColumnQuery);
    if ($result && $result->num_rows == 0) {
        $addColumnQuery = "ALTER TABLE prenotazioni ADD COLUMN nominativi TEXT AFTER posti";
        $myDB->query($addColumnQuery);
    }
} catch (Exception $e) {
    error_log("Errore nell'aggiornamento tabella prenotazioni: " . $e->getMessage());
}

$prenotazioneCompletata = false;
$errore = "";

// Funzione che invia una vera email di conferma prenotazione
function inviaEmailConfermaPrenotazione($email, $nome, $evento, $posti, $booking_id, $metodo_pagamento) {
    $oggetto = "🎉 Prenotazione Confermata - " . $evento['titoloE'];
    
    // URL della pagina da mostrare quando si scansiona il QR Code
    $urlQrCode = "https://projectworkgruppoquattro.altervista.org/marco.png";
    
    // Genera QR Code che punta all'URL usando un servizio gratuito
    // Il QR Code quando scansionato porterà alla pagina marco.png
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($urlQrCode);
    
    // Lista partecipanti con stile
    $partecipanti = '';
    if (isset($_SESSION['booking_data']['nominativi']) && is_array($_SESSION['booking_data']['nominativi'])) {
        $partecipanti = '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">';
        $partecipanti .= '<h3 style="color: #495057; margin: 0 0 10px 0; font-size: 16px;">👥 Partecipanti:</h3>';
        foreach ($_SESSION['booking_data']['nominativi'] as $index => $nominativo) {
            $partecipanti .= '<div style="padding: 5px 0; border-bottom: 1px solid #dee2e6; color: #6c757d;">
                                🎫 ' . ($index + 1) . '. ' . htmlspecialchars($nominativo) . '
                              </div>';
        }
        $partecipanti .= '</div>';
    }

    $messaggio = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Conferma Prenotazione</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
        <div style="max-width: 600px; margin: 0 auto; background-color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">🎉 Prenotazione Confermata!</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;">Il tuo posto è stato riservato con successo</p>
            </div>
            
            <!-- Contenuto principale -->
            <div style="padding: 30px;">
                <p style="font-size: 18px; color: #333; margin: 0 0 20px 0;">
                    Ciao <strong style="color: #667eea;">' . htmlspecialchars($nome) . '</strong>! 👋
                </p>
                
                <p style="color: #666; line-height: 1.6; margin-bottom: 25px;">
                    La tua prenotazione per <strong style="color: #333;">' . htmlspecialchars($evento['titoloE']) . '</strong> è stata confermata con successo.
                </p>
                
                <!-- Dettagli evento -->
                <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #667eea;">
                    <h2 style="color: #495057; margin: 0 0 15px 0; font-size: 20px;">📅 Dettagli Evento</h2>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                        <div style="flex: 1; min-width: 200px;">
                            <div style="margin-bottom: 10px;">
                                <span style="color: #6c757d; font-size: 14px;">📅 Data</span><br>
                                <strong style="color: #333; font-size: 16px;">' . date("d/m/Y", strtotime($evento['data'])) . '</strong>
                            </div>
                            
                            <div style="margin-bottom: 10px;">
                                <span style="color: #6c757d; font-size: 14px;">🕐 Orario</span><br>
                                <strong style="color: #333; font-size: 16px;">' . date("H:i", strtotime($evento['ora'])) . '</strong>
                            </div>
                        </div>
                        
                        <div style="flex: 1; min-width: 200px;">
                            <div style="margin-bottom: 10px;">
                                <span style="color: #6c757d; font-size: 14px;">🎫 Posti</span><br>
                                <strong style="color: #333; font-size: 16px;">' . intval($posti) . ' posto/i</strong>
                            </div>
                            
                            <div style="margin-bottom: 10px;">
                                <span style="color: #6c757d; font-size: 14px;">💳 Pagamento</span><br>
                                <strong style="color: #333; font-size: 16px;">' . htmlspecialchars($metodo_pagamento) . '</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                        <span style="color: #6c757d; font-size: 14px;">🆔 ID Prenotazione</span><br>
                        <strong style="color: #667eea; font-size: 18px; font-family: monospace;">#' . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . '</strong>
                    </div>
                </div>
                
                ' . $partecipanti . '
                
                <!-- QR Code -->
                <div style="text-align: center; background: #f8f9fa; padding: 25px; border-radius: 12px; margin: 25px 0;">
                    <h3 style="color: #495057; margin: 0 0 15px 0;">📱 Il tuo QR Code</h3>
                    <p style="color: #6c757d; margin: 0 0 15px 0; font-size: 14px;">Mostra questo codice all\'ingresso dell\'evento</p>
                    <img src="' . $qrCodeUrl . '" alt="QR Code Prenotazione" style="border: 2px solid #dee2e6; border-radius: 8px;">
                </div>
                
                <!-- Istruzioni -->
                <div style="background: #e7f3ff; border: 1px solid #b8daff; padding: 20px; border-radius: 8px; margin: 25px 0;">
                    <h3 style="color: #004085; margin: 0 0 10px 0; font-size: 16px;">ℹ️ Informazioni Importanti</h3>
                    <ul style="color: #004085; margin: 0; padding-left: 20px; line-height: 1.6;">
                        <li>Conserva questa email come conferma della tua prenotazione</li>
                        <li>Presenta il QR Code all\'ingresso dell\'evento</li>
                        <li>Arriva almeno 15 minuti prima dell\'orario di inizio</li>
                        <li>Per modifiche o cancellazioni, contattaci via email</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <p style="color: #666; margin: 0;">Grazie per aver scelto i nostri eventi! 🎊</p>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
                <p style="color: #6c757d; margin: 0; font-size: 14px;">
                    Questa email è stata generata automaticamente. Per assistenza contattaci a 
                    <a href="mailto:support@tuosito.it" style="color: #667eea;">support@tuosito.it</a>
                </p>
            </div>
        </div>
    </body>
    </html>';

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Eventi <Eventbooking@noreplay.it>\r\n";
    $headers .= "Reply-To: marco.bedeschi@itsolivetti.it\r\n";

    // Invia la mail
    return mail($email, $oggetto, $messaggio, $headers);
}

// Reset completo dei dati di prenotazione quando si accede alla pagina senza POST
// Questo assicura che ogni nuova prenotazione parta sempre dallo step 1
if (!isset($_POST['step'])) {
    unset($_SESSION['booking_data']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? '1';

    // STEP 2: raccolta dati utente
    if ($step === '2') {
        $posti = intval($_POST['posti']);
        
        // Verifica che i posti siano almeno 1
        if ($posti < 1) {
            $errore = "Devi prenotare almeno 1 posto.";
        }
        // Verifica limite massimo di 10 posti per prenotazione
        elseif ($posti > 10) {
            $errore = "Non puoi prenotare più di 10 posti per prenotazione.";
        }
        // Verifica che ci siano posti disponibili
        elseif ($posti > $evento['Posti']) {
            $errore = "Non ci sono abbastanza posti disponibili. Disponibili: " . $evento['Posti'];
        } else {
            $_SESSION['booking_data'] = [
                'evento_id' => intval($_POST['evento_id']),
                'nome' => $myDB->real_escape_string($_POST['nome']),
                'email' => $myDB->real_escape_string($_POST['email']),
                'posti' => $posti
            ];
            
            // Avanza automaticamente al step 2 (selezione pagamento)
            $currentStep = '2';
        }
    }

    // STEP 2.5: salvataggio metodo pagamento
    if ($step === '2.5') {
        $metodoPagamento = $_POST['metodo_pagamento'] ?? '';
        $_SESSION['booking_data']['metodo_pagamento'] = $metodoPagamento;

        if ($metodoPagamento === 'PayPal') {
            header("Location: 404.php?booking_id=" . session_id());
            exit;
        } elseif ($metodoPagamento === 'Bonifico') {
            header("Location: 404.php?booking_id=" . session_id());
            exit;
        } else {
            // Per carta o altri metodi, vai al step 3 (nominativi)
            $currentStep = '3';
        }
    }

    // STEP 3: gestione carta e vai ai nominativi
    if ($step === '3') {
        // Se è stata selezionata la carta, gestisci i dati della carta
        if (isset($_POST['metodo_pagamento']) && $_POST['metodo_pagamento'] === 'Carta') {
            $_SESSION['booking_data']['metodo_pagamento'] = 'Carta';
            
            // Salva i dati della carta nella sessione se necessario
            if (isset($_POST['carta_esistente']) && $_POST['carta_esistente'] !== 'nuova') {
                $_SESSION['booking_data']['carta_esistente'] = $_POST['carta_esistente'];
            } else {
                // Salva temporaneamente i dati della nuova carta nella sessione
                $_SESSION['booking_data']['numero_carta'] = $_POST['numero_carta'] ?? '';
                $_SESSION['booking_data']['scadenza_carta'] = $_POST['scadenza_carta'] ?? '';
                $_SESSION['booking_data']['cvv_carta'] = $_POST['cvv_carta'] ?? '';
                $_SESSION['booking_data']['nome_carta'] = $_POST['nome_carta'] ?? '';
                $_SESSION['booking_data']['tipo_carta'] = $_POST['tipo_carta'] ?? '';
                $_SESSION['booking_data']['salva_carta'] = $_POST['salva_carta'] ?? '0';
            }
        }
        
        // Vai automaticamente al step 3 (raccolta nominativi)
        $currentStep = '3';
    }

    // STEP 3.5: raccolta nominativi
    if ($step === '3.5') {
        $nominativi = $_POST['nominativi'] ?? [];
        $bookingData = $_SESSION['booking_data'] ?? null;
        
        if (!$bookingData) {
            $errore = "Dati prenotazione mancanti.";
        } elseif (count($nominativi) !== $bookingData['posti']) {
            $errore = "Devi inserire un nominativo per ogni posto prenotato.";
        } else {
            // Salva i nominativi nei dati di booking
            $_SESSION['booking_data']['nominativi'] = $nominativi;
            
            // Avanza automaticamente al step 4 dopo aver salvato i nominativi
            $currentStep = '4';
        }
    }

    // STEP 4: conferma prenotazione
    if ($step === '4') {
        // Se per qualche motivo il metodo di pagamento non è stato ancora salvato
        if (!isset($_SESSION['booking_data']['metodo_pagamento']) && isset($_POST['metodo_pagamento'])) {
            $_SESSION['booking_data']['metodo_pagamento'] = $_POST['metodo_pagamento'];
        }

        $bookingData = $_SESSION['booking_data'] ?? null;

        if (!$bookingData || empty($bookingData['metodo_pagamento'])) {
            $errore = "Metodo di pagamento mancante.";
        } elseif ($bookingData['posti'] > $evento['Posti']) {
            $errore = "Posti non disponibili.";
        } else {
            $pagamentoInfo = '';

            if ($bookingData['metodo_pagamento'] === 'Carta') {
                if (isset($bookingData['carta_esistente']) && $bookingData['carta_esistente'] !== 'nuova') {
                    $cartaId = intval($bookingData['carta_esistente']);
                    $pagamentoInfo = "Carta salvata ID: $cartaId";
                } else {
                    $numeroCarta = $myDB->real_escape_string($bookingData['numero_carta'] ?? '');
                    $scadenzaCarta = $myDB->real_escape_string($bookingData['scadenza_carta'] ?? '');
                    $cvvCarta = $myDB->real_escape_string($bookingData['cvv_carta'] ?? '');
                    $nomeCarta = $myDB->real_escape_string($bookingData['nome_carta'] ?? '');
                    $tipoCarta = $myDB->real_escape_string($bookingData['tipo_carta'] ?? '');

                    $numeroMasked = '**** **** **** ' . substr(preg_replace('/\D/', '', $numeroCarta), -4);

                    if (isset($bookingData['salva_carta']) && $bookingData['salva_carta'] === '1') {
                        $numeroHash = password_hash($numeroCarta, PASSWORD_DEFAULT);
                        $stmt = $myDB->prepare("INSERT INTO carte_credito (utente_id, numero_mascherato, numero_hash, scadenza, cvv, titolare, tipo, data_creazione) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("isssiss", $utente_id, $numeroMasked, $numeroHash, $scadenzaCarta, $cvvCarta, $nomeCarta, $tipoCarta);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $pagamentoInfo = "Nuova carta: $numeroMasked";
                }
            }

            // Inserimento prenotazione
            $nominativiJson = json_encode($bookingData['nominativi'] ?? []);
            $stmt = $myDB->prepare("
                INSERT INTO prenotazioni (evento_id, utente_id, nome, email, posti, pagamento, pagamento_info, nominativi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iissssss",
                $bookingData['evento_id'],
                $utente_id,
                $bookingData['nome'],
                $bookingData['email'],
                $bookingData['posti'],
                $bookingData['metodo_pagamento'],
                $pagamentoInfo,
                $nominativiJson
            );

            if ($stmt->execute()) {
                $booking_id = $myDB->insert_id;
                
                // Aggiorna posti disponibili
                $sqlUpdate = "UPDATE Eventi SET Posti = Posti - {$bookingData['posti']} WHERE id = {$bookingData['evento_id']}";
                $myDB->query($sqlUpdate);
                
                // Invia email di conferma
                $emailSent = inviaEmailConfermaPrenotazione(
                    $bookingData['email'],
                    $bookingData['nome'],
                    $evento,
                    $bookingData['posti'],
                    $booking_id,
                    $bookingData['metodo_pagamento']
                );
                
                $prenotazioneCompletata = true;

                // Ricarica dati evento aggiornati
                $sql = "SELECT * FROM Eventi WHERE id = $idEvento";
                $result = $myDB->query($sql);
                $evento = $result->fetch_assoc();
            } else {
                $errore = "Errore durante la prenotazione: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Determinazione dello step corrente basata sui dati presenti
$stepForView = isset($currentStep) ? $currentStep : null;
if ($stepForView === null) {
    // Se non è stato impostato da POST, calcola lo step come prima
    $bookingData = $_SESSION['booking_data'] ?? null;
    if (!isset($_POST['step']) && !isset($_SESSION['booking_data'])) {
        $stepForView = '1';
    } elseif ($bookingData) {
        if (!isset($bookingData['metodo_pagamento'])) {
            $stepForView = '2';
        } elseif (!isset($bookingData['nominativi'])) {
            $stepForView = '3';
        } else {
            $stepForView = '4';
        }
    } else {
        $stepForView = $_POST['step'] ?? '1';
    }
}
?>




<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Prenotazione - <?php echo htmlspecialchars($evento['titoloE']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="stili/stile_prenotazione.css">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="Immagini/logo.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Forza il testo scuro nei dropdown per la visibilità */
        select.form-control, select.form-control option {
            color: #000 !important;
            background-color: #fff !important;
        }
        
        /* Assicura che le opzioni siano leggibili */
        select option {
            color: #000 !important;
            background-color: #fff !important;
        }
        
        /* Checkbox personalizzato */
        .custom-checkbox {
            position: relative;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 1.2rem;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .custom-checkbox:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }
        
        .custom-checkbox input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .custom-checkbox .checkmark {
            height: 20px;
            width: 20px;
            background-color: transparent;
            border: 2px solid #667eea;
            border-radius: 4px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .custom-checkbox input:checked ~ .checkmark {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .custom-checkbox .checkmark:after {
            content: "";
            display: none;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .custom-checkbox input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-label {
            flex: 1;
            color: white;
            font-weight: 500;
        }
        
        .checkbox-label .main-text {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .checkbox-label .sub-text {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
        }
        
        .posti-counter {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Stile per il counter dei posti */
        .posti-counter small {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        #postiSelezionati {
            font-weight: bold;
            font-size: 1.1em;
            transition: color 0.3s ease;
        }
        
        /* Stili per i nominativi */
        .nominativo-field {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nominativo-field:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .nominativo-field label {
            color: #667eea;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .nominativo-field input {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: #ffffff !important;
            border-radius: 8px !important;
        }
        
        .nominativo-field input:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: #667eea !important;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
        }
        
        .nominativo-numero {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 8px;
        }
        
        /* Stili per la conferma finale */
        .confirmation-summary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .summary-item {
            color: white;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .participants-list {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0 0;
        }
        
        .participants-list li {
            padding: 0.25rem 0;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .confirmation-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-custom:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
    </style>

</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container booking-container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="booking-card">
                    <?php if ($prenotazioneCompletata): ?>
                        <div class="success-animation">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h1 class="success-title">Prenotazione Confermata!</h1>
                            <div class="success-message">
                                Grazie, <strong><?php echo htmlspecialchars($_SESSION['booking_data']['nome'] ?? ''); ?></strong>! <br>
                                Hai prenotato con successo <strong><?php echo $_SESSION['booking_data']['posti'] ?? ''; ?></strong> posto/i per <br>
                                <strong><?php echo htmlspecialchars($evento['titoloE']); ?></strong>
                                <br><br>
                                Riceverai una conferma via email all'indirizzo:<br>
                                <strong><?php echo htmlspecialchars($_SESSION['booking_data']['email'] ?? ''); ?></strong>
                            </div>
                            <a href="index.php" class="btn-home">
                                <i class="fas fa-home"></i>
                                Torna alla Home
                            </a>
                        </div>
                        <?php unset($_SESSION['booking_data']); ?>
                    <?php else: ?>
                        <!-- Header Evento -->
                        <div class="event-header">
                            <h1 class="event-title"><?php echo htmlspecialchars($evento['titoloE']); ?></h1>
                            <p class="event-subtitle">
                                <i class="fas fa-calendar-alt"></i>
                                Prenotazione Evento
                            </p>
                        </div>

                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php if ($stepForView == '1') echo 'active'; ?>">
                                <div class="step-circle">1</div>
                                <div class="step-label">Dati Personali</div>
                                <div class="step-connector"></div>
                            </div>
                            <div class="step <?php if ($stepForView == '2') echo 'active'; ?>">
                                <div class="step-circle">2</div>
                                <div class="step-label">Pagamento</div>
                                <div class="step-connector"></div>
                            </div>
                            <div class="step <?php if ($stepForView == '3') echo 'active'; ?>">
                                <div class="step-circle">3</div>
                                <div class="step-label">Nominativi</div>
                                <div class="step-connector"></div>
                            </div>
                            <div class="step <?php if ($stepForView == '4') echo 'active'; ?>">
                                <div class="step-circle">4</div>
                                <div class="step-label">Conferma</div>
                            </div>
                        </div>

                        <!-- Dettagli Evento (solo nello step 1) -->
                        <?php if ($stepForView == '1'): ?>
                        <div class="event-details">
                            <div class="detail-item">
                                <i class="fas fa-align-left detail-icon"></i>
                                <strong>Descrizione:</strong><br>
                                <?php echo nl2br(htmlspecialchars($evento['descrizione'])); ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar detail-icon"></i>
                                        <strong>Data:</strong><br>
                                        <?php echo date("d/m/Y", strtotime($evento['data'])); ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="detail-item">
                                        <i class="fas fa-clock detail-icon"></i>
                                        <strong>Orario:</strong><br>
                                        <?php echo date("H:i", strtotime($evento['ora'])); ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="detail-item">
                                        <i class="fas fa-chair detail-icon"></i>
                                        <strong>Disponibilità:</strong><br>
                                        <span class="availability-indicator <?php 
                                            $posti = $evento['Posti'];
                                            if ($posti > 20) echo 'availability-high';
                                            elseif ($posti > 5) echo 'availability-medium';
                                            else echo 'availability-low';
                                        ?>">
                                            <i class="fas fa-users"></i>
                                            <?php echo $posti; ?> posti
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Form Container -->
                        <div class="form-container">
                            <?php if (!empty($errore)): ?>
                                <div class="error-alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo $errore; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($stepForView == '1'): ?>
                                <h2 class="form-title">Inserisci i tuoi dati</h2>
                                <form method="POST">
                                    <input type="hidden" name="step" value="2">
                                    <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">

                                    <div class="form-group">
                                        <label for="nome" class="form-label text-white">
                                            <i class="fas fa-user"></i>
                                            Nome Completo
                                        </label>
                                        <input type="text" name="nome" id="nome" class="form-control" placeholder="Inserisci il tuo nome completo" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="email" class="form-label text-white">
                                            <i class="fas fa-envelope"></i>
                                            Email
                                        </label>
                                        <input type="email" name="email" id="email" class="form-control" placeholder="esempio@email.com" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="posti" class="form-label text-white">
                                            <i class="fas fa-ticket-alt"></i>
                                            Numero di Posti
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-info-circle"></i>
                                                Massimo 10 posti per prenotazione
                                            </small>
                                        </label>
                                        <input type="number" name="posti" id="posti" min="1" max="10" class="form-control" placeholder="Quanti posti vuoi prenotare? (max 10)" required>
                                        <div class="posti-counter mt-2">
                                            <small class="text-white">
                                                <span id="postiSelezionati">0</span> / 10 posti selezionati
                                            </small>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn-custom btn-primary-custom">
                                        <i class="fas fa-arrow-right me-2"></i>
                                        Continua al Pagamento
                                    </button>
                                </form>

                            <?php elseif ($stepForView == '2'): ?>
                                <h2 class="form-title">Seleziona il metodo di pagamento</h2>
                                <form method="POST" id="paymentForm">
                                    <input type="hidden" name="step" value="2.5" id="stepInput">
                                    
                                    <div class="payment-option">
                                        <label>
                                            <input type="radio" name="metodo_pagamento" value="Carta" required>
                                            <i class="fas fa-credit-card me-2"></i>Carta di Credito/Debito
                                        </label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <label>
                                            <input type="radio" name="metodo_pagamento" value="PayPal" required>
                                            <i class="fab fa-paypal me-2"></i>PayPal
                                        </label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <label>
                                            <input type="radio" name="metodo_pagamento" value="Bonifico" required>
                                            <i class="fas fa-university me-2"></i>Bonifico Bancario
                                        </label>
                                    </div>

                                    <!-- Sezione per i dettagli carta -->
                                    <div id="cardDetails" style="display: none; margin-top: 20px;">
                                        <h4 class="text-white" style="margin-bottom: 1.5rem;">
                                            <i class="fas fa-credit-card" style="color: #667eea; margin-right: 10px;"></i>
                                            Dettagli Carta
                                        </h4>
                                        
                                        <?php if (!empty($carteSalvate)): ?>
                                        <div class="form-group">
                                            <label for="carta_esistente" class="form-label text-white">
                                                <i class="fas fa-wallet"></i>
                                                Seleziona una carta salvata o aggiungi nuova
                                            </label>
                                            <select name="carta_esistente" id="carta_esistente" class="form-control">
                                                <option value="nuova">Aggiungi nuova carta</option>
                                                <?php foreach ($carteSalvate as $carta): ?>
                                                <option value="<?php echo $carta['id']; ?>">
                                                    <?php echo htmlspecialchars($carta['tipo_carta'] . ' - ' . $carta['numero_carta_masked']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>

                                        <div id="nuovaCartaFields" <?php if (!empty($carteSalvate)) echo 'style="display: none;"'; ?>>
                                            <div class="form-group">
                                                <label for="numero_carta" class="form-label text-white">
                                                    <i class="fas fa-credit-card"></i>
                                                    Numero Carta
                                                </label>
                                                <input type="text" name="numero_carta" id="numero_carta" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="scadenza_carta" class="form-label text-white">
                                                            <i class="fas fa-calendar"></i>
                                                            Scadenza
                                                        </label>
                                                        <input type="text" name="scadenza_carta" id="scadenza_carta" class="form-control" placeholder="MM/AA" maxlength="5">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="cvv_carta" class="form-label text-white">
                                                            <i class="fas fa-lock"></i>
                                                            CVV
                                                        </label>
                                                        <input type="text" name="cvv_carta" id="cvv_carta" class="form-control" placeholder="123" maxlength="4">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="nome_carta" class="form-label text-white">
                                                    <i class="fas fa-user"></i>
                                                    Nome del Titolare
                                                </label>
                                                <input type="text" name="nome_carta" id="nome_carta" class="form-control" placeholder="Nome come appare sulla carta">
                                            </div>

                                            <div class="form-group">
                                                <label for="tipo_carta" class="form-label text-white">
                                                    <i class="fas fa-tags"></i>
                                                    Tipo Carta
                                                </label>
                                                <select name="tipo_carta" id="tipo_carta" class="form-control">
                                                    <option value="">Seleziona tipo</option>
                                                    <option value="Visa">Visa</option>
                                                    <option value="MasterCard">MasterCard</option>
                                                    <option value="American Express">American Express</option>
                                                    <option value="Altro">Altro</option>
                                                </select>
                                            </div>

                                            <label class="custom-checkbox">
                                                <input type="checkbox" name="salva_carta" id="salva_carta" value="1">
                                                <span class="checkmark"></span>
                                                <div class="checkbox-label">
                                                    <div class="main-text">
                                                        <i class="fas fa-save" style="color: #667eea; margin-right: 8px;"></i>
                                                        Salva questa carta per futuri pagamenti
                                                    </div>
                                                    <div class="sub-text">
                                                        <i class="fas fa-shield-alt" style="margin-right: 5px;"></i>
                                                        I tuoi dati saranno crittografati e memorizzati in modo sicuro
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn-custom btn-success-custom" id="submitBtn">
                                        <i class="fas fa-arrow-right me-2"></i>
                                        Continua ai Nominativi
                                    </button>
                                </form>

                            <?php elseif ($stepForView == '3'): ?>
                                <h2 class="form-title">Inserisci i nominativi dei partecipanti</h2>
                                <p class="text-white mb-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Inserisci il nome completo per ogni partecipante. Questi nomi appariranno sui biglietti.
                                </p>
                                
                                <form method="POST">
                                    <input type="hidden" name="step" value="3.5">
                                    
                                    <div class="form-group">
                                        <label class="form-label text-white">
                                            <i class="fas fa-users"></i>
                                            Nominativi dei Partecipanti (<?php echo $_SESSION['booking_data']['posti'] ?? 0; ?> posti)
                                        </label>
                                        <div id="nominativiList">
                                            <?php 
                                            $postiPrenotati = $_SESSION['booking_data']['posti'] ?? 0;
                                            for ($i = 1; $i <= $postiPrenotati; $i++): 
                                            ?>
                                            <div class="nominativo-field">
                                                <label for="nominativo_<?php echo $i; ?>">
                                                    <span class="nominativo-numero"><?php echo $i; ?></span>
                                                    Partecipante <?php echo $i; ?>
                                                </label>
                                                <input type="text" 
                                                       name="nominativi[]" 
                                                       id="nominativo_<?php echo $i; ?>" 
                                                       class="form-control" 
                                                       placeholder="Nome e Cognome del partecipante <?php echo $i; ?>" 
                                                       required>
                                            </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn-custom btn-success-custom">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Continua alla Conferma
                                    </button>
                                </form>

                            <?php elseif ($stepForView == '4'): ?>
                                <h2 class="form-title">Conferma la tua prenotazione</h2>
                                <p class="text-white mb-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Controlla tutti i dettagli della tua prenotazione prima di confermare.
                                </p>
                                
                                <div class="confirmation-summary">
                                    <h4 class="text-white mb-3">
                                        <i class="fas fa-clipboard-list me-2"></i>
                                        Riepilogo Prenotazione
                                    </h4>
                                    
                                    <div class="summary-item">
                                        <strong>Evento:</strong> <?php echo htmlspecialchars($evento['titoloE']); ?>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Data:</strong> <?php echo date("d/m/Y", strtotime($evento['data'])); ?>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Orario:</strong> <?php echo date("H:i", strtotime($evento['ora'])); ?>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Nome:</strong> <?php echo htmlspecialchars($_SESSION['booking_data']['nome'] ?? ''); ?>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['booking_data']['email'] ?? ''); ?>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Posti:</strong> <?php echo $_SESSION['booking_data']['posti'] ?? 0; ?>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Metodo di pagamento:</strong> <?php echo $_SESSION['booking_data']['metodo_pagamento'] ?? ''; ?>
                                    </div>
                                    
                                    <?php if (isset($_SESSION['booking_data']['nominativi']) && !empty($_SESSION['booking_data']['nominativi'])): ?>
                                    <div class="summary-item">
                                        <strong>Partecipanti:</strong>
                                        <ul class="participants-list">
                                            <?php foreach ($_SESSION['booking_data']['nominativi'] as $index => $nominativo): ?>
                                            <li><?php echo ($index + 1) . '. ' . htmlspecialchars($nominativo); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="step" value="4">
                                    
                                    <div class="confirmation-buttons">
                                        <button type="submit" class="btn-custom btn-success-custom">
                                            <i class="fas fa-check-double me-2"></i>
                                            Conferma Prenotazione
                                        </button>
                                        <a href="?id=<?php echo $evento['id']; ?>" class="btn-custom btn-secondary-custom">
                                            <i class="fas fa-arrow-left me-2"></i>
                                            Ricomincia
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; 
    include 'cookie_banner.php';?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Validazione dinamica posti (solo se esiste l'input)
            const postiInput = document.getElementById('posti');
            if (postiInput) {
                const postiDisponibili = Math.min(<?php echo $evento['Posti']; ?>, 10);
                const postiCounter = document.getElementById('postiSelezionati');
                
                postiInput.max = postiDisponibili;
                
                postiInput.addEventListener('input', function () {
                    const value = parseInt(postiInput.value) || 0;
                    
                    if (postiCounter) {
                        postiCounter.textContent = Math.max(0, Math.min(value, 10));
                        
                        const parent = postiCounter.closest('.posti-counter');
                        if (value >= 8) {
                            parent.style.color = '#feca57';
                        } else if (value >= 5) {
                            parent.style.color = '#48dbfb';
                        } else {
                            parent.style.color = '#ffffff';
                        }
                    }
                    
                    if (value > 10) {
                        postiInput.setCustomValidity('Non puoi prenotare più di 10 posti per prenotazione.');
                        postiInput.style.borderColor = '#dc3545';
                        showPostiError('Limite massimo: 10 posti per prenotazione');
                    } else if (value > postiDisponibili) {
                        postiInput.setCustomValidity(`Non puoi prenotare più di ${postiDisponibili} posti.`);
                        postiInput.style.borderColor = '#dc3545';
                        showPostiError(`Solo ${postiDisponibili} posti disponibili`);
                    } else if (value < 1) {
                        postiInput.setCustomValidity('Devi prenotare almeno 1 posto.');
                        postiInput.style.borderColor = '#dc3545';
                        showPostiError('Minimo 1 posto richiesto');
                    } else {
                        postiInput.setCustomValidity('');
                        postiInput.style.borderColor = '#28a745';
                        hidePostiError();
                    }
                });
                
                postiInput.placeholder = `Quanti posti vuoi prenotare? (max ${postiDisponibili})`;
            }
            
            // Funzioni per mostrare/nascondere errori posti
            function showPostiError(message) {
                let errorDiv = document.getElementById('posti-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'posti-error';
                    errorDiv.style.cssText = `
                        color: #dc3545;
                        font-size: 0.875rem;
                        margin-top: 0.5rem;
                        padding: 0.5rem;
                        background-color: rgba(220, 53, 69, 0.1);
                        border: 1px solid rgba(220, 53, 69, 0.3);
                        border-radius: 6px;
                        display: flex;
                        align-items: center;
                        animation: shake 0.5s ease-in-out;
                    `;
                    const postiGroup = document.getElementById('posti').closest('.form-group');
                    postiGroup.appendChild(errorDiv);
                }
                errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>${message}`;
                errorDiv.style.display = 'flex';
            }
            
            function hidePostiError() {
                const errorDiv = document.getElementById('posti-error');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
            }

            // Gestione metodi di pagamento
            const metodiPagamento = document.querySelectorAll('input[name="metodo_pagamento"]');
            const cardDetails = document.getElementById('cardDetails');
            const stepInput = document.getElementById('stepInput');
            const submitBtn = document.getElementById('submitBtn');

            metodiPagamento.forEach(metodo => {
                metodo.addEventListener('change', function() {
                    if (this.value === 'Carta') {
                        cardDetails.style.display = 'block';
                        stepInput.value = '3';
                        submitBtn.innerHTML = '<i class="fas fa-arrow-right me-2"></i>Continua ai Nominativi';
                    } else {
                        cardDetails.style.display = 'none';
                        stepInput.value = '2.5';
                        if (this.value === 'PayPal') {
                            submitBtn.innerHTML = '<i class="fab fa-paypal me-2"></i>Procedi con PayPal';
                        } else if (this.value === 'Bonifico') {
                            submitBtn.innerHTML = '<i class="fas fa-university me-2"></i>Procedi con Bonifico';
                        }
                    }
                });
            });

            // Gestione carte salvate
            const cartaEsistente = document.getElementById('carta_esistente');
            const nuovaCartaFields = document.getElementById('nuovaCartaFields');
            
            if (cartaEsistente) {
                cartaEsistente.addEventListener('change', function() {
                    if (this.value === 'nuova') {
                        nuovaCartaFields.style.display = 'block';
                        document.getElementById('numero_carta').required = true;
                        document.getElementById('scadenza_carta').required = true;
                        document.getElementById('cvv_carta').required = true;
                        document.getElementById('nome_carta').required = true;
                        document.getElementById('tipo_carta').required = true;
                    } else {
                        nuovaCartaFields.style.display = 'none';
                        document.getElementById('numero_carta').required = false;
                        document.getElementById('scadenza_carta').required = false;
                        document.getElementById('cvv_carta').required = false;
                        document.getElementById('nome_carta').required = false;
                        document.getElementById('tipo_carta').required = false;
                    }
                });
            }

            // Validazione form step 1 (dati personali)
            const dataForm = document.querySelector('form[method="POST"]');
            if (dataForm && dataForm.querySelector('input[name="step"][value="2"]')) {
                dataForm.addEventListener('submit', function(e) {
                    const postiInput = document.getElementById('posti');
                    if (postiInput) {
                        const posti = parseInt(postiInput.value) || 0;
                        if (posti < 1) {
                            e.preventDefault();
                            postiInput.focus();
                            showPostiError('Devi prenotare almeno 1 posto');
                            alert('Devi prenotare almeno 1 posto per continuare.');
                            return false;
                        }
                        if (posti > 10) {
                            e.preventDefault();
                            postiInput.focus();
                            showPostiError('Massimo 10 posti per prenotazione');
                            alert('Non puoi prenotare più di 10 posti per prenotazione.');
                            return false;
                        }
                    }
                });
            }
            
            // Validazione form step 3 (nominativi)
            const nominativiForm = document.querySelector('form input[name="step"][value="3.5"]');
            if (nominativiForm) {
                const form = nominativiForm.closest('form');
                form.addEventListener('submit', function(e) {
                    const nominativi = document.querySelectorAll('input[name="nominativi[]"]');
                    
                    for (let i = 0; i < nominativi.length; i++) {
                        const nominativo = nominativi[i].value.trim();
                        if (!nominativo) {
                            e.preventDefault();
                            nominativi[i].focus();
                            nominativi[i].style.borderColor = '#dc3545';
                            alert(`Per favore inserisci il nome del partecipante ${i + 1}.`);
                            return false;
                        }
                        if (nominativo.length < 2) {
                            e.preventDefault();
                            nominativi[i].focus();
                            nominativi[i].style.borderColor = '#dc3545';
                            alert(`Il nome del partecipante ${i + 1} deve contenere almeno 2 caratteri.`);
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
