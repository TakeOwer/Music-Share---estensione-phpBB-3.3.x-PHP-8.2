<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\service;

/**
 * Verifiche sullo stato dell'estensione.
 *
 * Ogni controllo restituisce un esito e, quando qualcosa non va, il
 * suggerimento su come rimediare: un elenco di problemi senza indicazioni
 * su cosa fare serve a poco.
 */
class diagnostics
{
	/** Esiti possibili di un controllo. */
	const OK = 'ok';
	const AVVISO = 'avviso';
	const ERRORE = 'errore';

	protected $db;
	protected $config;
	protected $config_text;
	protected $auth;
	protected $container;
	protected $storage_helper;
	protected $root_path;
	protected $table_prefix;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\config\db_text $config_text,
		\phpbb\auth\auth $auth,
		\Symfony\Component\DependencyInjection\ContainerInterface $container,
		storage_helper $storage_helper,
		$root_path,
		$table_prefix
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->config_text = $config_text;
		$this->auth = $auth;
		$this->container = $container;
		$this->storage_helper = $storage_helper;
		$this->root_path = $root_path;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Costruisce un singolo controllo.
	 *
	 * @param string $etichetta chiave di lingua del nome del controllo
	 * @param string $valore    esito leggibile
	 * @param string $stato     OK | AVVISO | ERRORE
	 * @param string $rimedio   chiave di lingua del suggerimento
	 * @param array $param      parametri per la chiave del suggerimento
	 * @return array
	 */
	protected function c($etichetta, $valore, $stato = self::OK, $rimedio = '', array $param = array())
	{
		return array(
			'etichetta'	=> $etichetta,
			'valore'	=> (string) $valore,
			'stato'		=> $stato,
			'rimedio'	=> $rimedio,
			'param'		=> $param,
		);
	}

	/**
	 * Converte una dimensione dal formato di php.ini in byte.
	 *
	 * @param string $valore
	 * @return int
	 */
	protected function to_bytes($valore)
	{
		$valore = trim((string) $valore);

		if ($valore === '')
		{
			return 0;
		}

		$unita = strtolower(substr($valore, -1));
		$numero = (int) $valore;

		switch ($unita)
		{
			case 'g': $numero *= 1024;
			case 'm': $numero *= 1024;
			case 'k': $numero *= 1024;
		}

		return $numero;
	}

	/* ------------------------------------------------------------- */

	/**
	 * Ambiente del server.
	 *
	 * @return array
	 */
	public function check_environment()
	{
		$out = array();

		$out[] = $this->c('MS_CHK_PHP', PHP_VERSION,
			version_compare(PHP_VERSION, '7.2', '>=') ? self::OK : self::ERRORE,
			'MS_FIX_PHP');

		$met = (int) @ini_get('max_execution_time');
		$out[] = $this->c('MS_CHK_EXECTIME',
			$met === 0 ? 'illimitato' : $met . ' s',
			($met === 0 || $met >= 30) ? self::OK : self::AVVISO,
			'MS_FIX_EXECTIME');

		$out[] = $this->c('MS_CHK_MEMORY', (string) @ini_get('memory_limit'),
			$this->to_bytes(@ini_get('memory_limit')) >= 64 * 1048576 ? self::OK : self::AVVISO,
			'MS_FIX_MEMORY');

		// il limite dell'estensione non può superare quello di PHP
		$php_upload = $this->to_bytes(@ini_get('upload_max_filesize'));
		$php_post = $this->to_bytes(@ini_get('post_max_size'));
		$nostro = (int) $this->config['musicshare_max_filesize'];

		$out[] = $this->c('MS_CHK_UPLOADSIZE',
			@ini_get('upload_max_filesize') . ' (estensione: ' . round($nostro / 1048576, 1) . ' MB)',
			($nostro <= $php_upload) ? self::OK : self::ERRORE,
			'MS_FIX_UPLOADSIZE');

		$out[] = $this->c('MS_CHK_POSTSIZE', (string) @ini_get('post_max_size'),
			($php_post >= $php_upload) ? self::OK : self::AVVISO,
			'MS_FIX_POSTSIZE');

		$out[] = $this->c('MS_CHK_CURL',
			function_exists('curl_init') ? 'disponibile' : 'assente',
			function_exists('curl_init') ? self::OK : self::AVVISO,
			'MS_FIX_CURL');

		$out[] = $this->c('MS_CHK_HMAC',
			function_exists('hash_hmac') ? 'disponibile' : 'assente',
			function_exists('hash_hmac') ? self::OK : self::AVVISO,
			'MS_FIX_HMAC');

		$getid3 = $this->root_path . 'ext/salvocortesiano/musicshare/vendor/getid3/getid3.php';
		$out[] = $this->c('MS_CHK_GETID3',
			file_exists($getid3) ? 'presente' : 'MANCANTE',
			file_exists($getid3) ? self::OK : self::ERRORE,
			'MS_FIX_GETID3');

		return $out;
	}

