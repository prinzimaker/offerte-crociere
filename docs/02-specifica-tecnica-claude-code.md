# MSC API Proxy — Specifica Tecnica per Implementazione

## Panoramica del progetto

Realizzare un sistema composto da due componenti web su PHP 8.3:

1. **API Server** (`mscapi.fttn.it`) — endpoint REST che riceve chiamate da OCM e le logga su database
2. **Log Viewer** (`msclog.fttn.it`) — interfaccia web per consultare i log registrati

Il progetto è la **Fase 1** di un proxy che in futuro dovrà anche trasformare e inoltrare richieste verso le API MSC Crociere. Il codice deve quindi essere strutturato in modo da essere estensibile.

## Ambiente operativo

- **Server**: produzione (non esiste ambiente di sviluppo)
- **PHP**: 8.3
- **Database**: MySQL/MariaDB (verificare quale è disponibile sul server)
- **Web server**: Apache (verificare configurazione, probabilmente con VirtualHost per i due domini)
- **Approccio**: incrementale, ogni step deve essere testabile indipendentemente
- **Nessun framework pesante**: il progetto deve essere leggero e autocontenuto. Si può usare Composer per le dipendenze ma evitare framework come Laravel/Symfony. Preferire un micro-approccio.

## ATTENZIONE — Regole operative

- **Siamo in produzione.** Ogni modifica deve essere fatta con cautela.
- **Non cancellare mai file esistenti** sul server senza esplicita conferma.
- **Ogni step va testato** prima di procedere al successivo.
- **Backup** prima di ogni modifica significativa.
- **Chiedi sempre conferma** prima di toccare configurazioni Apache, DNS, o database.

---

## Struttura del progetto

```
/var/www/mscapi/                    ← radice del progetto (verificare path col team)
│
├── api/                            ← mscapi.fttn.it (API Server)
│   ├── public/
│   │   └── index.php              ← entry point, router
│   ├── src/
│   │   ├── Config.php             ← configurazione (DB, token, ecc.)
│   │   ├── Database.php           ← connessione e query DB (PDO)
│   │   ├── Auth.php               ← validazione token
│   │   ├── Router.php             ← routing delle richieste
│   │   ├── Logger.php             ← logica di salvataggio log
│   │   └── Response.php           ← helper per risposte JSON standardizzate
│   └── .htaccess                  ← rewrite rules per routing
│
├── log/                            ← msclog.fttn.it (Log Viewer)
│   ├── public/
│   │   └── index.php              ← entry point
│   ├── src/
│   │   ├── Config.php             ← configurazione
│   │   ├── Database.php           ← condiviso o importato
│   │   ├── Auth.php               ← autenticazione HTTP Basic (uid/password)
│   │   └── LogViewer.php          ← logica di lettura e filtraggio log
│   ├── templates/
│   │   ├── layout.php             ← template base HTML
│   │   ├── login.php              ← pagina di login
│   │   └── dashboard.php          ← vista principale dei log
│   └── .htaccess
│
├── shared/                         ← codice condiviso tra i due componenti
│   ├── Database.php               ← classe DB condivisa
│   └── Config.php                 ← config condivisa (credenziali DB)
│
├── sql/
│   └── schema.sql                 ← DDL per la creazione delle tabelle
│
├── composer.json                   ← se servono dipendenze
└── README.md
```

---

## Componente 1: API Server (mscapi.fttn.it)

### Endpoint

```
POST https://mscapi.fttn.it/log
```

### Autenticazione

Header HTTP:
```
Authorization: Bearer {token}
```

Il token è una stringa fissa definita in configurazione. Validazione:

```php
// Pseudocodice
if ($receivedToken !== Config::API_TOKEN) {
    return Response::json(401, ['error' => 'Unauthorized']);
}
```

### Parametri accettati (POST body, JSON)

| Parametro  | Tipo   | Obbligatorio | Descrizione |
|------------|--------|:---:|-------------|
| `function` | string | ✅ | Identificativo dell'operazione/origine della richiesta in OCM |
| `data`     | object/string | ✅ | Dati JSON associati alla richiesta |

### Esempio di richiesta

```http
POST /log HTTP/1.1
Host: mscapi.fttn.it
Authorization: Bearer 1234
Content-Type: application/json

{
    "function": "checkCabinAvailability",
    "data": {
        "cruiseId": "MSC12345",
        "cabinType": "balcony",
        "date": "2025-03-15",
        "passengers": 2,
        "maxPrice": 1200.00
    }
}
```

### Esempio di risposta

```json
{
    "status": "ok",
    "message": "Logged successfully",
    "log_id": 1234
}
```

### Cosa salva nel DB

Ogni chiamata viene salvata nella tabella `api_logs` con:
- ID autoincrementale
- `function` (stringa ricevuta)
- `data` (JSON ricevuto, salvato come TEXT/JSON)
- `ip_address` (IP del chiamante)
- `http_method` (GET/POST)
- `headers` (tutti gli header della richiesta, opzionale, come JSON)
- `created_at` (timestamp)

### Gestione errori

