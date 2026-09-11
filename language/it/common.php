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
]);
