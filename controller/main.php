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

		$top_songs = $this->song_repository->get_top_songs(10);
		$this->assign_song_list($top_songs, 'top_songs');

		$this->template->assign_vars(array(
			'U_UPLOAD'		=> $this->ucp_upload_url(),
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
			'DESCRIPTION_MAX'	=> (int) $this->config['musicshare_description_max'] ?: 300,
		));

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

		$stats = $this->song_repository->get_user_stats($user_id);

		if (!$stats['songs'])
		{
			trigger_error('MUSICSHARE_NO_SONGS', E_USER_NOTICE);
		}

		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['musicshare_songs_per_page'];
		$limit = $limit > 0 ? $limit : 25;

		$songs = $this->song_repository->get_public_songs_by_user($user_id, $start, $limit);
		$this->assign_song_list($songs);

		$username = !empty($songs[0]['username']) ? $songs[0]['username'] : '';

		$this->pagination->generate_template_pagination(
			$this->helper->route('salvocortesiano_musicshare_user', array('user_id' => $user_id)),
			'pagination',
			'start',
			$stats['songs'],
			$limit,
			$start
		);

		$this->template->assign_vars(array(
			'PAGE_USERNAME'	=> $username,
			'USER_SUMMARY'	=> $this->user->lang('MUSICSHARE_USER_SUMMARY', $stats['songs'], $stats['plays']),
		));

		return $this->helper->render('musicshare_user.html', $this->user->lang('MUSICSHARE_USER_SONGS', $username));
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

	protected function assign_song_list(array $songs, $block_name = 'songs')
	{
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
				'PLAY_COUNT_TEXT'	=> $this->user->lang('MUSICSHARE_PLAYS_COUNT', (int) $song['play_count']),
				'UPLOADER'		=> isset($song['username']) ? get_username_string('full', (int) $song['user_id'], $song['username'], isset($song['user_colour']) ? $song['user_colour'] : '') : '',
				'UPLOAD_DATE'	=> $this->user->format_date((int) $song['upload_time']),
				'S_HAS_UPLOADER'	=> isset($song['username']),
				'GENRES'		=> implode(', ', $song_genres),
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
