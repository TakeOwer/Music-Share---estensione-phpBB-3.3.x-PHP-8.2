<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\ucp;

class main_module
{
	public $u_action;
	public $page_title;
	public $tpl_name;

	public function main($id, $mode)
	{
		global $phpbb_container, $request, $template, $user, $config;

		$user->add_lang_ext('salvocortesiano/musicshare', 'common');

		// Anche le schede del pannello utente fanno parte della sezione
		// Musica: senza questa riga, con l'impostazione "mostra il lettore
		// solo nelle pagine Musica" il lettore non comparirebbe qui.
		$template->assign_var('S_MUSICSHARE_PAGE', true);

		switch ($mode)
		{
			case 'upload':
				$this->page_title = 'UCP_MUSICSHARE_UPLOAD';
				$this->tpl_name = 'ucp_musicshare_upload';
				$this->handle_upload($phpbb_container, $request, $template, $user, $config);
				break;

			case 'playlists':
				$this->page_title = 'UCP_MUSICSHARE_PLAYLISTS';
				$this->handle_playlists($phpbb_container, $request, $template, $user);
				break;

			default:
				$this->page_title = 'UCP_MUSICSHARE_SONGS';
				$this->tpl_name = 'ucp_musicshare_songs';
				$this->handle_songs($phpbb_container, $request, $template, $user);
				break;
		}
	}

