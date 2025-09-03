<?php
    ini_set("display_errors", "On"); 
    
    include_once('password.php');

    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutti gli Eventi - PrenotaEventi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Navbar e Footer CSS -->
    <link rel="stylesheet" href="stili/stile_navfoo.css">
    <!-- CSS specifico per Eventi -->
    <link rel="stylesheet" href="stili/stile_eventi.css">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
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
    
    <?php
        include 'navbar.php';
        
    ?>

    <!-- Hero Section per Eventi -->
    <section class="eventi-hero-section">
        <div class="container">
            <div class="eventi-hero-content text-center">
                <h1><i class="fa fa-music"></i> Scopri Tutti i Nostri Eventi</h1>
                <p>Una selezione completa di concerti, spettacoli e eventi musicali imperdibili</p>
            </div>
        </div>
    </section>

    <section class="eventi-section container" style="margin-top: 2rem;">
        <h2 class="mb-4 text-center">
            <i class="fa fa-calendar"></i> Eventi Disponibili
        </h2>
        <div class="row g-4">
            <?php
            // Prima query: solo eventi con posti disponibili
            $sql_available = "SELECT * FROM Eventi WHERE data >= CURDATE() AND Posti > 0 ORDER BY data ASC";
            $result_available = $myDB->query($sql_available);
            $eventi_disponibili = 0;

            if ($result_available && $result_available->num_rows > 0) {
                while($row = $result_available->fetch_assoc()) {
                    // Costruisci il percorso corretto dell'immagine
                    $imagePath = "Immagini/Eventi/" . ($row['Immagine'] ?? 'default_event.jpg');
                    
                    // Verifica se il file esiste, altrimenti usa l'immagine di default
                    if (!file_exists($imagePath) || empty($row['Immagine'])) {
                        $imagePath = "Immagini/Eventi/default_event.jpg";
                    }
                    $eventi_disponibili++;
                    ?>
                    <div class="col-md-4">
                        <div class="event-card p-3">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 alt="<?php echo htmlspecialchars($row['titoloE']); ?>"
                                 onerror="this.src='Immagini/Eventi/default_event.jpg'">
                            
                            <div class="event-card-content">
                                <h5><?php echo htmlspecialchars($row['titoloE']); ?></h5>
                                <p class="event-description"><?php echo htmlspecialchars($row['descrizione']); ?></p>
                                
                                <div class="event-info">
                                    <p><strong>Data:</strong> <?php echo date("d/m/Y", strtotime($row['data'])); ?></p>
                                    <p><strong>Ora:</strong> <?php echo date("H:i", strtotime($row['ora'])); ?></p>
                                    <p><strong>Posti disponibili:</strong> <?php echo htmlspecialchars($row['Posti']); ?></p>
                                    <?php if (isset($row['prezzo']) && $row['prezzo'] > 0): ?>
                                    <p><strong>Prezzo:</strong> €<?php echo number_format($row['prezzo'], 2); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="prenotazione.php?id=<?php echo $row['id']; ?>" class="btn w-100">
                                    <i class="fas fa-ticket-alt me-2"></i>Prenota
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }

            if ($eventi_disponibili == 0) {
                echo '<div class="col-12"><p class="text-center" style="color: white; font-size: 1.2rem;">Nessun evento disponibile al momento.</p></div>';
            }
            ?>
        </div>
    </section>

    <?php
    // Controlla se ci sono eventi sold out
    $sql_sold_out = "SELECT * FROM Eventi WHERE data >= CURDATE() AND Posti = 0 ORDER BY data ASC";
    $result_sold_out = $myDB->query($sql_sold_out);
    $eventi_sold_out = 0;

    if ($result_sold_out && $result_sold_out->num_rows > 0) {
        $eventi_sold_out = $result_sold_out->num_rows;
    }

    // Mostra la sezione sold out solo se ci sono eventi
    if ($eventi_sold_out > 0):
    ?>
    <!-- Sezione Eventi Sold Out -->
    <section class="eventi-section container" style="margin-top: 3rem;">
        <div class="sold-out-section">
            <button class="sold-out-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#soldOutEvents" aria-expanded="false" aria-controls="soldOutEvents">
                <div class="sold-out-header">
                    <h3><i class="fas fa-times-circle me-2"></i>Eventi Sold Out</h3>
                    <p class="sold-out-count"><?php echo $eventi_sold_out; ?> eventi non più disponibili</p>
                </div>
                <div class="sold-out-arrow">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </button>
            
            <div class="collapse" id="soldOutEvents">
                <div class="sold-out-content">
                    <div class="row g-4 mt-2">
                        <?php
                        // Reset del puntatore del risultato
                        if ($result_sold_out) {
                            $result_sold_out->data_seek(0);
                            while($row = $result_sold_out->fetch_assoc()) {
                                // Costruisci il percorso corretto dell'immagine
                                $imagePath = "Immagini/Eventi/" . ($row['Immagine'] ?? 'default_event.jpg');
                                
                                // Verifica se il file esiste, altrimenti usa l'immagine di default
                                if (!file_exists($imagePath) || empty($row['Immagine'])) {
                                    $imagePath = "Immagini/Eventi/default_event.jpg";
                                }
                                ?>
                                <div class="col-md-4">
                                    <div class="event-card p-3 event-sold-out">
                                        <div class="sold-out-overlay">
                                            <span class="sold-out-badge">SOLD OUT</span>
                                        </div>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                             alt="<?php echo htmlspecialchars($row['titoloE']); ?>"
                                             onerror="this.src='Immagini/Eventi/default_event.jpg'">
                                        
                                        <div class="event-card-content">
                                            <h5><?php echo htmlspecialchars($row['titoloE']); ?></h5>
                                            <p class="event-description"><?php echo htmlspecialchars($row['descrizione']); ?></p>
                                            
                                            <div class="event-info">
                                                <p><strong>Data:</strong> <?php echo date("d/m/Y", strtotime($row['data'])); ?></p>
                                                <p><strong>Ora:</strong> <?php echo date("H:i", strtotime($row['ora'])); ?></p>
                                                <p><strong>Posti disponibili:</strong> <span class="text-danger fw-bold">0 (Esaurito)</span></p>
                                                <?php if (isset($row['prezzo']) && $row['prezzo'] > 0): ?>
                                                <p><strong>Prezzo:</strong> €<?php echo number_format($row['prezzo'], 2); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <button class="btn w-100" disabled>
                                                <i class="fas fa-times-circle me-2"></i>Posti finiti
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <br><br>
    
    <?php
        include 'footer.php';
        include 'cookie_banner.php';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    
    <script>
        // Script per animare la freccia della sezione sold out
        document.addEventListener('DOMContentLoaded', function() {
            const soldOutToggle = document.querySelector('.sold-out-toggle');
            const soldOutCollapse = document.querySelector('#soldOutEvents');
            const arrow = document.querySelector('.sold-out-arrow i');
            
            if (soldOutToggle && soldOutCollapse && arrow) {
                soldOutCollapse.addEventListener('show.bs.collapse', function() {
                    arrow.style.transform = 'rotate(180deg)';
                    soldOutToggle.classList.add('expanded');
                });
                
                soldOutCollapse.addEventListener('hide.bs.collapse', function() {
                    arrow.style.transform = 'rotate(0deg)';
                    soldOutToggle.classList.remove('expanded');
                });
            }
        });
    </script>
</body>
</html>