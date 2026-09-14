# Music Share — estensione phpBB 3.3.x / PHP 8.2

Versione corrente: **1.24.2** (il numero si aggiorna nel campo `version` di `composer.json`).

Estensione in stile Spotify: upload brani (UCP + sezione forum dedicata),
sfoglia per genere, playlist personali, player globale con copertina,
tag, waveform e barra di avanzamento.

## Stato: completa e funzionante ✅

Tutte le parti concordate sono state implementate:

- **Database**: tabelle generi, brani, relazione brano-genere, playlist,
  relazione playlist-brano (`migrations/install_schema.php`)
- **ACL**: `u_musicshare_upload`, `u_musicshare_playlist`,
  `m_musicshare_manage` (categoria dedicata, assegnati di default ai
  REGISTERED — modificabile da ACP → Permessi)
- **Config**: percorso storage, estensioni ammesse, dimensione massima
  file, spazio massimo utente, waveform on/off, brani per pagina,
  moderazione on/off
- **ACP** (`acp/`): gestione generi (CRUD) + impostazioni, con verifica
  automatica della scrivibilità della cartella di storage
- **Upload + metadati** (`service/upload_handler.php`,
  `service/metadata_extractor.php`): estrazione titolo/artista/album/anno
  e copertina incorporata via `getid3`, validazione mime reale,
  quota per utente, cartelle per utente (`<storage>/<user_id>/...`)
- **UCP** (`ucp/`): "I miei brani" (modifica/elimina), "Carica brano",
  "Le mie playlist" (crea/modifica/elimina, gestisci brani contenuti)
- **Pagine pubbliche** (`controller/main.php`): sfoglia per genere,
  vista playlist pubblica, form di upload lato forum
- **Streaming** (`controller/stream.php`): file audio serviti con
  supporto `Range`/`Content-Range` (seek su file grandi) e copertine
- **Player globale persistente** (`styles/all/template/musicshare_player.html`
  + `musicshare.js`): iniettato in ogni pagina via template event,
  play/pausa/stop/avanti/indietro, **coda di riproduzione visibile e
  riordinabile** (pulsanti su/giù, rimozione, salto diretto a un brano),
  barra di avanzamento con seek, volume, marquee per titolo/artista
  lunghi, forma d'onda disegnata lato client via Web Audio API,
  **dissolvenza leggera** tra un brano e il successivo, **pulsante per
  ridurre il player a barra sottile**
- **Menu "Aggiungi a playlist"** a comparsa (sostituisce il prompt()
  iniziale): elenca le playlist dell'utente via AJAX e permette anche
  di crearne una nuova al volo; protetto da un hash anti-CSRF di sessione
  (`generate_link_hash`/`check_link_hash`)
- **Moderazione in ACP**: nuova scheda "Moderazione" con l'elenco dei
  brani in attesa di approvazione (quando `musicshare_require_approval`
  è attivo), con azioni Approva / Rifiuta ed elimina
- **Ricerca** (`/musicshare/search`) per titolo, artista o album, con
  paginazione
- **"I più ascoltati"**: sezione nella pagina Sfoglia con i 10 brani
  con più riproduzioni
- Lingue complete it/en (front-end, ACP, UCP, permessi)

## Nota tecnica sulla waveform

Genera i picchi audio **lato client** (Web Audio API + `<canvas>`) al
momento della riproduzione, invece che precalcolarli lato server
all'upload: evita una dipendenza da `ffmpeg` (non garantita su ogni
hosting) e funziona per qualsiasi formato che il browser sappia
decodificare. Il rovescio della medaglia è un breve calcolo alla prima
riproduzione di un brano (impercettibile per file di pochi MB). Se in
futuro vuoi il precalcolo server-side, si può aggiungere quando è
garantita la presenza di `ffmpeg`/`ffprobe` sul server.

## Installazione

1. Estrai lo zip dentro `ext/` del forum, così da ottenere
   `ext/salvocortesiano/musicshare/`
2. Nella cartella dell'estensione esegui `composer install --no-dev`
   (serve a scaricare `james-heinrich/getid3`, usato per leggere i tag
   audio); in alternativa richiedi il pacchetto dal composer.json della
   root del forum, se lo gestisci già così
3. Da ACP → Gestione estensioni, abilita "Music Share": crea
   automaticamente le tabelle, i permessi e i moduli ACP/UCP
4. Da ACP → Music Share → Impostazioni, verifica che la cartella di
   storage sia scrivibile e configura formati/limiti a piacere