	protected function handle_songs($phpbb_container, $request, $template, $user)
	{
		$song_repository = $phpbb_container->get('salvocortesiano.musicshare.song_repository');
		$storage_helper = $phpbb_container->get('salvocortesiano.musicshare.storage_helper');
		$genre_repository = $phpbb_container->get('salvocortesiano.musicshare.genre_repository');
		$genre_translator = $phpbb_container->get('salvocortesiano.musicshare.genre_translator');
		$controller_helper = $phpbb_container->get('controller.helper');
		$config = $phpbb_container->get('config');

		$user_id = (int) $user->data['user_id'];
		$action = $request->variable('action', '');
		$song_id = $request->variable('song_id', 0);

		if ($action === 'delete' && $song_id)
		{
			$song = $song_repository->get_song($song_id);

			if ($song && (int) $song['user_id'] === $user_id)
			{
				if (confirm_box(true))
				{
					$storage_helper->delete_song_files($song);
					$song_repository->delete_song($song_id);
					trigger_error($user->lang('MUSICSHARE_SONG_DELETED') . '<br /><br />' . sprintf('<a href="%1$s">%2$s</a>', $this->u_action, $user->lang('BACK_TO_PREV')));
				}
				else
				{
					confirm_box(false, $user->lang('MUSICSHARE_SONG_DELETE_CONFIRM'), build_hidden_fields(array(
						'mode'		=> 'songs',
						'action'	=> 'delete',
						'song_id'	=> $song_id,
					)));
				}
			}
		}

		add_form_key('musicshare_song_edit');

		if ($action === 'edit' && $song_id)
		{
			$song = $song_repository->get_song($song_id);

			if (!$song || (int) $song['user_id'] !== $user_id)
			{
				trigger_error('NO_AUTH_OPERATION', E_USER_WARNING);
			}

			if ($request->is_set_post('submit'))
			{
				if (!check_form_key('musicshare_song_edit'))
				{
					trigger_error('FORM_INVALID', E_USER_WARNING);
				}

				$genre_ids = array_map('intval', $request->variable('genre_ids', array(0)));

				$descrizione = trim(strip_tags($request->variable('song_description', '', true)));
				$descrizione = preg_replace('/[\r\n]+/', ' ', $descrizione);
				$max_desc = (int) $config['musicshare_description_max'];
				$max_desc = ($max_desc > 0) ? min(1000, $max_desc) : 300;

				$update = array(
					'song_description'	=> utf8_substr($descrizione, 0, $max_desc),
					'allow_download'	=> $request->variable('allow_download', 0) ? 1 : 0,
					'song_title'	=> $request->variable('song_title', '', true),
					'song_artist'	=> $request->variable('song_artist', '', true),
					'song_album'	=> $request->variable('song_album', '', true),
					'song_year'		=> $request->variable('song_year', 0),
				);

				$upload_handler = $phpbb_container->get('salvocortesiano.musicshare.upload_handler');
				$cover_error = '';

				if ($request->variable('remove_cover', false))
				{
					$upload_handler->remove_cover($song);
					$update['cover_path'] = '';
				}
				else
				{
					$cover_result = $upload_handler->handle_cover_upload($song);

					if ($cover_result['error'])
					{
						$cover_error = $user->lang($cover_result['error']);
					}
					else if ($cover_result['changed'])
					{
						$update['cover_path'] = $cover_result['cover_path'];
					}
				}

				if ($cover_error !== '')
				{
					trigger_error($cover_error . '<br /><br />' . sprintf('<a href="%1$s">%2$s</a>', $this->u_action, $user->lang('BACK_TO_PREV')), E_USER_WARNING);
				}

				$song_repository->update_song($song_id, $update);
				$song_repository->set_genres($song_id, $genre_ids);

				trigger_error($user->lang('MUSICSHARE_SONG_UPDATED') . '<br /><br />' . sprintf('<a href="%1$s">%2$s</a>', $this->u_action, $user->lang('BACK_TO_PREV')));
			}

			$current_genres = array_map(function ($g) { return (int) $g['genre_id']; }, $song_repository->get_song_genres($song_id));

			foreach ($genre_repository->get_all_grouped() as $category => $genres)
			{
				$template->assign_block_vars('genre_groups', array(
					'CATEGORY_NAME'	=> $genre_translator->category($category, 'MUSICSHARE_OTHER_GENRES'),
				));

				foreach ($genres as $genre)
				{
					$template->assign_block_vars('genre_groups.genres', array(
						'GENRE_ID'		=> (int) $genre['genre_id'],
						'GENRE_NAME'	=> $genre['genre_name'],
						'S_SELECTED'	=> in_array((int) $genre['genre_id'], $current_genres, true),
					));
				}
			}

			$edit_storage = $phpbb_container->get('salvocortesiano.musicshare.storage_helper');
			$edit_helper = $phpbb_container->get('controller.helper');
			$edit_cover = $edit_storage->get_cover_file($song);
			$edit_has_cover = $edit_cover && is_file($edit_cover);

			$template->assign_vars(array(
				'S_EDIT'		=> true,
				'S_HAS_COVER'	=> $edit_has_cover,
				'U_COVER'		=> $edit_has_cover ? $edit_helper->route('salvocortesiano_musicshare_cover', array('song_id' => $song['song_id'], 't' => time())) : '',
				'EDIT_SONG_ID'	=> (int) $song['song_id'],
				'EDIT_TITLE'	=> $song['song_title'],
				'EDIT_ARTIST'	=> $song['song_artist'],
				'EDIT_ALBUM'	=> $song['song_album'],
				'EDIT_YEAR'		=> $song['song_year'],
				'EDIT_DESCRIPTION'	=> isset($song['song_description']) ? (string) $song['song_description'] : '',
				'S_ALLOW_DOWNLOAD'	=> (!isset($song['allow_download']) || $song['allow_download']),
				// l'autore scarica sempre i propri brani, anche quelli per
				// cui ha vietato il download agli altri
				'S_CAN_DOWNLOAD'	=> !empty($config['musicshare_allow_download']),
				'U_DOWNLOAD'		=> $controller_helper->route('salvocortesiano_musicshare_download', array('song_id' => $song['song_id'])),
				'S_DOWNLOAD_ENABLED'	=> !empty($config['musicshare_allow_download']),
			'S_DESCRIPTIONS'	=> !isset($config['musicshare_descriptions']) || (bool) $config['musicshare_descriptions'],
			'S_BBCODE_ENABLED'	=> !isset($config['musicshare_bbcode']) || (bool) $config['musicshare_bbcode'],
			'DESCRIPTION_MAX'	=> (int) $config['musicshare_description_max'] ?: 300,
				'U_ACTION'		=> $this->u_action . '&action=edit&song_id=' . $song_id,
			));

			$this->tpl_name = 'ucp_musicshare_song_edit';

			return;
		}

		$songs = $song_repository->get_songs_by_user($user_id);
		$used_space = $song_repository->get_user_total_size($user_id);

		$storage_helper = $phpbb_container->get('salvocortesiano.musicshare.storage_helper');

		// generi di tutti i brani in una sola query
		$song_ids = array_map(function ($s) { return (int) $s['song_id']; }, $songs);
		$genres_map = $song_repository->get_genres_for_songs($song_ids);

		foreach ($songs as $song)
		{
			$cover = $storage_helper->get_cover_file($song);
			$has_cover = $cover && is_file($cover);
			$song_genres = isset($genres_map[(int) $song['song_id']]) ? $genres_map[(int) $song['song_id']] : array();

			$template->assign_block_vars('songs', array(
				'GENRES'		=> implode(', ', $song_genres),
				'DESCRIPTION'	=> (!isset($config['musicshare_descriptions']) || $config['musicshare_descriptions'])
					&& isset($song['song_description']) ? (string) $song['song_description'] : '',
				'UPLOAD_DATE'	=> $user->format_date((int) $song['upload_time']),
				'LIKES'			=> isset($song['song_likes']) ? (int) $song['song_likes'] : 0,
				'DISLIKES'		=> isset($song['song_dislikes']) ? (int) $song['song_dislikes'] : 0,
				'MY_VOTE'		=> 0,
				// i propri brani non si votano
				'S_CAN_VOTE'	=> false,
				'S_ALLOW_DOWNLOAD'	=> (!isset($song['allow_download']) || $song['allow_download']),
				// l'autore scarica sempre i propri brani, anche quelli per
				// cui ha vietato il download agli altri
				'S_CAN_DOWNLOAD'	=> !empty($config['musicshare_allow_download']),
				'U_DOWNLOAD'		=> $controller_helper->route('salvocortesiano_musicshare_download', array('song_id' => $song['song_id'])),
				'SONG_ID'		=> (int) $song['song_id'],
				'TITLE'			=> $song['song_title'],
				'ARTIST'		=> $song['song_artist'],
				'ALBUM'			=> $song['song_album'],
				'YEAR'			=> $song['song_year'] ? (int) $song['song_year'] : '',
				'DURATION'		=> gmdate('i:s', (int) $song['song_duration']),
				'APPROVED'		=> (bool) $song['song_approved'],
				'FILE_SIZE_MB'	=> round($song['file_size'] / 1048576, 2),
				'PLAY_COUNT'		=> (int) $song['play_count'],
				'PLAY_COUNT_TEXT'	=> $user->lang('MUSICSHARE_PLAYS_COUNT', (int) $song['play_count']),
				'U_STREAM'		=> $controller_helper->route('salvocortesiano_musicshare_stream', array('song_id' => $song['song_id'])),
				'U_COVER'		=> $has_cover ? $controller_helper->route('salvocortesiano_musicshare_cover', array('song_id' => $song['song_id'])) : '',
				'S_HAS_COVER'	=> $has_cover,
				'U_EDIT'		=> $this->u_action . '&amp;action=edit&amp;song_id=' . $song['song_id'],
				'U_DELETE'		=> $this->u_action . '&amp;action=delete&amp;song_id=' . $song['song_id'],
			));
		}

		global $config;
		$quota = (int) $config['musicshare_max_user_space'];
		$percent = ($quota > 0) ? min(100, round(($used_space / $quota) * 100)) : 0;

		$template->assign_vars(array(
			'U_ACTION'		=> $this->u_action,
			'USED_SPACE_MB'	=> round($used_space / 1048576, 2),
			'QUOTA_MB'		=> $quota > 0 ? round($quota / 1048576, 0) : 0,
			'QUOTA_PERCENT'	=> $percent,
			'S_HAS_QUOTA'	=> $quota > 0,
			'S_QUOTA_FULL'	=> $percent >= 90,
			'U_MUSICSHARE_BROWSE'	=> $controller_helper->route('salvocortesiano_musicshare_browse'),
			// serve al pulsante che copia il codice per i messaggi:
			// prima veniva assegnata solo nel ramo della modifica, quindi
			// nell'elenco il pulsante non compariva mai
			'S_BBCODE_ENABLED'	=> !isset($config['musicshare_bbcode']) || (bool) $config['musicshare_bbcode'],
		));
	}

