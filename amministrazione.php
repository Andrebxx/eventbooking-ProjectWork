<?php
// Mantiene la logica esistente di controllo accesso e recupero dati
session_start();
include_once("password.php");

// Verifica che l'utente sia loggato e sia admin
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Controlla se l'utente è admin
$utente_id = $_SESSION['id'];
$stmt = $myDB->prepare("SELECT is_admin FROM utenti WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $utente_id);
    $stmt->execute();
    $stmt->bind_result($is_admin);
    $stmt->fetch();
    $stmt->close();
} else {
    // Se la query fallisce, considera l'utente non admin per sicurezza
    $is_admin = false;
}

// Se non è admin, reindirizza alla home con un messaggio
if (!$is_admin) {
    $_SESSION['errore'] = "Accesso negato: è richiesto un account amministratore.";
    header("Location: index.php");
    exit;
}

// Statistiche del sito
$stats = [
    'utenti_totali' => 0,
    'eventi_totali' => 0,
    'prenotazioni_totali' => 0,
    'incasso_totale' => 0,
];

// Conta utenti
$result = $myDB->query("SELECT COUNT(*) AS total FROM utenti");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['utenti_totali'] = $row['total'];
}

// Conta eventi
$result = $myDB->query("SELECT COUNT(*) AS total FROM Eventi");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['eventi_totali'] = $row['total'];
}

// Conta prenotazioni
$result = $myDB->query("SELECT COUNT(*) AS total FROM prenotazioni");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['prenotazioni_totali'] = $row['total'];
}