	/**
	 * Cartella di archiviazione.
	 *
	 * @return array
	 */
	public function check_storage()
	{
		$out = array();
		$percorso = $this->storage_helper->get_storage_path();

		$out[] = $this->c('MS_CHK_PATH', $percorso);

		$esiste = is_dir($percorso);
		$out[] = $this->c('MS_CHK_PATH_EXISTS',
			$esiste ? 'esiste' : 'NON esiste',
			$esiste ? self::OK : self::ERRORE,
			'MS_FIX_PATH_EXISTS');

		$scrivibile = $esiste && is_writable($percorso);
		$out[] = $this->c('MS_CHK_WRITABLE',
			$scrivibile ? 'scrivibile' : 'NON scrivibile',
			$scrivibile ? self::OK : self::ERRORE,
			'MS_FIX_WRITABLE');

		$esposta = $esiste && !file_exists(rtrim($percorso, '/') . '/.htaccess');
		$out[] = $this->c('MS_CHK_PROTECTED',
			$esposta ? 'nessun .htaccess' : 'protetta da .htaccess',
			$esposta ? self::AVVISO : self::OK,
			'MS_FIX_PROTECTED');

		if ($esiste)
		{
			$libero = @disk_free_space($percorso);

			if ($libero !== false)
			{
				$out[] = $this->c('MS_CHK_DISKFREE',
					round($libero / 1048576) . ' MB',
					$libero > 100 * 1048576 ? self::OK : self::AVVISO,
					'MS_FIX_DISKFREE');
			}
		}

		// spazio occupato secondo il database
		$sql = 'SELECT COUNT(*) AS quanti, SUM(file_size) AS totale
			FROM ' . $this->table_prefix . 'musicshare_songs';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$out[] = $this->c('MS_CHK_SONGS',
			(int) $row['quanti'] . ' brani, ' . round(((float) $row['totale']) / 1048576, 1) . ' MB');