	protected function handle_upload($phpbb_container, $request, $template, $user, $config)
	{
		$upload_handler = $phpbb_container->get('salvocortesiano.musicshare.upload_handler');
		$genre_repository = $phpbb_container->get('salvocortesiano.musicshare.genre_repository');
		$genre_translator = $phpbb_container->get('salvocortesiano.musicshare.genre_translator');
		$storage_helper = $phpbb_container->get('salvocortesiano.musicshare.storage_helper');

		add_form_key('musicshare_upload');

		$error = '';
		$success = false;

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('musicshare_upload'))
			{
				$error = $user->lang('FORM_INVALID');
			}
			else
			{
				$title = $request->variable('song_title', '', true);
				$artist = $request->variable('song_artist', '', true);
				$genre_ids = array_map('intval', $request->variable('genre_ids', array(0)));

				$result = $upload_handler->handle_upload(
					(int) $user->data['user_id'],
					$genre_ids,
					$title,
					$artist,
					$request->variable('song_album', '', true),
					$request->variable('song_year', 0)
				);

				if ($result['success'])
				{
					$success = true;
				}
				else
				{
					$error = $user->lang($result['error']);
				}
			}
		}

		foreach ($genre_repository->get_all_grouped() as $category => $genres)
		{
			$template->assign_block_vars('genre_groups', array(
				'CATEGORY_NAME'	=> $genre_translator->category($category, 'MUSICSHARE_OTHER_GENRES'),
			));

			foreach ($genres as $genre)
			{
				$template->assign_block_vars('genre_groups.genres', array(
					'GENRE_ID'		=> (int) $genre['genre_id'],
					'GENRE_NAME'	=> $genre['genre_name'],
					'S_SELECTED'	=> false,
				));
			}
		}

		$template->assign_vars(array(
			'ERROR'				=> $error,
			'SUCCESS'			=> $success,
			'U_ACTION'			=> $this->u_action,
			'ALLOWED_EXT'		=> implode(', ', $storage_helper->get_allowed_extensions()),
			'MAX_FILESIZE_MB'	=> round(((int) $config['musicshare_max_filesize']) / 1048576, 1),
			'S_DOWNLOAD_ENABLED'	=> !empty($config['musicshare_allow_download']),
			'S_DESCRIPTIONS'	=> !isset($config['musicshare_descriptions']) || (bool) $config['musicshare_descriptions'],
			'S_BBCODE_ENABLED'	=> !isset($config['musicshare_bbcode']) || (bool) $config['musicshare_bbcode'],
			'DESCRIPTION_MAX'	=> (int) $config['musicshare_description_max'] ?: 300,
		));
	}

	protected function handle_playlists($phpbb_container, $request, $template, $user)
	{
		$playlist_repository = $phpbb_container->get('salvocortesiano.musicshare.playlist_repository');
		$song_repository = $phpbb_container->get('salvocortesiano.musicshare.song_repository');

		$user_id = (int) $user->data['user_id'];
		$action = $request->variable('action', '');
		$playlist_id = $request->variable('playlist_id', 0);
		$show_form = $request->variable('form', false);

		add_form_key('musicshare_playlist');

		if ($action === 'delete' && $playlist_id)
		{
			$playlist = $playlist_repository->get($playlist_id);

			if ($playlist && (int) $playlist['user_id'] === $user_id)
			{
				if (confirm_box(true))
				{
					$playlist_repository->delete($playlist_id);
					trigger_error($user->lang('MUSICSHARE_PLAYLIST_DELETED') . '<br /><br />' . sprintf('<a href="%1$s">%2$s</a>', $this->u_action, $user->lang('BACK_TO_PREV')));
				}
				else
				{
					confirm_box(false, $user->lang('MUSICSHARE_PLAYLIST_DELETE_CONFIRM'), build_hidden_fields(array(
						'mode'			=> 'playlists',
						'action'		=> 'delete',
						'playlist_id'	=> $playlist_id,
					)));
				}
			}
		}

		if (($action === 'add' || $action === 'edit') && $request->is_set_post('submit'))
		{
			if (!check_form_key('musicshare_playlist'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$name = $request->variable('playlist_name', '', true);
			$desc = $request->variable('playlist_desc', '', true);
			$is_public = $request->variable('is_public', false);

			if ($action === 'edit' && $playlist_id)
			{
				$playlist = $playlist_repository->get($playlist_id);
				if ($playlist && (int) $playlist['user_id'] === $user_id)
				{
					$playlist_repository->edit($playlist_id, $name, $desc, $is_public);
				}
			}
			else
			{
				$playlist_repository->add($user_id, $name, $desc, $is_public);
			}

			trigger_error($user->lang('MUSICSHARE_PLAYLIST_SAVED') . '<br /><br />' . sprintf('<a href="%1$s">%2$s</a>', $this->u_action, $user->lang('BACK_TO_PREV')));
		}

		if ($action === 'manage' && $playlist_id)
		{
			$playlist = $playlist_repository->get($playlist_id);

			if (!$playlist || (int) $playlist['user_id'] !== $user_id)
			{
				trigger_error('NO_AUTH_OPERATION', E_USER_WARNING);
			}

			$remove_song = $request->variable('remove_song', 0);
			if ($remove_song)
			{
				$playlist_repository->remove_song($playlist_id, $remove_song);
			}

			$add_song = $request->variable('add_song', 0);
			if ($add_song)
			{
				$playlist_repository->add_song($playlist_id, $add_song);
			}

			$playlist_song_ids = array();
			foreach ($playlist_repository->get_songs($playlist_id) as $song)
			{
				$playlist_song_ids[] = (int) $song['song_id'];

				$template->assign_block_vars('playlist_songs', array(
					'SONG_ID'	=> (int) $song['song_id'],
					'TITLE'		=> $song['song_title'],
					'ARTIST'	=> $song['song_artist'],
				));
			}

			foreach ($song_repository->get_songs_by_user($user_id) as $song)
			{
				if (in_array((int) $song['song_id'], $playlist_song_ids, true))
				{
					continue;
				}

				$template->assign_block_vars('my_songs', array(
					'SONG_ID'	=> (int) $song['song_id'],
					'TITLE'		=> $song['song_title'],
					'ARTIST'	=> $song['song_artist'],
				));
			}

			$template->assign_vars(array(
				'PLAYLIST_ID'	=> (int) $playlist['playlist_id'],
				'PLAYLIST_NAME'	=> $playlist['playlist_name'],
				'U_ACTION'		=> $this->u_action . '&action=manage&playlist_id=' . $playlist_id,
			));

			$this->tpl_name = 'ucp_musicshare_playlist_manage';

			return;
		}

		if ($show_form)
		{
			$edit_playlist = false;
			if ($action === 'edit' && $playlist_id)
			{
				$edit_playlist = $playlist_repository->get($playlist_id);
			}

			$template->assign_vars(array(
				'S_EDIT'			=> (bool) $edit_playlist,
				'EDIT_PLAYLIST_ID'	=> $edit_playlist ? (int) $edit_playlist['playlist_id'] : 0,
				'EDIT_NAME'			=> $edit_playlist ? $edit_playlist['playlist_name'] : '',
				'EDIT_DESC'			=> $edit_playlist ? $edit_playlist['playlist_desc'] : '',
				'EDIT_PUBLIC'		=> $edit_playlist ? (bool) $edit_playlist['is_public'] : false,
				'U_ACTION'			=> $this->u_action . '&action=' . ($edit_playlist ? 'edit' : 'add') . ($playlist_id ? '&playlist_id=' . $playlist_id : ''),
			));

			$this->tpl_name = 'ucp_musicshare_playlist_edit';

			return;
		}

		$this->tpl_name = 'ucp_musicshare_playlists';

		foreach ($playlist_repository->get_by_user($user_id) as $playlist)
		{
			$template->assign_block_vars('playlists', array(
				'PLAYLIST_ID'	=> (int) $playlist['playlist_id'],
				'NAME'			=> $playlist['playlist_name'],
				'IS_PUBLIC'		=> (bool) $playlist['is_public'],
				'SONG_COUNT'	=> count($playlist_repository->get_songs($playlist['playlist_id'])),
				'U_MANAGE'		=> $this->u_action . '&action=manage&playlist_id=' . $playlist['playlist_id'],
				'U_EDIT'		=> $this->u_action . '&action=edit&form=1&playlist_id=' . $playlist['playlist_id'],
				'U_DELETE'		=> $this->u_action . '&action=delete&playlist_id=' . $playlist['playlist_id'],
			));
		}

		$template->assign_vars(array(
			'U_ACTION'	=> $this->u_action,
			'U_ADD'		=> $this->u_action . '&action=add&form=1',
		));
	}
}