// Calcola incasso reale (da prenotazioni confermate per eventi passati)
$incassoReale = 0;
$result = $myDB->query("
    SELECT SUM(p.posti * e.prezzo) as totale 
    FROM prenotazioni p
    JOIN Eventi e ON p.evento_id = e.id
    WHERE e.data < CURDATE()
");
if ($result && $row = $result->fetch_assoc()) {
    $incassoReale = $row['totale'] ?: 0;
}

// Calcola incasso previsto (da prenotazioni per eventi futuri)
$incassoPrevisto = 0;
$result = $myDB->query("
    SELECT SUM(p.posti * e.prezzo) as totale 
    FROM prenotazioni p
    JOIN Eventi e ON p.evento_id = e.id
    WHERE e.data >= CURDATE()
");
if ($result && $row = $result->fetch_assoc()) {
    $incassoPrevisto = $row['totale'] ?: 0;
}

// Calcola incasso totale
$result = $myDB->query("SELECT SUM(p.posti * e.prezzo) AS totale FROM prenotazioni p JOIN Eventi e ON p.evento_id = e.id");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['incasso_totale'] = $row['totale'] ?: 0;
}

// Aggiungi alle statistiche
$stats['incasso_reale'] = $incassoReale;
$stats['incasso_previsto'] = $incassoPrevisto;

// Gestisci operazioni CRUD se necessario
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_event':
                // Aggiungi nuovo evento
                $titolo = $myDB->real_escape_string($_POST['titolo']);
                $descrizione = $myDB->real_escape_string($_POST['descrizione']);
                $data = $myDB->real_escape_string($_POST['data']);
                $ora = $myDB->real_escape_string($_POST['ora'] ?? '20:00');
                $posti = intval($_POST['posti']);
                $prezzo = floatval($_POST['prezzo']);
                
                // Gestione dell'immagine
                $immagine = "default_event.jpg"; // Valore predefinito
                $target_dir = "Immagini/Eventi/";
                
                // Crea la directory se non esiste
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                // Crea un'immagine di default se non esiste
                $default_image_path = $target_dir . "default_event.jpg";
                if (!file_exists($default_image_path)) {
                    // Crea un'immagine placeholder semplice
                    $width = 400;
                    $height = 300;
                    $image = imagecreate($width, $height);
                    
                    // Colori
                    $bg_color = imagecolorallocate($image, 240, 240, 240);
                    $text_color = imagecolorallocate($image, 100, 100, 100);
                    
                    // Testo
                    $text = "Immagine\nEvento";
                    imagestring($image, 5, ($width/2)-50, ($height/2)-20, $text, $text_color);
                    
                    // Salva l'immagine
                    imagejpeg($image, $default_image_path, 90);
                    imagedestroy($image);
                }
                
                if(isset($_FILES['immagine']) && $_FILES['immagine']['error'] === 0) {
                    $file_extension = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    // Verifica estensione file
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $message = "Formato file non supportato. Usa JPG, PNG, GIF o WEBP.";
                        $messageType = "danger";
                        break;
                    }
                    
                    // Verifica dimensione file (max 5MB)
                    if ($_FILES['immagine']['size'] > 5 * 1024 * 1024) {
                        $message = "Il file è troppo grande. Dimensione massima: 5MB.";
                        $messageType = "danger";
                        break;
                    }
                    
                    $new_filename = 'evento_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    // Verifica se l'immagine è effettivamente un'immagine
                    $check = getimagesize($_FILES['immagine']['tmp_name']);
                    if($check !== false) {
                        if (move_uploaded_file($_FILES['immagine']['tmp_name'], $target_file)) {
                            $immagine = $new_filename;
                        } else {
                            $message = "Errore durante il caricamento dell'immagine.";
                            $messageType = "warning";
                        }
                    } else {
                        $message = "Il file selezionato non è un'immagine valida.";
                        $messageType = "danger";
                        break;
                    }
                }
                
                $stmt = $myDB->prepare("INSERT INTO Eventi (titoloE, descrizione, data, ora, Posti, Immagine, prezzo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    $stmt->bind_param("ssssisd", $titolo, $descrizione, $data, $ora, $posti, $immagine, $prezzo);
                    
                    if ($stmt->execute()) {
                        $message = "Evento aggiunto con successo!";
                        $messageType = "success";
                    } else {
                        $message = "Errore durante l'aggiunta dell'evento: " . $stmt->error;
                        $messageType = "danger";
                    }
                    $stmt->close();
                } else {
                    $message = "Errore nella preparazione della query: " . $myDB->error;
                    $messageType = "danger";
                }
                break;
                
            case 'edit_event':
                // Modifica evento esistente
                $event_id = intval($_POST['event_id']);
                $titolo = $myDB->real_escape_string($_POST['titolo']);
                $descrizione = $myDB->real_escape_string($_POST['descrizione']);
                $data = $myDB->real_escape_string($_POST['data']);
                $ora = $myDB->real_escape_string($_POST['ora'] ?? '20:00'); // Ora con valore di default
                $posti = intval($_POST['posti']);
                $prezzo = floatval($_POST['prezzo']); // Nuovo campo prezzo
                
                // Controlla se c'è una nuova immagine
                if(isset($_FILES['immagine']) && $_FILES['immagine']['error'] === 0) {
                    $target_dir = "Immagini/Eventi/";
                    
                    // Crea la directory se non esiste
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    // Verifica estensione file
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $message = "Formato file non supportato. Usa JPG, PNG, GIF o WEBP.";
                        $messageType = "danger";
                        break;
                    }
                    
                    // Verifica dimensione file (max 5MB)
                    if ($_FILES['immagine']['size'] > 5 * 1024 * 1024) {
                        $message = "Il file è troppo grande. Dimensione massima: 5MB.";
                        $messageType = "danger";
                        break;
                    }
                    
                    $new_filename = 'evento_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    // Verifica se l'immagine è effettivamente un'immagine
                    $check = getimagesize($_FILES['immagine']['tmp_name']);
                    if($check !== false) {
                        if (move_uploaded_file($_FILES['immagine']['tmp_name'], $target_file)) {
                            // Elimina la vecchia immagine se non è quella di default
                            $old_image_stmt = $myDB->prepare("SELECT Immagine FROM Eventi WHERE id = ?");
                            if ($old_image_stmt) {
                                $old_image_stmt->bind_param("i", $event_id);
                                $old_image_stmt->execute();
                                $old_image_stmt->bind_result($old_image);
                                $old_image_stmt->fetch();
                                $old_image_stmt->close();
                                
                                if (!empty($old_image) && $old_image !== 'default_event.jpg' && file_exists($target_dir . $old_image)) {
                                    unlink($target_dir . $old_image);
                                }
                            }
                            
                            // Aggiorna con la nuova immagine
                            $stmt = $myDB->prepare("UPDATE Eventi SET titoloE = ?, descrizione = ?, data = ?, ora = ?, Posti = ?, Immagine = ?, prezzo = ? WHERE id = ?");
                            if ($stmt) {
                                $stmt->bind_param("ssssisdi", $titolo, $descrizione, $data, $ora, $posti, $new_filename, $prezzo, $event_id);
                            }
                        } else {
                            $message = "Errore durante il caricamento della nuova immagine.";
                            $messageType = "warning";
                        }
                    } else {
                        $message = "Il file selezionato non è un'immagine valida.";
                        $messageType = "danger";
                        break;
                    }
                } else {
                    // Aggiorna senza modificare l'immagine
                    $stmt = $myDB->prepare("UPDATE Eventi SET titoloE = ?, descrizione = ?, data = ?, ora = ?, Posti = ?, prezzo = ? WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ssssidi", $titolo, $descrizione, $data, $ora, $posti, $prezzo, $event_id);
                    }
                }
                
                if ($stmt && $stmt->execute()) {
                    $message = "Evento aggiornato con successo!";
                    $messageType = "success";
                } else {
                    if ($stmt) {
                        $message = "Errore durante l'aggiornamento dell'evento: " . $stmt->error;
                    } else {
                        $message = "Errore nella preparazione della query: " . $myDB->error;
                    }
                    $messageType = "danger";
                }
                if ($stmt) {
                    $stmt->close();
                }
                break;
            
            case 'delete_event':
                if (isset($_POST['event_id'])) {
                    $event_id = intval($_POST['event_id']);
                    
                    // Prima recupera l'immagine associata all'evento
                    $immagine_da_eliminare = '';
                    $stmt = $myDB->prepare("SELECT Immagine FROM Eventi WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $event_id);
                        $stmt->execute();
                        $stmt->bind_result($immagine_da_eliminare);
                        $stmt->fetch();
                        $stmt->close();
                    }
                    
                    // Elimina tutte le prenotazioni associate all'evento
                    $stmt = $myDB->prepare("DELETE FROM prenotazioni WHERE evento_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $event_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    // Elimina l'evento dal database
                    $stmt = $myDB->prepare("DELETE FROM Eventi WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $event_id);
                        
                        if ($stmt->execute()) {
                            if ($stmt->affected_rows > 0) {
                                // Se l'evento è stato eliminato con successo, elimina anche l'immagine
                                if (!empty($immagine_da_eliminare) && $immagine_da_eliminare !== 'default_event.jpg') {
                                    $percorso_immagine = "Immagini/Eventi/" . $immagine_da_eliminare;
                                    if (file_exists($percorso_immagine)) {
                                        if (unlink($percorso_immagine)) {
                                            $message = "Evento e immagine eliminati con successo.";
                                        } else {
                                            $message = "Evento eliminato con successo, ma errore nell'eliminazione dell'immagine.";
                                        }
                                    } else {
                                        $message = "Evento eliminato con successo (immagine non trovata).";
                                    }
                                } else {
                                    $message = "Evento eliminato con successo.";
                                }
                                $messageType = "success";
                            } else {
                                $message = "Evento non trovato o già eliminato.";
                                $messageType = "warning";
                            }
                        } else {
                            $message = "Errore durante l'eliminazione dell'evento: " . $stmt->error;
                            $messageType = "danger";
                        }
                        $stmt->close();
                    } else {
                        $message = "Errore nella preparazione della query: " . $myDB->error;
                        $messageType = "danger";
                    }
                }
                break;
                
            case 'delete_user':
                if (isset($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    
                    // Verifica che l'admin non possa eliminare se stesso
                    if ($user_id == $utente_id) {
                        $message = "Non puoi eliminare il tuo account.";
                        $messageType = "warning";
                    } else {
                        // Verifica se l'utente da eliminare è l'admin principale
                        $checkStmt = $myDB->prepare("SELECT nome FROM utenti WHERE id = ?");
                        if ($checkStmt) {
                            $checkStmt->bind_param("i", $user_id);
                            $checkStmt->execute();
                            $result = $checkStmt->get_result();
                            $userToDelete = $result->fetch_assoc();
                            $checkStmt->close();
                            
                            if ($userToDelete && strtolower(trim($userToDelete['nome'])) === 'admin') {
                                $message = "Non è possibile eliminare l'utente amministratore principale.";
                                $messageType = "danger";
                            } else {
                                // Prima elimina tutte le prenotazioni associate all'utente
                                $stmt = $myDB->prepare("DELETE FROM prenotazioni WHERE utente_id = ?");
                                if ($stmt) {
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                                
                                // Poi elimina l'utente
                                $stmt = $myDB->prepare("DELETE FROM utenti WHERE id = ?");
                                if ($stmt) {
                                    $stmt->bind_param("i", $user_id);
                                    
                                    if ($stmt->execute()) {
                                        if ($stmt->affected_rows > 0) {
                                            $message = "Utente eliminato con successo.";
                                            $messageType = "success";
                                        } else {
                                            $message = "Utente non trovato o già eliminato.";
                                            $messageType = "warning";
                                        }
                                    } else {
                                        $message = "Errore durante l'eliminazione dell'utente: " . $stmt->error;
                                        $messageType = "danger";
                                    }
                                    $stmt->close();
                                } else {
                                    $message = "Errore nella preparazione della query: " . $myDB->error;
                                    $messageType = "danger";
                                }
                            }
                        } else {
                            $message = "Errore nella verifica dell'utente: " . $myDB->error;
                            $messageType = "danger";
                        }
                    }
                }
                break;
                
            case 'edit_user':
                if (isset($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $nome = $myDB->real_escape_string($_POST['nome']);
                    $cognome = $myDB->real_escape_string($_POST['cognome']);
                    $email = $myDB->real_escape_string($_POST['email']);
                    $telefono = $myDB->real_escape_string($_POST['telefono'] ?? '');
                    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                    
                    // Verifica che l'admin non possa rimuovere i privilegi a se stesso
                    if ($user_id == $utente_id && $is_admin == 0) {
                        $message = "Non puoi rimuovere i privilegi di amministratore a te stesso.";
                        $messageType = "warning";
                    } else {
                        $stmt = $myDB->prepare("UPDATE utenti SET nome = ?, cognome = ?, email = ?, telefono = ?, is_admin = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("ssssii", $nome, $cognome, $email, $telefono, $is_admin, $user_id);
                            
                            if ($stmt->execute()) {
                                if ($stmt->affected_rows > 0) {
                                    $message = "Utente aggiornato con successo.";
                                    $messageType = "success";
                                } else {
                                    $message = "Nessuna modifica apportata o utente non trovato.";
                                    $messageType = "info";
                                }
                            } else {
                                $message = "Errore durante l'aggiornamento dell'utente: " . $stmt->error;
                                $messageType = "danger";
                            }
                            $stmt->close();
                        } else {
                            $message = "Errore nella preparazione della query: " . $myDB->error;
                            $messageType = "danger";
                        }
                    }
                }
                break;
                
            case 'delete_booking':
                if (isset($_POST['booking_id'])) {
                    $booking_id = intval($_POST['booking_id']);
                    $stmt = $myDB->prepare("DELETE FROM prenotazioni WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $booking_id);
                        
                        if ($stmt->execute()) {
                            if ($stmt->affected_rows > 0) {
                                $message = "Prenotazione eliminata con successo.";
                                $messageType = "success";
                            } else {
                                $message = "Prenotazione non trovata o già eliminata.";
                                $messageType = "warning";
                            }
                        } else {
                            $message = "Errore durante l'eliminazione della prenotazione: " . $stmt->error;
                            $messageType = "danger";
                        }
                        $stmt->close();
                    } else {
                        $message = "Errore nella preparazione della query: " . $myDB->error;
                        $messageType = "danger";
                    }
                }
                break;
        }
    }
}

// Recupera eventi
$eventi = [];
$result = $myDB->query("SELECT * FROM Eventi ORDER BY data DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $eventi[] = $row;
    }
}

