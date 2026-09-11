# Music Share — estensione phpBB 3.3.x / PHP 8.2

Versione corrente: **1.14.4** (il numero si aggiorna nel campo `version` di `composer.json`).

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


# musicshare
Estensione in stile Spotify: upload brani (UCP + sezione forum dedicata)
# Music Share

A complete music sharing section for phpBB 3.3: users upload their own
tracks, everyone listens through a persistent site-wide player, and the
board gets genres, playlists, votes, notifications and an optional
copyright-recognition check.

**Version 1.14.2** · phpBB 3.3.x · PHP 7.4+ · GPL-2.0-only

---

## What it does

Music Share turns a phpBB board into a small music library. Members
upload audio files, tag them by genre, describe them, and build
playlists. Everyone else browses, listens and votes without ever
leaving the page they are on.

It is built for **user-made loops and tracks and royalty-free music**.
It is not a tool for redistributing commercial releases, and it ships
with several features that help you keep it that way.

---

## Features

### Uploading

- Upload from the User Control Panel or from the Music section
- Real progress bar, AJAX upload, no page reload
- Formats: `mp3`, `ogg`, `oga`, `flac`, `wav`, `m4a`, `aac`
- Title, artist, album, year and cover art are read automatically from
  the file tags, and any of them can be overridden by hand
- Optional per-song description (length configurable, 300 by default)
- Per-song download switch: the uploader decides whether others may
  download the file
- Duplicate detection by file hash
- Per-user storage quota and per-file size limit
- Files are stored under random 32-character names, so the archive
  cannot be enumerated even if the folder were reachable

### Listening

- Site-wide player fixed to the bottom of the page
- Play, pause, stop, previous, next, seek, volume
- Client-side waveform, drawn with the Web Audio API
- Visible, reorderable queue; shuffle and repeat (off / all / one)
- Playback survives page changes and resumes at the exact position
- Media Session API support: track details on the phone lock screen
- Inline row controls: play, pause and a seekable progress bar on every
  song row, everywhere in the board
- The bottom player can be limited to the Music section, so ordinary
  board pages stay clean

### Browsing

- 77 predefined genres in 12 categories, fully editable
- Genre page, search by title, artist or album
- "Recently uploaded" box on the board index or on every page,
  with a configurable heading per language
- "Who shares music" page: every uploader with song count, total
  plays, likes, and their latest upload with artist, title, genre and
  date; searchable by username or e-mail
- Public per-user page listing that member's songs

### Community

- Likes and dislikes, one vote per user per song, uploaders cannot
  vote on their own songs
- Public and private playlists
- Song count and listening stats on the member profile and under each
  post
- Pop-up notices when someone uploads, with an optional sound
- phpBB notifications on upload, approval and rejection, delivered
  through the bell, e-mail and — if the Web Push extension is
  installed — browser push, with no extra configuration
- Optional private message to the uploader when a moderator approves
  or rejects a song
- `[musicshare]12[/musicshare]` BBCode embeds a full player in any post

### Moderation and administration

Six ACP tabs:

| Tab | What it does |
|---|---|
| **Settings** | Storage, limits, player, feed, notices, descriptions, cleanup |
| **Genres** | Full CRUD on genres and categories |
| **Authorised groups** | Five permissions per group, at a glance |
| **Moderation** | Approve, revoke, delete; search by title, artist, album or username |
| **Song recognition** | AudD / ACRCloud credentials, test button, groups to check |
| **Maintenance and checks** | 29 automated checks with a fix for every problem |

Five user permissions and one moderator permission:

- `u_musicshare_view` — access the Music section
- `u_musicshare_feed` — see the recent songs box
- `u_musicshare_notify` — receive new song notifications
- `u_musicshare_upload` — upload songs
- `u_musicshare_playlist` — create playlists
- `m_musicshare_manage` — moderate every song

### Copyright recognition (optional, off by default)

The extension can send the first few seconds of an upload to **AudD**
or **ACRCloud** and check whether it matches a commercial release.

**Read this carefully.** These services do not determine whether a song
is protected by copyright. They report whether the audio matches a
recording in their database. A match is a strong hint that the file is
not the user's own work, but it can be a false positive, because a lot
of royalty-free music is registered in the same databases. No match
does not mean the song is free: obscure tracks, remixes and live
recordings often go unrecognised.

Treat it as an aid to moderation, never as a legal check.

When a match is found the upload still succeeds, the user gets a clear
notice explaining that it may be a false positive, and the song is held
for moderator approval.

Only members of the groups you select are checked, and if you select no
group nothing is ever sent — so you never spend API credits by
accident.

---

## Installation

1. Download the latest release
2. Upload the `salvocortesiano/musicshare` folder to `ext/` on your board
3. Go to **ACP → Customise → Manage extensions** and enable Music Share
4. Go to **ACP → Extensions → Music Share → Settings** and check that
   the storage folder is writable
5. Open **Maintenance and checks**: it will tell you if anything else
   needs attention

No Composer step is required; getID3 is bundled.

### Requirements

- phpBB 3.3.0 or later
- PHP 7.4 or later
- A writable storage folder
- cURL or `allow_url_fopen`, only if you enable song recognition

---

## Configuration notes

**Storage.** The default path is `files/musicshare/`. The extension
writes an `.htaccess` there to block direct access and warns you in the
ACP if the folder is reachable from the web. On non-Apache servers,
place the folder outside the document root.

**Notifications.** New song notifications go to members with the
`u_musicshare_notify` permission. On install it is granted to
administrators and global moderators only. **Do not grant it to
Registered users on a large board**: one upload would create one
notification row per member. The Maintenance tab warns you when the
recipient count is unsafe.

**Cleanup.** Read notifications are pruned daily by a phpBB cron task,
in batches, with no system cron needed. Retention is configurable, and
there are manual buttons to clean read notifications or purge them all.

---

## Using it

### As a member

Upload from **UCP → Upload song**. Title and artist fill themselves in
from the file tags; correct them if the tags are wrong. Pick one or
more genres, optionally write a short description, and decide whether
others may download the file.

Click any song row anywhere on the board to play it. Click again to
pause. Drag the thin progress bar under the title to seek.

In **UCP → My songs**, the **Copy code** button puts
`[musicshare]12[/musicshare]` on your clipboard; paste it into a post
to embed that song with a full player.

### As an administrator

Start in **Maintenance and checks**. It verifies the server
environment, the storage folder, the database structure, the
consistency between songs and files on disk, the notification system
and every optional feature — and tells you exactly how to fix whatever
is wrong.

If you expect commercial uploads, consider turning on **Require
approval for uploaded songs** in Settings. It is the only reliable
control; recognition is a hint, not a filter.

---

## Credits

Salvo Cortesiano — <https://netshadows.de> — info@netshadows.de

Bundled: [getID3](https://github.com/JamesHeinrich/getID3) for reading
audio tags.

## Licence

GNU General Public License v2.0 only. See `license.txt`.
