<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Pagina Non Trovata | EventBooking</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- CSS Principale -->
    <link rel="stylesheet" href="stile.css">
    <!-- CSS Specifico per pagina 404 -->
    <link rel="stylesheet" href="stili/stile_404.css">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
    <link rel="icon" type="image/x-icon" href="Immagini/logo.ico">
   
</head>
<body>
    <?php
        include 'navbar.php';
    ?>
    <!-- Main Content -->
    <main class="main-content">
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
            
            <!-- Note musicali di sfondo -->
            <div class="musical-note note-1">♪</div>
            <div class="musical-note note-2">♫</div>
            <div class="musical-note note-3">♪</div>
            <div class="musical-note note-4">♫</div>
            <div class="musical-note note-5">♪</div>
            <div class="musical-note note-6">♫</div>
            
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
        </div>
        
        <!-- Effetti specifici per la pagina 404 -->
        <div class="background-effects">
            <div class="music-wave"></div>
            <div class="music-wave"></div>
            <div class="music-wave"></div>
            <div class="music-wave"></div>
            <div class="music-notes">🎵</div>
            <div class="music-notes">🎶</div>
            <div class="music-notes">🎵</div>
            <div class="music-notes">🎶</div>
        </div>
        
        <div class="error-container">
            <div class="error-code">404</div>
            <h1 class="error-title">🎵 Pagina Non Trovata</h1>
            <h2 class="error-subtitle">La pagina che cerchi non è nel nostro lineup!</h2>
            <p class="error-description">
                Sembra che la pagina che stai cercando non esista o sia stata rimossa. Non preoccuparti, puoi sempre tornare alla nostra homepage o contattarci per assistenza.
            </p>
            
            <div class="error-buttons">
                <a href="/Eventi.php" class="btn btn-primary">
                    🎶 Scopri gli Eventi
                </a>
                <a href="index.php#hero" class="btn btn-secondary">
                    ↩️ Torna alla home
                </a>
                <a href="index.php#contatti" class="btn btn-secondary">
                    📞 Contattaci
                </a>
            </div>
        </div>
    </main>
        <?php
        include 'footer.php';
        include 'cookie_banner.php';
    ?>

    <script>
        // Effetto parallax per le note musicali
        document.addEventListener('mousemove', function(e) {
            const notes = document.querySelectorAll('.music-notes');
            const waves = document.querySelectorAll('.music-wave');
            
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            notes.forEach((note, index) => {
                const speed = (index + 1) * 0.3;
                const x = (mouseX - 0.5) * speed * 30;
                const y = (mouseY - 0.5) * speed * 30;
                note.style.transform = `translate(${x}px, ${y}px)`;
            });
            
            waves.forEach((wave, index) => {
                const speed = (index + 1) * 0.1;
                const x = (mouseX - 0.5) * speed * 20;
                const y = (mouseY - 0.5) * speed * 20;
                wave.style.transform = `translate(${x}px, ${y}px) scale(1)`;
            });
        });

        // Hamburger menu toggle
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('.nav');
        
        if (hamburger && nav) {
            hamburger.addEventListener('click', function() {
                nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
            });
        }

        // Animazione delle onde musicali al click
        document.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(233, 30, 99, 0.3)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.left = (e.clientX - 50) + 'px';
            ripple.style.top = (e.clientY - 50) + 'px';
            ripple.style.width = '100px';
            ripple.style.height = '100px';
            ripple.style.pointerEvents = 'none';
            ripple.style.zIndex = '1000';
            
            document.body.appendChild(ripple);
            
            setTimeout(() => {
                document.body.removeChild(ripple);
            }, 600);
        });

        // Aggiungi stile per l'animazione ripple
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>