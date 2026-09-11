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
	'ACP_MUSICSHARE_TITLE'		=> 'Music Share',
	'ACP_MUSICSHARE_GENRES'		=> 'Generi',
	'ACP_MUSICSHARE_MODERATE'	=> 'Moderazione',
	'ACP_MUSICSHARE_SETTINGS'	=> 'Impostazioni',

	'MUSICSHARE_UPLOADED_BY'		=> 'Caricato da',
	'MUSICSHARE_UPLOAD_DATE'		=> 'Data caricamento',
	'MUSICSHARE_APPROVE'			=> 'Approva',
	'MUSICSHARE_REJECT'			=> 'Rifiuta ed elimina',
	'MUSICSHARE_REJECT_CONFIRM'	=> 'Vuoi davvero rifiutare ed eliminare definitivamente questo brano?',
	'MUSICSHARE_NO_PENDING_SONGS'	=> 'Nessun brano in attesa di approvazione.',
	'MUSICSHARE_SONG_APPROVED'		=> 'Brano approvato.',
	'MUSICSHARE_SONG_REJECTED'		=> 'Brano rifiutato ed eliminato.',

	'MUSICSHARE_GENRE_NAME'			=> 'Nome genere',
	'MUSICSHARE_GENRE_ORDER'		=> 'Ordine',
	'MUSICSHARE_GENRE_ORDER_EXPLAIN'	=> 'I generi vengono mostrati in ordine crescente; a parità di valore, in ordine alfabetico.',
	'MUSICSHARE_GENRE_NAME_EMPTY'	=> 'Devi indicare un nome per il genere.',
	'MUSICSHARE_GENRE_ADDED'		=> 'Genere aggiunto.',
	'MUSICSHARE_GENRE_UPDATED'		=> 'Genere aggiornato.',
	'MUSICSHARE_GENRE_DELETED'		=> 'Genere eliminato.',
	'MUSICSHARE_GENRE_DELETE_CONFIRM'	=> 'Vuoi davvero eliminare questo genere? I brani associati non verranno eliminati, ma perderanno questo genere.',
	'MUSICSHARE_ADD_GENRE'			=> 'Aggiungi genere',
	'MUSICSHARE_EDIT_GENRE'			=> 'Modifica genere',

	'MUSICSHARE_STORAGE_PATH'		=> 'Cartella di archiviazione',
	'MUSICSHARE_STORAGE_PATH_EXPLAIN'	=> 'Percorso in cui salvare i brani caricati, relativo alla root del forum (es. files/musicshare/) oppure assoluto (es. /var/www/musicshare/). Deve essere scrivibile dal server web.',
	'MUSICSHARE_STORAGE_WRITABLE'	=> 'La cartella configurata è scrivibile.',
	'MUSICSHARE_STORAGE_NOT_WRITABLE'	=> 'Attenzione: la cartella configurata non esiste o non è scrivibile. Gli upload non funzioneranno finché non viene corretta.',

	'MUSICSHARE_ALLOWED_EXT'		=> 'Estensioni consentite',
	'MUSICSHARE_ALLOWED_EXT_EXPLAIN'	=> 'Elenco separato da virgole, senza il punto (es. mp3,ogg,flac,wav,m4a,aac).',
	'MUSICSHARE_MAX_FILESIZE'		=> 'Dimensione massima per file (byte)',
	'MUSICSHARE_MAX_FILESIZE_EXPLAIN'	=> 'Impostato anche a livello di server/PHP (upload_max_filesize), che resta comunque il limite assoluto.',
	'MUSICSHARE_MAX_USER_SPACE'	=> 'Spazio massimo per utente (byte)',
	'MUSICSHARE_MAX_USER_SPACE_EXPLAIN'	=> 'Somma totale consentita per tutti i brani caricati da un singolo utente. Imposta 0 per nessun limite.',
	'MUSICSHARE_WAVEFORM_ENABLED'	=> 'Mostra la forma d\'onda nel player',
	'MUSICSHARE_WAVEFORM_ENABLED_EXPLAIN'	=> 'La forma d\'onda viene disegnata lato client mentre il brano è in riproduzione.',
	'MUSICSHARE_SONGS_PER_PAGE'	=> 'Brani per pagina',
	'MUSICSHARE_REQUIRE_APPROVAL'	=> 'Richiedi approvazione dei brani caricati',
	'MUSICSHARE_REQUIRE_APPROVAL_EXPLAIN'	=> 'Se attivo, i brani caricati non saranno visibili né riproducibili finché un moderatore non li approva (funzione di moderazione da implementare via ACP o database).',

	'MUSICSHARE_MB'					=> '%d MB',
	'MUSICSHARE_GB'					=> '%d GB',
	'MUSICSHARE_UNLIMITED'			=> 'Nessun limite',
	'MUSICSHARE_CUSTOM_VALUE'		=> 'Valore attuale personalizzato (%s MB)',
	'MUSICSHARE_PHP_LIMIT'			=> 'Limite imposto dal server PHP (upload_max_filesize)',

	'MUSICSHARE_GENRE_CATEGORY'			=> 'Categoria',
	'MUSICSHARE_GENRE_CATEGORY_EXPLAIN'	=> 'Raggruppa i generi affini (es. Rock, Metal, Jazz e Blues). Lascia vuoto per non assegnare alcuna categoria. Puoi scegliere una categoria esistente o scriverne una nuova.',
	'MUSICSHARE_NO_CATEGORY'			=> 'Senza categoria',
	'MUSICSHARE_GENRES_INTRO'			=> 'L\'estensione include gia\' un elenco di generi predefiniti suddivisi per categoria. Puoi aggiungerne di nuovi, modificarli o eliminarli liberamente.',

	'MUSICSHARE_PERSIST_PLAYER'			=> 'Riprendi la riproduzione cambiando pagina',
	'MUSICSHARE_PERSIST_PLAYER_EXPLAIN'	=> 'Se attivo, quando l\'utente naviga tra le pagine del forum il brano riparte automaticamente dal punto in cui era. Nota: il cambio pagina ricarica la pagina, quindi c\'e\' una brevissima interruzione. Alcuni browser bloccano la ripresa automatica finche\' l\'utente non clicca qualcosa nella nuova pagina: in quel caso il player resta pronto e basta premere Play.',

	'ACP_MUSICSHARE_GROUPS'			=> 'Gruppi autorizzati',
	'MUSICSHARE_GROUPS_INTRO'		=> 'Scegli quali gruppi possono caricare brani e creare playlist. Questa pagina scrive gli stessi permessi che trovi in ACP -> Permessi (u_musicshare_upload e u_musicshare_playlist): e\' solo una scorciatoia comoda. I gruppi in corsivo sono quelli predefiniti di phpBB. Attenzione: le caselle non spuntate impostano il permesso su \"No\", non su \"Mai\", quindi un utente puo\' comunque ottenere il permesso da un altro gruppo a cui appartiene.',
	'MUSICSHARE_CAN_UPLOAD'			=> 'Puo\' caricare brani',
	'MUSICSHARE_CAN_PLAYLIST'		=> 'Puo\' creare playlist',
	'MUSICSHARE_GROUPS_UPDATED'		=> 'Permessi dei gruppi aggiornati.',

	'MUSICSHARE_STORAGE_EXPOSED'		=> 'Attenzione: la cartella di archiviazione si trova dentro la radice del forum e non risulta protetta. I file audio potrebbero essere scaricabili direttamente conoscendone l\'indirizzo, aggirando permessi e moderazione. L\'estensione ha provato a creare un file .htaccess protettivo: se il messaggio resta, sposta la cartella fuori dalla radice del forum oppure proteggila dal pannello del tuo hosting.',
	'MUSICSHARE_ALLOW_DOWNLOAD'			=> 'Consenti il download dei brani',
	'MUSICSHARE_ALLOW_DOWNLOAD_EXPLAIN'	=> 'Interruttore generale del download. Se disattivo, nessun brano e\' scaricabile. Se attivo, accanto a ogni brano compare il pulsante di download, ma ciascun autore puo\' comunque vietarlo per i propri brani dalla pagina di caricamento o di modifica.',
	'MUSICSHARE_BLOCK_DUPLICATES'		=> 'Blocca i caricamenti doppi',
	'MUSICSHARE_BLOCK_DUPLICATES_EXPLAIN'	=> 'Confronta l\'impronta del file: impedisce a uno stesso utente di caricare due volte lo stesso brano occupando spazio inutilmente.',
	'MUSICSHARE_PENDING_SECTION'		=> 'Brani in attesa di approvazione',
	'MUSICSHARE_ALL_SONGS_SECTION'		=> 'Tutti i brani',
	'MUSICSHARE_UNAPPROVE'				=> 'Revoca approvazione',
	'MUSICSHARE_SONG_UNAPPROVED'		=> 'Approvazione revocata: il brano non e\' piu\' visibile agli utenti.',
	'MUSICSHARE_SIZE'					=> 'Dimensione',

	'MUSICSHARE_FOLDER_NAMING'			=> 'Nome delle cartelle utente',
	'MUSICSHARE_FOLDER_BY_USERNAME'		=> 'Nome utente',
	'MUSICSHARE_FOLDER_BY_ID'			=> 'Solo ID numerico',
	'MUSICSHARE_FOLDER_NAMING_EXPLAIN'	=> 'Come nominare la sottocartella di ogni utente dentro la cartella di archiviazione. Con "Nome utente" si ottiene ad esempio <em>salvo_2/</em> (il numero finale e\' l\'ID, serve a evitare collisioni fra nomi simili e a mantenere la cartella valida se l\'utente cambia nome). I caratteri non ammessi nei nomi di file vengono sostituiti. I brani gia\' caricati restano dove sono e continuano a funzionare: la modifica vale solo per i caricamenti successivi.',

	'MUSICSHARE_SHOW_STATS'			=> 'Mostra le statistiche musicali',
	'MUSICSHARE_SHOW_STATS_EXPLAIN'	=> 'Aggiunge il numero di brani caricati sotto il profilo nei messaggi e nella scheda profilo dell\'utente. Il conteggio per una pagina di messaggi viene calcolato con una sola interrogazione al database, quindi l\'impatto sulle prestazioni e\' trascurabile.',

	'MUSICSHARE_TOAST_ENABLED'			=> 'Avvisa dei nuovi brani caricati',
	'MUSICSHARE_TOAST_ENABLED_EXPLAIN'	=> 'Mostra agli utenti collegati un breve avviso a comparsa quando qualcun altro carica un brano. Il controllo avviene solo mentre la scheda del browser e\' in primo piano e non viene mai mostrato all\'autore del caricamento.',
	'MUSICSHARE_TOAST_INTERVAL'			=> 'Intervallo di controllo (secondi)',
	'MUSICSHARE_TOAST_INTERVAL_EXPLAIN'	=> 'Ogni quanto il browser chiede al forum se ci sono nuovi brani. Valori bassi rendono l\'avviso piu\' immediato ma aumentano il carico: sotto i 30 secondi non e\' consentito.',
	'MUSICSHARE_RECENT_COUNT'			=> 'Brani nel riquadro "Caricati di recente"',

	'MUSICSHARE_INDEX_FEED'				=> 'Riquadro "Caricati di recente"',
	'MUSICSHARE_INDEX_FEED_EXPLAIN'		=> 'Dove mostrare l\'elenco degli ultimi brani caricati dagli utenti. Il contenuto viene tenuto in cache per un minuto, quindi anche l\'opzione "su tutte le pagine" non aggiunge una interrogazione al database a ogni caricamento.',
	'MUSICSHARE_FEED_OFF'				=> 'Non mostrarlo',
	'MUSICSHARE_FEED_INDEX'				=> 'Solo nell\'indice del forum',
	'MUSICSHARE_FEED_ALL'				=> 'In tutte le pagine del forum',
	'MUSICSHARE_INDEX_FEED_COUNT'		=> 'Brani da mostrare in tutto',
	'MUSICSHARE_INDEX_FEED_PER_USER'	=> 'Brani massimi per utente',
	'MUSICSHARE_INDEX_FEED_PER_USER_EXPLAIN'	=> 'Impedisce che un solo utente molto attivo riempia l\'intero riquadro. Imposta 0 per non porre limiti.',

	'MUSICSHARE_SHIELD_VERSION'		=> 'versione',
	'MUSICSHARE_SHIELD_LICENSE'		=> 'licenza',
	'MUSICSHARE_SHIELD_REQUIRES'	=> 'Richiede:',

	'MUSICSHARE_PLAYER_SCOPE'			=> 'Dove mostrare il lettore in basso',

	'MUSICSHARE_PLAYER_ALWAYS'			=> 'Mostra sempre il lettore durante la riproduzione',
	'MUSICSHARE_PLAYER_ALWAYS_EXPLAIN'	=> 'Con "Si\'" la barra del lettore compare in fondo a qualsiasi pagina del forum non appena parte un brano. Con "No" compare solo nelle pagine della sezione Musica: altrove si comanda la riproduzione dai pulsanti sulla riga del brano, dove si puo\' anche cliccare sulla barra di avanzamento per spostarsi nel brano. Il "No" evita inoltre di scaricare una seconda volta il file audio per disegnare la forma d\'onda. In ogni caso, se il brano in ascolto non compare nella pagina, il lettore viene mostrato comunque per non lasciare l\'utente senza comandi.',

	'MUSICSHARE_VOTES_ENABLED'			=> 'Consenti mi piace / non mi piace sui brani',
	'MUSICSHARE_VOTES_ENABLED_EXPLAIN'	=> 'Aggiunge due pulsanti accanto a ogni brano. Ogni utente puo\' esprimere un solo voto per brano e puo\' cambiarlo o annullarlo premendo di nuovo. Chi ha caricato il brano non puo\' votarlo, e agli ospiti i conteggi sono visibili ma i pulsanti restano disattivati.',

	'MUSICSHARE_FEED_TITLE'			=> 'Titolo del riquadro',
	'MUSICSHARE_FEED_TITLE_EXPLAIN'	=> 'Testo mostrato in cima al riquadro dei brani recenti, uno per ciascuna lingua installata. Lascia vuoto per usare la traduzione predefinita dell\'estensione ("Caricati di recente"). A ogni utente viene mostrato il testo della propria lingua; se per quella lingua non ne hai indicato uno, si usa quello della lingua predefinita del forum e, in mancanza, la traduzione predefinita.',

	'MUSICSHARE_TOAST_SELF'			=> 'Avvisa anche chi ha caricato il brano',
	'MUSICSHARE_TOAST_SELF_EXPLAIN'	=> 'Di norma l\'avviso a comparsa non viene mostrato all\'autore del caricamento, che sa gia\' di averlo fatto. Con "Si\'" lo riceve anche lui, come conferma. L\'avviso arriva al primo controllo successivo al caricamento, quindi entro l\'intervallo impostato qui sotto.',

	'MUSICSHARE_CAN_VIEW'			=> 'Vede la sezione Musica',
	'MUSICSHARE_CAN_FEED'			=> 'Vede il riquadro brani',

	'MUSICSHARE_FEED_COUNT_ALL'			=> 'Tutti',
	'MUSICSHARE_INDEX_FEED_COUNT_EXPLAIN'	=> 'Quanti brani elencare nel riquadro. Oltre venti brani il riquadro non si allunga ma diventa scorrevole, così non occupa mezza pagina. Con "Tutti" non c\'e\' alcun tetto: su forum con molti caricamenti conviene comunque scegliere un numero, per non appesantire ogni pagina.',

	'MUSICSHARE_FEED_SCROLL_AFTER'			=> 'Attiva lo scorrimento oltre',
	'MUSICSHARE_FEED_SCROLL_AFTER_EXPLAIN'	=> 'Numero di brani oltre il quale il riquadro smette di allungarsi e diventa scorrevole. Valori ammessi da 10 a 10000; imposta 0 perche\' non scorra mai e si allunghi quanto serve.',

	'MUSICSHARE_DISCLAIMER_ACP'		=> 'Questa estensione e\' pensata per la condivisione di loop e brani realizzati dagli utenti e di musica royalty free. Non impedisce di per se\' il caricamento di materiale protetto da diritto d\'autore: la sorveglianza sui contenuti pubblicati resta a carico dello staff del forum. Se attivi la sezione, valuta di richiedere l\'approvazione dei brani caricati e di verificare periodicamente la scheda Moderazione.',

	'MUSICSHARE_NOTIFY_PM'			=> 'Avvisa anche con un messaggio privato',
	'MUSICSHARE_NOTIFY_PM_EXPLAIN'	=> 'Oltre alla notifica, invia all\'autore un messaggio privato quando un suo brano viene approvato, rifiutato o rimosso. Il messaggio risulta inviato dall\'utente che compie l\'azione. Le notifiche restano attive in ogni caso e ciascun utente puo\' regolarle da "Gestisci notifiche" nel proprio pannello.',

	'ACP_MUSICSHARE_RECOGNITION'	=> 'Riconoscimento brani',
	'MUSICSHARE_RECO_WARNING_TITLE'	=> 'Cosa fa davvero questo controllo',
	'MUSICSHARE_RECO_WARNING'		=> 'Questi servizi NON stabiliscono se un brano sia protetto dal diritto d\'autore: confrontano l\'audio con un archivio di pubblicazioni commerciali e dicono se corrisponde a una di esse. Una corrispondenza e\' un indizio forte che il file non sia opera dell\'utente, ma puo\' essere un falso positivo, perche\' anche molta musica royalty free e\' registrata in quegli archivi. L\'assenza di corrispondenza non significa che il brano sia libero: brani poco noti, remix e registrazioni dal vivo spesso non vengono riconosciuti. Trattalo come un aiuto alla moderazione, non come una verifica legale.',
	'MUSICSHARE_RECO_SERVICE'		=> 'Servizio di riconoscimento',
	'MUSICSHARE_RECO_SERVICE_EXPLAIN'	=> 'Scegli quale servizio usare. Con "Disattivato" nessun brano viene analizzato e non viene consumata alcuna richiesta. Le credenziali del servizio scelto devono essere compilate qui sotto, altrimenti il controllo resta inattivo.',
	'MUSICSHARE_RECO_OFF'			=> 'Disattivato',
	'MUSICSHARE_RECO_SECONDS'		=> 'Secondi di audio da analizzare',
	'MUSICSHARE_RECO_SECONDS_EXPLAIN'	=> 'Viene inviata solo la parte iniziale del brano, non il file intero. I servizi analizzano pochi secondi: da 10 a 15 e\' di norma sufficiente.',
	'MUSICSHARE_AUDD_TOKEN'			=> 'Token API di AudD',
	'MUSICSHARE_AUDD_TOKEN_EXPLAIN'	=> 'Si ottiene registrandosi su dashboard.audd.io. Sono previste alcune richieste gratuite, poi il servizio e\' a pagamento.',
	'MUSICSHARE_ACR_HOST'			=> 'Host ACRCloud',
	'MUSICSHARE_ACR_HOST_EXPLAIN'	=> 'Indirizzo del progetto, per esempio identify-eu-west-1.acrcloud.com. Lo trovi nella console di ACRCloud insieme alle chiavi.',
	'MUSICSHARE_ACR_KEY'			=> 'Access key ACRCloud',
	'MUSICSHARE_ACR_SECRET'			=> 'Access secret ACRCloud',
	'MUSICSHARE_RECO_GROUPS'		=> 'Gruppi soggetti al controllo',
	'MUSICSHARE_RECO_GROUPS_EXPLAIN'	=> 'Solo i caricamenti degli utenti appartenenti ai gruppi selezionati vengono analizzati. Se non selezioni alcun gruppo il controllo non viene mai eseguito: e\' voluto, per non consumare richieste a tua insaputa. Conviene escludere staff e utenti di fiducia.',
	'MUSICSHARE_RECO_CHECKED'		=> 'Controlla i caricamenti',
	'MUSICSHARE_RECO_TEST'			=> 'Verifica le credenziali',
	'MUSICSHARE_RECO_TEST_EXPLAIN'	=> 'La verifica interroga davvero il servizio, quindi consuma una richiesta del tuo piano. Con AudD viene analizzato il file di esempio della loro documentazione, perche\' un campione vuoto verrebbe rifiutato anche con un token valido.',
	'MUSICSHARE_RECO_TEST_OK'		=> 'Le credenziali funzionano: il servizio ha risposto correttamente.',
	'MUSICSHARE_RECO_TEST_FAILED'	=> 'Verifica non riuscita. Risposta del servizio: %s',
	'MUSICSHARE_RECO_TEST_NOT_CONFIGURED'	=> 'Scegli un servizio e compila le sue credenziali prima di eseguire la verifica.',
	'MUSICSHARE_RECO_TEST_NETWORK'	=> 'Impossibile contattare il servizio. Il server potrebbe non avere accesso a internet.',
	'MUSICSHARE_RECO_TEST_RESPONSE'	=> 'Il servizio ha risposto in un formato non riconosciuto.',

	// Guida all'ottenimento delle chiavi
	'MUSICSHARE_RECO_GUIDE_TITLE'	=> 'Come ottenere le chiavi API (guida passo passo)',
	'MUSICSHARE_GUIDE_AUDD_1'		=> 'Apri <strong>dashboard.audd.io</strong> e registrati con la tua email. Non serve carta di credito per iniziare.',
	'MUSICSHARE_GUIDE_AUDD_2'		=> 'Dopo la conferma dell\'email entri nel pannello: il token API compare subito nella pagina principale, e\' una stringa lunga di lettere e numeri.',
	'MUSICSHARE_GUIDE_AUDD_3'		=> 'Copialo e incollalo qui sotto nel campo "Token API di AudD".',
	'MUSICSHARE_GUIDE_AUDD_4'		=> 'Scegli "AudD" come servizio, salva, poi premi "Verifica le credenziali" per accertarti che funzioni.',
	'MUSICSHARE_GUIDE_ACR_1'		=> 'Apri <strong>console.acrcloud.com/signup</strong> e crea un account gratuito.',
	'MUSICSHARE_GUIDE_ACR_2'		=> 'Nella console crea un nuovo progetto di tipo <strong>Audio &amp; Video Recognition</strong> (riconoscimento audio e video), scegliendo la regione piu\' vicina: per l\'Europa va bene <em>eu-west-1</em>.',
	'MUSICSHARE_GUIDE_ACR_3'		=> 'Aperto il progetto, la console mostra tre valori: <strong>host</strong> (per esempio identify-eu-west-1.acrcloud.com), <strong>access key</strong> e <strong>access secret</strong>.',
	'MUSICSHARE_GUIDE_ACR_4'		=> 'Copia i tre valori nei rispettivi campi qui sotto. L\'host va scritto senza "https://" davanti.',
	'MUSICSHARE_GUIDE_ACR_5'		=> 'Scegli "ACRCloud" come servizio, salva, poi premi "Verifica le credenziali".',
	'MUSICSHARE_GUIDE_AFTER_TITLE'	=> 'Una volta inserite le chiavi',
	'MUSICSHARE_GUIDE_AFTER_1'		=> 'Spunta nella tabella qui sotto i gruppi i cui caricamenti vanno controllati. Finche\' non selezioni almeno un gruppo il controllo non parte e non viene consumata nessuna richiesta.',
	'MUSICSHARE_GUIDE_AFTER_2'		=> 'Conviene lasciare fuori staff e utenti di fiducia: ogni controllo costa una richiesta.',
	'MUSICSHARE_GUIDE_AFTER_3'		=> 'Fai una prova caricando un brano commerciale noto con un account di un gruppo controllato: deve comparire l\'avviso e il brano deve finire in attesa di approvazione.',
	'MUSICSHARE_GUIDE_PRICING'		=> 'prezzi',
	'MUSICSHARE_GUIDE_SERVICE_PAGE'	=> 'pagina del servizio',
	'MUSICSHARE_GUIDE_COST'			=> 'Attenzione ai costi: viene consumata una richiesta per ogni brano caricato da un utente dei gruppi controllati, anche quando non risulta alcuna corrispondenza. AudD offre alcune richieste gratuite e poi si paga a consumo; ACRCloud ha un periodo di prova gratuito. Controlla i piani aggiornati sui rispettivi siti prima di aprire il controllo a tutti gli utenti.',

	'MUSICSHARE_SEARCH_EXPLAIN'		=> 'Cerca per titolo, artista, album o nome dell\'utente che ha caricato il brano.',
	'MUSICSHARE_SEARCH_RESET'		=> 'Azzera ricerca',
	'MUSICSHARE_SEARCH_ACTIVE'		=> 'Ricerca attiva su "%1$s": %2$d brani corrispondenti. Premi "Azzera ricerca" per rivedere l\'elenco completo.',

	'MUSICSHARE_DESCRIPTIONS'			=> 'Consenti una descrizione per ogni brano',
	'MUSICSHARE_DESCRIPTIONS_EXPLAIN'	=> 'Aggiunge al modulo di caricamento un campo in cui l\'utente puo\' descrivere il brano con parole sue. La descrizione compare sotto il titolo in tutti gli elenchi, compreso il riquadro nelle pagine del forum. Disattivandola il campo sparisce e le descrizioni gia\' scritte non vengono piu\' mostrate, ma restano nel database e ricompaiono se riattivi l\'opzione.',
	'MUSICSHARE_DESCRIPTION_MAX'		=> 'Lunghezza massima della descrizione',
	'MUSICSHARE_DESCRIPTION_MAX_EXPLAIN'	=> 'Numero massimo di caratteri, da 50 a 1000. Il valore consigliato e\' 300: abbastanza per due righe, non tanto da sbilanciare gli elenchi. Il limite viene applicato anche lato server, non solo nel modulo.',

	'MUSICSHARE_TOAST_SOUND'		=> 'Segnale acustico sugli avvisi',
	'MUSICSHARE_TOAST_SOUND_EXPLAIN'	=> 'Riproduce due note brevi quando compare l\'avviso di un nuovo brano. Il suono viene generato dal browser, non c\'e\' alcun file da scaricare. Molti browser non consentono la riproduzione finche\' l\'utente non ha interagito con la pagina: in quel caso l\'avviso resta comunque visibile, solo muto.',
	'MUSICSHARE_TOAST_VOLUME'		=> 'Volume del segnale acustico',
	'MUSICSHARE_TOAST_VOLUME_EXPLAIN'	=> 'Da 0 a 100. Il valore consigliato e\' 30: udibile senza risultare invadente durante la navigazione.',

	'MUSICSHARE_BBCODE'				=> 'Consenti l\'inserimento dei brani nei messaggi',
	'MUSICSHARE_BBCODE_EXPLAIN'		=> 'Abilita il codice [musicshare]12[/musicshare], che inserisce nel messaggio un lettore completo del brano indicato, con riproduci, pausa, ferma e barra di avanzamento. In "I miei brani" ogni utente trova un pulsante che copia il codice gia\' pronto. I permessi vengono verificati alla lettura del messaggio: un brano rimosso, non approvato o non visibile a chi legge non viene riprodotto.',

	'MUSICSHARE_CAN_NOTIFY'			=> 'Riceve le notifiche',

	'MUSICSHARE_CLEANUP'			=> 'Pulizia delle notifiche',
	'MUSICSHARE_CLEANUP_ENABLED'	=> 'Pulizia automatica giornaliera',
	'MUSICSHARE_CLEANUP_ENABLED_EXPLAIN'	=> 'Una volta al giorno rimuove le notifiche dell\'estensione gia\' lette e piu\' vecchie del periodo indicato qui sotto. L\'operazione sfrutta le attivita\' pianificate di phpBB, quindi non serve alcun cron di sistema: viene eseguita in coda alla visita di una pagina, a blocchi, senza rallentare la navigazione.',
	'MUSICSHARE_CLEANUP_DAYS'		=> 'Conserva le notifiche lette per',
	'MUSICSHARE_CLEANUP_DAYS_EXPLAIN'	=> 'Giorni, da 0 a 365. Con 0 le notifiche lette vengono rimosse alla prima esecuzione, senza attesa. Le notifiche NON lette non vengono mai toccate.',
	'MUSICSHARE_CLEANUP_NOW'		=> 'Pulisci adesso',
	'MUSICSHARE_CLEANUP_NOW_EXPLAIN'	=> 'Rimuove subito tutte le notifiche dell\'estensione gia\' lette, ignorando il periodo di conservazione. Le notifiche ancora da leggere restano.',
	'MUSICSHARE_CLEANUP_STATE'		=> 'Notifiche lette rimovibili adesso: %1$d. Ultima pulizia: %2$s.',
	'MUSICSHARE_CLEANUP_NEVER'		=> 'mai eseguita',
	'MUSICSHARE_CLEANUP_DONE'		=> 'Pulizia completata: %d notifiche rimosse.',

	// ---- Scheda Manutenzione e verifiche ----
	'ACP_MUSICSHARE_MAINTENANCE'	=> 'Manutenzione e verifiche',
	'MS_MAINT_INTRO'		=> 'Questa scheda controlla lo stato reale dell\'estensione: ambiente del server, cartella di archiviazione, struttura del database, coerenza fra brani e file su disco, notifiche e funzioni facoltative. Accanto a ogni problema trovi il rimedio.',
	'MS_SUMMARY'			=> 'Riepilogo',
	'MS_STATE_OK'			=> 'a posto',
	'MS_STATE_WARN'			=> 'da valutare',
	'MS_STATE_ERROR'		=> 'da correggere',
	'MS_ALL_GOOD'			=> 'Nessun problema rilevato: l\'estensione risulta configurata correttamente.',
	'MS_HOWTO'				=> 'Come rimediare',
	'MS_RELOAD'				=> 'Ripeti le verifiche',
	'MS_RELOAD_EXPLAIN'		=> 'I controlli vengono eseguiti a ogni apertura della scheda: usa questo pulsante dopo aver corretto qualcosa.',
	'MS_CLEANUP_AVAILABLE'	=> 'Notifiche lette rimovibili adesso',

	'MS_SEC_ENV'			=> '1. Ambiente del server',
	'MS_SEC_STORAGE'		=> '2. Cartella di archiviazione',
	'MS_SEC_DB'				=> '3. Struttura del database',
	'MS_SEC_INTEGRITY'		=> '4. Coerenza fra database e file',
	'MS_SEC_NOTIF'			=> '5. Sistema di notifiche',
	'MS_SEC_FEATURES'		=> '6. Funzioni facoltative',
	'MS_SEC_ACTIONS'		=> 'Azioni',

	// controlli
	'MS_CHK_PHP'			=> 'Versione di PHP',
	'MS_CHK_EXECTIME'		=> 'Tempo massimo di esecuzione',
	'MS_CHK_MEMORY'			=> 'Memoria disponibile',
	'MS_CHK_UPLOADSIZE'		=> 'Dimensione massima di caricamento',
	'MS_CHK_POSTSIZE'		=> 'Dimensione massima dei dati inviati',
	'MS_CHK_CURL'			=> 'Libreria cURL',
	'MS_CHK_HMAC'			=> 'Funzione hash_hmac',
	'MS_CHK_GETID3'			=> 'Libreria getID3 (lettura dei tag)',
	'MS_CHK_PATH'			=> 'Percorso della cartella',
	'MS_CHK_PATH_EXISTS'	=> 'La cartella esiste',
	'MS_CHK_WRITABLE'		=> 'La cartella e\' scrivibile',
	'MS_CHK_PROTECTED'		=> 'Protezione dall\'accesso diretto',
	'MS_CHK_DISKFREE'		=> 'Spazio libero sul disco',
	'MS_CHK_SONGS'			=> 'Brani archiviati',
	'MS_CHK_TABLES'			=> 'Tabelle dell\'estensione',
	'MS_CHK_COLUMNS'		=> 'Colonne della tabella dei brani',
	'MS_CHK_MIGRATIONS'		=> 'Migrazioni applicate',
	'MS_CHK_MISSING_FILES'	=> 'Brani il cui file non esiste piu\'',
	'MS_CHK_MISSING_COVERS'	=> 'Copertine mancanti',
	'MS_CHK_ORPHANS'		=> 'File su disco non collegati ad alcun brano',
	'MS_CHK_ORPHAN_VOTES'	=> 'Voti riferiti a brani inesistenti',
	'MS_CHK_ORPHAN_PLAYLIST'	=> 'Voci di playlist riferite a brani inesistenti',
	'MS_CHK_ORPHAN_GENRES'	=> 'Associazioni di genere riferite a brani inesistenti',
	'MS_CHK_NOTIF_TYPES'	=> 'Tipi di notifica registrati',
	'MS_CHK_NOTIF_SERVICES'	=> 'Servizi dei tipi di notifica',
	'MS_CHK_NOTIF_USERS'	=> 'Destinatari delle notifiche',
	'MS_CHK_NOTIF_ROWS'		=> 'Notifiche presenti nel database',
	'MS_CHK_CLEANUP'		=> 'Pulizia automatica',
	'MS_CHK_RECO'			=> 'Riconoscimento dei brani',
	'MS_CHK_BBCODE'			=> 'Inserimento nei messaggi',
	'MS_CHK_TOAST'			=> 'Avvisi a comparsa',
	'MS_CHK_APPROVAL'		=> 'Approvazione dei caricamenti',
	'MS_CHK_PENDING'		=> 'Brani in attesa di approvazione',

	// rimedi
	'MS_FIX_PHP'			=> 'L\'estensione richiede PHP 7.2 o successivo. Aggiorna la versione di PHP dal pannello del tuo hosting.',
	'MS_FIX_EXECTIME'		=> 'Meno di 30 secondi possono non bastare per caricare un brano lungo, leggerne i tag e contattare il servizio di riconoscimento. Se puoi, alza max_execution_time; in alternativa riduci la dimensione massima dei file o disattiva il riconoscimento.',
	'MS_FIX_MEMORY'			=> 'Con meno di 64 MB la lettura dei tag di file grandi puo\' fallire. Alza memory_limit dal pannello del tuo hosting.',
	'MS_FIX_UPLOADSIZE'		=> 'La dimensione massima impostata nell\'estensione supera quella consentita da PHP: i file oltre il limite di PHP verranno rifiutati prima ancora di arrivare all\'estensione. Abbassa il valore in Impostazioni oppure alza upload_max_filesize sul server.',
	'MS_FIX_POSTSIZE'		=> 'post_max_size dovrebbe essere maggiore o uguale a upload_max_filesize, altrimenti i caricamenti al limite falliscono senza un messaggio chiaro.',
	'MS_FIX_CURL'			=> 'Senza cURL il riconoscimento dei brani usa i flussi di PHP, piu\' lenti e meno affidabili. Non e\' un problema se il riconoscimento resta disattivato.',
	'MS_FIX_HMAC'			=> 'Senza hash_hmac non e\' possibile firmare le richieste ad ACRCloud. Usa AudD oppure chiedi all\'hosting di abilitare l\'estensione hash di PHP.',
	'MS_FIX_GETID3'			=> 'La libreria che legge i tag dei file audio non e\' presente: titolo, artista, durata e copertina non verranno rilevati. Ricarica sul server la cartella vendor/getid3 dell\'estensione.',
	'MS_FIX_PATH_EXISTS'	=> 'La cartella indicata non esiste: creala sul server oppure correggi il percorso in Impostazioni. Verra\' creata automaticamente al primo caricamento se la cartella superiore e\' scrivibile.',
	'MS_FIX_WRITABLE'		=> 'Il server non puo\' scrivere nella cartella: nessun caricamento andra\' a buon fine. Imposta i permessi a 755 o 777 dal pannello file del tuo hosting.',
	'MS_FIX_PROTECTED'		=> 'La cartella non ha un file .htaccess che ne impedisca la lettura diretta. I file hanno nomi casuali e non sono indovinabili, ma la protezione e\' comunque consigliata. Verra\' creata al primo caricamento; se il server non e\' Apache, spostare la cartella fuori dalla radice del sito e\' la soluzione piu\' sicura.',
	'MS_FIX_DISKFREE'		=> 'Meno di 100 MB liberi: i prossimi caricamenti potrebbero fallire. Libera spazio o riduci la quota per utente.',
	'MS_FIX_TABLES'			=> 'Alcune tabelle dell\'estensione non esistono: l\'installazione e\' incompleta. Disabilita e riabilita l\'estensione da Gestione estensioni per far eseguire di nuovo le migrazioni.',
	'MS_FIX_COLUMNS'		=> 'Alcune colonne mancano: probabilmente una migrazione si e\' interrotta. Disabilita e riabilita l\'estensione; se il problema resta, controlla i permessi dell\'utente del database.',
	'MS_FIX_MISSING_FILES'	=> 'Questi brani risultano nel database ma il file non e\' piu\' sul disco: non sono riproducibili. Eliminali dalla scheda Moderazione oppure ripristina i file da un salvataggio.',
	'MS_FIX_MISSING_COVERS'	=> 'Le copertine di questi brani non sono piu\' sul disco: al loro posto compare l\'icona predefinita. Puoi ricaricarle dalla modifica del brano.',
	'MS_FIX_ORPHANS'		=> 'Sul disco ci sono file che nessun brano rivendica, di solito residui di caricamenti interrotti o di brani eliminati. Occupano spazio ma non causano malfunzionamenti: puoi rimuoverli a mano dal pannello file del tuo hosting.',
	'MS_FIX_ORPHAN_ROWS'	=> 'Ci sono righe che fanno riferimento a brani non piu\' esistenti. Non causano errori, ma se sono molte conviene rimuoverle con una query diretta sul database.',
	'MS_FIX_NOTIF_TYPES'	=> 'I tipi di notifica non risultano registrati o attivi: nessuna notifica verra\' inviata. Disabilita e riabilita l\'estensione per far eseguire di nuovo la migrazione che li registra.',
	'MS_FIX_NOTIF_SERVICES'	=> 'Alcuni tipi di notifica non sono costruibili: c\'e\' un problema nella configurazione dei servizi. Svuota la cache di phpBB; se il problema resta, ricarica i file dell\'estensione sul server.',
	'MS_FIX_NOTIF_NOBODY'	=> 'Nessun gruppo ha il permesso di ricevere le notifiche dei nuovi brani, quindi non ne verra\' inviata alcuna. Assegna il permesso ai gruppi voluti nella scheda Gruppi autorizzati.',
	'MS_FIX_NOTIF_TOOMANY'	=> 'I destinatari sono %1$d: ogni brano caricato creerebbe altrettante righe nel database, cioe\' circa %2$d ogni cento brani. L\'operazione puo\' rallentare o far fallire i caricamenti. Restringi il permesso "Riceve la notifica dei nuovi brani" a pochi gruppi nella scheda Gruppi autorizzati.',
	'MS_FIX_NOTIF_ROWS'		=> 'La tabella delle notifiche e\' molto popolata e viene interrogata a ogni pagina del forum. Ci sono %1$d notifiche gia\' lette che puoi rimuovere subito con il pulsante qui sotto.',
	'MS_FIX_CLEANUP'		=> 'La pulizia automatica e\' disattivata: le notifiche lette si accumuleranno senza limite. Attivala in Impostazioni.',
	'MS_FIX_RECO_GROUPS'	=> 'Il riconoscimento e\' configurato ma nessun gruppo e\' sottoposto al controllo, quindi non viene mai eseguito. Seleziona i gruppi nella scheda Riconoscimento brani.',
	'MS_FIX_TOAST'			=> 'Un intervallo inferiore a 30 secondi genera molte richieste al server da ogni scheda aperta. Portalo ad almeno 30 secondi in Impostazioni.',
	'MS_FIX_APPROVAL'		=> 'I brani caricati diventano subito visibili a tutti. Vista la natura dei contenuti musicali, valuta di attivare l\'approvazione preventiva in Impostazioni.',
	'MS_FIX_PENDING'		=> 'Ci sono brani in attesa da approvare: gli autori non possono ancora condividerli. Esaminali nella scheda Moderazione.',

	'MS_PURGE'				=> 'Azzera tutte le notifiche',
	'MS_PURGE_EXPLAIN'		=> 'Rimuove TUTTE le notifiche dell\'estensione, comprese quelle che gli utenti non hanno ancora letto: quegli avvisi spariranno senza essere mai stati visti. Non tocca i brani, le playlist o i voti, solo le notifiche. Utile per ripartire da zero dopo aver ristretto i destinatari.',
	'MS_PURGE_CONFIRM'		=> 'Stai per rimuovere %d notifiche dell\'estensione, comprese quelle non ancora lette dagli utenti. I brani e i dati non vengono toccati, ma gli avvisi non letti andranno persi. Vuoi procedere?',
	'MS_PURGE_DONE'			=> 'Rimosse %d notifiche.',
	'MS_NOTIF_TOTAL_LABEL'	=> 'Notifiche dell\'estensione presenti in tutto',

	'MS_FIX_NOTIF_ROWS_UNREAD'	=> 'Ci sono %1$d notifiche, quasi tutte ancora da leggere: la pulizia delle sole notifiche lette non ne rimuoverebbe nessuna. Sono il risultato di un invio a troppi destinatari. Restringi prima il permesso "Riceve la notifica dei nuovi brani" nella scheda Gruppi autorizzati, poi usa "Azzera tutte le notifiche" qui sotto per ripartire da zero.',
]);
