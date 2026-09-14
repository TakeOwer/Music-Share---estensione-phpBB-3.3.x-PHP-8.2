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

/**
 * Importazione in libreria di un allegato audio di un messaggio.
 *
 * Usata sia dal modulo manuale — dove l'autore compila titolo, generi e
 * descrizione — sia dall'importazione automatica dai forum scelti
 * dall'amministratore. Tenere il procedimento in un posto solo evita che
 * le due strade si comportino in modo diverso.
 *
 * Del file viene salvata una copia: l'allegato originale puo' essere
 * rimosso in qualsiasi momento dall'autore del messaggio, e un brano che
 * punta a un file sparito sarebbe muto.
 */
class attachment_importer
{
	protected $config;
	protected $db;
	protected $song_repository;
	protected $storage_helper;
	protected $metadata_extractor;
	protected $root_path;
	protected $table_prefix;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		song_repository $song_repository,
		storage_helper $storage_helper,
		metadata_extractor $metadata_extractor,
		$root_path,
		$table_prefix
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->song_repository = $song_repository;
		$this->storage_helper = $storage_helper;
		$this->metadata_extractor = $metadata_extractor;
		$this->root_path = $root_path;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Formati audio accettati, come impostati in ACP.
	 *
	 * @return array
	 */
	public function get_allowed_ext()
	{
		$ext = (string) $this->config['musicshare_allowed_ext'];
		$ext = array_filter(array_map('trim', explode(',', strtolower($ext))));

		return $ext ?: array('mp3', 'ogg', 'oga', 'flac', 'wav', 'm4a', 'aac');
	}

	/**
	 * L'allegato è in un formato che l'estensione accetta?
	 *
	 * @param array $attach
	 * @return bool
	 */
	public function is_audio(array $attach)
	{
		return in_array(strtolower((string) $attach['extension']), $this->get_allowed_ext(), true);
	}

	/**
	 * Percorso su disco dell'allegato.
	 *
	 * @param array $attach
	 * @return string
	 */
	public function get_path(array $attach)
	{
		return $this->root_path . (string) $this->config['upload_path'] . '/' . (string) $attach['physical_filename'];
	}

	/**
	 * Riga di un allegato.
	 *
	 * @param int $attach_id
	 * @return array|false
	 */
	public function get_attachment($attach_id)
	{
		$sql = 'SELECT attach_id, post_msg_id, topic_id, poster_id, is_orphan,
				physical_filename, real_filename, extension, mimetype, filesize
			FROM ' . $this->table_prefix . 'attachments
			WHERE attach_id = ' . (int) $attach_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	/**
	 * Allegati di un messaggio.
	 *
	 * @param int $post_id
	 * @return array
	 */
	public function get_post_attachments($post_id)
	{
		$sql = 'SELECT attach_id, post_msg_id, topic_id, poster_id, is_orphan,
				physical_filename, real_filename, extension, mimetype, filesize
			FROM ' . $this->table_prefix . 'attachments
			WHERE post_msg_id = ' . (int) $post_id . '
				AND is_orphan = 0';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Metadati letti dai tag del file.
	 *
	 * @param array $attach
	 * @return array
	 */
	public function read_meta(array $attach)
	{
		return $this->metadata_extractor->extract($this->get_path($attach));
	}

	/**
	 * Importa l'allegato creando un brano.
	 *
	 * @param array $attach   riga dell'allegato
	 * @param int $user_id    a chi viene attribuito il brano
	 * @param string $username nome dell'utente, per la cartella
	 * @param array $campi    titolo, artista, album, anno, descrizione,
	 *                        allow_download, generi. Quanto manca viene
	 *                        dedotto dai tag del file.
	 * @param bool $forza_attesa mette comunque il brano in approvazione
	 * @return array esito: success, error (chiave di lingua), song_id
	 */
	public function import(array $attach, $user_id, $username, array $campi = array(), $forza_attesa = false)
	{
		$errore = function ($chiave) {
			return array('success' => false, 'error' => $chiave, 'song_id' => 0);
		};

		if (!$this->is_audio($attach))
		{
			return $errore('MUSICSHARE_ATTACH_NOT_AUDIO');
		}

		$percorso = $this->get_path($attach);

		if (!is_file($percorso))
		{
			return $errore('MUSICSHARE_ATTACH_FILE_MISSING');
		}

		$hash = (string) md5_file($percorso);

		// gia' in libreria? l'impronta del file lo dice con certezza
		if ($hash !== '' && $this->song_repository->hash_exists_for_user($hash, (int) $user_id))
		{
			return $errore('MUSICSHARE_IMPORT_ALREADY');
		}

		// spazio disponibile: vale la stessa quota dei caricamenti diretti
		$quota = (int) $this->config['musicshare_max_user_space'];

		if ($quota > 0)
		{
			$usato = $this->song_repository->get_user_total_size((int) $user_id);

			if (($usato + (int) $attach['filesize']) > $quota)
			{
				return $errore('MUSICSHARE_UPLOAD_ERR_QUOTA');
			}
		}

		$meta = $this->metadata_extractor->extract($percorso);
		$estensione = strtolower((string) $attach['extension']);

		$cartella = $this->storage_helper->get_user_dir((int) $user_id, $username);
		$this->storage_helper->ensure_dir($cartella);

		$nome = $this->storage_helper->random_filename($estensione);

		if (!@copy($percorso, $cartella . $nome))
		{
			return $errore('MUSICSHARE_UPLOAD_ERR_MOVE');
		}

		$titolo = isset($campi['title']) ? trim($campi['title']) : '';

		if ($titolo === '')
		{
			$titolo = ($meta['title'] !== '')
				? $meta['title']
				: pathinfo((string) $attach['real_filename'], PATHINFO_FILENAME);
		}

		$approvato = empty($this->config['musicshare_require_approval']) && !$forza_attesa;

		$dati = array(
			'user_id'			=> (int) $user_id,
			'song_title'		=> $titolo,
			'song_artist'		=> isset($campi['artist']) && $campi['artist'] !== ''
				? $campi['artist'] : (string) $meta['artist'],
			'song_album'		=> isset($campi['album']) && $campi['album'] !== ''
				? $campi['album'] : (string) $meta['album'],
			'song_year'			=> !empty($campi['year']) ? (int) $campi['year'] : (int) $meta['year'],
			'song_duration'		=> (int) $meta['duration'],
			'song_bitrate'		=> (int) $meta['bitrate'],
			'song_bitrate_mode'	=> (string) $meta['bitrate_mode'],
			'song_samplerate'	=> (int) $meta['samplerate'],
			'song_channels'		=> (int) $meta['channels'],
			'file_path'			=> $this->storage_helper->get_user_folder((int) $user_id, $username) . '/' . $nome,
			'file_size'			=> (int) $attach['filesize'],
			'file_ext'			=> $estensione,
			'file_hash'			=> (string) md5_file($cartella . $nome),
			'upload_time'		=> time(),
			'song_approved'		=> $approvato ? 1 : 0,
			'allow_download'	=> !empty($campi['allow_download']) ? 1 : 0,
			'song_description'	=> isset($campi['description']) ? $campi['description'] : '',
		);

		$generi = isset($campi['genres']) ? array_filter(array_map('intval', (array) $campi['genres'])) : array();

		$song_id = $this->song_repository->add_song($dati, $generi);

		if ($song_id <= 0)
		{
			@unlink($cartella . $nome);

			return $errore('MUSICSHARE_UPLOAD_ERR_DB');
		}

		return array('success' => true, 'error' => '', 'song_id' => (int) $song_id, 'approved' => $approvato);
	}
}
