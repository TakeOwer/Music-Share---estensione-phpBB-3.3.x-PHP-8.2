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

use salvocortesiano\musicshare\repository\song_repository;

class upload_handler
{
	protected $config;
	protected $request;
	protected $user;
	protected $storage_helper;
	protected $metadata_extractor;
	protected $song_repository;
	protected $notifier;
	protected $recognizer;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\request\request $request,
		\phpbb\user $user,
		storage_helper $storage_helper,
		metadata_extractor $metadata_extractor,
		song_repository $song_repository,
		notifier $notifier = null,
		recognizer $recognizer = null
	)
	{
		$this->config = $config;
		$this->request = $request;
		$this->user = $user;
		$this->storage_helper = $storage_helper;
		$this->metadata_extractor = $metadata_extractor;
		$this->song_repository = $song_repository;
		$this->notifier = $notifier;
		$this->recognizer = $recognizer;
	}

	/**
	 * Verifica se un file è stato effettivamente caricato.
	 * phpBB disattiva le superglobali, quindi i file vanno letti
	 * tramite $request->file() e non tramite $_FILES.
	 *
	 * @param array $file
	 * @return bool
	 */
	/**
	 * Ripulisce la descrizione scritta dall'utente: niente marcatura,
	 * niente righe vuote in eccesso, lunghezza entro il limite scelto
	 * dall'amministratore.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function clean_description($text)
	{
		if (empty($this->config['musicshare_descriptions']))
		{
			return '';
		}

		$text = trim(strip_tags((string) $text));
		$text = preg_replace('/[\r\n]+/', ' ', $text);
		$text = preg_replace('/\s{2,}/', ' ', $text);

		$max = (int) $this->config['musicshare_description_max'];
		$max = ($max > 0) ? min(1000, $max) : 300;

		return utf8_substr($text, 0, $max);
	}

	protected function has_file($file)
	{
		// Attenzione: phpBB restituisce i valori di $request->file() come
		// stringhe ('0' invece di 0), quindi il confronto va fatto sul cast
		// a intero e mai con === sulla costante UPLOAD_ERR_OK.
		return !empty($file)
			&& !empty($file['name'])
			&& $file['name'] !== 'none'
			&& isset($file['error'])
			&& (int) $file['error'] === UPLOAD_ERR_OK
			&& !empty($file['tmp_name'])
			&& is_uploaded_file($file['tmp_name']);
	}

	/**
	 * Traduce i codici di errore PHP dell'upload in chiavi di lingua.
	 *
	 * @param array $file
	 * @return string
	 */
	protected function get_php_upload_error($file)
	{
		if (!isset($file['error']))
		{
			return 'MUSICSHARE_UPLOAD_ERR_NOFILE';
		}

		switch ((int) $file['error'])
		{
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return 'MUSICSHARE_UPLOAD_ERR_PHP_SIZE';

			case UPLOAD_ERR_PARTIAL:
				return 'MUSICSHARE_UPLOAD_ERR_PARTIAL';

			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
				return 'MUSICSHARE_UPLOAD_ERR_STORAGE';

			default:
				return 'MUSICSHARE_UPLOAD_ERR_NOFILE';
		}
	}

	/**
	 * Sostituisce la copertina di un brano già caricato con quella
	 * presente nel campo "cover_file" del form, se c'è.
	 *
	 * @param array $song riga del brano
	 * @return array array('changed' => bool, 'cover_path' => string, 'error' => string|false)
	 */
	public function handle_cover_upload(array $song)
	{
		$cover_file = $this->request->file('cover_file');

		if (!$this->has_file($cover_file))
		{
			return ['changed' => false, 'cover_path' => '', 'error' => false];
		}

		$ext = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));

		if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true))
		{
			return ['changed' => false, 'cover_path' => '', 'error' => 'MUSICSHARE_COVER_ERR_TYPE'];
		}

		// Verifica che sia davvero un'immagine, non solo l'estensione
		if (@getimagesize($cover_file['tmp_name']) === false)
		{
			return ['changed' => false, 'cover_path' => '', 'error' => 'MUSICSHARE_COVER_ERR_TYPE'];
		}

		if ((int) $cover_file['size'] > 5242880)
		{
			return ['changed' => false, 'cover_path' => '', 'error' => 'MUSICSHARE_COVER_ERR_SIZE'];
		}

		$user_id = (int) $song['user_id'];

		// riuso la cartella in cui si trova gia' il brano, cosi' la copertina
		// finisce sempre accanto al file a cui appartiene
		$user_folder = dirname((string) $song['file_path']);
		$user_folder = ($user_folder === '.' || $user_folder === '') ? (string) $user_id : $user_folder;
		$covers_dir = $this->storage_helper->get_storage_path() . $user_folder . '/covers/';

		if (!$this->storage_helper->ensure_dir($covers_dir))
		{
			return ['changed' => false, 'cover_path' => '', 'error' => 'MUSICSHARE_UPLOAD_ERR_STORAGE'];
		}

		// rimuovo la vecchia copertina (può avere un'estensione diversa)
		$this->remove_cover($song);

		$filename = $this->storage_helper->random_filename($ext);

		if (!move_uploaded_file($cover_file['tmp_name'], $covers_dir . $filename))
		{
			return ['changed' => false, 'cover_path' => '', 'error' => 'MUSICSHARE_UPLOAD_ERR_MOVE'];
		}

		return ['changed' => true, 'cover_path' => $user_folder . '/covers/' . $filename, 'error' => false];
	}

	/**
	 * Elimina dal disco la copertina attualmente associata al brano.
	 *
	 * @param array $song
	 * @return void
	 */
	public function remove_cover(array $song)
	{
		$old = $this->storage_helper->get_cover_file($song);

		if ($old && is_file($old))
		{
			@unlink($old);
		}
	}

	/**
	 * Gestisce l'upload di un brano (campi "song_file" e, facoltativo,
	 * "cover_file" del form).
	 *
	 * @param int $user_id
	 * @param array $genre_ids
	 * @param string $manual_title titolo inserito a mano (priorità sul tag)
	 * @param string $manual_artist
	 * @param string $manual_album
	 * @param int $manual_year
	 * @return array array('success' => bool, 'error' => string|false, 'song_id' => int|false)
	 */
	public function handle_upload($user_id, array $genre_ids, $manual_title = '', $manual_artist = '', $manual_album = '', $manual_year = 0)
	{
		$song_file = $this->request->file('song_file');
		$cover_file = $this->request->file('cover_file');

		if (!$this->has_file($song_file))
		{
			return array('success' => false, 'error' => $this->get_php_upload_error($song_file), 'song_id' => false);
		}

		$tmp_name = $song_file['tmp_name'];
		$orig_name = $song_file['name'];
		$file_size = (int) $song_file['size'];

		$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
		$allowed = $this->storage_helper->get_allowed_extensions();

		if (!in_array($ext, $allowed, true))
		{
			return array('success' => false, 'error' => 'MUSICSHARE_UPLOAD_ERR_TYPE', 'song_id' => false);
		}

		$max_size = (int) $this->config['musicshare_max_filesize'];
		if ($max_size > 0 && $file_size > $max_size)
		{
			return array('success' => false, 'error' => 'MUSICSHARE_UPLOAD_ERR_SIZE', 'song_id' => false);
		}

		$max_user_space = (int) $this->config['musicshare_max_user_space'];
		if ($max_user_space > 0)
		{
			$used = $this->song_repository->get_user_total_size($user_id);
			if ($used + $file_size > $max_user_space)
			{
				return array('success' => false, 'error' => 'MUSICSHARE_UPLOAD_ERR_QUOTA', 'song_id' => false);
			}
		}

		if (function_exists('finfo_open'))
		{
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $tmp_name);
			finfo_close($finfo);

			$allowed_mimes = array(
				'audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/flac', 'audio/x-flac',
				'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/mp4', 'audio/x-m4a',
				'audio/aac', 'audio/aacp', 'audio/vnd.dlna.adts', 'audio/x-hx-aac-adts',
				'video/ogg', 'application/ogg', 'video/mp4',
			);

			if (strpos((string) $mime, 'audio/') !== 0 && !in_array($mime, $allowed_mimes, true))
			{
				return array('success' => false, 'error' => 'MUSICSHARE_UPLOAD_ERR_MIME', 'song_id' => false);
			}
		}

		// impronta del file: serve a riconoscere lo stesso brano ricaricato
		$file_hash = @md5_file($tmp_name);
		$file_hash = ($file_hash === false) ? '' : $file_hash;

		$block_duplicates = !isset($this->config['musicshare_block_duplicates'])
			|| (bool) $this->config['musicshare_block_duplicates'];

		if ($block_duplicates && $file_hash !== '' && $this->song_repository->hash_exists_for_user($file_hash, $user_id))
		{
			return array('success' => false, 'error' => 'MUSICSHARE_UPLOAD_ERR_DUPLICATE', 'song_id' => false);
		}

		$username = (string) $this->user->data['username'];
		$user_folder = $this->storage_helper->get_user_folder($user_id, $username);
		$user_dir = $this->storage_helper->get_user_dir($user_id, $username);

		if (!$this->storage_helper->ensure_dir($user_dir))
		{
			return array('success' => false, 'error' => 'MUSICSHARE_UPLOAD_ERR_STORAGE', 'song_id' => false);
		}

		$tmp_filename = 'tmp_' . unique_id() . '.' . $ext;
		$dest_path = $user_dir . $tmp_filename;

		if (!move_uploaded_file($tmp_name, $dest_path))
		{
			return array('success' => false, 'error' => 'MUSICSHARE_UPLOAD_ERR_MOVE', 'song_id' => false);
		}

		$meta = $this->metadata_extractor->extract($dest_path);

		$title = ($manual_title !== '')
			? $manual_title
			: (($meta['title'] !== '') ? $meta['title'] : pathinfo($orig_name, PATHINFO_FILENAME));
		$artist = ($manual_artist !== '') ? $manual_artist : $meta['artist'];

		// Come per titolo e artista: quanto scritto a mano ha la
		// precedenza sui tag del file, che restano il ripiego.
		$album = ($manual_album !== '') ? $manual_album : $meta['album'];
		$year = ((int) $manual_year > 0) ? (int) $manual_year : (int) $meta['year'];

		$data = array(
			'user_id'		=> (int) $user_id,
			'song_title'	=> (string) $title,
			'song_artist'	=> (string) $artist,
			'song_album'	=> (string) $album,
			'song_year'		=> (int) $year,
			'song_duration'	=> (int) $meta['duration'],
			'file_path'		=> '',
			'file_ext'		=> $ext,
			'file_size'		=> $file_size,
			'file_hash'		=> $file_hash,
			'cover_path'	=> '',
			'waveform_path'	=> '',
			'upload_time'	=> time(),
			'play_count'	=> 0,
			'song_approved'	=> $this->config['musicshare_require_approval'] ? 0 : 1,
			// Predefinito 0, non 1: una casella non spuntata non viene
			// inviata affatto dal browser, quindi con predefinito 1 la
			// scelta dell'utente di vietare il download veniva ignorata.
			'allow_download'	=> $this->request->variable('allow_download', 0) ? 1 : 0,
			'song_description'	=> $this->clean_description($this->request->variable('song_description', '', true)),
		);

		$song_id = $this->song_repository->add_song($data, $genre_ids);

		// Nome casuale, non l'identificativo: con nomi progressivi
		// l'intero archivio sarebbe enumerabile se la cartella diventasse
		// raggiungibile dal web.
		$final_filename = $this->storage_helper->random_filename($ext);
		$final_rel_path = $user_folder . '/' . $final_filename;
		@rename($dest_path, $user_dir . $final_filename);

		$update = array('file_path' => $final_rel_path);
		$cover_rel_path = '';

		if ($this->has_file($cover_file))
		{
			$cover_ext = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));

			if (in_array($cover_ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true))
			{
				$covers_dir = $this->storage_helper->get_user_covers_dir($user_id, $username);
				$this->storage_helper->ensure_dir($covers_dir);
				$cover_filename = $this->storage_helper->random_filename($cover_ext);

				if (move_uploaded_file($cover_file['tmp_name'], $covers_dir . $cover_filename))
				{
					$cover_rel_path = $user_folder . '/covers/' . $cover_filename;
				}
			}
		}
		else if (!empty($meta['cover_data']))
		{
			$covers_dir = $this->storage_helper->get_user_covers_dir($user_id, $username);
			$this->storage_helper->ensure_dir($covers_dir);
			$cover_ext = (strpos((string) $meta['cover_mime'], 'png') !== false) ? 'png' : 'jpg';
			$cover_filename = $this->storage_helper->random_filename($cover_ext);

			if (@file_put_contents($covers_dir . $cover_filename, $meta['cover_data']) !== false)
			{
				$cover_rel_path = $user_folder . '/covers/' . $cover_filename;
			}
		}

		if ($cover_rel_path !== '')
		{
			$update['cover_path'] = $cover_rel_path;
		}

		// Riconoscimento: se il brano corrisponde a una pubblicazione
		// commerciale non si blocca il caricamento, ma si mette in
		// approvazione e si avvisa l'utente. Una corrispondenza è un
		// indizio, non una prova: può trattarsi di un falso positivo.
		$match = array('matched' => false);

		if ($this->recognizer !== null && $this->recognizer->applies_to($user_id))
		{
			$match = $this->recognizer->identify($dest_path, (int) $meta['duration']);

			if (!empty($match['matched']))
			{
				$update['song_approved'] = 0;
				$update['recognized'] = 1;
				$update['recognized_info'] = utf8_substr(trim(
					$match['artist'] . ' - ' . $match['title'] . ($match['label'] !== '' ? ' (' . $match['label'] . ')' : '')
				), 0, 255);
			}
		}

		$this->song_repository->update_song($song_id, $update);

		// La notifica parte solo per i brani già visibili: se è richiesta
		// l'approvazione, partirà quando il moderatore approva.
		if (empty($this->config['musicshare_require_approval']) && $this->notifier !== null)
		{
			$this->notifier->song_new(array(
				'song_id'		=> $song_id,
				'song_title'	=> $title,
				'user_id'		=> $user_id,
				'username'		=> $username,
			));
		}

		return array(
			'success'		=> true,
			'error'			=> false,
			'song_id'		=> $song_id,
			'recognized'	=> !empty($match['matched']),
			'match_title'	=> !empty($match['matched']) ? $match['title'] : '',
			'match_artist'	=> !empty($match['matched']) ? $match['artist'] : '',
			'match_label'	=> !empty($match['matched']) ? $match['label'] : '',
		);
	}
}
