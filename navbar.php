<?php
// Avvia la sessione se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('password.php');

// Verifica login e nome utente
$is_logged_in = isset($_SESSION['id']) && !empty($_SESSION['id']);
$user_name = '';
$is_admin = false;

if ($is_logged_in) {
    $utente_id = $_SESSION['id'];

    // Recupero nome utente dal DB
    $stmt = $myDB->prepare("SELECT nome, is_admin FROM utenti WHERE id = ?");
    $stmt->bind_param("i", $utente_id);
    $stmt->execute();
    $stmt->bind_result($nome_db, $admin_status);
    $stmt->fetch();
    $stmt->close();

    $user_name = $nome_db ?? 'Utente';
    $is_admin = $admin_status ? true : false;
}
?>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="stili/stile_navfoo.css">

<nav class="navbar navbar-expand-lg navbar-custom fixed-top" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="Immagini/logo.png" alt="Logo">
            EventBooking
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <?php foreach ($extra_links as $label => $link): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars($link) ?>"><?= htmlspecialchars($label) ?></a>
                    </li>
                <?php endforeach; ?>

                <!-- Area utente -->
                <li class="nav-item ms-3">
                    <?php if ($is_logged_in): ?>
                        <a href="profilo.php" class="login-btn">
                            <i class="bi bi-person-check me-2"></i>
                            <span>Ciao <?= htmlspecialchars($user_name) ?></span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="login-btn">
                            <i class="bi bi-person-circle me-2"></i>
                            <span>Accedi</span>
                        </a>
                    <?php endif; ?>
                </li>

                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle-custom" href="#" id="menuDropdown" role="button">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuDropdown">
                        <li><a class="dropdown-item" href="index.php"><i class="bi bi-house me-2"></i>Home</a></li>
                        <li><a class="dropdown-item" href="Eventi.php"><i class="bi bi-calendar-event me-2"></i>Eventi</a></li>
                        <?php if ($is_logged_in): ?>
                            <li><a class="dropdown-item" href="profilo.php"><i class="bi bi-person me-2"></i>Profilo</a></li>
                            <?php if ($is_admin): ?>
                                <li><a class="dropdown-item" href="amministrazione.php"><i class="bi bi-shield-lock me-2"></i>Amministrazione</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        <?php else: ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Accedi</a></li>
                            <li><a class="dropdown-item" href="register.php"><i class="bi bi-person-plus me-2"></i>Registrati</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('mainNavbar');
    window.addEventListener('scroll', function () {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Gestione avanzata del dropdown
    const dropdownToggle = document.getElementById('menuDropdown');
    const dropdownMenu = dropdownToggle?.nextElementSibling;
    
    if (dropdownToggle && dropdownMenu) {
        // Prevenire il comportamento predefinito e gestire manualmente
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle del dropdown
            const isOpen = dropdownMenu.classList.contains('show');
            
            // Chiudi tutti gli altri dropdown aperti
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('show');
                }
            });
            
            // Toggle del dropdown corrente
            if (isOpen) {
                dropdownMenu.classList.remove('show');
            } else {
                dropdownMenu.classList.add('show');
                // Focus management per accessibilità
                setTimeout(() => {
                    const firstItem = dropdownMenu.querySelector('.dropdown-item');
                    if (firstItem) firstItem.focus();
                }, 100);
            }
        });
        
        // Chiudi il dropdown quando si clicca fuori
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
        
        // Gestione tasti per accessibilità
        dropdownToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                dropdownToggle.click();
            } else if (e.key === 'Escape') {
                dropdownMenu.classList.remove('show');
            }
        });
        
        // Gestione navigazione con frecce
        dropdownMenu.addEventListener('keydown', function(e) {
            const items = Array.from(dropdownMenu.querySelectorAll('.dropdown-item'));
            const currentIndex = items.findIndex(item => item === document.activeElement);
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                items[prevIndex].focus();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                dropdownMenu.classList.remove('show');
                dropdownToggle.focus();
            }
        });
        
        // Effetto hover migliorato
        dropdownToggle.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1) rotate(5deg)';
        });
        
        dropdownToggle.addEventListener('mouseleave', function() {
            if (!dropdownMenu.classList.contains('show')) {
                this.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    }
});
</script>


