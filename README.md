# EventBooking 🎵

**EventBooking** è una piattaforma web moderna per la gestione e prenotazione di eventi musicali e culturali. Il sistema offre un'interfaccia utente elegante e un pannello di amministrazione completo per organizzatori di eventi.

## 🚀 Caratteristiche Principali

### Per gli Utenti
- **Registrazione e Login Sicuro**: Sistema di autenticazione con validazione email
- **Profilo Utente Personalizzato**: Gestione dei propri dati e foto profilo
- **Catalogo Eventi**: Visualizzazione di tutti gli eventi disponibili con filtri avanzati
- **Prenotazione Online**: Sistema di prenotazione con supporto per più posti
- **Gestione Pagamenti**: Integrazione sicura per pagamenti con carta di credito
- **Storico Prenotazioni**: Visualizzazione di tutte le prenotazioni effettuate

### Per gli Amministratori
- **Dashboard Amministrativa**: Pannello completo per la gestione del sistema
- **Gestione Eventi**: Creazione, modifica ed eliminazione di eventi
- **Gestione Utenti**: Amministrazione degli account utente
- **Monitoring Prenotazioni**: Visualizzazione e gestione di tutte le prenotazioni
- **Sistema di Sicurezza**: Protezione contro attacchi brute force

## 🛠️ Tecnologie Utilizzate

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5.3.3
- **Backend**: PHP 8.0
- **Database**: MySQL 8.0
- **Sicurezza**: Hash delle password, protezione CSRF, validazione input
- **UI/UX**: Font Awesome, Bootstrap Icons, animazioni CSS personalizzate

## 📋 Requisiti di Sistema

- **Server Web**: Apache/Nginx
- **PHP**: Versione 8.0 o superiore
- **MySQL**: Versione 8.0 o superiore
- **Estensioni PHP**: mysqli, session, hash

## 🔧 Installazione

1. **Clona il repository**
   ```bash
   git clone https://github.com/tuousername/eventbooking.git
   cd eventbooking
   ```

2. **Configura il Database**
   - Importa il file `my_projectworkgruppoquattro.sql` nel tuo database MySQL
   - Crea un file `password.php` per la configurazione del database:
   ```php
   <?php
   $servername = "localhost";
   $username = "your_username";
   $password = "your_password";
   $dbname = "my_projectworkgruppoquattro";

   $conn = new mysqli($servername, $username, $password, $dbname);
   $myDB = $conn; // Alias per compatibilità
   ?>
   ```

3. **Configura i Permessi**
   - Assicurati che la cartella `Immagini/` sia scrivibile
   - Configura i permessi appropriati per l'upload delle foto profilo

4. **Accedi al Sistema**
   - Visita `http://localhost/eventbooking`
   - Registra un nuovo account o usa le credenziali amministrative

## 📁 Struttura del Progetto

```
EventBooking/
├── index.php              # Pagina principale
├── login.php              # Sistema di autenticazione
├── register.php           # Registrazione utenti
├── Eventi.php              # Catalogo eventi
├── prenotazione.php        # Sistema prenotazioni
├── profilo.php             # Gestione profilo utente
├── amministrazione.php     # Pannello admin
├── navbar.php              # Navigazione
├── footer.php              # Footer del sito
├── cookie_banner.php       # Banner cookie policy
├── 404.php                 # Pagina errore 404
├── logout.php              # Logout
├── clear_attempts.php      # Reset tentativi login
├── informazionilegali.php  # Informazioni legali
├── Immagini/               # Risorse grafiche
│   ├── logo.png           # Logo principale
│   ├── Eventi/            # Immagini eventi
│   └── foto_utenti/       # Foto profilo utenti
└── stili/                  # Fogli di stile CSS
    ├── stile_index.css    # Stili homepage
    ├── stile_eventi.css   # Stili pagina eventi
    ├── stile_login.css    # Stili login/register
    ├── stile_profilo.css  # Stili profilo
    ├── stile_navfoo.css   # Stili navigazione
    └── ...                # Altri stili
```

## 🎯 Funzionalità Dettagliate

### Sistema di Prenotazione
- Prenotazione multipla con inserimento nominativi
- Calcolo automatico del prezzo totale
- Gestione disponibilità posti in tempo reale
- Conferma prenotazione via email

### Pannello Amministrativo
- Statistiche dashboard con grafici
- CRUD completo per eventi e utenti
- Gestione prenotazioni con filtri avanzati
- Sistema di backup e ripristino

### Sicurezza
- Protezione contro SQL Injection
- Hash sicuro delle password con `password_hash()`
- Sistema anti-brute force per login
- Validazione lato server di tutti gli input
- Protezione CSRF per form critici

## 🚦 Stati del Progetto

- ✅ Sistema di autenticazione completo
- ✅ Gestione eventi e prenotazioni
- ✅ Pannello amministrativo
- ✅ UI/UX responsive e moderna
- ✅ Sistema di pagamento integrato
- ✅ Gestione immagini e upload file

## 📝 Licenza

Questo progetto è sviluppato per scopi educativi nell'ambito del corso ProjectWork.

## 👥 Sviluppatori

Progetto realizzato dal **Gruppo Quattro** per il corso ProjectWork 2025.

## 📧 Contatti

Per supporto o domande: **bassiandrea24@gmail.com**

---

⭐ Se questo progetto ti è stato utile, lascia una stella su GitHub!
