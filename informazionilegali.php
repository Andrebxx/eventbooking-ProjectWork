<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBooking - Informazioni Legali</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Il tuo CSS -->
    
    <link rel="stylesheet" href="stili/stile_informazionilegali.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="stili/stile_navfoo.css">
    <link rel="stylesheet" href="stili/stile_coobanner.css">
    <link rel="icon" type="image/x-icon" href="Immagini/logo.ico">
    
    
</head>
<body>
    <!-- Elementi decorativi di sfondo -->
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
    </div>
    
    <div class="floating-particles">
        <div class="particle particle-1"></div>
        <div class="particle particle-2"></div>
        <div class="particle particle-3"></div>
        <div class="particle particle-4"></div>
        <div class="particle particle-5"></div>
        <div class="particle particle-6"></div>
    </div>
    
    <div class="background-waves">
        <div class="wave wave-1"></div>
        <div class="wave wave-2"></div>
        <div class="wave wave-3"></div>
    </div>
    
    <div class="floating-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
        <div class="circle circle-4"></div>
    </div>
    
    <div class="background-stars">
        <div class="star star-1"></div>
        <div class="star star-2"></div>
        <div class="star star-3"></div>
        <div class="star star-4"></div>
        <div class="star star-5"></div>
    </div>
    
    <div class="floating-icons">
        <div class="icon-shape icon-1">⚖️</div>
        <div class="icon-shape icon-2">📋</div>
        <div class="icon-shape icon-3">🔒</div>
        <div class="icon-shape icon-4">📊</div>
    </div>

    <?php
        $extra_links = [
            "Eventi" => "Eventi.php",
            "Contatti" => "index.php#contatti",          
        ];
        include 'navbar.php';

        // Show requested section or default to privacy
        $_GET['section'] = $_GET['section'] ?? 'privacy'; // Default to privacy if no section specified
        $availableSections = ['privacy', 'cookies', 'terms'];
        $selectedSection = in_array($_GET['section'], $availableSections) ? $_GET['section'] : 'privacy';
    ?>
    <br><br><br><br>
    <!-- Hero Section -->
    <section class="container">
        <div class="legal-hero">
            <h1><i class="fa fa-shield-alt me-3"></i>Informazioni Legali</h1>
            <p>Trasparenza e chiarezza per i nostri utenti</p>
        </div>
    </section>

    <!-- Navigation -->
    <section class="container">
        <div class="legal-navigation text-center">
            <button class="nav-btn" onclick="showSection('privacy')">
                <i class="fa fa-user-shield me-2"></i>Privacy Policy
            </button>
            <button class="nav-btn" onclick="showSection('cookies')">
                <i class="fa fa-cookie-bite me-2"></i>Cookie Policy
            </button>
            <button class="nav-btn" onclick="showSection('terms')">
                <i class="fa fa-file-contract me-2"></i>Termini di Servizio
            </button>
        </div>
    </section>

    <!-- Content -->
    <section class="container">
        <div class="legal-content">
            <!-- Privacy Policy Section -->
            <div id="privacy" class="section">
                <h2><i class="fa fa-user-shield"></i>Privacy Policy</h2>
                <h3>Privacy Policy di EventBooking</h3>
                <h4>1. Titolare del trattamento</h4>
                <p>
                    Il titolare del trattamento dei dati è EventBooking s.r.l., con sede legale in Via Piemonte, 6, 40024 Osteria Grande BO, partita IVA 12345678901, contattabile all’indirizzo email marco.bedeschi@itsolivetti.it e al numero +39 349 9415015.
                </p>
                <h4>2. Tipologie di dati personali raccolti</h4>
                <p>
                    EventBooking raccoglie e tratta le seguenti categorie di dati personali:
                </p>
                <ul>
                    <li><strong>Dati identificativi:</strong> nome e cognome.</li>
                    <li><strong>Dati di contatto:</strong> email.</li>
                    <li><strong>Dati di accesso e autenticazione:</strong> username, password (criptata).</li>
                    <li><strong>Dati di navigazione:</strong> indirizzi IP, log di accesso, dati di traffico, informazioni raccolte tramite cookie tecnici, analitici e di profilazione (le ultime due se accettate).</li>
                    <li><strong>Dati relativi alle prenotazioni:</strong> eventi prenotati, preferenze, modalità di pagamento (non vengono memorizzati dati sensibili relativi ai metodi di pagamento elettronico, gestiti direttamente dai provider di pagamento certificati).</li>
                </ul>
                <h4>3. Finalità e Base giuridica del trattamento</h4>
                <p>
                    I tuoi dati personali vengono trattati per le seguenti finalità:
                </p>
                <ul>
                    <li>Gestione registrazione e autenticazione account.</li>
                    <li>Gestione delle prenotazioni di eventi.</li>
                    <li>Adempimenti contrattuali e obblighi di legge.</li>
                    <li>Invio di comunicazioni amministrative e di servizio.</li>
                    <li>Analisi statistica e miglioramento della piattaforma (dati aggregati/anonimizzati).</li>
                    <li>Invio di newsletter, comunicazioni commerciali e promozionali (previo consenso).</li>
                    <li>Profilazione per offerte personalizzate (previo consenso esplicito).</li>
                </ul>
                <p>
                    Le basi giuridiche del trattamento sono: esecuzione di misure precontrattuali e contrattuali, obblighi legali, consenso esplicito dell’utente, legittimi interessi del titolare.
                </p>
                <h4>4. Modalità di trattamento e sicurezza</h4>
                <p>
                    I dati vengono trattati con strumenti manuali, elettronici o telematici, secondo logiche strettamente correlate alle finalità dichiarate e comunque in modo da garantire la sicurezza e la riservatezza dei dati stessi. Misure tecniche e organizzative specifiche (crittografia, backup, accessi autenticati, pseudonimizzazione quando applicabile) vengono adottate per limitare i rischi di perdita, uso illecito, accessi non autorizzati o divulgazione non consentita.
                </p>
                <h4>5. Obbligatorietà del conferimento</h4>
                <p>
                    Il conferimento dei dati identificativi e di contatto è obbligatorio per la registrazione e l’accesso ai servizi di prenotazione. Il mancato conferimento comporta l’impossibilità di utilizzare la piattaforma. Il consenso per finalità di marketing o profilazione, invece, è facoltativo e la mancata prestazione non pregiudica i servizi principali.
                </p>
                <h4>6. Destinatari e comunicazione dei dati</h4>
                <p>
                    I dati possono essere comunicati alle seguenti categorie di destinatari, strettamente per le finalità dichiarate:
                </p>
                <ul>
                    <li>Fornitori di servizi tecnici e cloud;</li>
                    <li>Partner e organizzatori di eventi (limitatamente ai dati necessari alla gestione delle prenotazioni);</li>
                    <li>Amministrazione finanziaria e altri soggetti per obblighi di legge;</li>
                    <li>Soggetti delegati o incaricati dalla società a svolgere attività strettamente correlate alle finalità sopra esposte;</li>
                    <li>Provider di sistemi di pagamento sicuri;</li>
                </ul>
                <p>
                    Non è previsto il trasferimento fuori dallo Spazio Economico Europeo, salvo uso di servizi cloud conformi al GDPR.
                </p>
                <h4>7. Conservazione dei dati</h4>
                <p>
                    I dati raccolti sono conservati per il tempo strettamente necessario alle finalità indicate, ovvero fino alla cancellazione dell’account, salvo obblighi di legge che impongano periodi di conservazione ulteriori (ad esempio per motivi fiscali o contabili). I dati di profilazione e per finalità di marketing verranno conservati per non oltre 24 mesi dal consenso, salvo rinnovo dello stesso.
                </p>
                <h4>8. Diritti dell’utente</h4>
                <p>
                    Ai sensi degli artt. 15-22 del GDPR, l’utente ha diritto in qualsiasi momento di:
                </p>
                <ul>
                    <li>Ottenere conferma dell’esistenza di dati che lo riguardano e accedervi;</li>
                    <li>Chiedere la rettifica, l’aggiornamento, l’integrazione o la cancellazione dei dati;</li>
                    <li>Opporsi o limitare il trattamento per motivi legittimi o revocare il consenso per trattamenti basati su consenso;</li>
                    <li>Ricevere copia dei dati forniti in formato strutturato (portabilità).</li>
                </ul>
                <p>
                    Le richieste relative all’esercizio dei propri diritti possono essere indirizzate al Titolare tramite i contatti sopra indicati.
                </p>
                <h4>9. Reclamo all’Autorità Garante</h4>
                <p>
                    L’utente ha diritto di proporre reclamo all’Autorità Garante per la protezione dei dati personali italiana, secondo le modalità disponibili sul sito <a href="https://www.garanteprivacy.it" target="_blank" rel="noopener">www.garanteprivacy.it</a>, qualora ritenga che il trattamento dei dati effettuato da EventBooking violi la normativa applicabile.
                </p>
                <h4>10. Aggiornamenti della policy</h4>
                <p>
                    EventBooking si riserva il diritto di aggiornare la presente informativa; le modifiche saranno tempestivamente comunicate agli utenti attraverso il sito o via email. Si raccomanda di consultare periodicamente questa sezione per restare informati sulle eventuali novità.
                </p>
                <p>
                    <strong>Ultimo aggiornamento:</strong> 19/07/2025
                </p>
                <p>
                    Questa privacy policy è redatta ai sensi degli articoli 13 e 14 del Regolamento UE 2016/679 (GDPR), inoltre integra e completa quanto stabilito dalla Cookie Policy e dai Termini di Servizio della piattaforma.
                </p>
            </div>


            <!-- Cookie Policy Section -->
            <div id="cookies" class="section">
                <h2><i class="fa fa-cookie-bite"></i>Cookie Policy</h2>
                <h3>Cookie Policy di EventBooking</h3>
                <h4>1. Titolare del Trattamento</h4>
                <p>
                    Il titolare del trattamento dei dati raccolti tramite i cookie è EventBooking. Per qualsiasi informazione o richiesta sull’uso dei cookie, è possibile contattare EventBooking ai riferimenti indicati nella sezione "Contatti" del sito.
                </p>
                <h4>2. Cosa sono i Cookie</h4>
                <p>
                    I cookie sono piccoli file di testo che i siti web visitati inviano al browser dell’utente, dove vengono memorizzati per essere poi ritrasmessi agli stessi siti alla visita successiva. I cookie consentono al sito di riconoscere gli utenti e memorizzare determinate informazioni per migliorare la loro esperienza online.
                </p>
                <h4>3. Tipologie di Cookie Utilizzati</h4>
                <p>
                    EventBooking utilizza le seguenti categorie di cookie:
                </p>
                <ul>
                    <li><strong>Cookie Tecnici:</strong> necessari per il corretto funzionamento del sito e per permettere la navigazione. Questi cookie assicurano le funzionalità di base, come la gestione delle sessioni utente e l’accesso alle aree riservate. Non richiedono il consenso dell’utente (ai sensi della direttiva 2009/136/CE, recepita dal D.Lgs. 28 maggio 2012 n. 69).</li>
                    <li><strong>Cookie Analitici:</strong> raccolgono informazioni aggregate e anonime sull’uso del sito (ad esempio, pagine visitate, durata delle visite, modalità di interazione) per migliorare prestazioni e servizi. Se non anonimizzati, viene richiesto il consenso dell’utente.</li>
                    <li><strong>Cookie di Profilazione:</strong> utilizzati per tracciare la navigazione degli utenti e creare profili sulle preferenze e le abitudini. Permettono di inviare pubblicità mirata in linea con gli interessi manifestati. Sono installati solo previo consenso esplicito dell’utente; la mancata accettazione non limita l’uso del sito, ma impedisce contenuti e offerte personalizzate.</li>
                </ul>
                <h4>4. Cookie di Terze Parti</h4>
                <p>
                    EventBooking può installare cookie di terze parti, come fornitori di servizi pubblicitari, piattaforme di analisi, social network o partner commerciali. L’utilizzo di tali cookie è soggetto alle rispettive informative che si consiglia di consultare per ulteriori dettagli.
                </p>
                <h4>5. Gestione delle Preferenze e Consenso</h4>
                <p>
                    Al primo accesso al sito, un banner informa l’utente dell’uso dei cookie e consente di accettare o negare l’installazione dei cookie non tecnici. L’utente può in qualsiasi momento modificare o revocare il proprio consenso tramite l’apposita area di gestione delle preferenze presente sul sito.
                </p>
                <h4>6. Modalità di Disabilitazione dei Cookie</h4>
                <p>
                    L’utente può gestire e/o disabilitare i cookie direttamente attraverso le impostazioni del proprio browser, seguendo le istruzioni specifiche per ciascun programma di navigazione. Attenzione: disabilitare i cookie tecnici potrebbe compromettere il funzionamento del sito e l’accesso ad alcune funzionalità.
                </p>
                <h4>7. Finalità e Conservazione</h4>
                <p>
                    I cookie vengono utilizzati esclusivamente per le finalità indicate. I dati raccolti attraverso i cookie vengono conservati nel rispetto della normativa vigente; i tempi di conservazione possono variare sulla base della tipologia di cookie e della finalità perseguita.
                </p>
                <h4>8. Aggiornamenti della Cookie Policy</h4>
                <p>
                    La presente cookie policy può essere soggetta a modifiche: EventBooking informerà gli utenti attraverso aggiornamenti pubblicati sul sito. Si invita a consultare periodicamente questa sezione per restare aggiornati.
                </p>
                <p>
                    Questa informativa è redatta ai sensi della normativa vigente in materia di protezione dei dati personali (GDPR, Direttiva ePrivacy e Provvedimenti del Garante Privacy).<br>
                    Per ulteriori dettagli consulta la privacy policy presente sul sito.
                </p>
            </div>


            <!-- Terms of Service Section -->
            <div id="terms" class="section">
                <h2><i class="fa fa-file-contract"></i>Termini di Servizio</h2>
                
                <h3>Termini di Servizio di EventBooking</h3>
                <h4>1. Oggetto del Servizio</h4>
                <p>
                    EventBooking offre agli utenti registrati la possibilità di prenotare la partecipazione a eventi organizzati da terzi o direttamente dalla piattaforma. L’accesso ai servizi di prenotazione è riservato esclusivamente agli utenti che hanno completato la procedura di registrazione e hanno un account attivo.
                </p>
                <h4>2. Registrazione e Account Utente</h4>
                <p>
                    La registrazione è obbligatoria per poter effettuare prenotazioni.<br>
                    L’utente si impegna a fornire dati personali veritieri e aggiornati durante la registrazione.<br>
                    Ogni utente può creare un solo account, che è strettamente personale e non cedibile.<br>
                    È responsabilità dell’utente mantenere la riservatezza delle proprie credenziali di accesso e ogni attività effettuata tramite il proprio account sarà ritenuta di sua esclusiva responsabilità.<br>
                    EventBooking si riserva il diritto di sospendere o cancellare l’account in caso di uso improprio, violazione dei presenti termini o fornitura di informazioni false.
                </p>
                <h4>3. Prenotazione di Eventi</h4>
                <p>
                    Solo gli utenti registrati possono effettuare prenotazioni tramite la piattaforma.<br>
                    La conferma della prenotazione avviene tramite comunicazione elettronica (email o notifica all’interno dell’account).<br>
                    L’utente è responsabile della correttezza delle informazioni inserite e della scelta dell’evento da prenotare.
                </p>
                <h4>4. Modifica e Cancellazione delle Prenotazioni</h4>
                <p>
                    Le condizioni per la modifica o la cancellazione di una prenotazione, compresi eventuali rimborsi, variano a seconda dell’evento e saranno indicate durante la procedura di prenotazione.<br>
                    EventBooking si impegna a garantire la massima trasparenza su tutte le condizioni relative ai singoli eventi.
                </p>
                <h4>5. Responsabilità della Piattaforma</h4>
                <p>
                    EventBooking si limita a fornire un servizio di intermediazione tra utenti e organizzatori di eventi.<br>
                    Non è responsabile per la cancellazione, il rinvio o eventuali modifiche degli eventi da parte degli organizzatori.<br>
                    Non assume responsabilità per il comportamento degli utenti o per danni derivanti dalla partecipazione agli eventi.
                </p>
                <h4>6. Uso Leale e Limitazioni</h4>
                <p>
                    È fatto divieto di utilizzare la piattaforma per attività illecite, fraudolente o che possano arrecare danno ad altri utenti o a terzi.<br>
                    EventBooking si riserva il diritto di limitare, sospendere o interrompere l’accesso ai servizi in caso di violazioni delle regole o uso improprio della piattaforma.
                </p>
                <h4>7. Modifiche ai Termini di Servizio</h4>
                <p>
                    EventBooking può modificare in qualsiasi momento i presenti termini; le modifiche saranno comunicate agli utenti tramite email o notifica interna.<br>
                    L’uso continuato della piattaforma dopo le modifiche ai termini implica l’accettazione delle nuove condizioni.
                </p>
                <h4>8. Privacy e Dati Personali</h4>
                <p>
                    I dati raccolti durante la registrazione e l’uso dei servizi saranno trattati in conformità alla normativa vigente e secondo quanto previsto nell’Informativa Privacy pubblicata sul sito.
                </p>
                <h4>9. Legge Applicabile e Foro Competente</h4>
                <p>
                    I presenti termini sono regolati dalla legge italiana.<br>
                    Per qualsiasi controversia relativa all’interpretazione e applicazione dei presenti termini sarà competente il foro di residenza o domicilio dell’utente, se ubicato in Italia.<br>
                    Questi termini possono essere personalizzati e integrati secondo esigenze specifiche della piattaforma e delle normative di settore.
                </p>

                <h3><i class="fa fa-envelope"></i>Contatti</h3>
                <p>Per qualsiasi domanda riguardo questi termini o per supporto con le prenotazioni, contattaci:</p>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <div class="contact-card">
                            <div class="contact-icon">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <h5>Email</h5>
                            <a href="mailto:marco.bedeschi@itsolivetti.it" class="contact-link">marco.bedeschi@itsolivetti.it</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="contact-card">
                            <div class="contact-icon">
                                <i class="fa fa-phone"></i>
                            </div>
                            <h5>Telefono</h5>
                            <a href="tel:+393499415015" class="contact-link">+39 349 9415015</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <br><br>

    <?php
        include 'footer.php';
        include 'cookie_banner.php';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        showSection('<?php echo $selectedSection; ?>');
        // Funzione per mostrare la sezione selezionata
        function showSection(sectionId) {
            console.log("Showing section:", sectionId);
            // Nascondi tutte le sezioni
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Rimuovi la classe active da tutti i bottoni
            const buttons = document.querySelectorAll('.nav-btn');
            buttons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Mostra la sezione selezionata
            document.getElementById(sectionId).classList.add('active');
            
            // Aggiungi la classe active al bottone corrispondente
            event.target.classList.add('active');
            
            // Scroll verso la sezione
            document.querySelector('.legal-content').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Funzione per gestire i parametri URL
        function handleUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            
            if (section && ['privacy', 'cookies', 'terms'].includes(section)) {
                showSection(section);
                // Attiva il bottone corrispondente
                const buttons = document.querySelectorAll('.nav-btn');
                buttons.forEach(button => {
                    const buttonText = button.textContent.toLowerCase();
                    if ((section === 'cookies' && buttonText.includes('cookie')) ||
                        (section === 'privacy' && buttonText.includes('privacy')) ||
                        (section === 'terms' && buttonText.includes('termini'))) {
                        button.classList.add('active');
                    }
                });
            } else {
                // Mostra privacy policy di default
                showSection('privacy');
                document.querySelector('.nav-btn').classList.add('active');
            }
        }

        // Inizializza la pagina
        document.addEventListener('DOMContentLoaded', function() {
            handleUrlParams();
        });

        // Aggiorna l'URL quando si cambia sezione
        const originalShowSection = showSection;
        showSection = function(sectionId) {
            originalShowSection(sectionId);
            history.pushState(null, null, `?section=${sectionId}`);
        };
    </script>
</body>
</html>