- Token mancante/invalido → 401
- Parametri mancanti → 400 con dettaglio del campo mancante
- Errore DB → 500 con messaggio generico (no dettagli interni)
- Tutti gli errori vengono comunque loggati su file (`/var/log/mscapi/error.log` o percorso configurabile)

---

## Componente 2: Log Viewer (msclog.fttn.it)

### Autenticazione

HTTP Basic Auth oppure form di login con sessione PHP. Credenziali fisse in configurazione:

```php
// Config
const LOG_USER = 'admin';
const LOG_PASS = 'password_da_definire';
```

### Funzionalità della dashboard

1. **Lista log paginata** (ultimi N record, con paginazione)
2. **Filtri**:
   - Per data/intervallo di date (da — a)
   - Per valore di `function` (dropdown con valori distinti trovati nel DB)
   - Per testo libero su `data` (LIKE search)
   - Per IP di origine
3. **Dettaglio singolo log**: cliccando su una riga, mostra il JSON completo formattato (pretty-print)
4. **Contatori**: numero totale di chiamate, raggruppate per `function` (una mini-dashboard)
5. **Export** (opzionale, fase successiva): download CSV dei risultati filtrati

### Interfaccia

- HTML semplice e funzionale, nessun framework JS pesante.
- CSS minimale (si può usare un CSS utility come Pico CSS o simile, caricato da CDN).
- Il JSON va visualizzato formattato e con syntax highlighting (una libreria JS leggera o `<pre>` con formattazione).
- La tabella dei log deve essere leggibile e ordinabile.

---

## Database

### Schema (schema.sql)

```sql
CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `function_name` VARCHAR(255) NOT NULL,
    `data` LONGTEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `http_method` VARCHAR(10) DEFAULT 'POST',
    `headers` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_function` (`function_name`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_function_date` (`function_name`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Note sul DB

- Usare PDO con prepared statements (mai query concatenate).
- La colonna `data` è LONGTEXT perché non sappiamo quanto saranno grandi i JSON.
- Gli indici su `function_name` e `created_at` sono essenziali per i filtri del Log Viewer.
- Prevedere in futuro una politica di retention/pulizia dei log vecchi (non implementare ora).

---

## Configurazione

File di configurazione unico in `shared/Config.php`:

```php
<?php
return [
    'db' => [
        'host'     => 'localhost',
        'dbname'   => 'mscapi',
        'user'     => 'mscapi_user',
        'password' => 'DA_DEFINIRE',
        'charset'  => 'utf8mb4',
    ],
    'api' => [
        'token' => '1234',  // Token fisso per autenticazione API
    ],
    'log_viewer' => [
        'username' => 'admin',
        'password' => 'DA_DEFINIRE',
    ],
];
```

Questo file **non deve essere nella document root** dei VirtualHost (accessibile solo via PHP, mai via browser).

---

## Step di implementazione (ordine)

### Step 1 — Setup base
- [ ] Verificare la struttura del server (path, versione PHP, DB disponibile)
- [ ] Creare il database e la tabella
- [ ] Creare la struttura delle cartelle del progetto
- [ ] Creare il file di configurazione

### Step 2 — API Server (mscapi.fttn.it)
- [ ] Creare `index.php` con routing base
- [ ] Implementare autenticazione Bearer Token
- [ ] Implementare endpoint `POST /log`
- [ ] Implementare salvataggio su DB
- [ ] Implementare risposte JSON standardizzate
- [ ] Implementare gestione errori
- [ ] Testare con curl o Postman

### Step 3 — VirtualHost Apache
- [ ] Configurare VirtualHost per `mscapi.fttn.it`
- [ ] Configurare VirtualHost per `msclog.fttn.it`
- [ ] Configurare certificati SSL (Let's Encrypt o esistenti)
- [ ] Testare che i domini rispondano correttamente

### Step 4 — Log Viewer (msclog.fttn.it)
- [ ] Implementare autenticazione (login)
- [ ] Implementare dashboard con lista log paginata
- [ ] Implementare filtri (data, function, testo)
- [ ] Implementare vista dettaglio con JSON formattato
- [ ] Implementare contatori per function

### Step 5 — Integrazione con OCM
- [ ] Aggiungere in OCM la chiamata al proxy (invio copia delle richieste)
- [ ] Verificare che i log arrivino correttamente
- [ ] Monitorare per qualche giorno

### Step 6 — Analisi e pianificazione Fase 2
- [ ] Analizzare i log raccolti
- [ ] Catalogare tutte le `function` distinte
- [ ] Pianificare la traduzione delle richieste

---

## Note aggiuntive per Claude Code

- **Non usare framework**: il progetto deve restare semplice e autocontenuto.
- **PHP 8.3**: sfruttare le feature moderne (typed properties, enums se utili, match, named arguments, ecc.)
- **Codice pulito**: classi ben separate, responsabilità singola, nomi chiari.
- **Sicurezza**: prepared statements, escape output HTML, validazione input, no errori esposti al pubblico.
- **Ogni file deve avere un commento in testa** che spiega cosa fa.
- **Non installare Composer se non strettamente necessario** per questa fase.
- **Chiedi sempre conferma prima di**: creare database, modificare configurazioni Apache, toccare file fuori dal progetto.