5. Da ACP → Music Share → Generi, crea i generi musicali
6. Da ACP → Permessi, assegna `u_musicshare_upload` ai gruppi che
   vuoi abilitare all'upload (di default è già assegnato ai REGISTERED)

## Possibili migliorie future (non incluse)

- Coda riordinabile tramite trascinamento (drag&drop) invece dei soli
  pulsanti su/giù (già presenti e funzionanti)
- Crossfade vero e proprio tra brani sovrapposti (ora c'è una
  dissolvenza in uscita sul brano corrente prima di caricare il
  successivo, non una sovrapposizione)
- Player minimizzato/espanso più rifinito graficamente

## Note tecniche

- Formati audio previsti: mp3, ogg/oga, flac, wav, m4a, aac
- Storage: `<storage_path>/<user_id>/<song_id>.<ext>` e
  `.../<user_id>/covers/<song_id>.jpg`, percorso configurabile da ACP
- Crediti inclusi nell'estensione: Salvo Cortesiano —
  https://netshadows.de — info@netshadows.de

## Aggiunte successive

- **Protezione dello storage**: creazione automatica di `.htaccess` e
  `index.html` nella cartella dei brani, con avviso in ACP se la cartella
  risulta raggiungibile dal web
- **Moderazione completa**: la scheda Moderazione elenca sia i brani in
  attesa sia **tutti** i brani, con ricerca, paginazione, approva/revoca
  ed elimina. Il permesso `m_musicshare_manage` è ora realmente usato:
  chi lo possiede vede il pulsante di eliminazione anche nelle liste
  pubbliche, senza bisogno di accedere all'ACP
- **Anti-duplicati** tramite impronta MD5 del file (attivabile da ACP)
- **Media Session API**: brano, copertina e controlli play/pausa/avanti
  compaiono nella schermata di blocco del telefono
- **Riproduzione casuale e ripeti** (coda intera o brano singolo), con
  lo stato conservato tra le pagine
- **Filtro per genere** nella ricerca, con i generi raggruppati per categoria
- **Barra della quota** in "I miei brani", che diventa arancione oltre il 90%
- **Download opzionale** del file originale, attivabile da ACP
- **Gruppi autorizzati**: scheda ACP per assegnare rapidamente ai gruppi
  i permessi di caricamento e di creazione playlist
- **Copertina modificabile** dalla pagina di modifica brano, con anteprima
  e possibilità di rimuoverla
- **Continuità del player** tra le pagine del forum (opzione ACP)

## Non implementato

- BBCode per incorporare un brano dentro un messaggio del forum
- Riproduzione realmente ininterrotta durante la navigazione (richiederebbe
  di trasformare il forum in una single page app)


## Riconoscimento dei brani (facoltativo)

L'estensione puo' confrontare i brani caricati con un archivio di
pubblicazioni commerciali, per aiutare la moderazione. Il controllo e'
disattivato per impostazione predefinita.

**Cosa fa e cosa non fa.** I servizi usati non stabiliscono se un brano
sia protetto dal diritto d'autore: dicono se l'audio corrisponde a una
pubblicazione presente nei loro archivi. Una corrispondenza e' un indizio
forte che il file non sia opera dell'utente, ma puo' essere un falso
positivo, perche' molta musica royalty free e' registrata negli stessi
archivi. L'assenza di corrispondenza non significa che il brano sia
libero. E' un aiuto alla moderazione, non una verifica legale.

### AudD

1. Registrarsi su https://dashboard.audd.io/
2. Il token API compare nella pagina principale del pannello
3. Incollarlo in ACP -> Music Share -> Riconoscimento brani
4. Documentazione: https://docs.audd.io/

### ACRCloud

1. Registrarsi su https://console.acrcloud.com/signup
2. Creare un progetto di tipo *Audio & Video Recognition*, scegliendo la
   regione piu' vicina (per l'Europa: eu-west-1)
3. Copiare host, access key e access secret nella scheda ACP
4. L'host va scritto senza "https://" davanti

### Dopo la configurazione

Nella scheda ACP si sceglie il servizio, si verifica la chiave con
l'apposito pulsante e si spuntano i gruppi i cui caricamenti vanno
controllati. **Senza almeno un gruppo selezionato il controllo non parte
mai**, per non consumare richieste a sorpresa.

Ogni brano caricato da un utente di un gruppo controllato consuma una
richiesta, anche quando non risulta alcuna corrispondenza.
