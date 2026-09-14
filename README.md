# 🎵 Music Share

![version](https://img.shields.io/badge/version-1.24.21-blue)
![phpBB](https://img.shields.io/badge/phpBB-3.3.0%2B-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![license](https://img.shields.io/badge/license-GPL--2.0--only-lightgrey)
![languages](https://img.shields.io/badge/languages-2-orange)

**A music library inside your board — not a separate site bolted onto it.**

Members upload their own tracks, browse them by genre, build playlists and
listen while they keep reading the forum. Every track can have its own
discussion topic, and every author has a page where the community can write
to them. The player follows you from page to page and never stops.

---

## ✨ What it does

|  | |
|---|---|
| 🎧 | **Continuous player** that survives page changes — cover art, scrolling tags, progress bar, queue |
| 📀 | **Library by genre and category**, with genres created from the ACP |
| 📝 | **Personal playlists**, reorderable, built from any list with one click |
| 👍 | **Likes and dislikes** on every track, one vote per person, revocable |
| ❤️ | **Favourites page** collecting everything you liked |
| 💬 | **Author wall** — comments and replies on each author's page, with 👍 👎 ❤️ reactions |
| 🔔 | **Follow an author** and get notified when they publish, plus a "what's new" page |
| 🗣️ | **Discussion topic per track**, opened in the forum section you choose |
| 📎 | **Automatic import** of audio attachments from the forum sections you pick |
| 🔍 | **Track recognition** via AudD and ACRCloud, to spot copyrighted uploads |
| 📊 | **Charts by period** — last 7 days, last 30 days, all time |
| 🧩 | **BBCode `[musicshare]12[/musicshare]`** embeds a full player inside any post |
| 🏠 | **Index block** with the latest uploads, scrollable and cached |
| 🛡️ | **Moderation queue** in the ACP and in the Moderator Control Panel |
| 🔧 | **Diagnostics and repair tools** for counters and dangling references |
| 🌍 | **Italian and English**, complete on both sides |

---

## 📥 Installation

1. Copy the `musicshare` folder to `ext/salvocortesiano/` on your board.
   The final path must be `ext/salvocortesiano/musicshare/`.
2. Go to **ACP → Customise → Manage extensions**.
3. Click **Enable** next to Music Share.
4. Empty the board cache (**ACP → General → Purge the cache**).

### Updating

1. Replace the `ext/salvocortesiano/musicshare/` folder with the new one.
2. Go to **ACP → Customise → Manage extensions**: if an update is pending,
   phpBB offers it. Run it — new versions often add database tables.
3. **Empty the cache.** Without this, migrations do not run and templates
   stay as they were compiled before.

> ⚠️ Always update from the extension page, never by only replacing files.
> A skipped migration means a missing table, and the feature it belongs to
> simply never appears — with no error message.

---

## ⚙️ First setup

Everything lives in **ACP → Extensions → Music Share**. The settings page is
split into eleven groups; these are the ones you cannot skip.

### 1. Storage and uploads

| Setting | What to do |
|---|---|
| Storage folder | A writable folder, outside the extension if you can — an extension update must never wipe the library |
| Allowed formats | `mp3, ogg, oga, flac, wav, m4a, aac` by default |
| Maximum file size | Keep it below your PHP `upload_max_filesize` |
| Space per user | `0` for no limit |

### 2. Permissions

Nothing works until you grant them. **ACP → Permissions → Groups**, Music
Share category:

| Permission | Give it to |
|---|---|
| `u_musicshare_view` | Everyone who may reach the music section |
| `u_musicshare_upload` | Whoever may upload |
| `u_musicshare_playlist` | Whoever may build playlists |
| `u_musicshare_feed` | Whoever sees the index block |
| `u_musicshare_notify` | ⚠️ **Not to Registered Users** — see below |
| `u_musicshare_wall_post` | Whoever may write on author walls |
| `u_musicshare_wall_edit` | Whoever may edit and delete their own comments |
| `m_musicshare_wall` | Staff who moderate everyone's comments |
| `m_musicshare_manage` | Staff who manage every track |

> ⚠️ **`u_musicshare_notify` on a large board.** This permission sends a
> notification to every holder on every upload. On a board with 20,000
> members, one track means 20,000 notification rows. Grant it to staff only
> and let everyone else use **Follow**, which notifies only those who asked.

### 3. Genres

**ACP → Music Share → Genres.** Nothing can be uploaded before at least one
genre exists. A ready 79-genre list can be loaded with one click.

---

## 👤 How members use it

### Uploading a track

From **Music Share → Upload**, or from **UCP → Music Share → Upload**.

Title, artist, album, year and cover art are **read from the file tags**, so
the fields can be left empty. The same goes for bitrate, VBR/CBR/ABR mode,
sample rate and channels: they are read from the file and never typed in.

What is worth filling in by hand:

- **Genres** — one or more, this is how people will find the track
- **Description** — two lines on where it comes from and why you are sharing it
- **Licence** — what you allow others to do with it
- **BPM and key** — for anyone who wants to mix it or play along;
  the key field offers the 24 common keys, or you can type your own
- **Open a topic for this track** — ticked by default

### Listening

Click any row. The player appears at the bottom and **keeps playing while you
browse the forum**. The queue is the list you clicked from, so clicking a
track in a genre page queues that genre.

### Playlists

The `+` button on every row adds a track to a playlist, or creates a new one
on the spot. Playlists live in **UCP → Music Share**.

### Following an author

The **Follow** button on an author's page. You will be notified of their new
tracks even if general notifications are off for you, and their uploads show
up under **News from those you follow**.

### The author wall

At the bottom of every author page. Comments stay on that page — they are not
forum posts. You can reply to someone else's comment, edit and delete your
own, and react with 👍 👎 ❤️.

Likes and dislikes are mutually exclusive; the heart is independent. You
cannot react to your own comment, and you cannot reply to yourself.

### Embedding a track in a post

`[musicshare]12[/musicshare]` where `12` is the track id. **UCP → My tracks**
has a button that copies the ready-made code. Permissions are checked when
the post is read, so a removed or unapproved track simply does not play.

---

## 🛠️ Admin panel

| Tab | What it is for |
|---|---|
| **Settings** | 54 options in eleven groups |
| **Genres** | Create genres and categories, or load the 79-genre list |
| **Groups** | Which groups may upload, listen, use playlists |
| **Moderation** | Approve, reject, edit or delete any track; bulk actions |
| **Recognition** | AudD and ACRCloud keys, and which groups get checked |
| **Maintenance** | Seven-section health check of the installation |
| **Tools** | Overview figures, counter repair, dangling reference cleanup |

### Tools worth knowing

- **Recalculate likes / downloads** — rebuilds the counters from the source
  tables. `0` is good news: it means nothing is out of step.
- **Re-read audio technical data** — fills bitrate, mode, sample rate and
  channels for tracks uploaded before version 1.24.19. It runs 25 tracks at a
  time; press it until the number reaches zero.
- **Remove orphan files** — deletes files on disk that no track claims.

> 📌 **Plays are never recalculated, and this is deliberate.** The dated log
> is pruned on a schedule because it only serves the period charts.
> Recalculating the total from it would zero everything older than the
> retention window. An imperfect counter beats a deleted history.

---

## 🔔 Notifications

Five types, all switchable by each member under **UCP → Board preferences →
Notifications**, in the Music Share group:

| Notification | Who gets it |
|---|---|
| New track uploaded | Holders of `u_musicshare_notify`, plus followers of that author |
| Track approved | The author |
| Track rejected | The author |
| Comment on your wall | The page owner |
| Reply to your comment | Whoever wrote the comment |

---

## 🧯 Troubleshooting

**A feature does not appear at all, with no error.**
A migration has not run. Go to ACP → Manage extensions and complete the
update, then purge the cache.

**Uploads fail on large files.**
The board setting is not the only limit. Check `upload_max_filesize`,
`post_max_size` and `max_execution_time` in PHP. The Maintenance tab shows
all three.

**The index block shows stale figures.**
It is cached on purpose. Voting and moderation clear it automatically;
purging the board cache always works.

**Imported tracks are audible to people who cannot read the source forum.**
This is expected and the ACP warns about it: the library has a single
permission of its own, forum permissions do not follow the track into it.
Only import from sections whose audience matches the music section's.

---

## 📄 Licence and credits

Released under **GPL-2.0-only**.

Developed by [Salvo Cortesiano](https://netshadows.de) — info@netshadows.de

Bundled third-party code: [getID3](https://github.com/JamesHeinrich/getID3)
for reading audio tags, under its own licence.

Track recognition uses [AudD](https://audd.io) and
[ACRCloud](https://www.acrcloud.com); both need an account of your own and
are off by default.

# 🎵 Music Share

![versione](https://img.shields.io/badge/versione-1.24.21-blue)
![phpBB](https://img.shields.io/badge/phpBB-3.3.0%2B-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![licenza](https://img.shields.io/badge/licenza-GPL--2.0--only-lightgrey)
![lingue](https://img.shields.io/badge/lingue-2-orange)

**Una libreria musicale dentro il forum, non un sito a parte appoggiato sopra.**

Gli utenti caricano i propri brani, li sfogliano per genere, si costruiscono
playlist e ascoltano mentre continuano a leggere il forum. Ogni brano può
avere il suo argomento di discussione, e ogni autore ha una pagina dove la
community può scrivergli. Il lettore ti segue di pagina in pagina e non si
ferma.

🇬🇧 [Read in English](README.md)

---

## ✨ Cosa fa

|  | |
|---|---|
| 🎧 | **Lettore continuo** che sopravvive al cambio pagina — copertina, tag scorrevoli, barra di avanzamento, coda |
| 📀 | **Libreria per genere e categoria**, con i generi creati dall'ACP |
| 📝 | **Playlist personali**, riordinabili, create da qualunque elenco con un clic |
| 👍 | **Mi piace e non mi piace** su ogni brano, un voto a testa, revocabile |
| ❤️ | **Pagina dei preferiti** con tutto quello a cui hai messo mi piace |
| 💬 | **Bacheca dell'autore** — commenti e risposte sulla sua pagina, con reazioni 👍 👎 ❤️ |
| 🔔 | **Segui un autore** e ricevi l'avviso quando pubblica, più la pagina delle novità |
| 🗣️ | **Argomento di discussione per brano**, aperto nella sezione che scegli |
| 📎 | **Importazione automatica** degli allegati audio dalle sezioni che indichi |
| 🔍 | **Riconoscimento dei brani** con AudD e ACRCloud, per intercettare materiale protetto |
| 📊 | **Classifiche per periodo** — ultimi 7 giorni, ultimi 30 giorni, sempre |
| 🧩 | **BBCode `[musicshare]12[/musicshare]`** che inserisce un lettore completo dentro un messaggio |
| 🏠 | **Riquadro nell'indice** con gli ultimi caricamenti, scorrevole e tenuto in cache |
| 🛡️ | **Coda di moderazione** in ACP e nel Pannello di Controllo Moderatore |
| 🔧 | **Diagnosi e strumenti di riparazione** per contatori e riferimenti rimasti appesi |
| 🌍 | **Italiano e inglese**, completi da entrambe le parti |

---

## 📥 Installazione

1. Copia la cartella `musicshare` dentro `ext/salvocortesiano/` del forum.
   Il percorso finale deve essere `ext/salvocortesiano/musicshare/`.
2. Vai in **ACP → Personalizza → Gestisci estensioni**.
3. Clicca **Attiva** accanto a Music Share.
4. Svuota la cache (**ACP → Generale → Svuota la cache**).

### Aggiornamento

1. Sostituisci la cartella `ext/salvocortesiano/musicshare/` con quella nuova.
2. Vai in **ACP → Personalizza → Gestisci estensioni**: se c'è un
   aggiornamento in sospeso, phpBB te lo propone. Eseguilo — le versioni
   nuove aggiungono spesso tabelle al database.
3. **Svuota la cache.** Senza, le migrazioni non partono e i template
   restano quelli compilati prima.

> ⚠️ Aggiorna sempre dalla pagina delle estensioni, mai sostituendo soltanto
> i file. Una migrazione saltata significa una tabella mancante, e la
> funzione a cui appartiene semplicemente non compare — senza nessun errore.

---

## ⚙️ Prima configurazione

Tutto sta in **ACP → Estensioni → Music Share**. La pagina delle impostazioni
è divisa in undici gruppi; questi sono quelli che non puoi saltare.

### 1. Archiviazione e caricamento

| Impostazione | Cosa fare |
|---|---|
| Cartella di archiviazione | Una cartella scrivibile, possibilmente fuori dall'estensione: un aggiornamento non deve mai cancellare la libreria |
| Formati consentiti | `mp3, ogg, oga, flac, wav, m4a, aac` di partenza |
| Dimensione massima del file | Tienila sotto `upload_max_filesize` di PHP |
| Spazio per utente | `0` per nessun limite |

### 2. Permessi

Finché non li concedi non funziona niente. **ACP → Permessi → Gruppi**,
categoria Music Share:

| Permesso | A chi darlo |
|---|---|
| `u_musicshare_view` | Tutti quelli che possono entrare nella sezione musica |
| `u_musicshare_upload` | Chi può caricare |
| `u_musicshare_playlist` | Chi può crearsi playlist |
| `u_musicshare_feed` | Chi vede il riquadro nell'indice |
| `u_musicshare_notify` | ⚠️ **Non a Utenti Registrati** — vedi sotto |
| `u_musicshare_wall_post` | Chi può scrivere sulle bacheche |
| `u_musicshare_wall_edit` | Chi può modificare ed eliminare i propri commenti |
| `m_musicshare_wall` | Lo staff che modera i commenti di tutti |
| `m_musicshare_manage` | Lo staff che gestisce tutti i brani |

> ⚠️ **`u_musicshare_notify` su un forum grande.** Questo permesso manda una
> notifica a ogni suo titolare a ogni caricamento. Su un forum da 20.000
> iscritti, un brano significa 20.000 righe di notifica. Concedilo solo allo
> staff e lascia che gli altri usino **Segui**, che avvisa solo chi lo ha
> chiesto.

### 3. Generi

**ACP → Music Share → Generi.** Non si può caricare niente prima che esista
almeno un genere. Un elenco pronto di 79 generi si carica con un clic.

---

## 👤 Come si usa

### Caricare un brano

Da **Music Share → Carica**, oppure da **PCM → Music Share → Carica**.

Titolo, artista, album, anno e copertina vengono **letti dai tag del file**,
quindi i campi si possono lasciare vuoti. Lo stesso vale per bitrate,
modalità VBR/CBR/ABR, frequenza e canali: si leggono dal file e non si
digitano mai.

Quello che conviene riempire a mano:

- **Generi** — uno o più, è così che la gente troverà il brano
- **Descrizione** — due righe su da dove viene e perché lo condividi
- **Licenza** — cosa consenti a chi vuole usarlo
- **BPM e tonalità** — servono a chi vuole mixarlo o suonarci sopra; il campo
  della tonalità propone le 24 più comuni, oppure puoi scriverla tu
- **Apri un argomento per questo brano** — spuntato di partenza

### Ascoltare

Clicca una riga qualsiasi. Il lettore compare in basso e **continua a suonare
mentre giri per il forum**. La coda è l'elenco da cui hai cliccato: se parti
dalla pagina di un genere, in coda ci finisce quel genere.

### Playlist

Il pulsante `+` su ogni riga aggiunge il brano a una playlist, o ne crea una
al volo. Le playlist stanno in **PCM → Music Share**.

### Seguire un autore

Il pulsante **Segui** sulla sua pagina. Riceverai l'avviso dei suoi brani
nuovi anche se le notifiche generali non sono attive per te, e i suoi
caricamenti compaiono sotto **Novità da chi segui**.

### La bacheca

In fondo alla pagina di ogni autore. I commenti restano lì: non sono messaggi
del forum. Puoi rispondere al commento di un altro, modificare ed eliminare i
tuoi, e reagire con 👍 👎 ❤️.

Mi piace e non mi piace si escludono a vicenda, il cuore è indipendente. Non
puoi reagire a un commento tuo, e non puoi rispondere a te stesso.

### Inserire un brano in un messaggio

`[musicshare]12[/musicshare]` dove `12` è il numero del brano. In
**PCM → I miei brani** c'è un pulsante che copia il codice già pronto. I
permessi vengono verificati alla lettura del messaggio, quindi un brano
rimosso o non approvato semplicemente non viene riprodotto.

---

## 🛠️ Pannello di amministrazione

| Scheda | A cosa serve |
|---|---|
| **Impostazioni** | 54 opzioni divise in undici gruppi |
| **Generi** | Creare generi e categorie, o caricare l'elenco dei 79 |
| **Gruppi** | Quali gruppi possono caricare, ascoltare, usare le playlist |
| **Moderazione** | Approvare, rifiutare, modificare o eliminare qualunque brano; azioni multiple |
| **Riconoscimento** | Chiavi AudD e ACRCloud, e quali gruppi vengono controllati |
| **Manutenzione** | Controllo dell'installazione in sette sezioni |
| **Strumenti** | Quadro d'insieme, riparazione contatori, pulizia riferimenti appesi |

### Strumenti da conoscere

- **Ricalcola mi piace / download** — rifà i contatori leggendo le tabelle
  originali. `0` è una buona notizia: vuol dire che non c'è niente di scollato.
- **Rileggi i dati tecnici dell'audio** — riempie bitrate, modalità,
  frequenza e canali per i brani caricati prima della versione 1.24.19.
  Lavora 25 brani per volta: premi finché il numero non arriva a zero.
- **Rimuovi i file orfani** — cancella dal disco i file che nessun brano
  rivendica.

> 📌 **Gli ascolti non si ricalcolano, ed è voluto.** Il registro datato viene
> sfoltito periodicamente perché serve solo alle classifiche per periodo.
> Ricalcolare il totale da lì azzererebbe tutto ciò che è più vecchio della
> finestra conservata. Meglio un contatore imperfetto che una cancellazione
> della storia.

---

## 🔔 Notifiche

Cinque tipi, tutti disattivabili da ciascun utente in **PCM → Preferenze →
Notifiche**, nel gruppo Music Share:

| Notifica | A chi arriva |
|---|---|
| Nuovo brano caricato | A chi ha `u_musicshare_notify`, più a chi segue quell'autore |
| Brano approvato | All'autore |
| Brano rifiutato | All'autore |
| Commento sulla tua bacheca | Al padrone della pagina |
| Risposta a un tuo commento | A chi aveva scritto il commento |

---

## 🧯 Se qualcosa non va

**Una funzione non compare proprio, e non dà errore.**
Una migrazione non è partita. Vai in ACP → Gestisci estensioni, completa
l'aggiornamento, poi svuota la cache.

**I caricamenti falliscono sui file grandi.**
Il limite dell'estensione non è l'unico. Controlla `upload_max_filesize`,
`post_max_size` e `max_execution_time` di PHP. La scheda Manutenzione te li
mostra tutti e tre.

**Il riquadro nell'indice mostra numeri vecchi.**
È tenuto in cache di proposito. Voti e moderazione la svuotano da soli;
svuotare la cache del forum funziona sempre.

**I brani importati si sentono anche da chi non può leggere la sezione di origine.**
È previsto, e l'ACP lo avvisa: la libreria ha un permesso suo unico, i
permessi delle sezioni non seguono il brano dentro di essa. Importa solo da
sezioni il cui pubblico coincide con quello della sezione musica.

---

## 📄 Licenza e crediti

Distribuita con licenza **GPL-2.0-only**.

Sviluppata da [Salvo Cortesiano](https://netshadows.de) — info@netshadows.de

Codice di terze parti incluso: [getID3](https://github.com/JamesHeinrich/getID3)
per la lettura dei tag audio, con la sua licenza.

Il riconoscimento dei brani usa [AudD](https://audd.io) e
[ACRCloud](https://www.acrcloud.com); entrambi richiedono un account tuo e
sono disattivati di partenza.
