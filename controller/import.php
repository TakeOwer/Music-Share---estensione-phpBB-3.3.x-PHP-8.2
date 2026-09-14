<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\controller;

use Symfony\Component\HttpFoundation\Response;
use salvocortesiano\musicshare\repository\genre_repository;
use salvocortesiano\musicshare\repository\song_repository;
use salvocortesiano\musicshare\service\storage_helper;
use salvocortesiano\musicshare\service\metadata_extractor;

/**
 * Importazione in libreria di un allegato audio di un messaggio.
 *
 * L'allegato resta dov'è: se ne fa una copia nell'archivio dei brani.
 * Tenere un riferimento al file originale sarebbe sembrato più
 * elegante, ma l'autore del messaggio puo' cancellare l'allegato in
 * qualsiasi momento e il brano in libreria resterebbe muto.
 */
class import
{
	protected $config;
	protected $template;
	protected $user;
	protected $auth;
	protected $request;
	protected $helper;
	protected $db;
	protected $genre_repository;
	protected $song_repository;
	protected $storage_helper;
	protected $metadata_extractor;
	protected $importer;
	protected $genre_translator;
	protected $root_path;
	protected $table_prefix;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		\phpbb\controller\helper $helper,
		\phpbb\db\driver\driver_interface $db,
		genre_repository $genre_repository,
		song_repository $song_repository,
		storage_helper $storage_helper,
		metadata_extractor $metadata_extractor,
		\salvocortesiano\musicshare\service\attachment_importer $importer,
		\salvocortesiano\musicshare\service\genre_translator $genre_translator,
		$root_path,
		$table_prefix
	)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->request = $request;
		$this->helper = $helper;
		$this->db = $db;
		$this->genre_repository = $genre_repository;
		$this->song_repository = $song_repository;
		$this->storage_helper = $storage_helper;
		$this->metadata_extractor = $metadata_extractor;
		$this->importer = $importer;
		$this->genre_translator = $genre_translator;
		$this->root_path = $root_path;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Modulo di importazione e salvataggio.
	 *
	 * @param int $attach_id
	 * @return Response
	 */
	public function handle($attach_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		if (empty($this->config['musicshare_attach_import']))
		{
			throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_ATTACH_IMPORT_OFF');
		}

		if (!$this->auth->acl_get('u_musicshare_upload'))
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_NO_PERMISSION');
		}

		$attach = $this->get_attachment((int) $attach_id);

		if (!$attach)
		{
			throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_ATTACH_NOT_FOUND');
		}

		$utente = (int) $this->user->data['user_id'];
		$proprio = ((int) $attach['poster_id'] === $utente);

		// Solo chi ha scritto il messaggio, o chi modera i brani.
		//
		// E' il controllo che rende sicura questa funzione: l'allegato
		// puo' trovarsi in un forum riservato, ma qui non si copia nulla
		// automaticamente, lo fa una persona che su quell'allegato ha
		// gia' titolo.
		if (!$proprio && !$this->auth->acl_get('m_musicshare_manage'))
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_NO_PERMISSION');
		}

		$estensione = strtolower((string) $attach['extension']);

		if (!in_array($estensione, $this->get_allowed_ext(), true))
		{
			throw new \phpbb\exception\http_exception(400, 'MUSICSHARE_ATTACH_NOT_AUDIO');
		}

		$percorso = $this->root_path . (string) $this->config['upload_path'] . '/' . (string) $attach['physical_filename'];

		if (!is_file($percorso))
		{
			throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_ATTACH_FILE_MISSING');
		}

		// gia' importato? l'impronta del file lo dice con certezza
		$hash = (string) md5_file($percorso);

		if ($hash !== '' && $this->song_repository->hash_exists_for_user($hash, (int) $this->user->data['user_id']))
		{
			$this->template->assign_vars(array(
				'S_ALREADY_IMPORTED'	=> true,
				'U_MY_SONGS'			=> append_sid($this->root_path . 'ucp.php',
					'i=-salvocortesiano-musicshare-ucp-main_module&amp;mode=songs'),
			));

			return $this->helper->render('musicshare_import.html', $this->user->lang('MUSICSHARE_ATTACH_IMPORT'));
		}

		$meta = $this->metadata_extractor->extract($percorso);
		$errore = '';

		if ($this->request->is_set_post('submit'))
		{
			$errore = $this->salva($attach);

			if ($errore === '')
			{
				$this->template->assign_vars(array(
					'S_IMPORTED'	=> true,
					'U_LIBRARY'		=> $this->helper->route('salvocortesiano_musicshare_browse'),
					'U_MY_SONGS'	=> append_sid($this->root_path . 'ucp.php',
						'i=-salvocortesiano-musicshare-ucp-main_module&amp;mode=songs'),
				));

				return $this->helper->render('musicshare_import.html', $this->user->lang('MUSICSHARE_ATTACH_IMPORT'));
			}
		}

		$this->assign_genres();

		$this->template->assign_vars(array(
			'ATTACH_NAME'		=> (string) $attach['real_filename'],
			'ATTACH_SIZE'		=> round(((float) $attach['filesize']) / 1048576, 2),
			'SUGGESTED_TITLE'	=> ($meta['title'] !== '') ? $meta['title']
				: pathinfo((string) $attach['real_filename'], PATHINFO_FILENAME),
			'SUGGESTED_ARTIST'	=> $meta['artist'],
			'SUGGESTED_ALBUM'	=> $meta['album'],
			'SUGGESTED_YEAR'	=> $meta['year'] ? (int) $meta['year'] : '',
			'ERROR_MESSAGE'		=> $errore,
			'S_ERROR'			=> ($errore !== ''),
			'S_DESCRIPTIONS'	=> !isset($this->config['musicshare_descriptions'])
				|| (bool) $this->config['musicshare_descriptions'],
			'DESCRIPTION_MAX'	=> (int) $this->config['musicshare_description_max'] ?: 300,
			'S_DOWNLOAD_ENABLED'	=> !empty($this->config['musicshare_allow_download']),
			'S_FORM_ACTION'		=> $this->helper->route('salvocortesiano_musicshare_import',
				array('attach_id' => (int) $attach['attach_id'])),
		));

		add_form_key('musicshare_import');

		return $this->helper->render('musicshare_import.html', $this->user->lang('MUSICSHARE_ATTACH_IMPORT'));
	}

	/**
	 * Raccoglie i dati del modulo e delega l'importazione al servizio.
	 *
	 * La copia del file, i controlli su quota e duplicati e la creazione
	 * del brano stanno nel servizio condiviso: cosi' l'importazione
	 * manuale e quella automatica seguono lo stesso procedimento.
	 *
	 * @return string messaggio d'errore, stringa vuota se riuscito
	 */
	protected function salva(array $attach)
	{
		if (!check_form_key('musicshare_import'))
		{
			return $this->user->lang('FORM_INVALID');
		}

		$titolo = trim($this->request->variable('song_title', '', true));

		if ($titolo === '')
		{
			return $this->user->lang('MUSICSHARE_TITLE_REQUIRED');
		}

		$esito = $this->importer->import(
			$attach,
			(int) $this->user->data['user_id'],
			(string) $this->user->data['username'],
			array(
				'title'			=> $titolo,
				'artist'		=> trim($this->request->variable('song_artist', '', true)),
				'album'			=> trim($this->request->variable('song_album', '', true)),
				'year'			=> (int) $this->request->variable('song_year', 0),
				'description'	=> $this->clean_description($this->request->variable('song_description', '', true)),
				'allow_download'	=> $this->request->variable('allow_download', 0),
				'genres'		=> $this->request->variable('genre_ids', array(0)),
			)
		);

		return $esito['success'] ? '' : $this->user->lang($esito['error']);
	}

	/**
	 * Descrizione ripulita, con le stesse regole del caricamento diretto.
	 */
	protected function clean_description($testo)
	{
		if (empty($this->config['musicshare_descriptions']))
		{
			return '';
		}

		$testo = trim(strip_tags((string) $testo));
		$testo = preg_replace('/[\r\n]+/', ' ', $testo);
		$testo = preg_replace('/\s{2,}/', ' ', $testo);

		$max = (int) $this->config['musicshare_description_max'];
		$max = ($max > 0) ? min(1000, $max) : 300;

		return utf8_substr($testo, 0, $max);
	}

	/**
	 * Elenco dei generi, raggruppati per categoria.
	 */
	protected function assign_genres()
	{
		$genres = $this->genre_repository->get_all_grouped();

		foreach ($genres as $category => $items)
		{
			$this->template->assign_block_vars('genre_groups', array(
				'CATEGORY_NAME'	=> $this->genre_translator->category($category),
			));

			foreach ($items as $genre)
			{
				$this->template->assign_block_vars('genre_groups.genres', array(
					'GENRE_ID'		=> (int) $genre['genre_id'],
					'GENRE_NAME'	=> $genre['genre_name'],
				));
			}
		}
	}

	/**
	 * Riga dell'allegato.
	 */
	protected function get_attachment($attach_id)
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

	protected function get_allowed_ext()
	{
		$ext = (string) $this->config['musicshare_allowed_ext'];
		$ext = array_filter(array_map('trim', explode(',', strtolower($ext))));

		return $ext ?: array('mp3', 'ogg', 'oga', 'flac', 'wav', 'm4a', 'aac');
	}
}
