<?php
    ini_set("display_errors", "On"); 
    
    include_once('password.php');

    if ($myDB->connect_error) {
        die("Connessione fallita: " . $myDB->connect_error);
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBooking - Home</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Il tuo CSS -->
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="stili/stile_index.css">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
    <link rel="stylesheet" href="stili/stile_navfoo.css">
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
        $extra_links = [
            "Eventi" => "#eventi",
            "Contatti" => "#contatti",          
        ];
        include 'navbar.php';
    ?>

    <!-- Hero -->
    <section class="hero-section" id="hero">
        <div class="hero-content">
            <h1><i class="fa fa-music"></i> Prenota il tuo evento</h1>
            <p>Scopri concerti e spettacoli mozzafiato. Scegli il tuo evento e assicurati il posto migliore!</p>
            <a href="#eventi" class="btn btn-hero">Scopri gli Eventi</a>
        </div>
    </section>

    <!-- Eventi -->
    <section id="eventi" class="container eventi-section">
        <h2><i class="fa fa-calendar"></i> Eventi Disponibili</h2>
        <div class="row g-4">
            <?php
            // Prima mostra eventi con posti disponibili
            $sql = "SELECT * FROM Eventi WHERE data >= CURDATE() AND Posti > 0 ORDER BY data ASC LIMIT 3";
            $result = $myDB->query($sql);

            $eventi_mostrati = 0;

            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Costruisci il percorso corretto dell'immagine
                    $imagePath = "Immagini/Eventi/" . ($row['Immagine'] ?? 'default_event.jpg');
                    
                    // Verifica se il file esiste, altrimenti usa l'immagine di default
                    if (!file_exists($imagePath) || empty($row['Immagine'])) {
                        $imagePath = "Immagini/Eventi/default_event.jpg";
                    }
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
                    $eventi_mostrati++;
                }
            }

            // Se abbiamo mostrato meno di 3 eventi, aggiungi eventi sold out
            if ($eventi_mostrati < 3) {
                $eventi_rimanenti = 3 - $eventi_mostrati;
                $sql_sold_out = "SELECT * FROM Eventi WHERE data >= CURDATE() AND Posti = 0 ORDER BY data ASC LIMIT $eventi_rimanenti";
                $result_sold_out = $myDB->query($sql_sold_out);

                if ($result_sold_out && $result_sold_out->num_rows > 0) {
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
                        $eventi_mostrati++;
                    }
                }
            }

            // Se non ci sono eventi
            if ($eventi_mostrati == 0) {
                echo '<div class="col-12"><p class="text-center">Nessun evento disponibile.</p></div>';
            }
            ?>
        </div>
        <!-- Pulsante mostra altri eventi -->
        <div class="text-center mt-4">
            <a href="Eventi.php" class="btn btn-hero" style="padding: 0.75rem 2.5rem; font-size: 1.15rem; border-radius: 30px;">
                Mostra gli altri eventi
            </a>
        </div>
    </section>

    <!-- Contatti - SEZIONE MIGLIORATA -->
    <section id="contatti" class="container contact-section" style="margin-top: 4rem; padding: 2rem 2rem 3rem 2rem;">
        <div class="contact-title text-center mb-4">
            <h2><i class="fa fa-envelope me-3"></i>Contattaci</h2>
            <p>Siamo qui per te! Raggiungi il nostro team per qualsiasi domanda o supporto.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fa fa-envelope"></i>
                    </div>
                    <h4>Email</h4>
                    <p>Scrivici per informazioni</p>
                    <a href="mailto:marco.bedeschi@itsolivetti.it" class="contact-link">marco.bedeschi@itsolivetti.it</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fa fa-phone"></i>
                    </div>
                    <h4>Telefono</h4>
                    <p>Chiamaci per assistenza diretta</p>
                    <a href="tel:+393499415015" class="contact-link">+39 349 9415015</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fa fa-clock"></i>
                    </div>
                    <h4>Orari</h4>
                    <p>Siamo disponibili:</p>
                    <p class="contact-link">Lun - Sab | 9:00 - 18:00</p>
                </div>
            </div>
        </div>
    </section>
    <br><br>

    <?php
        include 'footer.php';
        include 'cookie_banner.php';
    ?>

    <script>
document.querySelectorAll('a[href="#eventi"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var eventi = document.getElementById('eventi');
        if (eventi) {
            var offset = -80; // Cambia questo valore per regolare lo spazio sopra
            var top = eventi.getBoundingClientRect().top + window.pageYOffset + offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }
    });
});
</script>

<?php if (isset($_SESSION['errore'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
        <?php echo htmlspecialchars($_SESSION['errore']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['errore']); ?>
<?php endif; ?>

</body>
</html>