// Recupera utenti
$utenti = [];
$result = $myDB->query("SELECT * FROM utenti ORDER BY nome ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $utenti[] = $row;
    }
}

// Recupera prenotazioni
$prenotazioni = [];
$result = $myDB->query("
    SELECT p.*, e.titoloE, e.prezzo, u.nome AS nome_utente, u.email AS email_utente
    FROM prenotazioni p
    LEFT JOIN Eventi e ON p.evento_id = e.id
    LEFT JOIN utenti u ON p.utente_id = u.id
    ORDER BY p.data_prenotazione DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $prenotazioni[] = $row;
    }
}

// Ottieni dati per il grafico degli incassi mensili
$mesi = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
$incassiPerMese = array_fill(0, 12, 0);

$result = $myDB->query("
    SELECT 
        MONTH(e.data) AS mese,
        YEAR(e.data) AS anno,
        SUM(p.posti * e.prezzo) AS totale
    FROM prenotazioni p
    JOIN Eventi e ON p.evento_id = e.id
    WHERE e.data <= CURDATE()
    GROUP BY YEAR(e.data), MONTH(e.data)
    ORDER BY anno, mese
    LIMIT 12
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mese = intval($row['mese']) - 1; // Converte da 1-12 a 0-11 per l'indice array
        $incassiPerMese[$mese] = floatval($row['totale']);
    }
}

// Ottieni dati per il grafico degli eventi più popolari
$eventiPopolari = [];
$result = $myDB->query("
    SELECT e.titoloE, COUNT(p.id) as prenotazioni
    FROM Eventi e
    LEFT JOIN prenotazioni p ON e.id = p.evento_id
    GROUP BY e.id
    ORDER BY prenotazioni DESC
    LIMIT 5
");

$nomiEventi = [];
$prenotazioniEventi = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $nomiEventi[] = $row['titoloE'];
        $prenotazioniEventi[] = $row['prenotazioni'];
    }
}

// Ottieni dati per il grafico delle registrazioni utenti
$registrazioniMensili = array_fill(0, 6, 0); // ultimi 6 mesi

$result = $myDB->query("
    SELECT 
        MONTH(creato_il) AS mese,
        YEAR(creato_il) AS anno,
        COUNT(*) AS totale
    FROM utenti
    WHERE creato_il >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(creato_il), MONTH(creato_il)
    ORDER BY anno, mese
");

$mesiRecenti = [];
for ($i = 5; $i >= 0; $i--) {
    $data = new DateTime();
    $data->modify("-$i month");
    $mesiRecenti[] = $data->format('M');
}

$registrazioniDati = array_fill(0, 6, 0);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $diff = abs((date('Y') - $row['anno']) * 12 + (date('n') - $row['mese']));
        if ($diff < 6) {
            $registrazioniDati[5 - $diff] = $row['totale'];
        }
    }
}