		return $out;
	}

	/**
	 * Struttura del database.
	 *
	 * @return array
	 */
	public function check_database()
	{
		$out = array();
		$tools = new \phpbb\db\tools\tools($this->db);

		$tabelle = array(
			'musicshare_songs', 'musicshare_genres', 'musicshare_song_genre',
			'musicshare_playlists', 'musicshare_playlist_songs', 'musicshare_votes',
		);

		$mancanti = array();

		foreach ($tabelle as $t)
		{
			if (!$tools->sql_table_exists($this->table_prefix . $t))
			{
				$mancanti[] = $t;
			}
		}

		$out[] = $this->c('MS_CHK_TABLES',
			$mancanti ? 'mancanti: ' . implode(', ', $mancanti) : count($tabelle) . ' tabelle presenti',
			$mancanti ? self::ERRORE : self::OK,
			'MS_FIX_TABLES');

		$colonne = array(
			'song_approved', 'file_hash', 'allow_download', 'song_likes', 'song_dislikes',
			'recognized', 'recognized_info', 'song_description',
		);

		$col_mancanti = array();

		foreach ($colonne as $col)
		{
			if (!$tools->sql_column_exists($this->table_prefix . 'musicshare_songs', $col))
			{
				$col_mancanti[] = $col;
			}
		}

		$out[] = $this->c('MS_CHK_COLUMNS',
			$col_mancanti ? 'mancanti: ' . implode(', ', $col_mancanti) : count($colonne) . ' colonne presenti',
			$col_mancanti ? self::ERRORE : self::OK,
			'MS_FIX_COLUMNS');

		$sql = 'SELECT COUNT(*) AS quante FROM ' . $this->table_prefix . 'migrations
			WHERE migration_name ' . $this->db->sql_like_expression(
				$this->db->get_any_char() . 'musicshare' . $this->db->get_any_char());
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$out[] = $this->c('MS_CHK_MIGRATIONS', (int) $row['quante'] . ' applicate');

		return $out;
	}

	/**
	 * Elenco dei file su disco che nessun brano rivendica.
	 *
	 * Sono di solito residui di caricamenti interrotti o di brani
	 * eliminati quando il file non era piu' raggiungibile. Occupano
	 * spazio e basta.
	 *
	 * @param int $max quanti percorsi restituire al massimo
	 * @return array percorsi assoluti
	 */
	public function find_orphan_files($max = 500)
	{
		$base = $this->storage_helper->get_storage_path();
		$attesi = array();

		$sql = 'SELECT file_path, cover_path FROM ' . $this->table_prefix . 'musicshare_songs';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ((string) $row['file_path'] !== '')
			{
				$attesi[basename((string) $row['file_path'])] = true;
			}

			if ((string) $row['cover_path'] !== '')
			{
				$attesi[basename((string) $row['cover_path'])] = true;
			}
		}
		$this->db->sql_freeresult($result);

		$orfani = array();

		if (!is_dir($base))
		{
			return $orfani;
		}

		foreach ((array) @scandir($base) as $cartella)
		{
			if ($cartella === '.' || $cartella === '..' || !is_dir($base . $cartella))
			{
				continue;
			}

			foreach (array($base . $cartella . '/', $base . $cartella . '/covers/') as $dir)
			{
				if (!is_dir($dir))
				{
					continue;
				}

				foreach ((array) @scandir($dir) as $file)
				{
					if ($file === '.' || $file === '..' || $file === '.htaccess'
						|| $file === 'index.html' || is_dir($dir . $file))
					{
						continue;
					}

					if (!isset($attesi[$file]))
					{
						$orfani[] = $dir . $file;

						if (count($orfani) >= $max)
						{
							return $orfani;
						}
					}
				}
			}
		}

		return $orfani;
	}

	/**
	 * Rimuove i file orfani.
	 *
	 * @param int $max
	 * @return array rimossi, falliti
	 */
	public function delete_orphan_files($max = 500)
	{
		$rimossi = 0;
		$falliti = 0;

		foreach ($this->find_orphan_files($max) as $percorso)
		{
			if (@unlink($percorso))
			{
				$rimossi++;
			}
			else
			{
				$falliti++;
			}
		}

		return array('rimossi' => $rimossi, 'falliti' => $falliti);
	}

	/**
	 * Coerenza fra database e file su disco.
	 *
	 * @return array
	 */
	public function check_integrity()
	{
		$out = array();
		$base = $this->storage_helper->get_storage_path();

		// brani il cui file non esiste piu'
		$sql = 'SELECT song_id, song_title, file_path, cover_path
			FROM ' . $this->table_prefix . 'musicshare_songs';
		$result = $this->db->sql_query($sql);

		$senza_file = array();
		$senza_copertina = array();
		$attesi = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$percorso = $base . ltrim((string) $row['file_path'], '/');
			$attesi[basename($percorso)] = true;

			if (!is_file($percorso))
			{
				$senza_file[] = $row['song_title'] . ' (' . (int) $row['song_id'] . ')';
			}

			if ((string) $row['cover_path'] !== '')
			{
				$cop = $base . ltrim((string) $row['cover_path'], '/');
				$attesi[basename($cop)] = true;

				if (!is_file($cop))
				{
					$senza_copertina[] = $row['song_title'] . ' (' . (int) $row['song_id'] . ')';
				}
			}
		}
		$this->db->sql_freeresult($result);

		$out[] = $this->c('MS_CHK_MISSING_FILES',
			$senza_file ? count($senza_file) . ': ' . implode(', ', array_slice($senza_file, 0, 5)) : 'nessuno',
			$senza_file ? self::ERRORE : self::OK,
			'MS_FIX_MISSING_FILES');

		$out[] = $this->c('MS_CHK_MISSING_COVERS',
			$senza_copertina ? count($senza_copertina) . ': ' . implode(', ', array_slice($senza_copertina, 0, 5)) : 'nessuna',
			$senza_copertina ? self::AVVISO : self::OK,
			'MS_FIX_MISSING_COVERS');

		// file presenti su disco che nessun brano rivendica
		$orfani = 0;

		if (is_dir($base))
		{
			foreach ((array) @scandir($base) as $cartella)
			{
				if ($cartella === '.' || $cartella === '..' || !is_dir($base . $cartella))
				{
					continue;
				}

				foreach (array($base . $cartella . '/', $base . $cartella . '/covers/') as $dir)
				{
					if (!is_dir($dir))
					{
						continue;
					}

					foreach ((array) @scandir($dir) as $file)
					{
						if ($file === '.' || $file === '..' || is_dir($dir . $file)
							|| $file === '.htaccess' || $file === 'index.html')
						{
							continue;
						}

						if (!isset($attesi[$file]))
						{
							$orfani++;
						}
					}
				}
			}
		}

		$out[] = $this->c('MS_CHK_ORPHANS', $orfani ? $orfani . ' file' : 'nessuno',
			$orfani ? self::AVVISO : self::OK,
			'MS_FIX_ORPHANS');

		// voti e playlist che puntano a brani inesistenti
		foreach (array(
			'musicshare_votes'			=> 'MS_CHK_ORPHAN_VOTES',
			'musicshare_playlist_songs'	=> 'MS_CHK_ORPHAN_PLAYLIST',
			'musicshare_song_genre'		=> 'MS_CHK_ORPHAN_GENRES',
		) as $tabella => $etichetta)
		{
			$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table_prefix . $tabella . ' x
				WHERE NOT EXISTS (
					SELECT 1 FROM ' . $this->table_prefix . 'musicshare_songs s
					WHERE s.song_id = x.song_id
				)';
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$quanti = (int) $row['quanti'];

			$out[] = $this->c($etichetta, $quanti ? $quanti . ' righe' : 'nessuna',
				$quanti ? self::AVVISO : self::OK,
				'MS_FIX_ORPHAN_ROWS');
		}

		return $out;
	}

	/**
	 * Sistema di notifiche.
	 *
	 * @return array
	 */
	public function check_notifications()
	{
		$out = array();

		$tipi = array(
			'salvocortesiano.musicshare.notification.type.song_new',
			'salvocortesiano.musicshare.notification.type.song_approved',
			'salvocortesiano.musicshare.notification.type.song_rejected',
		);

		$sql = 'SELECT notification_type_id, notification_type_name, notification_type_enabled
			FROM ' . $this->table_prefix . 'notification_types
			WHERE ' . $this->db->sql_in_set('notification_type_name', $tipi);
		$result = $this->db->sql_query($sql);

		$registrati = array();
		$attivi = 0;

		while ($row = $this->db->sql_fetchrow($result))
		{
			$registrati[$row['notification_type_name']] = $row;

			if ($row['notification_type_enabled'])
			{
				$attivi++;
			}
		}
		$this->db->sql_freeresult($result);

		$out[] = $this->c('MS_CHK_NOTIF_TYPES',
			count($registrati) . ' su ' . count($tipi) . ' registrati, ' . $attivi . ' attivi',
			(count($registrati) === count($tipi) && $attivi === count($tipi)) ? self::OK : self::ERRORE,
			'MS_FIX_NOTIF_TYPES');

		// i servizi devono essere costruibili
		$rotti = array();

		foreach ($tipi as $t)
		{
			try
			{
				$this->container->get($t);
			}
			catch (\Exception $e)
			{
				$rotti[] = substr($t, strrpos($t, '.') + 1);
			}
		}

		$out[] = $this->c('MS_CHK_NOTIF_SERVICES',
			$rotti ? 'non costruibili: ' . implode(', ', $rotti) : 'tutti disponibili',
			$rotti ? self::ERRORE : self::OK,
			'MS_FIX_NOTIF_SERVICES');

		// quanti destinatari
		$lista = $this->auth->acl_get_list(false, 'u_musicshare_notify', 0);
		$destinatari = isset($lista[0]['u_musicshare_notify']) ? count($lista[0]['u_musicshare_notify']) : 0;

		$stato = self::OK;
		$rimedio = '';

		if ($destinatari === 0)
		{
			$stato = self::AVVISO;
			$rimedio = 'MS_FIX_NOTIF_NOBODY';
		}
		else if ($destinatari > 2000)
		{
			$stato = self::ERRORE;
			$rimedio = 'MS_FIX_NOTIF_TOOMANY';
		}

		$out[] = $this->c('MS_CHK_NOTIF_USERS', (string) $destinatari, $stato, $rimedio,
			array($destinatari, $destinatari * 100));

		// volume attuale
		if ($registrati)
		{
			$ids = array();

			foreach ($registrati as $r)
			{
				$ids[] = (int) $r['notification_type_id'];
			}

			$sql = 'SELECT COUNT(*) AS tot, SUM(notification_read) AS lette
				FROM ' . $this->table_prefix . 'notifications
				WHERE ' . $this->db->sql_in_set('notification_type_id', $ids);
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$tot = (int) $row['tot'];
			$lette = (int) $row['lette'];

			// Il suggerimento cambia a seconda della situazione: se non ci
			// sono notifiche lette, invitare a rimuovere quelle lette non
			// serve a nulla.
			$rimedio = '';
			$param = array();

			if ($tot > 50000)
			{
				if ($lette > 0)
				{
					$rimedio = 'MS_FIX_NOTIF_ROWS';
					$param = array($lette);
				}
				else
				{
					$rimedio = 'MS_FIX_NOTIF_ROWS_UNREAD';
					$param = array($tot);
				}
			}

			$out[] = $this->c('MS_CHK_NOTIF_ROWS',
				$tot . ' (' . $lette . ' lette, ' . ($tot - $lette) . ' da leggere)',
				$tot > 50000 ? self::AVVISO : self::OK,
				$rimedio, $param);
		}

		$ultima = (int) $this->config['musicshare_cleanup_last'];
		$out[] = $this->c('MS_CHK_CLEANUP',
			empty($this->config['musicshare_cleanup_enabled'])
				? 'disattivata'
				: ('attiva, ultima esecuzione: ' . ($ultima ? date('d/m/Y H:i', $ultima) : 'mai')),
			empty($this->config['musicshare_cleanup_enabled']) ? self::AVVISO : self::OK,
			'MS_FIX_CLEANUP');

		return $out;
	}

	/**
	 * Integrazione con le notifiche push del browser.
	 *
	 * Non serve alcun aggancio: l'estensione phpBB Browser Push
	 * Notifications registra un *metodo* di notifica, e phpBB applica i
	 * metodi a tutti i tipi registrati. Le nostre tre notifiche passano
	 * quindi dal push senza una riga di codice dedicata.
	 *
	 * Qui si verifica soltanto che sia installata, attiva e configurata:
	 * e' l'informazione che manca all'amministratore quando si chiede
	 * perche' le push non arrivino.
	 *
	 * @return array
	 */
	public function check_webpush()
	{
		$out = array();

		$installata = false;

		try
		{
			$installata = $this->container->get('ext.manager')->is_enabled('phpbb/webpushnotifications');
		}
		catch (\Exception $e)
		{
			$installata = false;
		}

		$out[] = $this->c('MS_CHK_WP_INSTALLED',
			$installata ? 'installata e attiva' : 'non installata',
			self::OK,
			$installata ? '' : 'MS_FIX_WP_INSTALLED');

		if (!$installata)
		{
			return $out;
		}

		// attiva e con le chiavi generate?
		$attiva = !empty($this->config['wpn_webpush_enable']);
		$chiavi = !empty($this->config['wpn_webpush_vapid_public'])
			&& !empty($this->config['wpn_webpush_vapid_private']);

		$out[] = $this->c('MS_CHK_WP_ENABLED',
			$attiva ? 'attive' : 'DISATTIVATE',
			$attiva ? self::OK : self::AVVISO,
			'MS_FIX_WP_ENABLED');

		$out[] = $this->c('MS_CHK_WP_KEYS',
			$chiavi ? 'presenti' : 'MANCANTI',
			$chiavi ? self::OK : self::ERRORE,
			'MS_FIX_WP_KEYS');

		$predefinito = !empty($this->config['wpn_webpush_method_enabled']);

		$out[] = $this->c('MS_CHK_WP_DEFAULT',
			$predefinito ? 'si' : 'no',
			$predefinito ? self::OK : self::AVVISO,
			'MS_FIX_WP_DEFAULT');

		// quanti utenti hanno davvero un dispositivo registrato
		$tools = new \phpbb\db\tools\tools($this->db);
		$tabella = $this->table_prefix . 'wpn_push_subscriptions';

		if ($tools->sql_table_exists($tabella))
		{
			$sql = 'SELECT COUNT(DISTINCT user_id) AS quanti FROM ' . $tabella;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$quanti = (int) $row['quanti'];

			$out[] = $this->c('MS_CHK_WP_SUBS', (string) $quanti,
				$quanti > 0 ? self::OK : self::AVVISO,
				'MS_FIX_WP_SUBS');
		}

		// quanti fra i destinatari dei nostri avvisi hanno spento il push
		$sql = 'SELECT COUNT(*) AS quanti
			FROM ' . $this->table_prefix . "user_notifications
			WHERE item_type = 'salvocortesiano.musicshare.notification.type.song_new'
				AND method = 'notification.method.phpbb.wpn.webpush'
				AND notify = 0";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$spenti = (int) $row['quanti'];

		$out[] = $this->c('MS_CHK_WP_OPTOUT', (string) $spenti,
			self::OK,
			$spenti > 0 ? 'MS_FIX_WP_OPTOUT' : '', array($spenti));

		return $out;
	}

	/**
	 * Funzioni facoltative e loro configurazione.
	 *
	 * @return array
	 */
	public function check_features()
	{
		$out = array();

		$reco = $this->container->get('salvocortesiano.musicshare.recognizer');
		$servizio = $reco->get_service();

		if ($servizio === '')
		{
			$out[] = $this->c('MS_CHK_RECO', 'disattivato', self::OK);
		}
		else
		{
			$gruppi = $reco->get_groups();

			$out[] = $this->c('MS_CHK_RECO', $servizio . ', gruppi controllati: ' . count($gruppi),
				empty($gruppi) ? self::AVVISO : self::OK,
				empty($gruppi) ? 'MS_FIX_RECO_GROUPS' : '');
		}

		$out[] = $this->c('MS_CHK_BBCODE',
			empty($this->config['musicshare_bbcode']) ? 'disattivato' : 'attivo',
			self::OK);

		$out[] = $this->c('MS_CHK_TOAST',
			empty($this->config['musicshare_toast_enabled'])
				? 'disattivati'
				: ('attivi ogni ' . (int) $this->config['musicshare_toast_interval'] . ' s'),
			(!empty($this->config['musicshare_toast_enabled'])
				&& (int) $this->config['musicshare_toast_interval'] < 30) ? self::AVVISO : self::OK,
			'MS_FIX_TOAST');

		$approvazione = !empty($this->config['musicshare_require_approval']);
		$out[] = $this->c('MS_CHK_APPROVAL',
			$approvazione ? 'richiesta' : 'non richiesta',
			$approvazione ? self::OK : self::AVVISO,
			'MS_FIX_APPROVAL');

		// registro dei download: c'e' e sta registrando?
		$tools_dl = new \phpbb\db\tools\tools($this->db);
		$tab_dl = $this->table_prefix . 'musicshare_downloads';

		if (!$tools_dl->sql_table_exists($tab_dl))
		{
			$out[] = $this->c('MS_CHK_DL_LOG', 'tabella assente', self::ERRORE, 'MS_FIX_DL_LOG_MISSING');
		}
		else
		{
			$sql = 'SELECT COUNT(*) AS quanti FROM ' . $tab_dl;
			$result = $this->db->sql_query($sql);
			$riga = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$quanti = (int) $riga['quanti'];

			$ore = isset($this->config['musicshare_play_interval'])
				? (int) $this->config['musicshare_play_interval'] : 12;

			$out[] = $this->c('MS_CHK_DL_LOG',
				$quanti . ' registrati, intervallo minimo ' . $ore . ' ore',
				self::OK,
				$quanti === 0 ? 'MS_FIX_DL_LOG_EMPTY' : '');
		}

		// argomenti di discussione: quanti brani ne hanno uno
		if (!empty($this->config['musicshare_topic_enabled']))
		{
			$sql = 'SELECT COUNT(*) AS totale,
					SUM(CASE WHEN topic_id > 0 THEN 1 ELSE 0 END) AS con_argomento
				FROM ' . $this->table_prefix . 'musicshare_songs';
			$result = $this->db->sql_query($sql);
			$riga = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$totale = (int) $riga['totale'];
			$con = (int) $riga['con_argomento'];

			$out[] = $this->c('MS_CHK_TOPICS',
				$con . ' su ' . $totale,
				($totale === 0 || $con > 0) ? self::OK : self::AVVISO,
				($con < $totale) ? 'MS_FIX_TOPICS' : '',
				array($totale - $con));
		}
		else
		{
			$out[] = $this->c('MS_CHK_TOPICS', 'funzione disattivata', self::OK);
		}

		// brani in attesa da troppo tempo
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table_prefix . 'musicshare_songs
			WHERE song_approved = 0';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$attesa = (int) $row['quanti'];
		$out[] = $this->c('MS_CHK_PENDING', $attesa ? $attesa . ' brani' : 'nessuno',
			$attesa > 0 ? self::AVVISO : self::OK,
			'MS_FIX_PENDING');

		return $out;
	}
}
