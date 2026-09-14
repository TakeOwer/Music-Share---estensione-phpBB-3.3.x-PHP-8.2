<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'MUSICSHARE_NAV_LINK'			=> 'Musica',
	'MUSICSHARE_BROWSE_TITLE'		=> 'Sfoglia per genere',
	'MUSICSHARE_UPLOAD_TITLE'		=> 'Carica un brano',
	'MUSICSHARE_GENRE_NOT_FOUND'	=> 'Genere non trovato.',
	'MUSICSHARE_PLAYLIST_NOT_FOUND'	=> 'Playlist non trovata.',
	'MUSICSHARE_PLAYLIST_PRIVATE'	=> 'Questa playlist è privata.',
	'MUSICSHARE_NO_PERMISSION'		=> 'Non hai il permesso di caricare brani.',

	'MUSICSHARE_UPLOAD_ERR_NOFILE'	=> 'Devi selezionare un file audio da caricare.',
	'MUSICSHARE_UPLOAD_ERR_TYPE'	=> 'Formato file non consentito.',
	'MUSICSHARE_UPLOAD_ERR_SIZE'	=> 'Il file supera la dimensione massima consentita.',
	'MUSICSHARE_UPLOAD_ERR_QUOTA'	=> 'Hai raggiunto lo spazio massimo disponibile per i tuoi brani.',
	'MUSICSHARE_UPLOAD_ERR_MIME'	=> 'Il file non sembra essere un file audio valido.',
	'MUSICSHARE_UPLOAD_ERR_STORAGE'	=> 'La cartella di archiviazione non è scrivibile. Contatta un amministratore.',
	'MUSICSHARE_UPLOAD_ERR_MOVE'	=> 'Non è stato possibile salvare il file caricato.',
	'MUSICSHARE_UPLOAD_SUCCESS'	=> 'Brano caricato con successo!',

	'MUSICSHARE_SONG_TITLE'		=> 'Titolo',
	'MUSICSHARE_SONG_ARTIST'		=> 'Artista',
	'MUSICSHARE_SONG_ALBUM'		=> 'Album',
	'MUSICSHARE_SONG_YEAR'			=> 'Anno',
	'MUSICSHARE_SONG_FILE'			=> 'File audio',
	'MUSICSHARE_TITLE_EXPLAIN'		=> 'Lascia vuoto per usare il titolo indicato nei tag del file, se presente.',
	'MUSICSHARE_COVER_FILE'		=> 'Copertina (opzionale)',
	'MUSICSHARE_COVER_FILE_EXPLAIN'	=> 'Se non ne carichi una, verrà usata automaticamente quella incorporata nei tag del file, se presente.',
	'MUSICSHARE_GENRES'			=> 'Generi',
	'MUSICSHARE_GENRES_EXPLAIN'	=> 'Seleziona uno o più generi per questo brano.',
	'MUSICSHARE_ALLOWED_FORMATS'	=> 'Formati consentiti',
	'MUSICSHARE_MAX_SIZE'			=> 'Dimensione massima',

	'MUSICSHARE_SONG_UPDATED'		=> 'Brano aggiornato con successo.',
	'MUSICSHARE_SONG_DELETED'		=> 'Brano eliminato.',
	'MUSICSHARE_SONG_DELETE_CONFIRM'	=> 'Vuoi davvero eliminare questo brano? L\'operazione è irreversibile.',
	'MUSICSHARE_APPROVED'			=> 'Approvato',
	'MUSICSHARE_PENDING_APPROVAL'	=> 'In attesa di approvazione',

	'MUSICSHARE_PLAYLIST_NAME'		=> 'Nome playlist',
	'MUSICSHARE_PLAYLIST_DESC'		=> 'Descrizione',
	'MUSICSHARE_PLAYLIST_PUBLIC'	=> 'Playlist pubblica',
	'MUSICSHARE_PLAYLIST_SAVED'	=> 'Playlist salvata.',
	'MUSICSHARE_PLAYLIST_DELETED'	=> 'Playlist eliminata.',
	'MUSICSHARE_PLAYLIST_DELETE_CONFIRM'	=> 'Vuoi davvero eliminare questa playlist?',
	'MUSICSHARE_ADD_TO_PLAYLIST'	=> 'Aggiungi a playlist',
	'MUSICSHARE_REMOVE_FROM_PLAYLIST'	=> 'Rimuovi dalla playlist',
	'MUSICSHARE_NEW_PLAYLIST'		=> 'Nuova playlist',
	'MUSICSHARE_MANAGE_PLAYLIST'	=> 'Gestisci brani',

	'MUSICSHARE_USED_SPACE'		=> 'Spazio utilizzato',
	'MUSICSHARE_NO_SONGS'			=> 'Nessun brano da mostrare.',
	'MUSICSHARE_NO_PLAYLISTS'		=> 'Non hai ancora creato playlist.',
	'MUSICSHARE_PLAY'				=> 'Riproduci',

	'MUSICSHARE_TOP_SONGS'			=> 'I più ascoltati',
	'MUSICSHARE_SEARCH'			=> 'Cerca',
	'MUSICSHARE_SEARCH_PLACEHOLDER'	=> 'Cerca per titolo, artista o album...',
	'MUSICSHARE_SEARCH_RESULTS_FOR'	=> 'Risultati per',
	'MUSICSHARE_NO_RESULTS'		=> 'Nessun brano trovato.',

	'MUSICSHARE_PLAYLIST_NAME_EMPTY'	=> 'Devi indicare un nome per la nuova playlist.',
	'MUSICSHARE_NEW_PLAYLIST_NAME'	=> 'Oppure crea una nuova playlist...',
	'MUSICSHARE_ADDED_TO_PLAYLIST'	=> 'Brano aggiunto alla playlist.',
	'MUSICSHARE_ALREADY_IN_PLAYLIST'	=> 'Il brano era già in questa playlist.',
	'MUSICSHARE_ADD_ERROR'			=> 'Non è stato possibile aggiungere il brano alla playlist.',
	'MUSICSHARE_QUEUE'				=> 'Coda',
	'MUSICSHARE_QUEUE_EMPTY'		=> 'La coda è vuota.',
	'MUSICSHARE_REMOVE_FROM_QUEUE'	=> 'Rimuovi dalla coda',

	'UCP_MUSICSHARE_TITLE'			=> 'Music Share',
	'UCP_MUSICSHARE_SONGS'			=> 'I miei brani',
	'UCP_MUSICSHARE_UPLOAD'		=> 'Carica brano',
	'UCP_MUSICSHARE_PLAYLISTS'		=> 'Le mie playlist',

	'MUSICSHARE_OTHER_GENRES'		=> 'Altri generi',

	'MUSICSHARE_UPLOAD_ERR_PHP_SIZE'	=> 'Il file supera il limite di upload impostato sul server (upload_max_filesize). Contatta un amministratore.',
	'MUSICSHARE_UPLOAD_ERR_PARTIAL'		=> 'Il caricamento del file si e\' interrotto. Riprova.',

	'MUSICSHARE_UPLOAD_STARTING'	=> 'Caricamento in corso...',
	'MUSICSHARE_UPLOAD_PROCESSING'	=> 'Trasferimento completato, elaborazione del file in corso...',
	'MUSICSHARE_UPLOAD_FAILED'		=> 'Caricamento non riuscito. Riprova.',

	'MUSICSHARE_CLICK_TO_PLAY'		=> 'Clicca su un brano per ascoltarlo: il player compare in basso nella pagina.',
	'MUSICSHARE_GO_TO_BROWSE'		=> 'Vai alla sezione Musica',

	'MUSICSHARE_RESUME_HINT'		=> 'Premi Play per riprendere',

	'MUSICSHARE_EDIT_SONG'			=> 'Modifica brano',
	'MUSICSHARE_NO_COVER'			=> 'Nessuna copertina impostata per questo brano.',
	'MUSICSHARE_REMOVE_COVER'		=> 'Rimuovi la copertina attuale',
	'MUSICSHARE_COVER_EDIT_EXPLAIN'	=> 'Scegli un\'immagine per sostituire la copertina attuale. Formati ammessi: jpg, png, gif, webp (massimo 5 MB).',
	'MUSICSHARE_COVER_ERR_TYPE'		=> 'La copertina deve essere un\'immagine valida in formato jpg, png, gif o webp.',
	'MUSICSHARE_COVER_ERR_SIZE'		=> 'La copertina supera la dimensione massima consentita (5 MB).',

	'MUSICSHARE_UPLOAD_ERR_DUPLICATE'	=> 'Hai gia\' caricato questo stesso file. Controlla in "I miei brani".',
	'MUSICSHARE_SONG_NOT_FOUND'			=> 'Brano non trovato.',
	'MUSICSHARE_DOWNLOAD'				=> 'Scarica',
	'MUSICSHARE_ALL_GENRES'				=> 'Tutti i generi',
	'MUSICSHARE_SHUFFLE'				=> 'Riproduzione casuale',
	'MUSICSHARE_REPEAT'					=> 'Ripeti',
	'MUSICSHARE_REPEAT_OFF'				=> 'Ripeti: disattivato',
	'MUSICSHARE_REPEAT_ALL'				=> 'Ripeti tutta la coda',
	'MUSICSHARE_REPEAT_ONE'				=> 'Ripeti il brano corrente',

	'MUSICSHARE_PENDING_EXPLAIN'	=> 'Il brano e\' visibile e ascoltabile solo da te finche\' un moderatore non lo approva.',

	'MUSICSHARE_MENU'				=> 'Opzioni del lettore',
	'MUSICSHARE_CLOSE_PLAYER'		=> 'Chiudi il lettore',
	'MUSICSHARE_CLEAR_QUEUE'		=> 'Svuota la coda',

	'MUSICSHARE_DURATION'			=> 'Durata',
	'MUSICSHARE_UPLOADED_BY'		=> 'Caricato da',
	'MUSICSHARE_USER_SONGS'			=> 'Brani di %s',
	'MUSICSHARE_USER_SUMMARY'		=> '%1$d brani caricati, %2$d ascolti complessivi.',
	'MUSICSHARE_POST_SONGS'			=> 'Brani',
	'MUSICSHARE_POST_SONGS_TITLE'	=> 'Vedi i brani caricati da questo utente',
	'MUSICSHARE_PROFILE_SONGS'		=> 'Brani caricati',
	'MUSICSHARE_PROFILE_PLAYS'		=> 'Ascolti ricevuti',
	'MUSICSHARE_PROFILE_DURATION'	=> 'Durata totale',
	'MUSICSHARE_DURATION_HM'		=> '%1$d h %2$d min',
	'MUSICSHARE_DURATION_M'			=> '%d min',

	'MUSICSHARE_RECENT_SONGS'		=> 'Caricati di recente',
	'MUSICSHARE_NEW_UPLOAD'			=> 'Nuovo brano',

	'MUSICSHARE_PAUSE'				=> 'Pausa',

	'MUSICSHARE_UPLOAD_DATE'		=> 'Caricato il',

	'MUSICSHARE_LIKE'				=> 'Mi piace',
	'MUSICSHARE_DISLIKE'			=> 'Non mi piace',
	'MUSICSHARE_VOTE_OWN'			=> 'Non puoi votare un brano che hai caricato tu.',
	'MUSICSHARE_VOTE_LOGIN'			=> 'Devi accedere per votare un brano.',
	'MUSICSHARE_VOTE_ERROR'			=> 'Voto non valido.',
	'MUSICSHARE_VOTES_DISABLED'		=> 'I voti sui brani sono disattivati.',

	'MUSICSHARE_ALLOW_DOWNLOAD_SONG'		=> 'Download di questo brano',
	'MUSICSHARE_ALLOW_DOWNLOAD_SONG_YES'	=> 'Permetti agli altri utenti di scaricare il file',
	'MUSICSHARE_ALLOW_DOWNLOAD_SONG_EXPLAIN'	=> 'Se togli la spunta il brano resta ascoltabile in streaming, ma nessuno potra\' scaricarne il file. Tu e i moderatori potete scaricarlo comunque.',
	'MUSICSHARE_DOWNLOAD_OFF_BOARD'			=> 'Il download dei brani e\' disattivato su tutto il forum dall\'amministratore, quindi questa scelta non ha effetto.',

	'MUSICSHARE_DOWNLOAD_ON'		=> 'Download consentito',
	'MUSICSHARE_DOWNLOAD_OFF'		=> 'Download non consentito',

	'MUSICSHARE_SONGS_COUNT'		=> array(
		0	=> 'Nessun brano',
		1	=> '%d brano',
		2	=> '%d brani',
	),

	'MUSICSHARE_NO_VIEW_PERMISSION'	=> 'Non hai il permesso di accedere alla sezione Musica.',

	'MUSICSHARE_SHOW_ALL_GENRES'	=> 'Mostra tutti i generi, anche quelli senza brani',
	'MUSICSHARE_SHOW_USED_GENRES'	=> 'Mostra solo i generi con almeno un brano',

	'MUSICSHARE_PLAYS_COUNT'		=> array(
		0	=> 'Nessun ascolto',
		1	=> '%d ascolto',
		2	=> '%d ascolti',
	),

	'MUSICSHARE_UPLOADERS'			=> 'Chi condivide musica',
	'MUSICSHARE_UPLOADER'			=> 'Utente',
	'MUSICSHARE_UPLOADERS_SEARCH'	=> 'Cerca per nome utente o email...',
	'MUSICSHARE_NO_UPLOADERS'		=> 'Nessun utente ha ancora caricato brani.',
	'MUSICSHARE_LAST_UPLOAD'		=> 'Ultimo caricamento',
	'MUSICSHARE_SEE_SONGS'			=> 'Vedi i brani',
	'MUSICSHARE_SORT_BY'			=> 'Ordina per',
	'MUSICSHARE_SORT_SONGS'			=> 'brani caricati',
	'MUSICSHARE_SORT_PLAYS'			=> 'ascolti',
	'MUSICSHARE_SORT_RECENT'		=> 'caricamento più recente',
	'MUSICSHARE_SORT_NAME'			=> 'nome utente',
	'MUSICSHARE_UPLOADERS_COUNT'	=> array(
		0	=> 'Nessun utente ha ancora caricato brani',
		1	=> '%d utente ha caricato brani',
		2	=> '%d utenti hanno caricato brani',
	),

	'MUSICSHARE_DISCLAIMER_TITLE'	=> 'Avviso importante sui diritti d\'autore',
	'MUSICSHARE_DISCLAIMER_TEXT'	=> 'Questa sezione nasce per condividere <strong>loop e brani realizzati dagli utenti stessi</strong> e <strong>musica royalty free</strong> liberamente distribuibile.<br />Caricando un file dichiari di esserne l\'autore oppure di disporre dei diritti necessari per distribuirlo. Il caricamento di materiale protetto da diritto d\'autore senza autorizzazione non e\' consentito e la responsabilita\' di quanto pubblicato ricade esclusivamente sull\'utente che effettua il caricamento. Lo staff puo\' rimuovere in qualsiasi momento, senza preavviso, i brani segnalati o ritenuti non conformi.',

	// Notifiche e messaggi privati
	'MUSICSHARE_NOTIFICATION_GROUP'			=> 'Music Share',
	'MUSICSHARE_NOTIFICATION_SONG_APPROVED'	=> 'Un mio brano viene approvato',
	'MUSICSHARE_NOTIFICATION_SONG_REJECTED'	=> 'Un mio brano viene rifiutato o rimosso',
	'MUSICSHARE_NOTIFICATION_SONG_NEW'		=> 'Qualcuno carica un nuovo brano',
	'MUSICSHARE_NOTIFICATION_WALL'			=> 'Qualcuno scrive sulla mia bacheca',
	'MUSICSHARE_NOTIFICATION_WALL_TITLE'	=> '<strong>%1$s</strong> ha scritto sulla tua bacheca.',
	'MUSICSHARE_NOTIFICATION_WALL_REPLY'	=> 'Qualcuno risponde a un mio commento in bacheca',
	'MUSICSHARE_NOTIFICATION_WALL_REPLY_TITLE'	=> '<strong>%1$s</strong> ha risposto a un tuo commento.',
	'MUSICSHARE_NOTIFICATION_APPROVED_TITLE'	=> 'Il tuo brano <strong>%s</strong> è stato approvato.',
	'MUSICSHARE_NOTIFICATION_REJECTED_TITLE'	=> 'Il tuo brano <strong>%s</strong> non è stato approvato ed è stato rimosso.',
	'MUSICSHARE_NOTIFICATION_NEW_TITLE'		=> '<strong>%1$s</strong> ha caricato il brano <strong>%2$s</strong>.',

	'MUSICSHARE_PM_APPROVED_SUBJECT'		=> 'Il tuo brano è stato approvato',
	'MUSICSHARE_PM_APPROVED_BODY'			=> 'Il brano [b]%1$s[/b] che hai caricato è stato approvato ed è ora visibile a tutti gli utenti del forum.',
	'MUSICSHARE_PM_REJECTED_SUBJECT'		=> 'Il tuo brano non è stato approvato',
	'MUSICSHARE_PM_REJECTED_BODY'			=> 'Il brano [b]%1$s[/b] che hai caricato non è stato approvato ed è stato rimosso dalla sezione Musica.[br][br]Per chiarimenti puoi rivolgerti allo staff.',
	'MUSICSHARE_PM_REJECTED_BODY_REASON'	=> 'Il brano [b]%1$s[/b] che hai caricato non è stato approvato ed è stato rimosso dalla sezione Musica.[br][br]Motivazione: [i]%2$s[/i]',

	// Riconoscimento del brano
	'MUSICSHARE_RECO_MATCH_TITLE'	=> 'Possibile brano protetto',
	'MUSICSHARE_RECO_MATCH_TEXT'	=> 'Il file che hai caricato corrisponde a una pubblicazione commerciale presente negli archivi: <strong>%s</strong>.<br /><br />Potrebbe trattarsi di un <strong>falso positivo</strong>: anche molta musica royalty free è registrata negli stessi archivi. Se il brano è opera tua o ne hai i diritti, puoi lasciarlo: sarà uno dei moderatori a valutarlo.<br /><br />Il brano è stato caricato ma resta <strong>in attesa di approvazione</strong>. Caricando materiale di cui non hai i diritti te ne assumi la responsabilità.',

	'MUSICSHARE_NOTICE'				=> 'Music Share',

	'MUSICSHARE_DESCRIPTION'			=> 'Descrizione (facoltativa)',
	'MUSICSHARE_DESCRIPTION_EXPLAIN'	=> 'Due righe per raccontare il brano: da dove viene, come lo hai realizzato, perche\' lo condividi. Compare sotto il titolo in tutti gli elenchi. La marcatura HTML non e\' consentita.',

	'MUSICSHARE_TAG_FALLBACK'		=> 'Lascia vuoto per usare il valore indicato nei tag del file, se presente.',

	'MUSICSHARE_SONG_FILE_EXPLAIN'	=> 'Scegli il file audio da caricare. Titolo, artista, album, anno e copertina vengono letti dai tag del file, se presenti.',
	'MUSICSHARE_ARTIST_EXPLAIN'		=> 'Lascia vuoto per usare l\'artista indicato nei tag del file, se presente.',
	'MUSICSHARE_EDIT_FIELD_EXPLAIN'	=> 'Correggi il valore se il tag del file era errato o incompleto.',

	'MUSICSHARE_UPLOAD_PENDING'		=> 'Caricamento completato. Il brano resta in attesa di approvazione da parte di un moderatore e non e\' ancora visibile agli altri utenti.',

	'MUSICSHARE_STOP'				=> 'Ferma',
	'MUSICSHARE_SEEK'				=> 'Posizione nel brano',
	'MUSICSHARE_EMBED_LOADING'		=> 'Caricamento del brano...',
	'MUSICSHARE_EMBED_MISSING'		=> 'Brano non disponibile: potrebbe essere stato rimosso o non essere visibile a te.',
	'MUSICSHARE_EMBED_PENDING'		=> 'In attesa di approvazione',
	'MUSICSHARE_COPY_BBCODE'		=> 'Copia codice per i messaggi',
	'MUSICSHARE_BBCODE_COPIED'		=> 'Codice copiato: incollalo in un messaggio per inserire il brano.',
	'MUSICSHARE_BBCODE_MANUAL'		=> 'Copia questo codice e incollalo in un messaggio:',

	'MUSICSHARE_COPY_BBCODE_SHORT'	=> 'Copia codice',

	'MUSICSHARE_LAST_SONG'			=> 'Ultimo brano caricato',

	// Categorie di genere predefinite. Sono righe del database, quindi
	// modificabili dall'amministratore: queste traduzioni valgono solo
	// finche' il nome resta quello inserito dall'estensione.
	'MUSICSHARE_CAT_DANCE'			=> 'Dance ed Elettronica',
	'MUSICSHARE_CAT_HIPHOP'			=> 'Hip-Hop e Urban',
	'MUSICSHARE_CAT_POP'			=> 'Pop',
	'MUSICSHARE_CAT_ROCK'			=> 'Rock',
	'MUSICSHARE_CAT_METAL'			=> 'Metal',
	'MUSICSHARE_CAT_TRADITIONAL'	=> 'Popolare, Tradizionale e Musica Italiana',
	'MUSICSHARE_CAT_LATIN'			=> 'Musica Latina e Caraibica',
	'MUSICSHARE_CAT_JAZZ'			=> 'Jazz e Blues',
	'MUSICSHARE_CAT_SOUL'			=> 'Soul, Funk e Disco',
	'MUSICSHARE_CAT_CLASSICAL'		=> 'Musica Classica e Colonne Sonore',
	'MUSICSHARE_CAT_FOLK'			=> 'Folk, Country e Radici',
	'MUSICSHARE_CAT_WORLD'			=> 'Musica Globale ed Etnica',

	// Usate anche dal Pannello di Controllo Moderatore, che non
	// carica il file di lingua dell'area amministrativa
	'MUSICSHARE_APPROVE'	=> 'Approva',
	'MUSICSHARE_NO_PENDING_SONGS'	=> 'Nessun brano in attesa di approvazione.',
	'MUSICSHARE_SEARCH_EXPLAIN'	=> 'Cerca per titolo, artista, album o nome dell\'utente che ha caricato il brano.',
	'MUSICSHARE_SEARCH_RESET'	=> 'Azzera ricerca',
	'MUSICSHARE_UNAPPROVE'	=> 'Revoca approvazione',

	// Pannello di Controllo Moderatore
	'MCP_MUSICSHARE_TITLE'		=> 'Music Share',
	'MCP_MUSICSHARE_PENDING'	=> 'Brani da approvare',
	'MCP_MUSICSHARE_SONGS'		=> 'Tutti i brani',
	'MCP_MUSICSHARE_TOTAL'		=> array(
		0	=> 'Nessun brano da mostrare.',
		1	=> '%d brano.',
		2	=> '%d brani.',
	),
	'MCP_MUSICSHARE_CONFIRM_DELETE'	=> 'Vuoi eliminare il brano "%s"? Il file viene rimosso dal server e l\'operazione non puo\' essere annullata. All\'autore viene inviato un avviso.',
	'MUSICSHARE_ACTIONS'		=> 'Azioni',
	'MUSICSHARE_PENDING'		=> 'In attesa',
	'MUSICSHARE_STATUS'			=> 'Stato',
	'MUSICSHARE_RECOGNIZED_FLAG'	=> 'Corrispondenza trovata',

	'MUSICSHARE_SONG_APPROVED'	=> 'Brano approvato.',
	'MUSICSHARE_SONG_UNAPPROVED'	=> 'Approvazione revocata: il brano non e\' piu\' visibile agli utenti.',
	'MUSICSHARE_SONG_REJECTED'	=> 'Brano rifiutato ed eliminato.',
	'MUSICSHARE_SONG_ALREADY_APPROVED'	=> 'Questo brano risultava gia\' approvato: non e\' stato modificato e all\'autore non e\' stato inviato un secondo avviso. Se lo vedevi ancora fra quelli in attesa, la pagina non era aggiornata.',
	'MUSICSHARE_SONG_ALREADY_UNAPPROVED'	=> 'Questo brano risultava gia\' in attesa di approvazione: non e\' stato modificato e all\'autore non e\' stato inviato un secondo avviso.',

	// Importazione di un allegato audio in libreria
	'MUSICSHARE_ATTACH_IMPORT'		=> 'Aggiungi alla libreria',
	'MUSICSHARE_IMPORT_FILE'		=> 'File da importare',
	'MUSICSHARE_IMPORT_FILE_EXPLAIN'	=> 'L\'allegato resta nel messaggio: in libreria ne viene salvata una copia, cosi\' il brano continua a funzionare anche se un giorno il messaggio venisse modificato o rimosso.',
	'MUSICSHARE_IMPORT_SUBMIT'		=> 'Aggiungi alla libreria',
	'MUSICSHARE_IMPORT_DONE'		=> 'Brano aggiunto alla libreria.',
	'MUSICSHARE_IMPORT_ALREADY'		=> 'Questo file e\' gia\' presente nella tua libreria: non e\' stato aggiunto una seconda volta.',
	'MUSICSHARE_MY_SONGS'			=> 'I miei brani',
	'MUSICSHARE_ATTACH_NOT_FOUND'	=> 'Allegato non trovato.',
	'MUSICSHARE_ATTACH_NOT_AUDIO'	=> 'Questo allegato non e\' in un formato audio fra quelli accettati.',
	'MUSICSHARE_ATTACH_FILE_MISSING'	=> 'Il file dell\'allegato non e\' piu\' presente sul server.',
	'MUSICSHARE_ATTACH_IMPORT_OFF'	=> 'L\'importazione degli allegati in libreria e\' disattivata.',
	'MUSICSHARE_TITLE_REQUIRED'		=> 'Indica un titolo per il brano.',

	'MUSICSHARE_DISCUSS'			=> 'Discuti questo brano',

	'MUSICSHARE_CREATE_TOPIC'		=> 'Discussione nel forum',
	'MUSICSHARE_CREATE_TOPIC_YES'	=> 'Apri un argomento per questo brano',
	'MUSICSHARE_CREATE_TOPIC_EXPLAIN'	=> 'Viene aperto a tuo nome un argomento con il lettore gia\' incorporato, dove gli altri possono commentare. Se ora non lo vuoi, potrai aprirlo in seguito dal pulsante accanto al brano.',
	'MUSICSHARE_PUBLISH_TOPIC'		=> 'Apri la discussione per questo brano',
	'MUSICSHARE_TOPIC_OFF'			=> 'L\'apertura automatica degli argomenti e\' disattivata.',
	'MUSICSHARE_TOPIC_NOT_YOURS'	=> 'Puoi aprire la discussione solo per i brani che hai caricato tu: l\'argomento verrebbe scritto a tuo nome.',
	'MUSICSHARE_TOPIC_PENDING'		=> 'Il brano e\' ancora in attesa di approvazione: la discussione si potra\' aprire quando sara\' visibile a tutti.',
	'MUSICSHARE_TOPIC_FAILED'		=> 'Non e\' stato possibile aprire l\'argomento. Verifica di poter scrivere nella sezione indicata dall\'amministratore.',

	'MUSICSHARE_LIKED'			=> 'Brani che mi piacciono',
	'MUSICSHARE_LIKED_EMPTY'	=> 'Non hai ancora messo "mi piace" a nessun brano. Il pollice in su accanto a ogni brano lo aggiunge qui.',
	'MUSICSHARE_LIKED_LOGIN'	=> 'Devi essere collegato per vedere i tuoi brani preferiti.',

	'MCP_MUSICSHARE_BULK'			=> 'Azione sui brani selezionati',
	'MCP_MUSICSHARE_BULK_CHOOSE'	=> 'Scegli un\'azione...',
	'MCP_MUSICSHARE_BULK_EXPLAIN'	=> 'Massimo 100 brani per volta. I brani gia\' nello stato richiesto vengono saltati e ai loro autori non viene inviato un secondo avviso.',
	'MCP_MUSICSHARE_BULK_DONE'		=> 'Operazione completata: %1$d brani modificati, %2$d saltati.',
	'MCP_MUSICSHARE_NONE_SELECTED'	=> 'Non hai selezionato alcun brano.',

	'MUSICSHARE_TOP_PERIOD_7'		=> 'Ultimi 7 giorni',
	'MUSICSHARE_TOP_PERIOD_30'		=> 'Ultimo mese',
	'MUSICSHARE_TOP_PERIOD_ALL'		=> 'Da sempre',

	'MUSICSHARE_FOLLOW'			=> 'Segui questo utente',
	'MUSICSHARE_UNFOLLOW'		=> 'Smetti di seguire',
	'MUSICSHARE_FOLLOWERS'		=> 'Seguito da',
	'MUSICSHARE_FOLLOW_EXPLAIN'	=> 'Riceverai una notifica quando pubblica un nuovo brano, anche se le notifiche generali non sono attive per te.',
	'MUSICSHARE_FOLLOW_LOGIN'	=> 'Devi essere collegato per seguire un utente.',
	'MUSICSHARE_FOLLOW_OFF'		=> 'La funzione "segui" e\' disattivata su questo forum.',
	'MUSICSHARE_FOLLOW_SELF'	=> 'Non puoi seguire te stesso.',

	// Dati utili a chi vuole usare il brano, non solo ascoltarlo
	'MUSICSHARE_LICENSE'		=> 'Licenza d\'uso',
	'MUSICSHARE_LICENSE_EXPLAIN'	=> 'Dichiara cosa consenti a chi vuole usare il tuo brano. Non e\' un contratto e non sostituisce un accordo scritto, ma mette nero su bianco le tue intenzioni ed evita malintesi.',
	'MUSICSHARE_LIC_NONE'		=> 'Non indicata',
	'MUSICSHARE_LIC_CC0'		=> 'CC0 - Uso libero, nessuna condizione',
	'MUSICSHARE_LIC_CCBY'		=> 'CC BY - Libero, citando l\'autore',
	'MUSICSHARE_LIC_CCBYSA'		=> 'CC BY-SA - Libero, citando l\'autore e con la stessa licenza',
	'MUSICSHARE_LIC_CCBYNC'		=> 'CC BY-NC - Libero per usi non commerciali, citando l\'autore',
	'MUSICSHARE_LIC_FREE'		=> 'Uso libero anche commerciale',
	'MUSICSHARE_LIC_ASK'		=> 'Contattatemi prima di usarlo',
	'MUSICSHARE_LIC_ALLRIGHTS'	=> 'Tutti i diritti riservati - solo ascolto',
	'MUSICSHARE_BPM'			=> 'Battiti al minuto (BPM)',
	'MUSICSHARE_BPM_EXPLAIN'	=> 'Facoltativo. Utile a chi cerca un loop da inserire in un proprio pezzo. Lascia 0 se non lo sai.',
	'MUSICSHARE_KEY'			=> 'Tonalita\'',
	'MUSICSHARE_KEY_EXPLAIN'	=> 'Facoltativa. La tonalita\' e\' la nota su cui il brano "si posa": serve a chi vuole mixarlo o suonarci sopra. Clicca nel campo per scegliere dall\'elenco, oppure scrivila a mano: si accettano sia la notazione anglosassone (Am, F#m, C) sia quella italiana (la minore, do maggiore). Se non la conosci lascia vuoto, non e\' obbligatoria.',
	'MUSICSHARE_KEY_NOTES'		=> 'do,do#,re,mib,mi,fa,fa#,sol,lab,la,sib,si',
	'MUSICSHARE_KEY_MAJOR'		=> 'maggiore',
	'MUSICSHARE_KEY_MINOR'		=> 'minore',
	'MUSICSHARE_KBITS'			=> 'kbit/s',
	'MUSICSHARE_KHZ'			=> 'kHz',
	'MUSICSHARE_DECIMAL'		=> ',',
	'MUSICSHARE_MONO'			=> 'Mono',
	'MUSICSHARE_STEREO'			=> 'Stereo',
	'MUSICSHARE_CHANNELS_N'		=> '%d canali',
	'MUSICSHARE_REPLACE_FILE'	=> 'Sostituisci il file audio',
	'MUSICSHARE_REPLACE_FILE_EXPLAIN'	=> 'Carica una nuova versione del brano. Ascolti, voti, generi e discussione restano com\'erano: cambia solo il contenuto. Lascia vuoto per non toccare il file.',

	'MUSICSHARE_DOWNLOADS'		=> 'Download totali',
	'MUSICSHARE_STATS'			=> 'Ascolti / Download',

	'MUSICSHARE_PLAYLIST_SONGS'		=> 'Brani',

	'MUSICSHARE_FOLLOWING_NEWS'		=> 'Novita\' da chi segui',
	'MUSICSHARE_FOLLOWS_LIST'		=> 'Utenti che segui',
	'MUSICSHARE_FOLLOW_SINCE'		=> 'Segui da',
	'MUSICSHARE_FOLLOWING_EMPTY'	=> 'Gli utenti che segui non hanno ancora pubblicato brani.',
	'MUSICSHARE_FOLLOWING_NOBODY'	=> 'Non segui ancora nessuno. Il pulsante "Segui questo utente" si trova nella pagina di ogni autore: da quel momento riceverai un avviso quando pubblica, e i suoi brani compariranno qui.',

	'MUSICSHARE_BACK_LIBRARY'		=> 'Torna alla libreria',
	'MUSICSHARE_FOLLOWING_COUNT'	=> 'Utenti seguiti',

	'MUSICSHARE_CONTACT'			=> 'Contatta l\'autore',
	'MUSICSHARE_CONTACT_EXPLAIN'	=> 'Apre un messaggio privato del forum. Utile per chiedere il permesso di usare un brano o per proporre una collaborazione.',

	'MUSICSHARE_PM_SUBJECT'			=> 'A proposito di: %s',

	// pagina pubblica dell'autore
	'MUSICSHARE_AUTHOR'				=> 'Autore',
	'MUSICSHARE_AUTHOR_SINCE'		=> 'iscritto dal',
	'MUSICSHARE_AUTHOR_PROFILE'		=> 'Profilo sul forum',
	'MUSICSHARE_AUTHOR_ALL_SONGS'	=> 'Tutta la produzione',
	'MUSICSHARE_AUTHOR_EMPTY'		=> 'Questo utente non ha ancora pubblicato brani. Puoi seguirlo da qui: riceverai un avviso appena ne carica uno.',
	'MUSICSHARE_USER_NOT_FOUND'		=> 'Utente inesistente.',
	'MUSICSHARE_STAT_SONGS'			=> 'Brani',
	'MUSICSHARE_STAT_PLAYS'			=> 'Ascolti',
	'MUSICSHARE_STAT_DOWNLOADS'		=> 'Download',
	'MUSICSHARE_STAT_LIKES'			=> 'Mi piace',
	'MUSICSHARE_STAT_DISLIKES'		=> 'Non mi piace',

	// bacheca dell'autore
	'MUSICSHARE_WALL'				=> 'Bacheca',
	'MUSICSHARE_WALL_EMPTY'			=> 'Nessun commento per ora.',
	'MUSICSHARE_WALL_SEND'			=> 'Scrivi sulla bacheca',
	'MUSICSHARE_WALL_PLACEHOLDER'	=> 'Scrivi un commento...',
	'MUSICSHARE_WALL_REPLY'			=> 'Rispondi',
	'MUSICSHARE_WALL_REPLY_PLACEHOLDER'	=> 'Scrivi una risposta...',
	'MUSICSHARE_WALL_EDITED'		=> 'modificato il',
	'MUSICSHARE_WALL_EDITED_MOD'	=> 'modificato dalla moderazione il',
	'MUSICSHARE_WALL_HEART'			=> 'Mi arriva al cuore',
	'MUSICSHARE_WALL_REACT_OWN'		=> 'Non puoi reagire a un commento che hai scritto tu.',
	'MUSICSHARE_WALL_DELETE_CONFIRM'	=> 'Eliminare questo commento? Spariscono anche le risposte che ha ricevuto.',
	'MUSICSHARE_WALL_EXPLAIN'		=> 'Il commento resta su questa pagina. Sono ammessi BBCode e faccine.',
	'MUSICSHARE_WALL_EMPTY_TEXT'	=> 'Il commento e\' vuoto.',
	'MUSICSHARE_WALL_NOT_FOUND'		=> 'Commento inesistente.',
	'MUSICSHARE_WALL_LOGIN'			=> 'Devi essere collegato per commentare.',
	'MUSICSHARE_WALL_NO_PERMISSION'	=> 'Non hai il permesso di scrivere sulle bacheche.',
	'MUSICSHARE_WALL_FOLLOW_FIRST'	=> 'Solo chi segue questo autore puo\' scrivere sulla sua bacheca.',
	'MUSICSHARE_WALL_OFF'			=> 'Le bacheche non sono attive.',
]);
