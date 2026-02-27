# MSC API Proxy — Documento di Soluzione Architetturale

## Contesto

Il sistema attuale (Drupal + OCM) comunica con le API di MSC Crociere per verificare la disponibilità di cabine e relative tariffe. A seguito di modifiche apportate da MSC alle proprie API, il backend OCM (PHP 7.2) non è più in grado di ottenere risposte affidabili in tempo reale.

Il codice di OCM è fortemente personalizzato e non facilmente manutenibile. Intervenire direttamente su di esso comporterebbe rischi elevati di regressione e tempi non prevedibili.

## Soluzione proposta

Viene introdotto un **nuovo componente proxy**, scritto in PHP 8.3, che si posiziona tra OCM e le API MSC. Questo componente opera **in parallelo al sistema esistente**, senza modificarne il funzionamento: il sito resta in produzione e operativo durante tutto il processo.

### Fase 1 — Logging e analisi (oggetto di questo documento)

Prima di intervenire sulle chiamate, è necessario comprendere esattamente quali richieste OCM invia verso MSC, con quali dati e con quale frequenza. Per questo motivo la prima fase consiste nell'attivazione di un sistema di **intercettazione e registrazione** delle chiamate.

#### Come funziona

1. OCM, nel momento in cui effettua una chiamata verso MSC, invierà una copia della richiesta anche al nuovo proxy.
2. Il proxy **registra** ogni chiamata in un database, salvando:
   - Il tipo di operazione richiesta (parametro `function`)
   - I dati associati alla richiesta (parametro `data`, in formato JSON)
   - Data e ora della chiamata
   - Indirizzo IP di origine
3. I dati registrati sono consultabili tramite un'**interfaccia web protetta da credenziali di accesso**.

#### Componenti

| Componente | Dominio | Funzione |
|---|---|---|
| **API Server** | `mscapi.fttn.it` | Riceve le chiamate da OCM e le registra sul database |
| **Log Viewer** | `msclog.fttn.it` | Interfaccia web per consultare e analizzare le chiamate registrate |

#### Sicurezza

- Le chiamate API sono protette da un **token di autenticazione**.
- L'interfaccia di consultazione log è protetta da **username e password**.
- Nessun dato sensibile degli utenti finali viene registrato.

### Impatto sul sistema in produzione

- **Il sito continua a funzionare normalmente.** Il proxy non sostituisce né interrompe alcun flusso esistente.
- OCM continuerà a operare come oggi, con le sue logiche di fallback su database locale.
- Il proxy riceve una **copia** delle richieste: anche in caso di malfunzionamento del proxy, il flusso originale non viene alterato.
- L'approccio è **incrementale**: ogni passo viene verificato prima di procedere al successivo.

### Fasi successive (pianificate ma non ancora attive)

Una volta completata la fase di logging e analisi, e comprese tutte le tipologie di chiamate, si procederà con:

- **Fase 2** — Traduzione delle richieste dal vecchio al nuovo formato API MSC
- **Fase 3** — Sostituzione delle risposte, con il proxy che restituisce a OCM i dati nel formato atteso
- **Fase 4** — Gestione dei fallback e delle risposte in caso di errore

Ogni fase verrà concordata e approvata prima dell'implementazione.

## Schema del flusso — Fase 1

```
┌──────────┐     ┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│  Utente  │────▶│   Drupal     │────▶│   OCM (PHP 7.2)  │────▶│  MSC API     │
│          │     │  Frontend    │     │                  │     │  (invariato) │
└──────────┘     └──────────────┘     └────────┬─────────┘     └──────────────┘
                                               │
                                               │ copia della richiesta
                                               ▼
                                      ┌──────────────────┐
                                      │  NUOVO PROXY     │
                                      │  mscapi.fttn.it  │
                                      │  (PHP 8.3)       │
                                      └────────┬─────────┘
                                               │
                                               ▼
                                      ┌──────────────────┐     ┌──────────────────┐
                                      │   Database Log   │◀────│  Log Viewer      │
                                      │                  │     │  msclog.fttn.it  │
                                      └──────────────────┘     └──────────────────┘
```

## Note

- Non è disponibile un ambiente di sviluppo/staging: il lavoro viene svolto direttamente in produzione, con un approccio a step controllati.
- Ogni modifica viene testata prima di essere considerata attiva.
- Il sistema è progettato per essere completamente reversibile in ogni fase.