// Ottieni dati per il grafico a torta delle categorie eventi
$categorieEventi = [
    'Concerti' => 0,
    'Teatro' => 0,
    'Festival' => 0,
    'Fiere' => 0,
    'Altri' => 0
];

foreach ($eventi as $evento) {
    $titolo = strtolower($evento['titoloE']);
    
    if (strpos($titolo, 'concerto') !== false) {
        $categorieEventi['Concerti']++;
    } elseif (strpos($titolo, 'teatro') !== false || strpos($titolo, 'opera') !== false) {
        $categorieEventi['Teatro']++;
    } elseif (strpos($titolo, 'festival') !== false) {
        $categorieEventi['Festival']++;
    } elseif (strpos($titolo, 'fiera') !== false || strpos($titolo, 'expo') !== false) {
        $categorieEventi['Fiere']++;
    } else {
        $categorieEventi['Altri']++;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Pannello Amministrazione</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="stili/stile_amministrazione.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/x-icon" href="Immagini/logo.ico">
    <!-- Aggiungi Chart.js per i grafici -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>
<body>
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
    
    <!-- Toggle Sidebar Button for mobile -->
    <button id="sidebarToggle" class="d-md-none">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-music"></i>
                    <h3>Pannello Admin</h3>
                </a>
            </div>
            
            <div class="sidebar-menu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="#dashboard" class="nav-link active" data-bs-toggle="tab" data-bs-target="#dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#events" class="nav-link" data-bs-toggle="tab" data-bs-target="#events">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#users" class="nav-link" data-bs-toggle="tab" data-bs-target="#users">
                            <i class="fas fa-users"></i>
                            <span>Utenti</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#bookings" class="nav-link" data-bs-toggle="tab" data-bs-target="#bookings">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Prenotazioni</span>
                        </a>
                    </li>
                    <li class="nav-item mt-5">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home"></i>
                            <span>Torna al sito</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="content-area">
            <!-- Header -->
            <div class="admin-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1>Dashboard Amministrazione</h1>
                        <p>Benvenuto nel pannello di controllo</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Aggiorna dati
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--primary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-info">
                        <h2 class="stats-number"><?php echo number_format($stats['utenti_totali']); ?></h2>
                        <p class="stats-label">Utenti Registrati</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--success);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stats-info">
                        <h2 class="stats-number"><?php echo number_format($stats['eventi_totali']); ?></h2>
                        <p class="stats-label">Eventi Totali</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--warning);">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stats-info">
                        <h2 class="stats-number"><?php echo number_format($stats['prenotazioni_totali']); ?></h2>
                        <p class="stats-label">Prenotazioni</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--info);">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stats-info">
                        <h2 class="stats-number"><?php echo number_format($stats['incasso_totale'], 2); ?>€</h2>
                        <p class="stats-label">Incasso Totale</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--danger);">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stats-info">
                        <h2 class="stats-number"><?php echo number_format($stats['incasso_reale'], 2); ?>€</h2>
                        <p class="stats-label">Incasso Realizzato</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon" style="background-color: var(--success);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stats-info">
                        <h2 class="stats-number"><?php echo number_format($stats['incasso_previsto'], 2); ?>€</h2>
                        <p class="stats-label">Incasso Previsto</p>
                    </div>
                </div>
            </div>
            
            <!-- Content Tabs -->
            <div class="content-card">
                <div class="tab-content" id="adminTabContent">
                    <!-- Dashboard Tab (predefinita) -->
                    <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                        <div class="dashboard-tab">
                            <h3 class="mb-4">Analisi Dati & Statistiche</h3>
                            
                            <div class="chart-row">
                                <div class="chart-container">
                                    <h4><i class="fas fa-chart-line me-2"></i>Tendenza Registrazioni</h4>
                                    <canvas id="registrationsChart"></canvas>
                                </div>
                                <div class="chart-container">
                                    <h4><i class="fas fa-chart-pie me-2"></i>Distribuzione Prenotazioni</h4>
                                    <canvas id="bookingsChart"></canvas>
                                </div>
                            </div>
                            
                            <div class="chart-row">
                                <div class="chart-container">
                                    <h4><i class="fas fa-chart-bar me-2"></i>Eventi più Popolari</h4>
                                    <canvas id="eventsChart"></canvas>
                                </div>
                                <div class="chart-container">
                                    <h4><i class="fas fa-euro-sign me-2"></i>Incassi Mensili</h4>
                                    <canvas id="incomeChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Eventi Tab -->
                    <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
                        <div class="card-header">
                            <h3>Gestione Eventi</h3>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                <i class="fas fa-plus me-2"></i>Nuovo Evento
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Immagine</th>
                                            <th>Titolo</th>
                                            <th>Data</th>
                                            <th>Ora</th>
                                            <th>Posti</th>
                                            <th>Prezzo</th>
                                            <th class="text-end">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eventi as $evento): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($evento['id']); ?></td>
                                            <td>
                                                <div class="event-image">
                                                    <?php 
                                                    $imagePath = "Immagini/Eventi/" . ($evento['Immagine'] ?? 'default_event.jpg');
                                                    if (!file_exists($imagePath)) {
                                                        $imagePath = "Immagini/Eventi/default_event.jpg";
                                                    }
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                                         alt="<?php echo htmlspecialchars($evento['titoloE']); ?>"
                                                         onerror="this.src='Immagini/Eventi/default_event.jpg'"
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                                </div>
                                            </td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($evento['titoloE']); ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-calendar-day me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($evento['data'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo htmlspecialchars($evento['ora']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $evento['Posti'] > 10 ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                    <?php echo htmlspecialchars($evento['Posti']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo number_format($evento['prezzo'], 2); ?> €
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-primary btn-sm action-btn edit-event-btn" 
                                                        data-id="<?php echo $evento['id']; ?>"
                                                        data-titolo="<?php echo htmlspecialchars($evento['titoloE']); ?>"
                                                        data-descrizione="<?php echo htmlspecialchars($evento['descrizione']); ?>"
                                                        data-data="<?php echo htmlspecialchars($evento['data']); ?>"
                                                        data-ora="<?php echo htmlspecialchars($evento['ora']); ?>"
                                                        data-posti="<?php echo htmlspecialchars($evento['Posti']); ?>"
                                                        data-prezzo="<?php echo htmlspecialchars($evento['prezzo']); ?>"
                                                        data-immagine="<?php echo htmlspecialchars($evento['Immagine'] ?? ''); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
                                                    <input type="hidden" name="action" value="delete_event">
                                                    <input type="hidden" name="event_id" value="<?php echo $evento['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm action-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($eventi)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-calendar-times fa-2x mb-3"></i>
                                                    <p>Nessun evento trovato</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Utenti Tab -->
                    <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <div class="card-header">
                            <h3>Gestione Utenti</h3>
                            <div>
                                <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Cerca utenti..." style="width: 250px;">
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Utente</th>
                                            <th>Email</th>
                                            <th>Telefono</th>
                                            <th>Ruolo</th>
                                            <th>Registrato il</th>
                                            <th class="text-end">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($utenti as $utente): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($utente['id']); ?></td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?php 
                                                        $fotoPath = "Immagini/foto_utenti/" . ($utente['foto_profilo'] ?? 'default.jpg');
                                                        
                                                        // Verifica se il file esiste, altrimenti usa l'immagine di default
                                                        if (!file_exists($fotoPath) || empty($utente['foto_profilo']) || $utente['foto_profilo'] === 'default.jpg') {
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
                                                                $width = 100;
                                                                $height = 100;
                                                                $image = imagecreate($width, $height);
                                                                
                                                                // Colori
                                                                $bg_color = imagecolorallocate($image, 200, 200, 200);
                                                                $text_color = imagecolorallocate($image, 100, 100, 100);
                                                                
                                                                // Icona utente stilizzata
                                                                imagefilledellipse($image, 50, 35, 30, 30, $text_color); // Testa
                                                                imagefilledellipse($image, 50, 75, 60, 40, $text_color); // Corpo
                                                                
                                                                // Salva l'immagine
                                                                imagejpeg($image, $default_path, 90);
                                                                imagedestroy($image);
                                                            }
                                                            $fotoPath = $default_path;
                                                        }
                                                        ?>
                                                        
                                                        <?php if (file_exists($fotoPath)): ?>
                                                            <img src="<?php echo htmlspecialchars($fotoPath); ?>" 
                                                                 alt="Foto profilo di <?php echo htmlspecialchars($utente['nome']); ?>"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                                 style="width: 36px; height: 36px; object-fit: cover; border-radius: 50%;">
                                                            <div style="display: none; width: 36px; height: 36px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                                                <?php echo strtoupper(substr($utente['nome'], 0, 1)); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                                                                <?php echo strtoupper(substr($utente['nome'], 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="fw-medium"><?php echo htmlspecialchars($utente['nome'] . ' ' . ($utente['cognome'] ?? '')); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($utente['email']); ?></td>
                                            <td>
                                                <?php if (!empty($utente['telefono'])): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-phone me-1"></i>
                                                        <?php echo htmlspecialchars($utente['telefono']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($utente['is_admin']) && $utente['is_admin']): ?>
                                                    <span class="badge bg-warning text-dark">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Utente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo isset($utente['creato_il']) ? date('d/m/Y', strtotime($utente['creato_il'])) : 'N/D'; ?>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-primary btn-sm action-btn edit-user-btn"
                                                        data-id="<?php echo $utente['id']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($utente['nome']); ?>"
                                                        data-cognome="<?php echo htmlspecialchars($utente['cognome'] ?? ''); ?>"
                                                        data-email="<?php echo htmlspecialchars($utente['email']); ?>"
                                                        data-telefono="<?php echo htmlspecialchars($utente['telefono'] ?? ''); ?>"
                                                        data-admin="<?php echo $utente['is_admin'] ? '1' : '0'; ?>"
                                                        title="Modifica utente">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $utente['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm action-btn" 
                                                            <?php 
                                                            if ($utente['id'] == $utente_id) {
                                                                echo 'disabled title="Non puoi eliminare il tuo account"';
                                                            } elseif (strtolower(trim($utente['nome'])) === 'admin') {
                                                                echo 'disabled title="Non è possibile eliminare l\'utente amministratore principale"';
                                                            }
                                                            ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($utenti)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-users-slash fa-2x mb-3"></i>
                                                    <p>Nessun utente trovato</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Prenotazioni Tab -->
                    <div class="tab-pane fade" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                        <div class="card-header">
                            <h3>Gestione Prenotazioni</h3>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Filtra
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                    <li><a class="dropdown-item" href="#">Tutte le prenotazioni</a></li>
                                    <li><a class="dropdown-item" href="#">Ultimi 7 giorni</a></li>
                                    <li><a class="dropdown-item" href="#">Ultimo mese</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Evento</th>
                                            <th>Utente</th>
                                            <th>Posti</th>
                                            <th>Importo</th>
                                            <th>Pagamento</th>
                                            <th>Data</th>
                                            <th class="text-end">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prenotazioni as $prenotazione): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prenotazione['id']); ?></td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($prenotazione['titoloE'] ?? 'N/D'); ?></td>
                                            <td><?php echo htmlspecialchars($prenotazione['nome_utente'] ?? 'N/D'); ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($prenotazione['posti']); ?> posti
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo number_format($prenotazione['posti'] * $prenotazione['prezzo'], 2); ?> €
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($prenotazione['pagamento'] === 'Carta'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-credit-card me-1"></i>
                                                        <?php echo htmlspecialchars($prenotazione['pagamento']); ?>
                                                    </span>
                                                <?php elseif ($prenotazione['pagamento'] === 'PayPal'): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fab fa-paypal me-1"></i>
                                                        <?php echo htmlspecialchars($prenotazione['pagamento']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($prenotazione['pagamento']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    <?php echo isset($prenotazione['data_prenotazione']) ? date('d/m/Y H:i', strtotime($prenotazione['data_prenotazione'])) : 'N/D'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-info btn-sm action-btn view-booking-btn" 
                                                        data-id="<?php echo $prenotazione['id']; ?>"
                                                        title="Visualizza dettagli">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Sei sicuro di voler eliminare questa prenotazione?');">
                                                    <input type="hidden" name="action" value="delete_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo $prenotazione['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm action-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($prenotazioni)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-ticket-alt fa-2x mb-3"></i>
                                                    <p>Nessuna prenotazione trovata</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Aggiungi Evento -->
    <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addEventModalLabel">Aggiungi Nuovo Evento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_event">
                        <div class="mb-3">
                            <label for="titolo" class="form-label">Titolo dell'evento</label>
                            <input type="text" class="form-control" id="titolo" name="titolo" required>
                        </div>
                        <div class="mb-3">
                            <label for="descrizione" class="form-label">Descrizione</label>
                            <textarea class="form-control" id="descrizione" name="descrizione" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data" class="form-label">Data</label>
                                <input type="date" class="form-control" id="data" name="data" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ora" class="form-label">Ora</label>
                                <input type="time" class="form-control" id="ora" name="ora" value="20:00" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="posti" class="form-label">Posti disponibili</label>
                                <input type="number" class="form-control" id="posti" name="posti" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prezzo" class="form-label">Prezzo per biglietto (€)</label>
                                <input type="number" class="form-control" id="prezzo" name="prezzo" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="immagine" class="form-label">Immagine dell'evento</label>
                            <input type="file" class="form-control" id="immagine" name="immagine" accept="image/*">
                            <div class="form-text">Dimensione massima: 5MB. Formati supportati: JPG, JPEG, PNG, GIF, WEBP</div>
                            <div class="mt-2">
                                <small class="text-muted">Anteprima:</small>
                                <div id="imagePreview" style="margin-top: 10px; display: none;">
                                    <img id="previewImg" src="" alt="Anteprima" style="max-width: 200px; max-height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modifica Evento -->
    <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editEventModalLabel">Modifica Evento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_event">
                        <input type="hidden" name="event_id" id="edit_event_id">
                        <div class="mb-3">
                            <label for="edit_titolo" class="form-label">Titolo dell'evento</label>
                            <input type="text" class="form-control" id="edit_titolo" name="titolo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descrizione" class="form-label">Descrizione</label>
                            <textarea class="form-control" id="edit_descrizione" name="descrizione" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_data" class="form-label">Data</label>
                                <input type="date" class="form-control" id="edit_data" name="data" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_ora" class="form-label">Ora</label>
                                <input type="time" class="form-control" id="edit_ora" name="ora" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_posti" class="form-label">Posti disponibili</label>
                                <input type="number" class="form-control" id="edit_posti" name="posti" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_prezzo" class="form-label">Prezzo per biglietto (€)</label>
                                <input type="number" class="form-control" id="edit_prezzo" name="prezzo" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_immagine" class="form-label">Immagine dell'evento</label>
                            <input type="file" class="form-control" id="edit_immagine" name="immagine" accept="image/*">
                            <div class="form-text">Lascia vuoto per mantenere l'immagine attuale. Dimensione massima: 5MB.</div>
                            <div class="mt-2">
                                <small class="text-muted">Immagine attuale:</small>
                                <div id="currentImagePreview" style="margin-top: 10px;">
                                    <img id="currentImg" src="" alt="Immagine attuale" style="max-width: 200px; max-height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                                <small class="text-muted">Nuova anteprima:</small>
                                <div id="editImagePreview" style="margin-top: 10px; display: none;">
                                    <img id="editPreviewImg" src="" alt="Nuova anteprima" style="max-width: 200px; max-height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Aggiorna evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Dettagli Prenotazione -->
    <div class="modal fade" id="viewBookingModal" tabindex="-1" aria-labelledby="viewBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #6c5ce7, #a29bfe); border: none;">
                    <h5 class="modal-title text-white fw-bold" id="viewBookingModalLabel">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Dettagli Prenotazione
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="booking-details" style="background: linear-gradient(135deg, rgba(30, 30, 60, 0.95), rgba(60, 40, 70, 0.9)); min-height: 400px;">
                    <!-- I dettagli verranno caricati via JavaScript -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-white fs-5">Caricamento dettagli...</p>
                    </div>
                </div>
                <div class="modal-footer" style="background: rgba(30, 30, 60, 0.8); border: none;">
                    <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifica Utente -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editUserModalLabel">Modifica Utente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_user_nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="edit_user_nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_cognome" class="form-label">Cognome</label>
                            <input type="text" class="form-control" id="edit_user_cognome" name="cognome" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_user_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_telefono" class="form-label">Telefono</label>
                            <input type="tel" class="form-control" id="edit_user_telefono" name="telefono" placeholder="Opzionale">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_user_is_admin" name="is_admin" value="1">
                                <label class="form-check-label" for="edit_user_is_admin">
                                    <strong>Amministratore</strong>
                                    <small class="text-muted d-block">L'utente avrà accesso al pannello di amministrazione</small>
                                </label>
                            </div>
                        </div>
                        <div class="alert alert-warning" role="alert" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Aggiorna utente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Sidebar links for tabs
            const sidebarLinks = document.querySelectorAll('.sidebar-menu .nav-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // On mobile, close sidebar after clicking a link
                    if (window.innerWidth < 992) {
                        sidebar.classList.remove('active');
                    }
                });
            });
            
            // User search functionality
            const userSearch = document.getElementById('userSearch');
            if (userSearch) {
                userSearch.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const userRows = document.querySelectorAll('#users tbody tr');
                    
                    userRows.forEach(row => {
                        const userName = row.querySelector('.user-info span').textContent.toLowerCase();
                        const userEmail = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        
                        if (userName.includes(searchValue) || userEmail.includes(searchValue)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
            
            // Modifica evento - Popola il form
            const editEventBtns = document.querySelectorAll('.edit-event-btn');
            editEventBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const titolo = this.getAttribute('data-titolo');
                    const descrizione = this.getAttribute('data-descrizione');
                    const data = this.getAttribute('data-data');
                    const ora = this.getAttribute('data-ora');
                    const posti = this.getAttribute('data-posti');
                    const prezzo = this.getAttribute('data-prezzo');
                    const immagine = this.getAttribute('data-immagine'); // Variabile mancante
                    
                    document.getElementById('edit_event_id').value = id;
                    document.getElementById('edit_titolo').value = titolo;
                    document.getElementById('edit_descrizione').value = descrizione;
                    document.getElementById('edit_data').value = formatDateForInput(data);
                    document.getElementById('edit_ora').value = ora;
                    document.getElementById('edit_posti').value = posti;
                    document.getElementById('edit_prezzo').value = prezzo;
                    
                    // Mostra l'immagine attuale
                    const currentImg = document.getElementById('currentImg');
                    if (immagine && immagine !== '') {
                        currentImg.src = 'Immagini/Eventi/' + immagine;
                        currentImg.onerror = function() {
                            this.src = 'Immagini/Eventi/default_event.jpg';
                        };
                    } else {
                        currentImg.src = 'Immagini/Eventi/default_event.jpg';
                    }
                    
                    // Nascondi anteprima nuova immagine
                    document.getElementById('editImagePreview').style.display = 'none';
                    document.getElementById('edit_immagine').value = '';
                    
                    const editEventModal = new bootstrap.Modal(document.getElementById('editEventModal'));
                    editEventModal.show();
                });
            });
            
            // Modifica utente - Popola il form
            const editUserBtns = document.querySelectorAll('.edit-user-btn');
            editUserBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');
                    const cognome = this.getAttribute('data-cognome');
                    const email = this.getAttribute('data-email');
                    const telefono = this.getAttribute('data-telefono');
                    const isAdmin = this.getAttribute('data-admin') === '1';
                    
                    document.getElementById('edit_user_id').value = id;
                    document.getElementById('edit_user_nome').value = nome;
                    document.getElementById('edit_user_cognome').value = cognome;
                    document.getElementById('edit_user_email').value = email;
                    document.getElementById('edit_user_telefono').value = telefono;
                    document.getElementById('edit_user_is_admin').checked = isAdmin;
                    
                    // Disabilita il checkbox admin se è l'utente corrente
                    const currentUserId = <?php echo $utente_id; ?>;
                    if (parseInt(id) === currentUserId) {
                        document.getElementById('edit_user_is_admin').disabled = true;
                        document.querySelector('.alert-warning').style.display = 'block';
                    } else {
                        document.getElementById('edit_user_is_admin').disabled = false;
                        document.querySelector('.alert-warning').style.display = 'none';
                    }
                    
                    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    editUserModal.show();
                });
            });
            
            // Visualizza dettagli prenotazione
            const viewBookingBtns = document.querySelectorAll('.view-booking-btn');
            viewBookingBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const bookingId = this.getAttribute('data-id');
                    const bookingDetails = document.getElementById('booking-details');
                    
                    // Qui potresti fare una chiamata AJAX per ottenere i dettagli completi
                    // Per semplicità, creiamo un contenuto HTML di esempio
                    const prenotazioni = <?php echo json_encode($prenotazioni); ?>;
                    const prenotazione = prenotazioni.find(p => p.id == bookingId);
                    
                    if (prenotazione) {
                        let paymentIcon = '';
                        let paymentClass = '';
                        
                        if (prenotazione.pagamento === 'Carta') {
                            paymentIcon = '<i class="fas fa-credit-card me-2"></i>';
                            paymentClass = 'success';
                        } else if (prenotazione.pagamento === 'PayPal') {
                            paymentIcon = '<i class="fab fa-paypal me-2"></i>';
                            paymentClass = 'info';
                        } else {
                            paymentIcon = '<i class="fas fa-money-bill me-2"></i>';
                            paymentClass = 'secondary';
                        }
                        
                        let html = `
                            <div class="row g-4">
                                <!-- Sezione Evento -->
                                <div class="col-md-6">
                                    <div class="card h-100" style="background: rgba(108, 92, 231, 0.1); border: 2px solid rgba(108, 92, 231, 0.3); border-radius: 20px; backdrop-filter: blur(15px);">
                                        <div class="card-header text-center" style="background: linear-gradient(135deg, rgba(108, 92, 231, 0.3), rgba(162, 155, 254, 0.2)); border: none; border-radius: 20px 20px 0 0;">
                                            <h6 class="text-white fw-bold mb-0">
                                                <i class="fas fa-calendar-check me-2 text-primary"></i>
                                                Informazioni Evento
                                            </h6>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-music text-primary me-3" style="font-size: 1.2rem;"></i>
                                                    <strong class="text-white">Titolo:</strong>
                                                </div>
                                                <div class="ms-5">
                                                    <span class="badge bg-primary px-3 py-2 fs-6">${prenotazione.titoloE || 'N/D'}</span>
                                                </div>
                                            </div>
                                            <div class="mb-0">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-hashtag text-secondary me-3" style="font-size: 1.2rem;"></i>
                                                    <strong class="text-white">ID Evento:</strong>
                                                </div>
                                                <div class="ms-5">
                                                    <span class="badge bg-secondary px-3 py-2">#${prenotazione.evento_id}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Sezione Utente -->
                                <div class="col-md-6">
                                    <div class="card h-100" style="background: rgba(0, 184, 148, 0.1); border: 2px solid rgba(0, 184, 148, 0.3); border-radius: 20px; backdrop-filter: blur(15px);">
                                        <div class="card-header text-center" style="background: linear-gradient(135deg, rgba(0, 184, 148, 0.3), rgba(85, 239, 196, 0.2)); border: none; border-radius: 20px 20px 0 0;">
                                            <h6 class="text-white fw-bold mb-0">
                                                <i class="fas fa-user me-2 text-success"></i>
                                                Informazioni Utente
                                            </h6>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-user-circle text-success me-3" style="font-size: 1.2rem;"></i>
                                                    <strong class="text-white">Nome:</strong>
                                                </div>
                                                <div class="ms-5">
                                                    <span class="text-white fs-6">${prenotazione.nome_utente || 'N/D'}</span>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-envelope text-info me-3" style="font-size: 1.2rem;"></i>
                                                    <strong class="text-white">Email:</strong>
                                                </div>
                                                <div class="ms-5">
                                                    <span class="text-info fs-6">${prenotazione.email_utente || 'N/D'}</span>
                                                </div>
                                            </div>
                                            <div class="mb-0">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-hashtag text-secondary me-3" style="font-size: 1.2rem;"></i>
                                                    <strong class="text-white">ID Utente:</strong>
                                                </div>
                                                <div class="ms-5">
                                                    <span class="badge bg-secondary px-3 py-2">#${prenotazione.utente_id}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Sezione Prenotazione (full width) -->
                                <div class="col-12">
                                    <div class="card" style="background: rgba(253, 203, 110, 0.1); border: 2px solid rgba(253, 203, 110, 0.3); border-radius: 20px; backdrop-filter: blur(15px);">
                                        <div class="card-header text-center" style="background: linear-gradient(135deg, rgba(253, 203, 110, 0.3), rgba(255, 234, 167, 0.2)); border: none; border-radius: 20px 20px 0 0;">
                                            <h6 class="text-white fw-bold mb-0">
                                                <i class="fas fa-ticket-alt me-2 text-warning"></i>
                                                Dettagli Prenotazione
                                            </h6>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="row g-3">
                                                <div class="col-sm-6 col-md-3">
                                                    <div class="text-center p-3" style="background: rgba(108, 92, 231, 0.2); border-radius: 15px; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                                                        <i class="fas fa-users text-primary mb-2" style="font-size: 1.8rem;"></i>
                                                        <div>
                                                            <strong class="text-white d-block mb-1">Posti</strong>
                                                            <span class="badge bg-primary px-3 py-2 fs-6">${prenotazione.posti}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-md-3">
                                                    <div class="text-center p-3" style="background: rgba(0, 184, 148, 0.2); border-radius: 15px; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                                                        <i class="fas fa-euro-sign text-success mb-2" style="font-size: 1.8rem;"></i>
                                                        <div>
                                                            <strong class="text-white d-block mb-1">Importo</strong>
                                                            <span class="badge bg-success px-3 py-2 fs-6">${(prenotazione.posti * prenotazione.prezzo).toFixed(2)} €</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-md-3">
                                                    <div class="text-center p-3" style="background: rgba(116, 185, 255, 0.2); border-radius: 15px; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                                                        <div class="mb-2" style="font-size: 1.8rem; color: #74b9ff;">${paymentIcon.replace('me-2', '').replace('fas fa-credit-card', 'fas fa-credit-card').replace('fab fa-paypal', 'fab fa-paypal')}</div>
                                                        <div>
                                                            <strong class="text-white d-block mb-1">Pagamento</strong>
                                                            <span class="badge bg-${paymentClass} px-3 py-2">${prenotazione.pagamento}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-md-3">
                                                    <div class="text-center p-3" style="background: rgba(253, 203, 110, 0.2); border-radius: 15px; min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                                                        <i class="fas fa-calendar-alt text-warning mb-2" style="font-size: 1.8rem;"></i>
                                                        <div>
                                                            <strong class="text-white d-block mb-1">Data</strong>
                                                            <span class="badge bg-warning text-dark px-2 py-2 fs-6">${prenotazione.data_prenotazione ? new Date(prenotazione.data_prenotazione).toLocaleDateString('it-IT') + '<br>' + new Date(prenotazione.data_prenotazione).toLocaleTimeString('it-IT', {hour: '2-digit', minute: '2-digit'}) : 'N/D'}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        bookingDetails.innerHTML = html;
                    } else {
                        bookingDetails.innerHTML = '<div class="alert alert-danger">Prenotazione non trovata.</div>';
                    }
                    
                    const viewBookingModal = new bootstrap.Modal(document.getElementById('viewBookingModal'));
                    viewBookingModal.show();
                });
            });
            
            // Aggiungi anteprima immagine per nuovo evento
            document.getElementById('immagine').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('imagePreview');
                const previewImg = document.getElementById('previewImg');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });
            
            // Aggiungi anteprima immagine per modifica evento
            document.getElementById('edit_immagine').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('editImagePreview');
                const previewImg = document.getElementById('editPreviewImg');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });
            
            // Funzioni helper per formattare le date
            window.formatDateForInput = function(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            }
            
            window.formatDate = function(dateString) {
                if (!dateString) return 'N/D';
                const date = new Date(dateString);
                return date.toLocaleDateString('it-IT', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        });
        
        // Inizializzazione dei grafici - Aspetta che tutti gli elementi siano caricati
        setTimeout(function() {
            initializeCharts();
        }, 100);
        
        // Funzione per inizializzare i grafici
        function initializeCharts() {
            try {
                // 1. Grafico registrazioni utenti
                const registrationsCanvas = document.getElementById('registrationsChart');
                if (registrationsCanvas) {
                    const registrationsCtx = registrationsCanvas.getContext('2d');
                    const registrationsChart = new Chart(registrationsCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($mesiRecenti); ?>,
                            datasets: [{
                                label: 'Nuovi Utenti',
                                data: <?php echo json_encode($registrazioniDati); ?>,
                                backgroundColor: 'rgba(108, 92, 231, 0.2)',
                                borderColor: 'rgba(108, 92, 231, 1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.8)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        color: 'rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            }
                        }
                    });
                }

                // 2. Grafico a torta per le categorie eventi
                const bookingsCanvas = document.getElementById('bookingsChart');
                if (bookingsCanvas) {
                    const bookingsCtx = bookingsCanvas.getContext('2d');
                    const bookingsChart = new Chart(bookingsCtx, {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo json_encode(array_keys($categorieEventi)); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_values($categorieEventi)); ?>,
                                backgroundColor: [
                                    'rgba(108, 92, 231, 0.8)',
                                    'rgba(0, 184, 148, 0.8)',
                                    'rgba(231, 76, 60, 0.8)',
                                    'rgba(253, 203, 110, 0.8)',
                                    'rgba(116, 185, 255, 0.8)'
                                ],
                                borderColor: [
                                    'rgba(108, 92, 231, 1)',
                                    'rgba(0, 184, 148, 1)',
                                    'rgba(231, 76, 60, 1)',
                                    'rgba(253, 203, 110, 1)',
                                    'rgba(116, 185, 255, 1)'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: 'rgba(255, 255, 255, 0.8)',
                                        padding: 15
                                    }
                                }
                            }
                        }
                    });
                }

                // 3. Grafico eventi più popolari
                const eventsCanvas = document.getElementById('eventsChart');
                if (eventsCanvas) {
                    const eventsCtx = eventsCanvas.getContext('2d');
                    const eventsChart = new Chart(eventsCtx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode($nomiEventi); ?>,
                            datasets: [{
                                label: 'Prenotazioni',
                                data: <?php echo json_encode($prenotazioniEventi); ?>,
                                backgroundColor: 'rgba(0, 184, 148, 0.8)',
                                borderColor: 'rgba(0, 184, 148, 1)',
                                borderWidth: 2,
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.8)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.8)',
                                        maxRotation: 45
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        color: 'rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            }
                        }
                    });
                }

                // 4. Grafico incassi mensili
                const incomeCanvas = document.getElementById('incomeChart');
                if (incomeCanvas) {
                    const incomeCtx = incomeCanvas.getContext('2d');
                    const incomeChart = new Chart(incomeCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($mesi); ?>,
                            datasets: [{
                                label: 'Incassi (€)',
                                data: <?php echo json_encode($incassiPerMese); ?>,
                                backgroundColor: 'rgba(231, 76, 60, 0.2)',
                                borderColor: 'rgba(231, 76, 60, 1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: 'rgba(231, 76, 60, 1)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.8)',
                                        callback: function(value) {
                                            return '€' + value.toFixed(2);
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        color: 'rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Errore nell\'inizializzazione dei grafici:', error);
            }
        }
    </script>
</body>
</html>