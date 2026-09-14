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

use salvocortesiano\musicshare\repository\genre_repository;
use salvocortesiano\musicshare\repository\song_repository;
use salvocortesiano\musicshare\repository\playlist_repository;
use salvocortesiano\musicshare\repository\wall_repository;
use salvocortesiano\musicshare\service\metadata_extractor;
use salvocortesiano\musicshare\service\upload_handler;
use salvocortesiano\musicshare\service\storage_helper;

class main
{
	protected $config;
	protected $template;
	protected $user;
	protected $auth;
	protected $request;
	protected $helper;
	protected $pagination;
	protected $genre_repository;
	protected $song_repository;
	protected $playlist_repository;
	protected $upload_handler;
	protected $storage_helper;
	protected $genre_translator;
	protected $topic_creator;
	protected $follow_repository;
	protected $license_helper;
	protected $contact_helper;
	protected $wall_manager;
	protected $wall_repository;
	protected $follows_table;
	protected $songs_table;
	protected $root_path;
	protected $php_ext;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		\phpbb\controller\helper $helper,
		\phpbb\pagination $pagination,
		genre_repository $genre_repository,
		song_repository $song_repository,
		playlist_repository $playlist_repository,
		upload_handler $upload_handler,
		storage_helper $storage_helper,
		\salvocortesiano\musicshare\service\genre_translator $genre_translator,
		\salvocortesiano\musicshare\service\topic_creator $topic_creator,
		\salvocortesiano\musicshare\repository\follow_repository $follow_repository,
		\salvocortesiano\musicshare\service\license_helper $license_helper,
		\salvocortesiano\musicshare\service\contact_helper $contact_helper,
		\salvocortesiano\musicshare\service\wall_manager $wall_manager,
		\salvocortesiano\musicshare\repository\wall_repository $wall_repository,
		$follows_table,
		$songs_table,
		$root_path,
		$php_ext
	)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->request = $request;
		$this->helper = $helper;
		$this->pagination = $pagination;
		$this->genre_repository = $genre_repository;
		$this->song_repository = $song_repository;
		$this->playlist_repository = $playlist_repository;
		$this->upload_handler = $upload_handler;
		$this->storage_helper = $storage_helper;
		$this->genre_translator = $genre_translator;
		$this->topic_creator = $topic_creator;
		$this->follow_repository = $follow_repository;
		$this->license_helper = $license_helper;
		$this->contact_helper = $contact_helper;
		$this->wall_manager = $wall_manager;
		$this->wall_repository = $wall_repository;
		$this->follows_table = $follows_table;
		$this->songs_table = $songs_table;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function browse()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		// Con decine di generi predefiniti la pagina diventava un muro di
		// riquadri quasi tutti vuoti. Di norma si mostrano solo i generi
		// che hanno almeno un brano; un collegamento permette di vederli
		// tutti quando serve.
		$show_all = (bool) $this->request->variable('all', 0);

		$total_genres = 0;
		$shown_genres = 0;

		foreach ($this->genre_repository->get_all_grouped() as $category => $genres)
		{
			$rows = array();
			$category_songs = 0;

			foreach ($genres as $genre)
			{
				$count = $this->song_repository->count_songs_by_genre($genre['genre_id']);
				$total_genres++;

				if (!$show_all && $count === 0)
				{
					continue;
				}

				$category_songs += $count;
				$rows[] = array(
					'GENRE_ID'		=> (int) $genre['genre_id'],
					'GENRE_NAME'	=> $genre['genre_name'],
					'SONG_COUNT'	=> $count,
					'S_EMPTY'		=> ($count === 0),
					'U_GENRE'		=> $this->helper->route('salvocortesiano_musicshare_genre', array('genre_id' => $genre['genre_id'])),
				);
			}

			if (empty($rows))
			{
				continue;
			}

			$shown_genres += count($rows);

			$this->template->assign_block_vars('categories', array(
				'CATEGORY_NAME'	=> $this->genre_translator->category($category),
				'SONG_COUNT'	=> $category_songs,
				'S_OPEN'		=> ($category_songs > 0),
			));

			foreach ($rows as $row)
			{
				$this->template->assign_block_vars('categories.genres', $row);
			}
		}

		$this->template->assign_vars(array(
			'S_SHOW_ALL_GENRES'	=> $show_all,
			'S_HAS_HIDDEN'		=> ($shown_genres < $total_genres),
			'U_TOGGLE_GENRES'	=> $this->helper->route(
				'salvocortesiano_musicshare_browse',
				$show_all ? array() : array('all' => 1)
			),
			'HIDDEN_COUNT'		=> ($total_genres - $shown_genres),
		));

		$recent_count = (int) $this->config['musicshare_recent_count'];
		$recent_count = $recent_count > 0 ? $recent_count : 8;

		$recent_songs = $this->song_repository->get_recent_songs($recent_count);
		$this->assign_song_list($recent_songs, 'recent_songs');

		// Classifica per periodo: senza una finestra temporale i brani
		// caricati per primi resterebbero in cima per sempre e uno nuovo
		// non entrerebbe mai, per quanto piaccia.
		$periodi = array(7, 30, 0);
		$periodo = (int) $this->request->variable('top', 7);

		if (!in_array($periodo, $periodi, true))
		{
			$periodo = 7;
		}

		$top_songs = $this->song_repository->get_top_songs_period($periodo, 10);

		// se nel periodo scelto non ha ascoltato nessuno si mostra la
		// classifica generale, invece di un elenco vuoto
		if (empty($top_songs) && $periodo > 0)
		{
			$top_songs = $this->song_repository->get_top_songs(10);
			$periodo = 0;
		}

		foreach ($periodi as $p)
		{
			$this->template->assign_block_vars('top_periods', array(
				'VALUE'		=> $p,
				'NAME'		=> $this->user->lang('MUSICSHARE_TOP_PERIOD_' . ($p > 0 ? $p : 'ALL')),
				'S_ACTIVE'	=> ($p === $periodo),
				'U_LINK'	=> $this->helper->route('salvocortesiano_musicshare_browse') . '?top=' . $p,
			));
		}
		$this->assign_song_list($top_songs, 'top_songs');

		$this->template->assign_vars(array(
			'U_UPLOAD'		=> $this->ucp_upload_url(),
			// i preferiti hanno senso solo per chi è collegato
			'U_LIKED'		=> ((int) $this->user->data['user_id'] !== ANONYMOUS)
				? $this->helper->route('salvocortesiano_musicshare_liked') : '',
			'U_FOLLOWING_NEWS'	=> ((int) $this->user->data['user_id'] !== ANONYMOUS
				&& !empty($this->config['musicshare_follows_enabled']))
				? $this->helper->route('salvocortesiano_musicshare_following') : '',
			'U_FOLLOWS_LIST'	=> $this->helper->route('salvocortesiano_musicshare_follows'),
			'S_CAN_UPLOAD'	=> (bool) $this->auth->acl_get('u_musicshare_upload'),
			'S_HAS_TOP_SONGS'	=> !empty($top_songs),
			'S_HAS_RECENT_SONGS'	=> !empty($recent_songs),
		));

		return $this->helper->render('musicshare_browse.html', $this->user->lang('MUSICSHARE_BROWSE_TITLE'));
	}

	public function search()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		$keywords = $this->request->variable('q', '', true);
		$genre_id = $this->request->variable('genre', 0);

		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['musicshare_songs_per_page'];
		$limit = $limit > 0 ? $limit : 25;

		$total = 0;
		$songs = array();

		if (trim($keywords) !== '' || $genre_id > 0)
		{
			$total = $this->song_repository->count_search($keywords, $genre_id);
			$songs = $this->song_repository->search($keywords, $start, $limit, $genre_id);
		}

		$this->assign_song_list($songs);

		if ($total > 0)
		{
			$base_url = $this->helper->route('salvocortesiano_musicshare_search', array('q' => $keywords, 'genre' => $genre_id));
			$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total, $limit, $start);
		}

		foreach ($this->genre_repository->get_all_grouped() as $category => $genres)
		{
			$this->template->assign_block_vars('filter_groups', array(
				'CATEGORY_NAME'	=> $this->genre_translator->category($category),
			));

			foreach ($genres as $genre)
			{
				$this->template->assign_block_vars('filter_groups.genres', array(
					'GENRE_ID'		=> (int) $genre['genre_id'],
					'GENRE_NAME'	=> $genre['genre_name'],
					'S_SELECTED'	=> ((int) $genre['genre_id'] === $genre_id),
				));
			}
		}

		$this->template->assign_vars(array(
			'SEARCH_QUERY'	=> $keywords,
			'TOTAL_SONGS'	=> $total,
			'S_SEARCHED'	=> trim($keywords) !== '' || $genre_id > 0,
		));

		return $this->helper->render('musicshare_search.html', $this->user->lang('MUSICSHARE_SEARCH'));
	}

	/**
	 * I brani a cui l'utente ha messo "mi piace".
	 *
	 * I voti c'erano gia', ma non portavano da nessuna parte: chi ne
	 * metteva cento non aveva modo di ritrovarli.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	/**
	 * Inizia o smette di seguire un autore.
	 *
	 * @param int $author_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function follow($author_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->check_view_permission();

		if ((int) $this->user->data['user_id'] === ANONYMOUS)
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_FOLLOW_LOGIN');
		}

		if (empty($this->config['musicshare_follows_enabled']))
		{
			throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_FOLLOW_OFF');
		}

		$author_id = (int) $author_id;
		$utente = (int) $this->user->data['user_id'];

		if ($author_id === $utente)
		{
			throw new \phpbb\exception\http_exception(400, 'MUSICSHARE_FOLLOW_SELF');
		}

		// il collegamento cambia stato, quindi va protetto dal token
		if (!check_link_hash($this->request->variable('hash', ''), 'musicshare_follow'))
		{
			throw new \phpbb\exception\http_exception(403, 'FORM_INVALID');
		}

		if ($this->follow_repository->is_following($utente, $author_id))
		{
			$this->follow_repository->unfollow($utente, $author_id);
		}
		else
		{
			$this->follow_repository->follow($utente, $author_id);
		}

		return new \Symfony\Component\HttpFoundation\RedirectResponse(
			$this->helper->route('salvocortesiano_musicshare_user', array('user_id' => $author_id))
		);
	}

	/**
	 * Novita' dagli utenti che segui.
	 *
	 * Il "segui" serviva solo a ricevere una notifica: mancava il posto
	 * dove guardare cosa hanno pubblicato le persone che segui.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function following()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		if ((int) $this->user->data['user_id'] === ANONYMOUS)
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_FOLLOW_LOGIN');
		}

		$user_id = (int) $this->user->data['user_id'];
		$tabella = $this->follows_table;

		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['musicshare_songs_per_page'];
		$limit = $limit > 0 ? $limit : 25;

		$total = $this->song_repository->count_songs_from_followed($user_id, $tabella);
		$songs = $this->song_repository->get_songs_from_followed($user_id, $tabella, $start, $limit);

		$this->assign_song_list($songs);

		$this->pagination->generate_template_pagination(
			$this->helper->route('salvocortesiano_musicshare_following'),
			'pagination', 'start', $total, $limit, $start
		);

		$this->assign_subnav('following');

		$this->template->assign_vars(array(
			'TOTAL_SONGS'	=> $total,
			'SONGS_SUMMARY'	=> $this->user->lang('MUSICSHARE_SONGS_COUNT', (int) $total),
			'FOLLOWING'		=> $this->follow_repository->count_following($user_id),
		));

		return $this->helper->render('musicshare_following.html', $this->user->lang('MUSICSHARE_FOLLOWING_NEWS'));
	}

	/**
	 * Elenco degli utenti che segui, con la possibilita' di smettere.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function follows()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		if ((int) $this->user->data['user_id'] === ANONYMOUS)
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_FOLLOW_LOGIN');
		}

		$user_id = (int) $this->user->data['user_id'];
		$elenco = $this->follow_repository->get_following($user_id, $this->songs_table);
		// I conteggi delle bacheche si chiedono tutti insieme prima del
		// ciclo: con molti utenti seguiti, una interrogazione per riga
		// si sentirebbe.
		$bacheche = $this->wall_manager->is_enabled()
			? $this->wall_repository->count_for_users(
				array_map('intval', array_column($elenco, 'author_id'))
			)
			: array();

		foreach ($elenco as $riga)
		{
			$autore = (int) $riga['author_id'];

			$this->template->assign_block_vars('follows', array(
				'USERNAME'		=> get_username_string('full', (int) $riga['author_id'], $riga['username'], $riga['user_colour']),
				'SONGS'			=> (int) $riga['songs'],
				'FOLLOW_DATE'	=> $this->user->format_date((int) $riga['follow_time']),
				'U_SONGS'		=> $this->helper->route('salvocortesiano_musicshare_user', array('user_id' => (int) $riga['author_id'])),
				'U_UNFOLLOW'	=> $this->helper->route('salvocortesiano_musicshare_follow', array('author_id' => (int) $riga['author_id']))
					. '?hash=' . generate_link_hash('musicshare_follow'),
				'U_CONTACT'		=> $this->pm_url((int) $riga['author_id'], (int) $riga['user_allow_pm']),
				// la bacheca sta sulla pagina dell'autore: il pulsante
				// ci porta e si ferma sul riquadro
				'U_WALL'		=> $this->wall_manager->is_enabled()
					? $this->helper->route('salvocortesiano_musicshare_user', array('user_id' => $autore)) . '#musicshare-wall'
					: '',
				'WALL_COUNT'	=> isset($bacheche[$autore]) ? $bacheche[$autore] : 0,
			));
		}

		$this->assign_subnav('follows');

		$this->template->assign_vars(array(
			'FOLLOWING'		=> count($elenco),
		));

		return $this->helper->render('musicshare_follows.html', $this->user->lang('MUSICSHARE_FOLLOWS_LIST'));
	}

	public function liked()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		if ((int) $this->user->data['user_id'] === ANONYMOUS)
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_LIKED_LOGIN');
		}

		$user_id = (int) $this->user->data['user_id'];

		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['musicshare_songs_per_page'];
		$limit = $limit > 0 ? $limit : 25;

		$total = $this->song_repository->count_liked_songs($user_id);
		$songs = $this->song_repository->get_liked_songs($user_id, $start, $limit);

		$this->assign_song_list($songs);

		$this->pagination->generate_template_pagination(
			$this->helper->route('salvocortesiano_musicshare_liked'),
			'pagination', 'start', $total, $limit, $start
		);

		$this->assign_subnav('liked');

		$this->template->assign_vars(array(
			'TOTAL_SONGS'	=> $total,
			'SONGS_SUMMARY'	=> $this->user->lang('MUSICSHARE_SONGS_COUNT', (int) $total),
			'U_UPLOAD'		=> $this->ucp_upload_url(),
			'S_LIKED_PAGE'	=> true,
		));

		return $this->helper->render('musicshare_liked.html', $this->user->lang('MUSICSHARE_LIKED'));
	}

	public function genre($genre_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		$genre = $this->genre_repository->get_one($genre_id);

		if (!$genre)
		{
			trigger_error('MUSICSHARE_GENRE_NOT_FOUND', E_USER_WARNING);
		}

		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['musicshare_songs_per_page'];
		$limit = $limit > 0 ? $limit : 25;

		$total = $this->song_repository->count_songs_by_genre($genre_id);
		$songs = $this->song_repository->get_songs_by_genre($genre_id, $start, $limit);

		$this->assign_song_list($songs);

		$base_url = $this->helper->route('salvocortesiano_musicshare_genre', array('genre_id' => $genre_id));
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total, $limit, $start);

		$this->template->assign_vars(array(
			'GENRE_NAME'	=> $genre['genre_name'],
			'TOTAL_SONGS'	=> $total,
			'SONGS_SUMMARY'	=> $this->user->lang('MUSICSHARE_SONGS_COUNT', (int) $total),
			'U_UPLOAD'		=> $this->ucp_upload_url(),
		));

		return $this->helper->render('musicshare_genre.html', $genre['genre_name']);
	}

	public function playlist($playlist_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		$playlist = $this->playlist_repository->get($playlist_id);

		if (!$playlist)
		{
			trigger_error('MUSICSHARE_PLAYLIST_NOT_FOUND', E_USER_WARNING);
		}

		if (!$playlist['is_public'] && (int) $playlist['user_id'] !== (int) $this->user->data['user_id'])
		{
			trigger_error('MUSICSHARE_PLAYLIST_PRIVATE', E_USER_WARNING);
		}

		$songs = $this->playlist_repository->get_songs($playlist_id);
		$this->assign_song_list($songs);

		$this->template->assign_vars(array(
			'PLAYLIST_NAME'	=> $playlist['playlist_name'],
			'PLAYLIST_DESC'	=> $playlist['playlist_desc'],
		));

		return $this->helper->render('musicshare_playlist.html', $playlist['playlist_name']);
	}

	public function upload()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		if (!$this->auth->acl_get('u_musicshare_upload'))
		{
			trigger_error('MUSICSHARE_NO_PERMISSION', E_USER_WARNING);
		}

		// il caricamento si fa dalla scheda dell'UCP: chi arriva qui da un
		// vecchio collegamento o da un segnalibro viene accompagnato lì
		if (!$this->request->is_set_post('submit'))
		{
			return $this->helper->redirect($this->ucp_upload_url());
		}

		add_form_key('musicshare_upload');

		$error = '';
		$success = false;

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('musicshare_upload'))
			{
				$error = $this->user->lang('FORM_INVALID');
			}
			else
			{
				$title = $this->request->variable('song_title', '', true);
				$artist = $this->request->variable('song_artist', '', true);
				$genre_ids = array_map('intval', $this->request->variable('genre_ids', array(0)));

				$result = $this->upload_handler->handle_upload(
					(int) $this->user->data['user_id'],
					$genre_ids,
					$title,
					$artist,
					$this->request->variable('song_album', '', true),
					$this->request->variable('song_year', 0)
				);

				if ($result['success'])
				{
					$success = true;
				}
				else
				{
					$error = $this->user->lang($result['error']);
				}
			}
		}

		foreach ($this->genre_repository->get_all_grouped() as $category => $genres)
		{
			$this->template->assign_block_vars('genre_groups', array(
				'CATEGORY_NAME'	=> $this->genre_translator->category($category),
			));

			foreach ($genres as $genre)
			{
				$this->template->assign_block_vars('genre_groups.genres', array(
					'GENRE_ID'		=> (int) $genre['genre_id'],
					'GENRE_NAME'	=> $genre['genre_name'],
					'S_SELECTED'	=> false,
				));
			}
		}

		$this->template->assign_vars(array(
			'ERROR'				=> $error,
			'SUCCESS'			=> $success,
			'U_ACTION'			=> $this->helper->route('salvocortesiano_musicshare_upload'),
			'ALLOWED_EXT'		=> implode(', ', $this->storage_helper->get_allowed_extensions()),
			'MAX_FILESIZE_MB'	=> round(((int) $this->config['musicshare_max_filesize']) / 1048576, 1),
			'S_DOWNLOAD_ENABLED'	=> !empty($this->config['musicshare_allow_download']),
			'S_DESCRIPTIONS'	=> !isset($this->config['musicshare_descriptions']) || (bool) $this->config['musicshare_descriptions'],
			'S_TOPIC_ENABLED'	=> ($this->topic_creator !== null && $this->topic_creator->is_enabled()),
			'S_SHOW_LICENSE'	=> !isset($this->config['musicshare_show_license']) || (bool) $this->config['musicshare_show_license'],
			'S_SHOW_BPM'		=> !isset($this->config['musicshare_show_bpm']) || (bool) $this->config['musicshare_show_bpm'],
			'EDIT_BPM'			=> '',
			'EDIT_KEY'			=> '',
			'DESCRIPTION_MAX'	=> (int) $this->config['musicshare_description_max'] ?: 300,
		));

		// Licenze e tonalita' suggerite. L'elenco delle licenze non veniva
		// riempito qui: il menu a tendina di questa pagina restava vuoto,
		// mentre nel Pannello di Controllo Utente funzionava.
		foreach ($this->license_helper->get_options('') as $voce)
		{
			$this->template->assign_block_vars('licenses', $voce);
		}

		foreach ($this->license_helper->get_key_options() as $voce)
		{
			$this->template->assign_block_vars('key_options', $voce);
		}

		return $this->helper->render('musicshare_upload.html', $this->user->lang('MUSICSHARE_UPLOAD_TITLE'));
	}

	/**
	 * Indirizzo della scheda di caricamento nel pannello di controllo
	 * utente. La pagina pubblica esisteva ma usava l'impaginazione a due
	 * colonne del forum, molto meno leggibile: i collegamenti puntano
	 * quindi tutti alla scheda dell'UCP.
	 *
	 * @return string
	 */
	/**
	 * Blocca l'accesso diretto alle pagine della sezione Musica a chi non
	 * ha il permesso: nascondere il collegamento non basta, l'indirizzo
	 * resterebbe raggiungibile scrivendolo a mano.
	 *
	 * @return void
	 */
	protected function check_view_permission()
	{
		if (!$this->auth->acl_get('u_musicshare_view'))
		{
			trigger_error('MUSICSHARE_NO_VIEW_PERMISSION', E_USER_WARNING);
		}
	}

	protected function ucp_upload_url()
	{
		return append_sid(
			$this->root_path . 'ucp.' . $this->php_ext,
			'i=-salvocortesiano-musicshare-ucp-main_module&amp;mode=upload'
		);
	}

	/**
	 * Elenco degli utenti che hanno caricato brani, con ricerca,
	 * ordinamento e collegamento alla raccolta di ciascuno.
	 */
	public function uploaders()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		$keywords = $this->request->variable('q', '', true);
		$order = $this->request->variable('o', 'songs');
		$order = in_array($order, array('songs', 'plays', 'recent', 'name'), true) ? $order : 'songs';

		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['musicshare_songs_per_page'];
		$limit = $limit > 0 ? $limit : 25;

		$total = $this->song_repository->count_uploaders($keywords);
		$rows = $this->song_repository->get_uploaders($start, $limit, $keywords, $order);

		// ultimo brano di ciascun utente della pagina, piu' i suoi generi:
		// due interrogazioni in tutto, non due per riga
		$coppie = array();

		foreach ($rows as $row)
		{
			$coppie[(int) $row['user_id']] = (int) $row['last_upload'];
		}

		$ultimi = $this->song_repository->get_last_songs($coppie);
		$generi_ultimi = $this->song_repository->get_genres_for_songs(
			array_map(function ($s) { return (int) $s['song_id']; }, $ultimi)
		);

		// stessa regola dell'elenco dei seguiti: una query per l'intera
		// pagina invece di una per riga
		$bacheche = $this->wall_manager->is_enabled()
			? $this->wall_repository->count_for_users(array_keys($coppie))
			: array();

		foreach ($rows as $row)
		{
			$uid = (int) $row['user_id'];
			$ultimo = isset($ultimi[$uid]) ? $ultimi[$uid] : null;
			$generi = ($ultimo && isset($generi_ultimi[(int) $ultimo['song_id']]))
				? $generi_ultimi[(int) $ultimo['song_id']]
				: array();

			$this->template->assign_block_vars('uploaders', array(
				'S_HAS_LAST'		=> ($ultimo !== null),
				'LAST_TITLE'		=> $ultimo ? (string) $ultimo['song_title'] : '',
				'LAST_ARTIST'		=> $ultimo ? (string) $ultimo['song_artist'] : '',
				'LAST_ALBUM'		=> $ultimo ? (string) $ultimo['song_album'] : '',
				'LAST_GENRES'		=> implode(', ', $generi),
				'U_LAST_SONG'		=> $ultimo
					? $this->helper->route('salvocortesiano_musicshare_user', array('user_id' => $uid))
					: '',
				'USERNAME'		=> get_username_string('full', (int) $row['user_id'], $row['username'], $row['user_colour']),
				'SONGS'			=> (int) $row['songs'],
				'SONGS_TEXT'	=> $this->user->lang('MUSICSHARE_SONGS_COUNT', (int) $row['songs']),
				'PLAYS'			=> (int) $row['plays'],
				'PLAYS_TEXT'	=> $this->user->lang('MUSICSHARE_PLAYS_COUNT', (int) $row['plays']),
				'LIKES'			=> (int) $row['likes'],
				'LAST_UPLOAD'	=> $this->user->format_date((int) $row['last_upload']),
				'U_SONGS'		=> $this->helper->route('salvocortesiano_musicshare_user', array('user_id' => (int) $row['user_id'])),
				'U_CONTACT'		=> $this->pm_url((int) $row['user_id'],
					isset($row['user_allow_pm']) ? (int) $row['user_allow_pm'] : null),
				'U_WALL'		=> $this->wall_manager->is_enabled()
					? $this->helper->route('salvocortesiano_musicshare_user', array('user_id' => $uid)) . '#musicshare-wall'
					: '',
				'WALL_COUNT'	=> isset($bacheche[$uid]) ? $bacheche[$uid] : 0,
			));
		}

		$base = array('q' => $keywords, 'o' => $order);
		$this->pagination->generate_template_pagination(
			$this->helper->route('salvocortesiano_musicshare_uploaders', $base),
			'pagination',
			'start',
			$total,
			$limit,
			$start
		);

		foreach (array('songs', 'plays', 'recent', 'name') as $key)
		{
			$this->template->assign_block_vars('sorts', array(
				'KEY'		=> $key,
				'NAME'		=> $this->user->lang('MUSICSHARE_SORT_' . strtoupper($key)),
				'S_ACTIVE'	=> ($key === $order),
				'U_SORT'	=> $this->helper->route('salvocortesiano_musicshare_uploaders', array('q' => $keywords, 'o' => $key)),
			));
		}

		$this->template->assign_vars(array(
			'SEARCH_QUERY'	=> $keywords,
			'TOTAL_UPLOADERS'	=> $total,
			'UPLOADERS_SUMMARY'	=> $this->user->lang('MUSICSHARE_UPLOADERS_COUNT', (int) $total),
			'U_ACTION'		=> $this->helper->route('salvocortesiano_musicshare_uploaders'),
			'S_HAS_UPLOADERS'	=> !empty($rows),
		));

		return $this->helper->render('musicshare_uploaders.html', $this->user->lang('MUSICSHARE_UPLOADERS'));
	}

	/**
	 * Pagina pubblica con i brani caricati da un utente.
	 */
	public function user_songs($user_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		$user_id = (int) $user_id;

		// L'autore si cerca nella tabella degli utenti, non nella prima
		// riga dei suoi brani: cosi' la pagina esiste anche per chi non
		// ha ancora pubblicato nulla, e chi la guarda puo' comunque
		// seguirlo o scrivergli. Un identificativo inesistente, l'ospite
		// e i motori di ricerca restano un errore.
		$autore = $this->song_repository->get_author($user_id);

		if (!$autore || $user_id === ANONYMOUS || (int) $autore['user_type'] === USER_IGNORE)
		{
			trigger_error('MUSICSHARE_USER_NOT_FOUND', E_USER_WARNING);
		}

		$stats = $this->song_repository->get_user_stats($user_id);

		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['musicshare_songs_per_page'];
		$limit = $limit > 0 ? $limit : 25;

		$songs = $this->song_repository->get_public_songs_by_user($user_id, $start, $limit);
		$this->assign_song_list($songs);

		// "I piu' ascoltati" ha senso solo da due brani in su: con uno
		// solo ripeterebbe la produzione completa riga per riga
		if ($stats['songs'] > 1)
		{
			$piu_ascoltati = $this->song_repository->get_top_songs_by_user($user_id, 5);

			if (!empty($piu_ascoltati))
			{
				$this->assign_song_list($piu_ascoltati, 'top_songs');
			}
		}

		$username = (string) $autore['username'];
		$colore = isset($autore['user_colour']) ? $autore['user_colour'] : '';

		if ($stats['songs'] > 0)
		{
			$this->pagination->generate_template_pagination(
				$this->helper->route('salvocortesiano_musicshare_user', array('user_id' => $user_id)),
				'pagination',
				'start',
				$stats['songs'],
				$limit,
				$start
			);
		}

		$this->assign_subnav('user');

		$segue = ((int) $this->user->data['user_id'] !== ANONYMOUS)
			&& $this->follow_repository->is_following((int) $this->user->data['user_id'], $user_id);

		$this->assign_wall($user_id, $segue);

		$this->template->assign_vars(array(
			'PAGE_USERNAME'		=> get_username_string('full', $user_id, $username, $colore),
			'AUTHOR_AVATAR'		=> $this->author_avatar($autore),
			'U_AUTHOR_PROFILE'	=> get_username_string('profile', $user_id, $username, $colore),
			'AUTHOR_REGDATE'	=> $this->user->format_date((int) $autore['user_regdate'], 'M Y'),
			'U_CONTACT'			=> $this->pm_url($user_id, (int) $autore['user_allow_pm']),
			// seguire l'autore: chi lo fa riceve l'avviso dei suoi nuovi
			// brani anche senza il permesso generale sulle notifiche
			'S_CAN_FOLLOW'	=> !empty($this->config['musicshare_follows_enabled'])
				&& (int) $this->user->data['user_id'] !== ANONYMOUS
				&& (int) $this->user->data['user_id'] !== $user_id,
			'S_FOLLOWING'	=> $segue,
			'U_FOLLOW'		=> $this->helper->route('salvocortesiano_musicshare_follow', array('author_id' => $user_id))
				. '?hash=' . generate_link_hash('musicshare_follow'),
			'STAT_FOLLOWERS'	=> $this->follow_repository->count_followers($user_id),
			'STAT_SONGS'		=> $stats['songs'],
			'STAT_PLAYS'		=> $stats['plays'],
			'STAT_DOWNLOADS'	=> $stats['downloads'],
			'STAT_LIKES'		=> $stats['likes'],
			'STAT_DISLIKES'		=> $stats['dislikes'],
			'STAT_DURATION'		=> $this->format_duration($stats['duration']),
			'S_HAS_SONGS'		=> $stats['songs'] > 0,
			'USER_SUMMARY'	=> $this->user->lang('MUSICSHARE_USER_SUMMARY', $stats['songs'], $stats['plays']),
		));

		return $this->helper->render('musicshare_user.html', $this->user->lang('MUSICSHARE_USER_SONGS', $username));
	}

	/**
	 * Prepara il riquadro della bacheca sulla pagina dell'autore.
	 *
	 * Mostra gli ultimi messaggi dell'argomento in forma di schede piu'
	 * il modulo per scrivere. Non e' un sistema di commenti a parte:
	 * sono messaggi veri del forum, e infatti il pulsante accanto porta
	 * all'argomento completo.
	 *
	 * @param int $author_id
	 * @param bool $segue chi guarda segue gia' l'autore
	 * @return void
	 */
	protected function assign_wall($author_id, $segue)
	{
		$stato = $this->wall_manager->get_context($author_id, $segue);

		if (empty($stato['enabled']))
		{
			$this->template->assign_var('S_WALL', false);

			return;
		}

		$per_pagina = $this->wall_manager->get_preview_count();
		$start = $this->request->variable('cstart', 0);
		$start = $start > 0 ? $start : 0;

		$commenti = $this->wall_repository->get_comments($author_id, $start, $per_pagina);
		$risposte = $this->wall_repository->get_replies(
			array_map(function ($c) { return (int) $c['comment_id']; }, $commenti)
		);

		// reazioni di tutti i commenti e di tutte le risposte della pagina
		// in due sole interrogazioni, non due per scheda
		$ids = array();

		foreach ($commenti as $riga)
		{
			$ids[] = (int) $riga['comment_id'];
		}

		foreach ($risposte as $gruppo)
		{
			foreach ($gruppo as $risposta)
			{
				$ids[] = (int) $risposta['comment_id'];
			}
		}

		$reazioni = $this->wall_repository->get_reactions(
			$ids,
			(int) $this->user->data['user_id']
		);

		foreach ($commenti as $riga)
		{
			$id = (int) $riga['comment_id'];

			$this->template->assign_block_vars('wall_comments', $this->comment_vars($riga, $author_id, $stato, $reazioni));

			if (!empty($risposte[$id]))
			{
				foreach ($risposte[$id] as $risposta)
				{
					$this->template->assign_block_vars('wall_comments.replies',
						$this->comment_vars($risposta, $author_id, $stato, $reazioni));
				}
			}
		}

		$totale = $this->wall_repository->count_comments($author_id);

		if ($totale > $per_pagina)
		{
			// senza ancora: generate_template_pagination attacca cstart in
			// coda, e dopo un'ancora finirebbe dentro il frammento invece
			// che nella parte interrogativa
			$this->pagination->generate_template_pagination(
				$this->helper->route('salvocortesiano_musicshare_user', array('user_id' => (int) $author_id)),
				'wall_pagination',
				'cstart',
				$totale,
				$per_pagina,
				$start
			);
		}

		// La chiave vale per tutti i moduli della bacheca, non solo per
		// quello di scrittura: chi modera puo' eliminare un commento
		// anche dove non gli e' consentito scriverne, e senza chiave il
		// server rifiuterebbe l'invio.
		if ((int) $this->user->data['user_id'] !== ANONYMOUS)
		{
			add_form_key('musicshare_wall');
		}

		$this->template->assign_vars(array(
			'S_WALL'			=> true,
			'S_WALL_COMMENT'	=> !empty($stato['can_comment']),
			'WALL_COUNT'		=> $this->wall_repository->count_all($author_id),
			'WALL_MAXLENGTH'	=> \salvocortesiano\musicshare\service\wall_manager::MAX_LENGTH,
			'U_WALL_POST'		=> $this->helper->route('salvocortesiano_musicshare_wall', array('author_id' => (int) $author_id)),
			'WALL_NOTICE'		=> !empty($stato['motivo']) ? $this->user->lang($stato['motivo']) : '',
		));
	}

	/**
	 * Variabili di una scheda di commento, uguali per i commenti e per
	 * le risposte.
	 *
	 * @param array $riga
	 * @param int $author_id padrone della bacheca
	 * @param array $stato quello di get_context()
	 * @return array
	 */
	protected function comment_vars(array $riga, $author_id, array $stato, array $reazioni = array())
	{
		$id = (int) $riga['comment_id'];
		$gestibile = $this->wall_manager->can_manage($riga, $stato);
		$utente = (int) $this->user->data['user_id'];

		$mie = isset($reazioni[$id]['mine']) ? $reazioni[$id]['mine'] : array();
		$conteggi = isset($reazioni[$id]['counts']) ? $reazioni[$id]['counts'] : array();

		$mi_piace = wall_repository::REACT_LIKE;
		$non_mi_piace = wall_repository::REACT_DISLIKE;
		$cuore = wall_repository::REACT_HEART;

		return array(
			'COMMENT_ID'	=> $id,
			'USERNAME'		=> get_username_string('full', (int) $riga['user_id'], $riga['username'], $riga['user_colour']),
			'AVATAR'		=> $this->author_avatar($riga),
			'COMMENT_DATE'	=> $this->user->format_date((int) $riga['comment_time']),
			'TEXT'			=> $this->wall_manager->render_text($riga),
			// il testo grezzo serve solo a chi puo' modificare: e' quello
			// che finisce dentro il modulo di modifica
			'RAW_TEXT'		=> $gestibile ? $this->wall_manager->render_edit($riga) : '',
			'S_EDITED'		=> ((int) $riga['edit_time'] > 0),
			'EDITED_DATE'	=> (int) $riga['edit_time'] > 0
				? $this->user->format_date((int) $riga['edit_time'])
				: '',
			// il padrone di casa si riconosce a colpo d'occhio
			'S_OWNER'		=> ((int) $riga['user_id'] === (int) $author_id),
			'S_CAN_MANAGE'	=> $gestibile,
			// non si risponde a se stessi: sotto il proprio commento
			// resta solo Modifica
			'S_CAN_REPLY'	=> !empty($stato['can_comment'])
				&& (int) $riga['parent_id'] === 0
				&& (int) $riga['user_id'] !== $utente,
			// i conteggi si vedono sempre, anche da ospiti; a premerli
			// puo' essere solo chi e' collegato e non ha scritto il
			// commento: non si reagisce a quello che si e' scritto
			'S_CAN_REACT'	=> ($utente !== ANONYMOUS && (int) $riga['user_id'] !== $utente),
			'REACT_LIKE'		=> isset($conteggi[$mi_piace]) ? $conteggi[$mi_piace] : 0,
			'REACT_DISLIKE'		=> isset($conteggi[$non_mi_piace]) ? $conteggi[$non_mi_piace] : 0,
			'REACT_HEART'		=> isset($conteggi[$cuore]) ? $conteggi[$cuore] : 0,
			'S_MY_LIKE'			=> in_array($mi_piace, $mie, true),
			'S_MY_DISLIKE'		=> in_array($non_mi_piace, $mie, true),
			'S_MY_HEART'		=> in_array($cuore, $mie, true),
			// una modifica fatta da qualcun altro si dichiara: un
			// moderatore che riscrive le parole di un altro senza che si
			// veda sarebbe peggio del commento che voleva correggere
			'S_EDITED_BY_OTHER'	=> ((int) $riga['edit_time'] > 0
				&& (int) $riga['edit_user'] > 0
				&& (int) $riga['edit_user'] !== (int) $riga['user_id']),
		);
	}

	/**
	 * Scrive, modifica o cancella un commento della bacheca.
	 *
	 * Un solo punto di ingresso per tutte e tre le azioni: i controlli
	 * su chi puo' fare cosa sono gli stessi e non vanno ripetuti in tre
	 * posti diversi, dove finirebbero per divergere.
	 *
	 * @param int $author_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function wall($author_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->check_view_permission();

		$author_id = (int) $author_id;
		$autore = $this->song_repository->get_author($author_id);

		if (!$autore || $author_id === ANONYMOUS || (int) $autore['user_type'] === USER_IGNORE)
		{
			throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_USER_NOT_FOUND');
		}

		if (!check_form_key('musicshare_wall'))
		{
			throw new \phpbb\exception\http_exception(403, 'FORM_INVALID');
		}

		$segue = ((int) $this->user->data['user_id'] !== ANONYMOUS)
			&& $this->follow_repository->is_following((int) $this->user->data['user_id'], $author_id);

		$stato = $this->wall_manager->get_context($author_id, $segue);

		if (empty($stato['enabled']))
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_WALL_OFF');
		}

		$azione = $this->request->variable('wall_action', 'add');
		$comment_id = $this->request->variable('comment_id', 0);
		$testo = $this->request->variable('wall_text', '', true);

		if ($azione === 'edit' || $azione === 'delete')
		{
			$commento = $this->wall_repository->get_comment($comment_id);

			// il commento deve appartenere a questa bacheca: altrimenti
			// basterebbe cambiare un numero nel modulo per toccare i
			// commenti di un'altra pagina
			if (!$commento || (int) $commento['wall_user_id'] !== $author_id)
			{
				throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_WALL_NOT_FOUND');
			}

			if (!$this->wall_manager->can_manage($commento, $stato))
			{
				throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_WALL_NO_PERMISSION');
			}

			if ($azione === 'delete')
			{
				$this->wall_manager->remove($commento);

				return $this->wall_redirect($author_id);
			}

			if (trim($testo) === '')
			{
				throw new \phpbb\exception\http_exception(400, 'MUSICSHARE_WALL_EMPTY_TEXT');
			}

			$this->wall_manager->edit($commento, $testo);

			return $this->wall_redirect($author_id, (int) $commento['comment_id']);
		}

		if (empty($stato['can_comment']))
		{
			$motivo = !empty($stato['motivo']) ? $stato['motivo'] : 'MUSICSHARE_WALL_OFF';

			throw new \phpbb\exception\http_exception(403, $motivo);
		}

		if (trim($testo) === '')
		{
			throw new \phpbb\exception\http_exception(400, 'MUSICSHARE_WALL_EMPTY_TEXT');
		}

		$nuovo = $this->wall_manager->add(
			$author_id,
			$this->request->variable('parent_id', 0),
			$testo,
			$stato
		);

		return $this->wall_redirect($author_id, $nuovo);
	}

	/**
	 * Ritorno alla pagina dell'autore, fermandosi sul commento toccato.
	 *
	 * @param int $author_id
	 * @param int $comment_id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function wall_redirect($author_id, $comment_id = 0)
	{
		$ancora = $comment_id > 0 ? '#musicshare-comment-' . (int) $comment_id : '#musicshare-wall';

		return new \Symfony\Component\HttpFoundation\RedirectResponse(
			$this->helper->route('salvocortesiano_musicshare_user', array('user_id' => (int) $author_id)) . $ancora
		);
	}

	/**
	 * Nome di chi ha caricato, con il collegamento alla sua pagina di
	 * Music Share invece che al profilo del forum.
	 *
	 * Il collegamento si costruisce qui e non nei template perche' il
	 * nome compare in cinque punti diversi: cambiarlo in un posto solo
	 * evita che qualcuno resti indietro.
	 *
	 * @param int $user_id
	 * @param string $username
	 * @param string $colore
	 * @return string
	 */
	protected function uploader_link($user_id, $username, $colore = '')
	{
		$user_id = (int) $user_id;
		// get_username_string si occupa di colore e caratteri speciali;
		// 'no_profile' restituisce il nome senza collegamento, cosi' il
		// nostro non finisce dentro il suo
		$nome = get_username_string('no_profile', $user_id, $username, $colore);

		if ($user_id <= 0 || $user_id === ANONYMOUS)
		{
			return $nome;
		}

		return '<a href="' . $this->helper->route('salvocortesiano_musicshare_user', array('user_id' => $user_id)) . '">' . $nome . '</a>';
	}

	/**
	 * Avatar dell'autore, nella forma gia' pronta di phpBB.
	 *
	 * phpbb_get_user_avatar() sta in includes/functions_display.php, che
	 * non e' fra i file caricati sempre: va incluso a mano, ma solo se
	 * la funzione non c'e' gia', altrimenti una seconda inclusione nella
	 * stessa richiesta fa terminare PHP con un errore fatale.
	 *
	 * @param array $autore riga della tabella utenti
	 * @return string codice dell'immagine, vuoto se non ha avatar
	 */
	protected function author_avatar(array $autore)
	{
		if (!function_exists('phpbb_get_user_avatar'))
		{
			include($this->root_path . 'includes/functions_display.' . $this->php_ext);
		}

		return phpbb_get_user_avatar($autore);
	}

	/**
	 * Durata in ore e minuti. Stessa resa del riquadro nel profilo, per
	 * non mostrare la stessa cifra in due modi diversi.
	 *
	 * @param int $secondi
	 * @return string
	 */
	protected function format_duration($secondi)
	{
		$secondi = (int) $secondi;
		$ore = (int) floor($secondi / 3600);
		$minuti = (int) floor(($secondi % 3600) / 60);

		if ($ore > 0)
		{
			return $this->user->lang('MUSICSHARE_DURATION_HM', $ore, $minuti);
		}

		return $this->user->lang('MUSICSHARE_DURATION_M', $minuti);
	}

	/**
	 * Eliminazione di un brano dalle pagine pubbliche: consentita
	 * all'autore e a chi ha il permesso di moderazione m_musicshare_manage.
	 */
	public function delete_song($song_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');
		$this->template->assign_var('S_MUSICSHARE_PAGE', true);
		$this->check_view_permission();

		$song = $this->song_repository->get_song($song_id);

		if (!$song)
		{
			trigger_error('MUSICSHARE_SONG_NOT_FOUND', E_USER_WARNING);
		}

		$is_owner = ((int) $song['user_id'] === (int) $this->user->data['user_id']);
		$is_moderator = (bool) $this->auth->acl_get('m_musicshare_manage');

		if (!$is_owner && !$is_moderator)
		{
			trigger_error('MUSICSHARE_NO_PERMISSION', E_USER_WARNING);
		}

		if (confirm_box(true))
		{
			$this->storage_helper->delete_song_files($song);
			$this->song_repository->delete_song($song_id);

			$message = $this->user->lang('MUSICSHARE_SONG_DELETED')
				. '<br /><br />' . sprintf(
					'<a href="%1$s">%2$s</a>',
					$this->helper->route('salvocortesiano_musicshare_browse'),
					$this->user->lang('MUSICSHARE_GO_TO_BROWSE')
				);

			trigger_error($message);
		}

		confirm_box(false, $this->user->lang('MUSICSHARE_SONG_DELETE_CONFIRM'), build_hidden_fields([
			'song_id'	=> (int) $song_id,
		]));

		return $this->helper->redirect($this->helper->route('salvocortesiano_musicshare_browse'));
	}

	/**
	 * Apre l'argomento di discussione per un brano già in libreria.
	 *
	 * Puo' farlo soltanto l'autore del brano: phpBB attribuisce il
	 * messaggio all'utente collegato, quindi se lo aprisse un altro
	 * risulterebbe scritto da lui.
	 *
	 * @param int $song_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function publish($song_id)
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		$creator = $this->topic_creator;

		if ($creator === null || !$creator->is_enabled())
		{
			throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_TOPIC_OFF');
		}

		$song = $this->song_repository->get_song((int) $song_id);

		if (!$song)
		{
			throw new \phpbb\exception\http_exception(404, 'MUSICSHARE_SONG_NOT_FOUND');
		}

		if ((int) $song['user_id'] !== (int) $this->user->data['user_id'])
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_TOPIC_NOT_YOURS');
		}

		if (empty($song['song_approved']))
		{
			throw new \phpbb\exception\http_exception(403, 'MUSICSHARE_TOPIC_PENDING');
		}

		if (!empty($song['topic_id']))
		{
			// esiste gia': si va li' invece di aprirne un secondo
			return new \Symfony\Component\HttpFoundation\RedirectResponse(
				append_sid($this->root_path . 'viewtopic.' . $this->php_ext, 't=' . (int) $song['topic_id'])
			);
		}

		$topic_id = $creator->create_for_song($song);

		if (!$topic_id)
		{
			throw new \phpbb\exception\http_exception(500, 'MUSICSHARE_TOPIC_FAILED');
		}

		return new \Symfony\Component\HttpFoundation\RedirectResponse(
			append_sid($this->root_path . 'viewtopic.' . $this->php_ext, 't=' . (int) $topic_id)
		);
	}

	/**
	 * L'utente puo' aprire ora l'argomento per questo brano?
	 *
	 * Serve che la funzione sia attiva, che il brano sia suo e gia'
	 * visibile, e che un argomento non ci sia gia'.
	 *
	 * @param array $song
	 * @return bool
	 */
	protected function can_publish(array $song, $argomento_valido = null)
	{
		// Se non si sa nulla dell'argomento si guarda il solo
		// identificativo; se invece si e' gia' verificato che non esiste
		// piu', il pulsante deve tornare disponibile.
		$ha_argomento = ($argomento_valido === null)
			? !empty($song['topic_id'])
			: (bool) $argomento_valido;

		return $this->topic_creator !== null
			&& $this->topic_creator->is_enabled()
			&& !$ha_argomento
			&& !empty($song['song_approved'])
			&& (int) $song['user_id'] === (int) $this->user->data['user_id'];
	}

	/**
	 * Barra di spostamento fra le pagine personali.
	 *
	 * Il collegamento alla pagina corrente viene omesso: chi ci si trova
	 * gia' non ha bisogno di un pulsante che lo riporti dov'e'. Al suo
	 * posto compare sempre il ritorno alla libreria.
	 *
	 * @param string $corrente liked | following | follows
	 * @return void
	 */
	/**
	 * Indirizzo per scrivere un messaggio privato a un utente.
	 *
	 * Le regole stanno nel servizio condiviso, che le applica allo
	 * stesso modo negli elenchi, nelle righe dei brani e nella
	 * moderazione.
	 *
	 * @param int $destinatario
	 * @param mixed $accetta_pm
	 * @return string
	 */
	protected function pm_url($destinatario, $accetta_pm = null)
	{
		return $this->contact_helper->get_pm_url($destinatario, $accetta_pm);
	}

	protected function assign_subnav($corrente)
	{
		$collegato = ((int) $this->user->data['user_id'] !== ANONYMOUS);
		$segui_attivo = !empty($this->config['musicshare_follows_enabled']);

		$this->template->assign_vars(array(
			'U_BROWSE'			=> $this->helper->route('salvocortesiano_musicshare_browse'),
			'U_LIKED'			=> ($collegato && $corrente !== 'liked')
				? $this->helper->route('salvocortesiano_musicshare_liked') : '',
			'U_FOLLOWING_NEWS'	=> ($collegato && $segui_attivo && $corrente !== 'following')
				? $this->helper->route('salvocortesiano_musicshare_following') : '',
			'U_FOLLOWS_LIST'	=> ($collegato && $segui_attivo && $corrente !== 'follows')
				? $this->helper->route('salvocortesiano_musicshare_follows') : '',
		));
	}

	protected function assign_song_list(array $songs, $block_name = 'songs')
	{
		// Quali argomenti esistono davvero e sono leggibili da chi guarda.
		// Il solo identificativo salvato non basta: l'argomento puo'
		// essere stato cancellato, nascosto o spostato in una sezione
		// che questo utente non puo' leggere.
		$topic_ids = array();

		foreach ($songs as $song)
		{
			if (!empty($song['topic_id']))
			{
				$topic_ids[] = (int) $song['topic_id'];
			}
		}

		$argomenti = $this->song_repository->get_visible_topics($topic_ids);

		foreach ($argomenti as $tid => $fid)
		{
			if (!$this->auth->acl_get('f_read', $fid))
			{
				unset($argomenti[$tid]);
			}
		}

		// generi e voti di tutti i brani della pagina, una query ciascuno
		$song_ids = array_map(function ($s) { return (int) $s['song_id']; }, $songs);
		$genres_map = $this->song_repository->get_genres_for_songs($song_ids);
		$my_votes = $this->song_repository->get_user_votes($song_ids, (int) $this->user->data['user_id']);
		$votes_on = !empty($this->config['musicshare_votes_enabled']);
		$descriptions_on = !isset($this->config['musicshare_descriptions'])
			|| (bool) $this->config['musicshare_descriptions'];

		foreach ($songs as $song)
		{
			$song_genres = isset($genres_map[(int) $song['song_id']]) ? $genres_map[(int) $song['song_id']] : array();
			$cover = $this->storage_helper->get_cover_file($song);
			$has_cover = $cover && is_file($cover);

			$is_owner = ((int) $song['user_id'] === (int) $this->user->data['user_id']);
			$can_manage = $is_owner || $this->auth->acl_get('m_musicshare_manage');

			$this->template->assign_block_vars($block_name, array(
				'SONG_ID'		=> (int) $song['song_id'],
				'S_CAN_MANAGE'	=> (bool) $can_manage,
				'U_DELETE'		=> $can_manage ? $this->helper->route('salvocortesiano_musicshare_delete', array('song_id' => $song['song_id'])) : '',
				'S_CAN_DOWNLOAD'	=> (!empty($this->config['musicshare_allow_download'])
					&& (!isset($song['allow_download']) || $song['allow_download'])),
				'U_DOWNLOAD'	=> $this->helper->route('salvocortesiano_musicshare_download', array('song_id' => $song['song_id'])),
				'TITLE'			=> $song['song_title'],
				'ARTIST'		=> $song['song_artist'],
				'ALBUM'			=> $song['song_album'],
				'YEAR'			=> $song['song_year'] ? (int) $song['song_year'] : '',
				'DURATION'		=> gmdate('i:s', (int) $song['song_duration']),
				'DURATION_RAW'	=> (int) $song['song_duration'],
				'PLAY_COUNT'	=> (int) $song['play_count'],
				'DOWNLOAD_COUNT'	=> isset($song['download_count']) ? (int) $song['download_count'] : 0,
				'PLAY_COUNT_TEXT'	=> $this->user->lang('MUSICSHARE_PLAYS_COUNT', (int) $song['play_count']),
				'UPLOADER'		=> isset($song['username']) ? $this->uploader_link((int) $song['user_id'], $song['username'], isset($song['user_colour']) ? $song['user_colour'] : '') : '',
				'UPLOAD_DATE'	=> $this->user->format_date((int) $song['upload_time']),
				'S_HAS_UPLOADER'	=> isset($song['username']),
				'GENRES'		=> implode(', ', $song_genres),
				'U_TOPIC'		=> (!empty($song['topic_id']) && isset($argomenti[(int) $song['topic_id']]))
					? append_sid($this->root_path . 'viewtopic.' . $this->php_ext, 't=' . (int) $song['topic_id'])
					: '',
				'LICENSE'		=> (!isset($this->config['musicshare_show_license']) || $this->config['musicshare_show_license'])
					? $this->license_helper->label(isset($song['song_license']) ? $song['song_license'] : '') : '',
				'BPM'			=> (!isset($this->config['musicshare_show_bpm']) || $this->config['musicshare_show_bpm'])
					&& !empty($song['song_bpm']) ? (int) $song['song_bpm'] : '',
				'QUALITY'		=> metadata_extractor::quality_label($song, $this->user),
				'SONG_KEY'		=> (!isset($this->config['musicshare_show_bpm']) || $this->config['musicshare_show_bpm'])
					&& !empty($song['song_key']) ? (string) $song['song_key'] : '',
				'U_CONTACT'		=> $this->contact_helper->get_pm_url((int) $song['user_id'],
					isset($song['user_allow_pm']) ? (int) $song['user_allow_pm'] : null,
					(int) $song['song_id']),
				'U_PUBLISH'		=> $this->can_publish($song, isset($argomenti[(int) $song['topic_id']]))
					? $this->helper->route('salvocortesiano_musicshare_publish', array('song_id' => (int) $song['song_id']))
					: '',
				'DESCRIPTION'	=> ($descriptions_on && isset($song['song_description']))
					? (string) $song['song_description'] : '',
				'LIKES'			=> isset($song['song_likes']) ? (int) $song['song_likes'] : 0,
				'DISLIKES'		=> isset($song['song_dislikes']) ? (int) $song['song_dislikes'] : 0,
				'MY_VOTE'		=> isset($my_votes[(int) $song['song_id']]) ? (int) $my_votes[(int) $song['song_id']] : 0,
				'S_CAN_VOTE'	=> ($votes_on && (int) $this->user->data['user_id'] !== ANONYMOUS && !$is_owner),
				'U_STREAM'		=> $this->helper->route('salvocortesiano_musicshare_stream', array('song_id' => $song['song_id'])),
				'U_COVER'		=> $has_cover ? $this->helper->route('salvocortesiano_musicshare_cover', array('song_id' => $song['song_id'])) : '',
				'S_HAS_COVER'	=> $has_cover,
			));
		}
	}
}
