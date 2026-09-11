<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Assegna a ogni pagina del forum le variabili di template necessarie
 * al player globale e al link di navigazione verso Music Share
 * (il link vero e proprio viene iniettato tramite il template event
 * styles/all/template/event/navbar_header_notifications_after.html,
 * il player tramite styles/all/template/event/page_footer_after.html).
 */
class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $auth;
	protected $helper;
	protected $config;
	protected $config_text;
	protected $song_repository;
	protected $cache;
	protected $root_path;
	protected $php_ext;

	public static function getSubscribedEvents()
	{
		return array(
			'core.page_header'	=> 'add_page_vars',
			'core.user_setup'	=> 'load_language',
			'core.text_formatter_s9e_configure_after'	=> 'add_bbcode',
			'core.permissions'	=> 'add_permissions',
			'core.memberlist_view_profile'		=> 'show_profile_stats',
			'core.viewtopic_post_rowset_data'	=> 'collect_post_authors',
			'core.viewtopic_modify_post_row'	=> 'show_post_song_count',
			'core.index_modify_page_title'		=> 'show_index_feed',
		);
	}

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\controller\helper $helper,
		\phpbb\config\config $config,
		\phpbb\config\db_text $config_text,
		\salvocortesiano\musicshare\repository\song_repository $song_repository,
		\phpbb\cache\driver\driver_interface $cache,
		$root_path,
		$php_ext
	)
	{
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->config = $config;
		$this->config_text = $config_text;
		$this->song_repository = $song_repository;
		$this->cache = $cache;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Registra la categoria e i permessi dell'estensione, così da
	 * renderli visibili e assegnabili in ACP -> Permessi.
	 *
	 * @param \phpbb\event\data $event
	 */
	/** @var bool evita di preparare il riquadro due volte nella stessa pagina */
	protected $feed_assigned = false;

	/** @var array elenco degli autori dei messaggi della pagina */
	protected $post_authors = array();

	/** @var array conteggi già calcolati, user_id => brani */
	protected $song_counts = null;

	/**
	 * Registra il BBCode [musicshare]12[/musicshare] per inserire un
	 * brano nei
	 * messaggi.
	 *
	 * Il BBCode produce soltanto un segnaposto con l'identificativo: il
	 * lettore vero viene costruito dal JavaScript, che chiede al server i
	 * dati del brano. Così i permessi vengono verificati al momento della
	 * visualizzazione e non restano incisi nel messaggio: se un brano
	 * viene rimosso o reso privato, il messaggio non mostra piu' nulla di
	 * riproducibile.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function add_bbcode($event)
	{
		if (isset($this->config['musicshare_bbcode']) && !$this->config['musicshare_bbcode'])
		{
			return;
		}

		$configurator = $event['configurator'];

		if (isset($configurator->BBCodes['MUSICSHARE']))
		{
			return;
		}

		try
		{
			$configurator->BBCodes->addCustom(
				'[musicshare]{UINT}[/musicshare]',
				'<span class="musicshare-embed" data-song-id="{UINT}"></span>'
			);
		}
		catch (\Exception $e)
		{
			// un BBCode già definito altrove non deve impedire il
			// caricamento della pagina
		}

		$event['configurator'] = $configurator;
	}

	/**
	 * Carica il file di lingua dell'estensione all'avvio di ogni pagina.
	 *
	 * Serve alle pagine che non passano dal nostro codice ma mostrano
	 * comunque nostre stringhe: per esempio "Gestisci notifiche" nel
	 * pannello utente, che elenca i tipi di notifica registrati e ne
	 * cerca le etichette senza sapere da quale estensione provengano.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function load_language($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name'	=> 'salvocortesiano/musicshare',
			'lang_set'	=> 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Statistiche musicali nella scheda profilo dell'utente.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function show_profile_stats($event)
	{
		if (!$this->stats_enabled())
		{
			return;
		}

		// questo evento scatta prima di core.page_header: senza questa
		// riga le stringhe usate qui sotto resterebbero non tradotte
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		$data = $event['member'];
		$user_id = (int) $data['user_id'];

		$stats = $this->song_repository->get_user_stats($user_id);

		$this->template->assign_vars(array(
			'MUSICSHARE_PROFILE_SONGS'		=> $stats['songs'],
			'MUSICSHARE_PROFILE_PLAYS'		=> $stats['plays'],
			'MUSICSHARE_PROFILE_DURATION'	=> $this->format_duration($stats['duration']),
			'U_MUSICSHARE_PROFILE_SONGS'	=> $this->helper->route(
				'salvocortesiano_musicshare_user',
				array('user_id' => $user_id)
			),
			// nel profilo la voce compare sempre, anche a zero: altrimenti
			// sembra che la funzione non stia funzionando
			'S_MUSICSHARE_PROFILE'			=> true,
			'S_MUSICSHARE_PROFILE_HAS_SONGS'	=> $stats['songs'] > 0,
		));
	}

	/**
	 * Raccoglie gli autori dei messaggi mostrati, per poi contare i loro
	 * brani con una sola query invece di una per messaggio.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function collect_post_authors($event)
	{
		if (!$this->stats_enabled())
		{
			return;
		}

		// l'evento fornisce i dati di un singolo messaggio, non l'intero
		// elenco: si prende l'autore da 'row', che è la riga del database
		$row = $event['row'];

		if (!empty($row['poster_id']))
		{
			$this->post_authors[(int) $row['poster_id']] = true;
		}
		else if (!empty($row['user_id']))
		{
			$this->post_authors[(int) $row['user_id']] = true;
		}
	}

	/**
	 * Numero di brani caricati, mostrato sotto il profilo nei messaggi.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function show_post_song_count($event)
	{
		if (!$this->stats_enabled())
		{
			return;
		}

		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		$user_id = (int) $event['poster_id'];

		if ($user_id <= 0)
		{
			$row = $event['row'];
			$user_id = isset($row['poster_id']) ? (int) $row['poster_id'] : 0;
		}

		if ($user_id <= 0 || $user_id === ANONYMOUS)
		{
			return;
		}

		if ($this->song_counts === null)
		{
			$ids = array_keys($this->post_authors);
			$ids[] = $user_id;
			$this->song_counts = $this->song_repository->count_songs_for_users($ids);
		}

		$count = isset($this->song_counts[$user_id]) ? $this->song_counts[$user_id] : 0;

		if ($count <= 0)
		{
			return;
		}

		$post_row = $event['post_row'];
		$post_row['MUSICSHARE_SONG_COUNT'] = $count;
		$post_row['U_MUSICSHARE_USER_SONGS'] = $this->helper->route(
			'salvocortesiano_musicshare_user',
			array('user_id' => $user_id)
		);
		$event['post_row'] = $post_row;
	}

	/**
	 * Riquadro con gli ultimi brani caricati, mostrato nell'indice.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function show_index_feed($event)
	{
		if ($this->feed_mode() !== 1)
		{
			return;
		}

		$this->assign_feed();
	}

	/**
	 * Prepara le variabili del riquadro. Il risultato viene tenuto in
	 * cache per un minuto: senza, il riquadro su tutte le pagine
	 * significherebbe una interrogazione in più a ogni caricamento.
	 *
	 * @return void
	 */
	protected function assign_feed()
	{
		if ($this->feed_assigned)
		{
			return;
		}

		// chi non ha il permesso non vede il riquadro
		if (!$this->auth->acl_get('u_musicshare_feed'))
		{
			return;
		}

		$this->feed_assigned = true;
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		// 0 = mostra tutti i brani, nessun tetto
		$total = isset($this->config['musicshare_index_feed_count'])
			? (int) $this->config['musicshare_index_feed_count']
			: 20;
		$per_user = (int) $this->config['musicshare_index_feed_per_user'];

		$cache_key = self::feed_cache_key_from_config($this->config);
		$songs = $this->cache->get($cache_key);

		if ($songs === false)
		{
			$songs = $this->song_repository->get_recent_songs_capped($total, $per_user);
			$this->cache->put($cache_key, $songs, 60);
		}

		$song_ids = array_map(function ($s) { return (int) $s['song_id']; }, $songs);
		$my_votes = $this->song_repository->get_user_votes($song_ids, (int) $this->user->data['user_id']);
		$votes_on = !empty($this->config['musicshare_votes_enabled']);
		$downloads_on = !empty($this->config['musicshare_allow_download']);
		$descriptions_on = !isset($this->config['musicshare_descriptions'])
			|| (bool) $this->config['musicshare_descriptions'];

		foreach ($songs as $song)
		{
			$this->template->assign_block_vars('musicshare_feed', array(
				'SONG_ID'		=> (int) $song['song_id'],
				'TITLE'			=> $song['song_title'],
				'ARTIST'		=> $song['song_artist'],
				'ALBUM'			=> $song['song_album'],
				'YEAR'			=> $song['song_year'] ? (int) $song['song_year'] : '',
				'DURATION'		=> gmdate('i:s', (int) $song['song_duration']),
				'PLAY_COUNT'		=> (int) $song['play_count'],
				'DESCRIPTION'		=> ($descriptions_on && isset($song['song_description']))
					? (string) $song['song_description'] : '',
				'PLAY_COUNT_TEXT'	=> $this->user->lang('MUSICSHARE_PLAYS_COUNT', (int) $song['play_count']),
				'UPLOADER'		=> get_username_string('full', (int) $song['user_id'], $song['username'], $song['user_colour']),
				'UPLOAD_DATE'	=> $this->user->format_date((int) $song['upload_time']),
				'U_STREAM'		=> $this->helper->route('salvocortesiano_musicshare_stream', array('song_id' => $song['song_id'])),
				'U_COVER'		=> !empty($song['cover_path'])
					? $this->helper->route('salvocortesiano_musicshare_cover', array('song_id' => $song['song_id']))
					: '',
				'S_HAS_COVER'	=> !empty($song['cover_path']),
				'LIKES'			=> isset($song['song_likes']) ? (int) $song['song_likes'] : 0,
				'DISLIKES'		=> isset($song['song_dislikes']) ? (int) $song['song_dislikes'] : 0,
				'MY_VOTE'		=> isset($my_votes[(int) $song['song_id']]) ? (int) $my_votes[(int) $song['song_id']] : 0,
				'S_CAN_VOTE'	=> ($votes_on
					&& (int) $this->user->data['user_id'] !== ANONYMOUS
					&& (int) $song['user_id'] !== (int) $this->user->data['user_id']),
				'S_CAN_DOWNLOAD'	=> ($downloads_on
					&& (!isset($song['allow_download']) || $song['allow_download'])),
				'U_DOWNLOAD'	=> $this->helper->route('salvocortesiano_musicshare_download', array('song_id' => $song['song_id'])),
			));
		}

		$this->template->assign_vars(array(
			'S_MUSICSHARE_FEED'		=> !empty($songs),
			'MUSICSHARE_FEED_TITLE'	=> $this->feed_title(),
			// soglia impostabile da ACP; 0 = il riquadro non scorre mai
			'S_MUSICSHARE_FEED_SCROLL'	=> $this->feed_should_scroll(count($songs)),
		));
	}

	/**
	 * Durata complessiva in ore e minuti, o in minuti se è poca.
	 *
	 * @param int $seconds
	 * @return string
	 */
	protected function format_duration($seconds)
	{
		$seconds = (int) $seconds;
		$hours = (int) floor($seconds / 3600);
		$minutes = (int) floor(($seconds % 3600) / 60);

		if ($hours > 0)
		{
			return $this->user->lang('MUSICSHARE_DURATION_HM', $hours, $minutes);
		}

		return $this->user->lang('MUSICSHARE_DURATION_M', $minutes);
	}

	public function add_permissions($event)
	{
		$categories = $event['categories'];
		$categories['musicshare'] = 'ACL_CAT_MUSICSHARE';
		$event['categories'] = $categories;

		$permissions = $event['permissions'];
		$permissions['u_musicshare_upload'] = array('lang' => 'ACL_U_MUSICSHARE_UPLOAD', 'cat' => 'musicshare');
		$permissions['u_musicshare_playlist'] = array('lang' => 'ACL_U_MUSICSHARE_PLAYLIST', 'cat' => 'musicshare');
		$permissions['m_musicshare_manage'] = array('lang' => 'ACL_M_MUSICSHARE_MANAGE', 'cat' => 'musicshare');
		$permissions['u_musicshare_view'] = array('lang' => 'ACL_U_MUSICSHARE_VIEW', 'cat' => 'musicshare');
		$permissions['u_musicshare_feed'] = array('lang' => 'ACL_U_MUSICSHARE_FEED', 'cat' => 'musicshare');
		$permissions['u_musicshare_notify'] = array('lang' => 'ACL_U_MUSICSHARE_NOTIFY', 'cat' => 'musicshare');
		$event['permissions'] = $permissions;
	}

	/**
	 * Il riquadro deve diventare scorrevole?
	 *
	 * @param int $count brani effettivamente mostrati
	 * @return bool
	 */
	protected function feed_should_scroll($count)
	{
		$after = isset($this->config['musicshare_feed_scroll_after'])
			? (int) $this->config['musicshare_feed_scroll_after']
			: 20;

		// 0 = mai: il riquadro si allunga quanto serve
		if ($after <= 0)
		{
			return false;
		}

		return ($count > $after);
	}

	/**
	 * Titolo del riquadro: quello impostato in ACP per la lingua
	 * dell'utente, altrimenti la traduzione predefinita.
	 *
	 * Il testo viene protetto qui perché phpBB non applica l'escape
	 * automatico ai valori passati ai template: senza questo passaggio un
	 * titolo contenente marcatura finirebbe tale e quale nella pagina.
	 *
	 * @return string
	 */
	protected function feed_title()
	{
		$default = $this->user->lang('MUSICSHARE_RECENT_SONGS');
		$raw = (string) $this->config_text->get('musicshare_feed_title');

		if ($raw === '')
		{
			return $default;
		}

		$titles = json_decode($raw, true);

		if (!is_array($titles))
		{
			return $default;
		}

		$iso = isset($this->user->lang_name) ? (string) $this->user->lang_name : '';

		if ($iso !== '' && !empty($titles[$iso]))
		{
			return htmlspecialchars((string) $titles[$iso], ENT_QUOTES, 'UTF-8');
		}

		// nessun testo per questa lingua: si prova quella predefinita del forum
		$board = isset($this->config['default_lang']) ? (string) $this->config['default_lang'] : '';

		if ($board !== '' && !empty($titles[$board]))
		{
			return htmlspecialchars((string) $titles[$board], ENT_QUOTES, 'UTF-8');
		}

		return $default;
	}

	/**
	 * Stringhe passate al JavaScript, già pronte come oggetto JSON.
	 *
	 * Prima venivano scritte a mano nel template fra apici singoli: una
	 * sola stringa contenente un apostrofo bastava a rompere l'intero
	 * blocco di configurazione, e con esso il menù delle playlist e i
	 * voti. Con json_encode l'escape è garantito per costruzione.
	 *
	 * @return string
	 */
	protected function js_lang()
	{
		$chiavi = array(
			'noticeTitle'			=> 'MUSICSHARE_NOTICE',
			'newPlaylistNameEmpty'	=> 'MUSICSHARE_PLAYLIST_NAME_EMPTY',
			'addedToPlaylist'		=> 'MUSICSHARE_ADDED_TO_PLAYLIST',
			'alreadyInPlaylist'		=> 'MUSICSHARE_ALREADY_IN_PLAYLIST',
			'addError'				=> 'MUSICSHARE_ADD_ERROR',
			'queueEmpty'			=> 'MUSICSHARE_QUEUE_EMPTY',
			'uploadStarting'		=> 'MUSICSHARE_UPLOAD_STARTING',
			'uploadProcessing'		=> 'MUSICSHARE_UPLOAD_PROCESSING',
			'uploadFailed'			=> 'MUSICSHARE_UPLOAD_FAILED',
			'resumeHint'			=> 'MUSICSHARE_RESUME_HINT',
			'repeatOff'				=> 'MUSICSHARE_REPEAT_OFF',
			'repeatAll'				=> 'MUSICSHARE_REPEAT_ALL',
			'repeatOne'				=> 'MUSICSHARE_REPEAT_ONE',
			'newUpload'				=> 'MUSICSHARE_NEW_UPLOAD',
			'play'					=> 'MUSICSHARE_PLAY',
			'pause'					=> 'MUSICSHARE_PAUSE',
			'like'					=> 'MUSICSHARE_LIKE',
			'dislike'				=> 'MUSICSHARE_DISLIKE',
			'removeFromQueue'		=> 'MUSICSHARE_REMOVE_FROM_QUEUE',
			'recoWarningTitle'		=> 'MUSICSHARE_RECO_MATCH_TITLE',
			'recoWarning'			=> 'MUSICSHARE_RECO_MATCH_TEXT',
			'stop'					=> 'MUSICSHARE_STOP',
			'seek'					=> 'MUSICSHARE_SEEK',
			'embedLoading'			=> 'MUSICSHARE_EMBED_LOADING',
			'embedMissing'			=> 'MUSICSHARE_EMBED_MISSING',
			'embedPending'			=> 'MUSICSHARE_EMBED_PENDING',
			'bbcodeCopied'			=> 'MUSICSHARE_BBCODE_COPIED',
			'bbcodeManual'			=> 'MUSICSHARE_BBCODE_MANUAL',
		);

		$out = array();

		foreach ($chiavi as $nome_js => $chiave)
		{
			$out[$nome_js] = $this->user->lang($chiave);
		}

		// JSON_HEX_TAG impedisce che un eventuale </script> nel testo
		// chiuda il blocco di script
		return json_encode($out, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Chiave della cache del riquadro. Definita qui una volta sola perché
	 * anche il voto deve poterla invalidare: i dati in cache contengono i
	 * contatori dei voti, che altrimenti resterebbero vecchi fino alla
	 * scadenza.
	 *
	 * @param int $total
	 * @param int $per_user
	 * @return string
	 */
	public static function feed_cache_key($total, $per_user)
	{
		return '_musicshare_feed_' . (int) $total . '_' . (int) $per_user;
	}

	/**
	 * Ricava la chiave della cache dalla configurazione. Usata anche dal
	 * voto e dall'ascolto per invalidarla: calcolandola in un solo posto
	 * non può capitare che i tre punti usino parametri diversi e che
	 * l'invalidazione manchi il bersaglio.
	 *
	 * @param \phpbb\config\config $config
	 * @return string
	 */
	public static function feed_cache_key_from_config($config)
	{
		$total = isset($config['musicshare_index_feed_count'])
			? (int) $config['musicshare_index_feed_count']
			: 20;

		return self::feed_cache_key($total, (int) $config['musicshare_index_feed_per_user']);
	}

	/**
	 * Le funzioni aggiunte dopo la prima installazione devono restare
	 * attive anche prima che la relativa migrazione sia stata eseguita:
	 * senza questo controllo resterebbero spente in silenzio.
	 *
	 * @return bool
	 */
	protected function stats_enabled()
	{
		return !isset($this->config['musicshare_show_stats'])
			|| (bool) $this->config['musicshare_show_stats'];
	}

	/**
	 * @return int 0 = nessun riquadro, 1 = solo indice, 2 = tutte le pagine
	 */
	protected function feed_mode()
	{
		if (!isset($this->config['musicshare_index_feed']))
		{
			return 1;
		}

		return (int) $this->config['musicshare_index_feed'];
	}

	/**
	 * Restituisce un marcatore basato sulla data di modifica dei file
	 * statici: cambia da solo a ogni aggiornamento dell'estensione e
	 * costringe il browser a riscaricarli.
	 *
	 * @return string
	 */
	protected function get_asset_version()
	{
		static $version = null;

		if ($version !== null)
		{
			return $version;
		}

		$base = $this->root_path . 'ext/salvocortesiano/musicshare/styles/all/';
		$files = [
			$base . 'theme/musicshare.css',
			$base . 'template/musicshare/musicshare.js',
			$base . 'template/musicshare/musicshare-upload.js',
			$base . 'template/musicshare/musicshare-toast.js',
		];

		$latest = 0;

		foreach ($files as $file)
		{
			$time = @filemtime($file);

			if ($time !== false && $time > $latest)
			{
				$latest = $time;
			}
		}

		$version = ($latest > 0) ? (string) $latest : '1';

		return $version;
	}

	public function add_page_vars()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		if ($this->feed_mode() === 2)
		{
			$this->assign_feed();
		}

		$can_view = (bool) $this->auth->acl_get('u_musicshare_view');

		$this->template->assign_vars(array(
			// senza permesso di accesso non si mostra nemmeno il
			// collegamento: la variabile è la condizione usata dai template
			'U_MUSICSHARE_BROWSE'			=> $can_view ? $this->helper->route('salvocortesiano_musicshare_browse') : '',
			'U_MUSICSHARE_UPLOADERS'		=> $can_view ? $this->helper->route('salvocortesiano_musicshare_uploaders') : '',
			// il caricamento si fa dalla scheda dell'UCP, non dalla vecchia
			// pagina pubblica, che ora vi reindirizza
			'U_MUSICSHARE_UPLOAD'			=> append_sid(
				$this->root_path . 'ucp.' . $this->php_ext,
				'i=-salvocortesiano-musicshare-ucp-main_module&amp;mode=upload'
			),
			'U_MUSICSHARE_SEARCH'			=> $this->helper->route('salvocortesiano_musicshare_search'),
			'U_MUSICSHARE_AJAX_PLAYLIST'	=> $this->helper->route('salvocortesiano_musicshare_ajax_add_to_playlist'),
			'U_MUSICSHARE_AJAX_UPLOAD'		=> $this->helper->route('salvocortesiano_musicshare_ajax_upload'),
			'U_MUSICSHARE_AJAX_RECENT'		=> $this->helper->route('salvocortesiano_musicshare_ajax_recent'),
			'U_MUSICSHARE_AJAX_VOTE'		=> $this->helper->route('salvocortesiano_musicshare_ajax_vote'),
			'U_MUSICSHARE_AJAX_EMBED'		=> $this->helper->route('salvocortesiano_musicshare_ajax_embed'),
			'S_MUSICSHARE_TOAST_SOUND'		=> !isset($this->config['musicshare_toast_sound'])
				|| (bool) $this->config['musicshare_toast_sound'],
			'MUSICSHARE_TOAST_VOLUME'		=> isset($this->config['musicshare_toast_volume'])
				? (int) $this->config['musicshare_toast_volume'] : 30,
			'MUSICSHARE_JS_LANG'			=> $this->js_lang(),
			'S_MUSICSHARE_TOAST'			=> !empty($this->config['musicshare_toast_enabled']),
			'MUSICSHARE_TOAST_INTERVAL'	=> (int) $this->config['musicshare_toast_interval'] ?: 90,
			'U_MUSICSHARE_AJAX_PLAYLISTS'	=> $this->helper->route('salvocortesiano_musicshare_ajax_playlists'),
			'U_MUSICSHARE_AJAX_CREATE'		=> $this->helper->route('salvocortesiano_musicshare_ajax_create_playlist'),
			'S_MUSICSHARE_CAN_UPLOAD'		=> (bool) $this->auth->acl_get('u_musicshare_upload'),
			'S_MUSICSHARE_CAN_PLAYLIST'		=> (bool) $this->auth->acl_get('u_musicshare_playlist'),
			// se la chiave non esiste ancora (migrazione non eseguita) la
			// funzione resta attiva, altrimenti sarebbe disattivata in silenzio
			'S_MUSICSHARE_PERSIST_PLAYER'	=> !isset($this->config['musicshare_persist_player']) || (bool) $this->config['musicshare_persist_player'],
			'MUSICSHARE_PLAYER_SCOPE'		=> isset($this->config['musicshare_player_scope'])
				? (string) $this->config['musicshare_player_scope']
				: 'music',
			'S_MUSICSHARE_LOGGED_IN'		=> (int) $this->user->data['user_id'] !== ANONYMOUS,
			'MUSICSHARE_AJAX_HASH'			=> generate_link_hash('musicshare_ajax'),
			'MUSICSHARE_THEME_PATH'		=> generate_board_url() . '/ext/salvocortesiano/musicshare/styles/all/theme/',
			'MUSICSHARE_ASSET_PATH'		=> generate_board_url() . '/ext/salvocortesiano/musicshare/styles/all/template/musicshare/',
			// Marcatore di versione degli asset: senza di esso il browser
			// continua a servire dalla propria cache il CSS e il JavaScript
			// vecchi anche dopo l'aggiornamento dell'estensione.
			'MUSICSHARE_ASSET_VER'		=> $this->get_asset_version(),
		));
	}
}
