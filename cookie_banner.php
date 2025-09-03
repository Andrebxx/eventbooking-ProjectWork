<!-- Overlay per bloccare l'interazione con la pagina -->
<div id="cookieOverlay" class="cookie-overlay">
    <div id="cookieBanner" class="cookie-banner-wrapper">
        <div class="cookie-container">
            <div class="cookie-header">
                <div class="cookie-icon">
                    <i class="fas fa-cookie-bite"></i>
                </div>
                <h5 class="cookie-title">Cookie Policy</h5>
                <button id="closeCookieBanner" class="cookie-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="cookie-content">
                <p class="cookie-description">
                    Utilizziamo i cookie per personalizzare la tua esperienza. Scegli quali cookie accettare per continuare.
                </p>
                
                <div class="cookie-options">
                    <div class="cookie-option essential">
                        <div class="option-header">
                            <div class="option-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="option-info">
                                <h6>Cookie Essenziali</h6>
                                <small>I cookie necessari contribuiscono a rendere fruibile il sito web abilitandone funzionalità di base quali la navigazione sulle pagine e l'accesso alle aree protette del sito.</small>
                            </div>
                            <div class="option-status enabled">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cookie-option">
                        <div class="option-header">
                            <div class="option-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="option-info">
                                <h6>Cookie Analitici</h6>
                                <small>I cookie analitici ci consentono di monitorare e analizzare il comportamento degli utenti sul nostro sito, raccogliendo dati in forma aggregata e anonima relativi, ad esempio, alle pagine visitate, alla durata della navigazione e alle modalità di interazione con i contenuti. Queste informazioni ci aiutano a comprendere meglio le esigenze dei visitatori e a migliorare costantemente funzionalità, prestazioni ed esperienza d’uso della piattaforma. I dati raccolti tramite questi cookie non permettono in alcun modo di risalire all’identità personale degli utenti.</small>
                            </div>
                            <label class="cookie-switch">
                                <input type="checkbox" id="analyticsCookies">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="cookie-option">
                        <div class="option-header">
                            <div class="option-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="option-info">
                                <h6>Cookie di Profilazione</h6>
                                <small>Utilizziamo cookie di profilazione, nostri e di terze parti, al fine di analizzare le preferenze e le abitudini degli utenti durante la navigazione e offrire contenuti, promozioni e annunci pubblicitari personalizzati in linea con gli interessi rilevati. Questi cookie raccolgono dati sulle interazioni con il sito, sulle pagine visualizzate e sulle scelte compiute, al fine di creare un profilo dell’utente e migliorare la qualità dei servizi offerti. L’installazione di cookie di profilazione avviene esclusivamente previo consenso esplicito, che può essere modificato in qualsiasi momento accedendo alle impostazioni del sito.</small>
                            </div>
                            <label class="cookie-switch">
                                <input type="checkbox" id="marketingCookies">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="cookie-actions">
                    <button id="rejectOptional" class="btn-secondary">
                        Solo Essenziali
                    </button>
                    <button id="acceptSelected" class="btn-primary">
                        Accetta Selezionati
                    </button>
                    <button id="acceptAll" class="btn-accent">
                        Accetta Tutto
                    </button>
                </div>
                
                <div class="cookie-footer">
                    <a href="informazionilegali.php#cookies" class="cookie-link">
                        <i class="fas fa-info-circle me-1"></i>
                        Maggiori informazioni
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('cookieOverlay');
    const banner = document.getElementById('cookieBanner');
    const closeBanner = document.getElementById('closeCookieBanner');
    const acceptSelected = document.getElementById('acceptSelected');
    const acceptAll = document.getElementById('acceptAll');
    const rejectOptional = document.getElementById('rejectOptional');
    const analyticsCheckbox = document.getElementById('analyticsCookies');
    const marketingCheckbox = document.getElementById('marketingCookies');

    // Verifica se il consenso è già stato dato
    if (localStorage.getItem('cookieConsent')) {
        overlay.classList.add('hidden');
        return;
    }

    // Previeni scroll del body quando il banner è aperto
    document.body.style.overflow = 'hidden';

    // Funzione per nascondere il banner con animazione
    function hideBanner(callback) {
        overlay.classList.add('closing');
        setTimeout(() => {
            overlay.classList.add('hidden');
            overlay.classList.remove('closing');
            document.body.style.overflow = 'auto'; // Ripristina lo scroll
            if (callback) callback();
        }, 400);
    }

    // Previeni click sul contenuto dietro l'overlay
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            // Non fare nulla, mantieni il banner aperto
        }
    });

    // Funzione per salvare le preferenze
    function saveConsent(analytics, marketing) {
        const acceptedCookies = {
            analytics: analytics,
            marketing: marketing,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('cookieConsent', JSON.stringify(acceptedCookies));
        
        // Carica gli script in base alle preferenze
        if (analytics) {
            console.log("🔍 Cookie di analisi attivati");
            // Qui aggiungi il codice per Google Analytics o altri script di analisi
            // Esempio: gtag('config', 'GA_TRACKING_ID');
        }
        if (marketing) {
            console.log("📢 Cookie di marketing attivati");
            // Qui aggiungi il codice per Facebook Pixel, Google Ads, etc.
        }
        
        hideBanner();
    }

    // Chiudi banner (solo essenziali)
    closeBanner.addEventListener('click', () => {
        saveConsent(false, false);
    });

    // Solo essenziali
    rejectOptional.addEventListener('click', () => {
        saveConsent(false, false);
    });

    // Accetta selezionati
    acceptSelected.addEventListener('click', () => {
        saveConsent(analyticsCheckbox.checked, marketingCheckbox.checked);
    });

    // Accetta tutto
    acceptAll.addEventListener('click', () => {
        analyticsCheckbox.checked = true;
        marketingCheckbox.checked = true;
        saveConsent(true, true);
    });

    // Animazione per i toggle switches
    [analyticsCheckbox, marketingCheckbox].forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const option = this.closest('.cookie-option');
            if (this.checked) {
                option.style.background = 'rgba(102, 126, 234, 0.1)';
                option.style.borderColor = 'rgba(102, 126, 234, 0.3)';
            } else {
                option.style.background = 'rgba(255, 255, 255, 0.05)';
                option.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            }
        });
    });

    // Gestione tasti da tastiera
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !overlay.classList.contains('hidden')) {
            saveConsent(false, false);
        }
    });
});
</script>