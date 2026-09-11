<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\acp;

class main_module
{
	public $u_action;
	public $page_title;
	public $tpl_name;

	public function main($id, $mode)
	{
		global $phpbb_container, $request, $template, $user, $config;

		$user->add_lang_ext('salvocortesiano/musicshare', ['info_acp_musicshare', 'common']);

		// distintivi con versione, requisiti e licenza in cima a ogni scheda
		$template->assign_vars($this->identity_vars($phpbb_container, $config));

		if ($mode === 'genres')
		{
			$this->page_title = 'ACP_MUSICSHARE_GENRES';
			$this->tpl_name = 'musicshare_genres';
			$this->handle_genres($phpbb_container, $request, $template, $user);
		}
		else if ($mode === 'groups')
		{
			$this->page_title = 'ACP_MUSICSHARE_GROUPS';
			$this->tpl_name = 'musicshare_groups';
			$this->handle_groups($phpbb_container, $request, $template, $user);
		}
		else if ($mode === 'moderate')
		{
			$this->page_title = 'ACP_MUSICSHARE_MODERATE';
			$this->tpl_name = 'musicshare_moderate';
			$this->handle_moderate($phpbb_container, $request, $template, $user);
		}
		else if ($mode === 'maintenance')
		{
			$this->page_title = 'ACP_MUSICSHARE_MAINTENANCE';
			$this->tpl_name = 'musicshare_maintenance';
			$this->handle_maintenance($phpbb_container, $request, $template, $user, $config);
		}
		else if ($mode === 'recognition')
		{
			$this->page_title = 'ACP_MUSICSHARE_RECOGNITION';
			$this->tpl_name = 'musicshare_recognition';
			$this->handle_recognition($phpbb_container, $request, $template, $user, $config);
		}
		else
		{
			$this->page_title = 'ACP_MUSICSHARE_SETTINGS';
			$this->tpl_name = 'musicshare_settings';
			$this->handle_settings($phpbb_container, $request, $template, $user, $config);
		}
	}

	protected function handle_genres($phpbb_container, $request, $template, $user)
	{
		$genre_repository = $phpbb_container->get('salvocortesiano.musicshare.genre_repository');
		$genre_translator = $phpbb_container->get('salvocortesiano.musicshare.genre_translator');

		add_form_key('musicshare_genres');

		$action = $request->variable('action', '');
		$genre_id = $request->variable('genre_id', 0);

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('musicshare_genres'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$name = $request->variable('genre_name', '', true);
			$category = $request->variable('genre_category', '', true);
			$order = $request->variable('genre_order', 0);

			if ($name === '')
			{
				trigger_error($user->lang('MUSICSHARE_GENRE_NAME_EMPTY') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if ($genre_id)
			{
				$genre_repository->edit($genre_id, $name, $category, $order);
				trigger_error($user->lang('MUSICSHARE_GENRE_UPDATED') . adm_back_link($this->u_action));
			}
			else
			{
				$genre_repository->add($name, $category, $order);
				trigger_error($user->lang('MUSICSHARE_GENRE_ADDED') . adm_back_link($this->u_action));
			}
		}

		if ($action === 'delete' && $genre_id)
		{
			if (confirm_box(true))
			{
				$genre_repository->delete($genre_id);
				trigger_error($user->lang('MUSICSHARE_GENRE_DELETED') . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang('MUSICSHARE_GENRE_DELETE_CONFIRM'), build_hidden_fields(array(
					'i'			=> $request->variable('i', ''),
					'mode'		=> 'genres',
					'action'	=> 'delete',
					'genre_id'	=> $genre_id,
				)));
			}
		}

		$edit_genre = false;
		if ($action === 'edit' && $genre_id)
		{
			$edit_genre = $genre_repository->get_one($genre_id);
		}

		foreach ($genre_repository->get_all_grouped() as $category => $genres)
		{
			$template->assign_block_vars('categories', [
				'CATEGORY_NAME'	=> $genre_translator->category($category, 'MUSICSHARE_NO_CATEGORY'),
				'GENRE_COUNT'	=> count($genres),
			]);

			foreach ($genres as $genre)
			{
				$template->assign_block_vars('categories.genres', [
					'GENRE_ID'		=> (int) $genre['genre_id'],
					'GENRE_NAME'	=> $genre['genre_name'],
					'GENRE_ORDER'	=> (int) $genre['genre_order'],
					'U_EDIT'		=> $this->u_action . '&amp;action=edit&amp;genre_id=' . $genre['genre_id'],
					'U_DELETE'		=> $this->u_action . '&amp;action=delete&amp;genre_id=' . $genre['genre_id'],
				]);
			}
		}

		// Categorie esistenti, proposte come suggerimenti nel form
		foreach ($genre_repository->get_categories() as $category)
		{
			$template->assign_block_vars('category_suggestions', [
				'NAME'	=> $category,
			]);
		}

		$template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			'S_EDIT'				=> (bool) $edit_genre,
			'EDIT_GENRE_ID'			=> $edit_genre ? (int) $edit_genre['genre_id'] : 0,
			'EDIT_GENRE_NAME'		=> $edit_genre ? $edit_genre['genre_name'] : '',
			'EDIT_GENRE_CATEGORY'	=> $edit_genre ? $edit_genre['genre_category'] : '',
			'EDIT_GENRE_ORDER'		=> $edit_genre ? (int) $edit_genre['genre_order'] : 0,
		));
	}

	/**
	 * Pannello di comodo per assegnare rapidamente ai gruppi i permessi
	 * di caricamento brani e creazione playlist. Scrive negli stessi
	 * permessi ACL di phpBB (u_musicshare_upload, u_musicshare_playlist),
	 * quindi resta tutto coerente con ACP -> Permessi.
	 */
	/**
	 * Permessi dell'estensione attualmente assegnati a ciascun gruppo.
	 *
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param array $permissions
	 * @return array group_id => array(opzione => valore)
	 */
	protected function get_group_permissions($db, array $permissions)
	{
		$sql = 'SELECT ao.auth_option, ag.group_id, ag.auth_setting
			FROM ' . ACL_OPTIONS_TABLE . ' ao, ' . ACL_GROUPS_TABLE . ' ag
			WHERE ag.auth_option_id = ao.auth_option_id
				AND ag.forum_id = 0
				AND ' . $db->sql_in_set('ao.auth_option', $permissions);
		$result = $db->sql_query($sql);

		$out = array();

		while ($row = $db->sql_fetchrow($result))
		{
			$out[(int) $row['group_id']][$row['auth_option']] = (int) $row['auth_setting'];
		}
		$db->sql_freeresult($result);

		// i permessi ereditati da un ruolo non stanno nella tabella dei
		// gruppi: vanno letti dal ruolo assegnato
		$sql = 'SELECT ag.group_id, ao.auth_option, ard.auth_setting
			FROM ' . ACL_GROUPS_TABLE . ' ag, ' . ACL_ROLES_DATA_TABLE . ' ard, ' . ACL_OPTIONS_TABLE . ' ao
			WHERE ag.auth_role_id <> 0
				AND ag.forum_id = 0
				AND ard.role_id = ag.auth_role_id
				AND ao.auth_option_id = ard.auth_option_id
				AND ' . $db->sql_in_set('ao.auth_option', $permissions);
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$gid = (int) $row['group_id'];

			// un valore assegnato direttamente al gruppo ha la precedenza
			if (!isset($out[$gid][$row['auth_option']]))
			{
				$out[$gid][$row['auth_option']] = (int) $row['auth_setting'];
			}
		}
		$db->sql_freeresult($result);

		return $out;
	}

	/**
	 * Identificativo del ruolo dei permessi utente assegnato a un gruppo.
	 *
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param int $group_id
	 * @return int 0 se il gruppo non usa alcun ruolo
	 */
	protected function get_user_role_id($db, $group_id)
	{
		$sql = 'SELECT ag.auth_role_id
			FROM ' . ACL_GROUPS_TABLE . ' ag, ' . ACL_ROLES_TABLE . ' ar
			WHERE ag.group_id = ' . (int) $group_id . '
				AND ag.forum_id = 0
				AND ag.auth_role_id <> 0
				AND ar.role_id = ag.auth_role_id
				AND ar.role_type = ' . "'u_'";
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return $row ? (int) $row['auth_role_id'] : 0;
	}

	/**
	 * Nomi leggibili dei ruoli indicati.
	 *
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param array $role_ids
	 * @return string
	 */
	protected function get_role_names($db, array $role_ids)
	{
		if (empty($role_ids))
		{
			return '';
		}

		global $user;

		$sql = 'SELECT role_name FROM ' . ACL_ROLES_TABLE . '
			WHERE ' . $db->sql_in_set('role_id', array_map('intval', $role_ids));
		$result = $db->sql_query($sql);

		$nomi = array();

		while ($row = $db->sql_fetchrow($result))
		{
			// i ruoli predefiniti hanno il nome come chiave di lingua
			$nomi[] = isset($user->lang[$row['role_name']])
				? $user->lang[$row['role_name']]
				: $row['role_name'];
		}
		$db->sql_freeresult($result);

		return implode(', ', $nomi);
	}

	protected function handle_groups($phpbb_container, $request, $template, $user)
	{
		global $db, $auth, $phpbb_root_path, $phpEx;

		if (!class_exists('auth_admin'))
		{
			include($phpbb_root_path . 'includes/acp/auth.' . $phpEx);
		}

		$auth_admin = new \auth_admin();

		$permissions = ['u_musicshare_view', 'u_musicshare_feed', 'u_musicshare_notify', 'u_musicshare_upload', 'u_musicshare_playlist'];

		add_form_key('musicshare_groups');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('musicshare_groups'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$can_view = array_map('intval', $request->variable('can_view', [0]));
			$can_feed = array_map('intval', $request->variable('can_feed', [0]));
			$can_notify = array_map('intval', $request->variable('can_notify', [0]));
			$can_upload = array_map('intval', $request->variable('can_upload', [0]));
			$can_playlist = array_map('intval', $request->variable('can_playlist', [0]));

			// Stato attuale, per intervenire SOLO sui gruppi cambiati.
			//
			// acl_set() cancella l'eventuale ruolo assegnato al gruppo per
			// i permessi utente: scorrere tutti i gruppi a ogni
			// salvataggio azzerava i ruoli dell'intero forum, compresi
			// quelli dei gruppi che l'amministratore non aveva toccato.
			$attuali = $this->get_group_permissions($db, $permissions);

			$modificati = 0;
			$ruoli_toccati = array();

			$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE;
			$result = $db->sql_query($sql);
			$gruppi = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);

			foreach ($gruppi as $row)
			{
				$group_id = (int) $row['group_id'];

				$voluto = array(
					'u_musicshare_view'		=> in_array($group_id, $can_view, true) ? ACL_YES : ACL_NO,
					'u_musicshare_feed'		=> in_array($group_id, $can_feed, true) ? ACL_YES : ACL_NO,
					'u_musicshare_notify'	=> in_array($group_id, $can_notify, true) ? ACL_YES : ACL_NO,
					'u_musicshare_upload'	=> in_array($group_id, $can_upload, true) ? ACL_YES : ACL_NO,
					'u_musicshare_playlist'	=> in_array($group_id, $can_playlist, true) ? ACL_YES : ACL_NO,
				);

				$cambiato = false;

				foreach ($voluto as $opzione => $valore)
				{
					$prima = isset($attuali[$group_id][$opzione]) ? (int) $attuali[$group_id][$opzione] : ACL_NO;

					if ($prima !== (int) $valore)
					{
						$cambiato = true;
						break;
					}
				}

				if (!$cambiato)
				{
					continue;
				}

				$modificati++;

				// Se il gruppo usa un ruolo per i permessi utente, la
				// modifica va applicata AL RUOLO: e' quanto fa phpBB
				// stesso nelle proprie migrazioni, ed e' l'unico modo di
				// non distruggere l'assegnazione del ruolo.
				$role_id = $this->get_user_role_id($db, $group_id);

				if ($role_id)
				{
					$auth_admin->acl_set_role($role_id, $voluto);
					$ruoli_toccati[$role_id] = true;
				}
				else
				{
					$auth_admin->acl_set('group', 0, $group_id, $voluto);
				}
			}

			$auth->acl_clear_prefetch();
			$phpbb_container->get('cache.driver')->purge();

			$messaggio = $user->lang('MUSICSHARE_GROUPS_UPDATED');

			if ($modificati === 0)
			{
				$messaggio = $user->lang('MUSICSHARE_GROUPS_UNCHANGED');
			}
			else if (!empty($ruoli_toccati))
			{
				// va detto: un ruolo e' condiviso, la modifica vale per
				// tutti i gruppi che lo usano
				$messaggio .= '<br /><br />' . $user->lang(
					'MUSICSHARE_GROUPS_ROLES_TOUCHED',
					$this->get_role_names($db, array_keys($ruoli_toccati))
				);
			}

			trigger_error($messaggio . adm_back_link($this->u_action));
		}

		// Impostazioni attuali, ruoli compresi: un gruppo che eredita il
		// permesso da un ruolo deve risultare spuntato, altrimenti la
		// tabella mostrerebbe caselle vuote e il primo salvataggio
		// toglierebbe permessi che l'amministratore vedeva concessi.
		$current = $this->get_group_permissions($db, $permissions);

		$sql = 'SELECT group_id, group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
			ORDER BY group_type DESC, group_name ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$group_id = (int) $row['group_id'];
			$settings = isset($current[$group_id]) ? $current[$group_id] : [];

			$colour = trim((string) $row['group_colour']);

			$template->assign_block_vars('groups', [
				'GROUP_ID'		=> $group_id,
				'GROUP_NAME'	=> ($row['group_type'] == GROUP_SPECIAL)
					? $user->lang('G_' . $row['group_name'])
					: $row['group_name'],
				'S_SPECIAL'		=> ($row['group_type'] == GROUP_SPECIAL),
				// il colore arriva dal database: si accettano solo cifre
				// esadecimali, così non può finire altro dentro lo stile
				'GROUP_COLOUR'	=> preg_match('/^([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $colour) ? '#' . $colour : '',
				'S_CAN_VIEW'	=> isset($settings['u_musicshare_view']) && $settings['u_musicshare_view'] === ACL_YES,
				'S_CAN_FEED'	=> isset($settings['u_musicshare_feed']) && $settings['u_musicshare_feed'] === ACL_YES,
				'S_CAN_NOTIFY'	=> isset($settings['u_musicshare_notify']) && $settings['u_musicshare_notify'] === ACL_YES,
				'S_CAN_UPLOAD'	=> isset($settings['u_musicshare_upload']) && $settings['u_musicshare_upload'] === ACL_YES,
				'S_CAN_PLAYLIST'	=> isset($settings['u_musicshare_playlist']) && $settings['u_musicshare_playlist'] === ACL_YES,
			]);
		}
		$db->sql_freeresult($result);

		$template->assign_vars([
			'U_ACTION'	=> $this->u_action,
		]);
	}

	protected function handle_moderate($phpbb_container, $request, $template, $user)
	{
		$song_repository = $phpbb_container->get('salvocortesiano.musicshare.song_repository');
		$storage_helper = $phpbb_container->get('salvocortesiano.musicshare.storage_helper');

		$action = $request->variable('action', '');
		$song_id = $request->variable('song_id', 0);

		// Indirizzo di ritorno con un parametro variabile: senza, il
		// browser puo' servire dalla cache la pagina precedente, in cui
		// il brano appena approvato compare ancora fra quelli in attesa.
		$ritorno = $this->u_action . '&amp;t=' . time();

		if ($action === 'approve' && $song_id)
		{
			$song = $song_repository->get_song($song_id);

			if (!$song)
			{
				trigger_error($user->lang('MUSICSHARE_SONG_NOT_FOUND') . adm_back_link($ritorno), E_USER_WARNING);
			}

			// Se il brano risulta gia' approvato non si rifa' nulla.
			//
			// Senza questo controllo un secondo clic sullo stesso
			// collegamento - per esempio tornando a una pagina rimasta
			// in cache nel browser, dove il brano compare ancora fra
			// quelli in attesa - inviava all'autore un secondo messaggio
			// privato identico al primo.
			if (!empty($song['song_approved']))
			{
				trigger_error($user->lang('MUSICSHARE_SONG_ALREADY_APPROVED') . adm_back_link($ritorno));
			}

			$song_repository->approve_song($song_id);

			$notifier = $phpbb_container->get('salvocortesiano.musicshare.notifier');

			// all'autore: il suo brano è stato approvato
			$notifier->song_approved($song);

			// agli altri utenti: il brano è ora visibile. Con
			// l'approvazione obbligatoria questa notifica non poteva
			// partire al caricamento, perché il brano non era ancora
			// visibile a nessuno.
			$notifier->song_new($song);

			trigger_error($user->lang('MUSICSHARE_SONG_APPROVED') . adm_back_link($ritorno));
		}

		if ($action === 'reject' && $song_id)
		{
			if (confirm_box(true))
			{
				$song = $song_repository->get_song($song_id);

				if ($song)
				{
					$notifier = $phpbb_container->get('salvocortesiano.musicshare.notifier');

					// prima si avvisa l'autore, poi si cancella: dopo la
					// cancellazione i dati del brano non ci sarebbero più
					$notifier->song_rejected($song, $request->variable('reject_reason', '', true));
					$notifier->purge_song_notifications($song_id);

					$storage_helper->delete_song_files($song);
					$song_repository->delete_song($song_id);
				}

				trigger_error($user->lang('MUSICSHARE_SONG_REJECTED') . adm_back_link($ritorno));
			}
			else
			{
				confirm_box(false, $user->lang('MUSICSHARE_REJECT_CONFIRM'), build_hidden_fields(array(
					'i'			=> $request->variable('i', ''),
					'mode'		=> 'moderate',
					'action'	=> 'reject',
					'song_id'	=> $song_id,
				)));
			}
		}

		if ($action === 'unapprove' && $song_id)
		{
			$song = $song_repository->get_song($song_id);

			if (!$song)
			{
				trigger_error($user->lang('MUSICSHARE_SONG_NOT_FOUND') . adm_back_link($ritorno), E_USER_WARNING);
			}

			// come per l'approvazione: un secondo clic non deve mandare
			// all'autore un altro messaggio identico
			if (empty($song['song_approved']))
			{
				trigger_error($user->lang('MUSICSHARE_SONG_ALREADY_UNAPPROVED') . adm_back_link($ritorno));
			}

			$song_repository->set_approved($song_id, false);
			$phpbb_container->get('salvocortesiano.musicshare.notifier')->song_rejected($song);

			trigger_error($user->lang('MUSICSHARE_SONG_UNAPPROVED') . adm_back_link($ritorno));
		}

		// Brani in attesa: 50 per pagina, con paginazione propria.
		//
		// Il parametro si chiama "pstart" e non "start" perche' nella
		// stessa schermata c'e' anche l'elenco completo, che usa "start":
		// con lo stesso nome, sfogliare un elenco sposterebbe anche
		// l'altro.
		$pending_limit = 50;
		$pending_start = $request->variable('pstart', 0);
		$pending_total = $song_repository->count_pending_songs();

		// se si arriva a una pagina che non esiste piu' (per esempio dopo
		// aver approvato gli ultimi brani) si torna alla prima
		if ($pending_start >= $pending_total)
		{
			$pending_start = max(0, $pending_total - $pending_limit);
			$pending_start = $pending_start - ($pending_start % $pending_limit);
		}

		$pending = $song_repository->get_pending_songs($pending_start, $pending_limit);

		$phpbb_container->get('pagination')->generate_template_pagination(
			$this->u_action,
			'pending_pagination',
			'pstart',
			$pending_total,
			$pending_limit,
			$pending_start
		);

		$template->assign_vars(array(
			'PENDING_TOTAL'		=> $pending_total,
			'S_PENDING_PAGED'	=> ($pending_total > $pending_limit),
		));

		foreach ($pending as $song)
		{
			$template->assign_block_vars('pending', array(
				'SONG_ID'		=> (int) $song['song_id'],
				'TITLE'			=> $song['song_title'],
				'ARTIST'		=> $song['song_artist'],
				'ALBUM'			=> $song['song_album'],
				'USERNAME'		=> $song['username'],
				'UPLOAD_DATE'	=> $user->format_date($song['upload_time']),
				'U_APPROVE'		=> $this->u_action . '&action=approve&song_id=' . $song['song_id'],
				'U_REJECT'		=> $this->u_action . '&action=reject&song_id=' . $song['song_id'],
			));
		}

		// Elenco completo dei brani, con ricerca e paginazione
		$keywords = $request->variable('q', '', true);
		$start = $request->variable('start', 0);
		$limit = 25;

		$total = $song_repository->count_all_songs($keywords);
		$all_songs = $song_repository->get_all_songs($start, $limit, $keywords);

		foreach ($all_songs as $song)
		{
			$template->assign_block_vars('all_songs', array(
				'SONG_ID'		=> (int) $song['song_id'],
				'TITLE'			=> $song['song_title'],
				'ARTIST'		=> $song['song_artist'],
				'USERNAME'		=> $song['username'],
				'APPROVED'		=> (bool) $song['song_approved'],
				'PLAY_COUNT'	=> (int) $song['play_count'],
				'FILE_SIZE_MB'	=> round($song['file_size'] / 1048576, 2),
				'UPLOAD_DATE'	=> $user->format_date($song['upload_time']),
				'U_APPROVE'		=> $this->u_action . '&amp;action=approve&amp;song_id=' . $song['song_id'],
				'U_UNAPPROVE'	=> $this->u_action . '&amp;action=unapprove&amp;song_id=' . $song['song_id'],
				'U_DELETE'		=> $this->u_action . '&amp;action=reject&amp;song_id=' . $song['song_id'],
			));
		}

		$phpbb_container->get('pagination')->generate_template_pagination(
			$this->u_action . ($keywords !== '' ? '&amp;q=' . urlencode($keywords) : ''),
			'pagination',
			'start',
			$total,
			$limit,
			$start
		);

		$template->assign_vars(array(
			'U_ACTION'			=> $this->u_action,
			'S_HAS_PENDING'		=> !empty($pending),
			'S_HAS_SONGS'		=> !empty($all_songs),
			'TOTAL_SONGS'		=> $total,
			'SEARCH_QUERY'		=> $keywords,
			// il pulsante di azzeramento rimanda alla stessa scheda senza
			// criteri: prima l'unico modo era svuotare il campo a mano
			'S_SEARCH_ACTIVE'	=> (trim($keywords) !== ''),
			'U_RESET_SEARCH'	=> $this->u_action,
			'L_MUSICSHARE_SEARCH_ACTIVE'	=> $user->lang('MUSICSHARE_SEARCH_ACTIVE', $keywords, (int) $total),
		));
	}

	protected function handle_settings($phpbb_container, $request, $template, $user, $config)
	{
		global $db;

		$storage_helper = $phpbb_container->get('salvocortesiano.musicshare.storage_helper');

		add_form_key('musicshare_settings');

		if ($request->is_set_post('clean_notifications'))
		{
			if (!check_form_key('musicshare_settings'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			// pulizia immediata: rimuove tutte le notifiche già lette,
			// senza attendere il periodo di conservazione
			$rimosse = $phpbb_container->get('salvocortesiano.musicshare.notification_cleaner')->clean(0, 200);
			$config->set('musicshare_cleanup_last', time());

			trigger_error(
				$user->lang('MUSICSHARE_CLEANUP_DONE', (int) $rimosse) . adm_back_link($this->u_action)
			);
		}

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('musicshare_settings'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$config->set('musicshare_storage_path', $request->variable('musicshare_storage_path', 'files/musicshare/'));
			$config->set('musicshare_allowed_ext', $request->variable('musicshare_allowed_ext', 'mp3,ogg,oga,flac,wav,m4a,aac'));
			$config->set('musicshare_max_filesize', $request->variable('musicshare_max_filesize', 20971520));
			$config->set('musicshare_max_user_space', $request->variable('musicshare_max_user_space', 524288000));
			$config->set('musicshare_waveform_enabled', $request->variable('musicshare_waveform_enabled', false) ? 1 : 0);
			$config->set('musicshare_songs_per_page', $request->variable('musicshare_songs_per_page', 25));
			$config->set('musicshare_require_approval', $request->variable('musicshare_require_approval', false) ? 1 : 0);
			$config->set('musicshare_persist_player', $request->variable('musicshare_persist_player', false) ? 1 : 0);

			$scope = $request->variable('musicshare_player_scope', 'music');
			$config->set('musicshare_player_scope', ($scope === 'all') ? 'all' : 'music');
			$config->set('musicshare_show_stats', $request->variable('musicshare_show_stats', false) ? 1 : 0);
			$config->set('musicshare_votes_enabled', $request->variable('musicshare_votes_enabled', false) ? 1 : 0);
			$config->set('musicshare_descriptions', $request->variable('musicshare_descriptions', false) ? 1 : 0);
			$config->set('musicshare_bbcode', $request->variable('musicshare_bbcode', false) ? 1 : 0);
			$config->set('musicshare_cleanup_enabled', $request->variable('musicshare_cleanup_enabled', false) ? 1 : 0);
			$config->set('musicshare_cleanup_days', max(0, min(365, $request->variable('musicshare_cleanup_days', 30))));
			$config->set('musicshare_description_max', max(50, min(1000, $request->variable('musicshare_description_max', 300))));

			// titolo del riquadro, una voce per lingua; le voci vuote
			// vengono scartate così si torna alla traduzione predefinita
			$titles = $request->variable('feed_title', array('' => ''), true);
			$clean = array();

			foreach ($titles as $iso => $text)
			{
				$text = trim((string) $text);

				if ($text !== '')
				{
					$clean[(string) $iso] = $text;
				}
			}

			$phpbb_container->get('config_text')->set(
				'musicshare_feed_title',
				empty($clean) ? '' : json_encode($clean)
			);
			$config->set('musicshare_toast_enabled', $request->variable('musicshare_toast_enabled', false) ? 1 : 0);
			$config->set('musicshare_toast_interval', max(30, $request->variable('musicshare_toast_interval', 90)));
			$config->set('musicshare_toast_self', $request->variable('musicshare_toast_self', false) ? 1 : 0);
			$config->set('musicshare_toast_sound', $request->variable('musicshare_toast_sound', false) ? 1 : 0);
			$config->set('musicshare_toast_volume', max(0, min(100, $request->variable('musicshare_toast_volume', 30))));
			$config->set('musicshare_notify_pm', $request->variable('musicshare_notify_pm', false) ? 1 : 0);
			$config->set('musicshare_recent_count', max(1, $request->variable('musicshare_recent_count', 8)));

			$feed_mode = $request->variable('musicshare_index_feed', 1);
			$config->set('musicshare_index_feed', in_array($feed_mode, [0, 1, 2], true) ? $feed_mode : 1);
			$feed_count = $request->variable('musicshare_index_feed_count', 20);
			$config->set('musicshare_index_feed_count', ($feed_count === 0) ? 0 : max(10, min(1000, $feed_count)));
			$config->set('musicshare_index_feed_per_user', max(0, $request->variable('musicshare_index_feed_per_user', 2)));

			$scroll_after = $request->variable('musicshare_feed_scroll_after', 20);
			$config->set('musicshare_feed_scroll_after', ($scroll_after === 0) ? 0 : max(10, min(10000, $scroll_after)));

			// il riquadro è in cache: va invalidata subito dopo la modifica
			$phpbb_container->get('cache.driver')->purge();
			$config->set('musicshare_allow_download', $request->variable('musicshare_allow_download', false) ? 1 : 0);
			$config->set('musicshare_block_duplicates', $request->variable('musicshare_block_duplicates', false) ? 1 : 0);

			$naming = $request->variable('musicshare_folder_naming', 'username');
			$config->set('musicshare_folder_naming', ($naming === 'id') ? 'id' : 'username');

			trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
		}

		$writable = $storage_helper->is_storage_writable();

		// crea (se manca) il .htaccess protettivo e verifica l'esposizione
		if ($writable)
		{
			$storage_helper->protect_dir($storage_helper->get_storage_path());
		}

		$web_exposed = $storage_helper->is_web_exposed();

		// Valori selezionabili per la dimensione massima di un singolo file
		$filesize_choices = [2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 30, 50, 100];
		$current_filesize = (int) $config['musicshare_max_filesize'];

		foreach ($filesize_choices as $mb)
		{
			$bytes = $mb * 1048576;

			$template->assign_block_vars('filesize_options', [
				'VALUE'			=> $bytes,
				'LABEL'			=> $user->lang('MUSICSHARE_MB', $mb),
				'S_SELECTED'	=> ($bytes === $current_filesize),
			]);
		}

		// Se il valore salvato non è tra quelli proposti, lo aggiungo in coda
		// per non perderlo silenziosamente al primo salvataggio.
		if (!in_array($current_filesize, array_map(function ($mb) { return $mb * 1048576; }, $filesize_choices), true))
		{
			$template->assign_block_vars('filesize_options', [
				'VALUE'			=> $current_filesize,
				'LABEL'			=> $user->lang('MUSICSHARE_CUSTOM_VALUE', round($current_filesize / 1048576, 1)),
				'S_SELECTED'	=> true,
			]);
		}

		// Valori selezionabili per lo spazio massimo per utente (0 = illimitato)
		$space_choices_mb = [50, 60, 80, 100, 120, 140, 200, 300, 400, 500, 600, 800];
		$space_choices_gb = [1, 2, 3, 5, 10];
		$current_space = (int) $config['musicshare_max_user_space'];
		$known_space = [0];

		$template->assign_block_vars('space_options', [
			'VALUE'			=> 0,
			'LABEL'			=> $user->lang('MUSICSHARE_UNLIMITED'),
			'S_SELECTED'	=> ($current_space === 0),
		]);

		foreach ($space_choices_mb as $mb)
		{
			$bytes = $mb * 1048576;
			$known_space[] = $bytes;

			$template->assign_block_vars('space_options', [
				'VALUE'			=> $bytes,
				'LABEL'			=> $user->lang('MUSICSHARE_MB', $mb),
				'S_SELECTED'	=> ($bytes === $current_space),
			]);
		}

		foreach ($space_choices_gb as $gb)
		{
			$bytes = $gb * 1073741824;
			$known_space[] = $bytes;

			$template->assign_block_vars('space_options', [
				'VALUE'			=> $bytes,
				'LABEL'			=> $user->lang('MUSICSHARE_GB', $gb),
				'S_SELECTED'	=> ($bytes === $current_space),
			]);
		}

		if (!in_array($current_space, $known_space, true))
		{
			$template->assign_block_vars('space_options', [
				'VALUE'			=> $current_space,
				'LABEL'			=> $user->lang('MUSICSHARE_CUSTOM_VALUE', round($current_space / 1048576, 1)),
				'S_SELECTED'	=> true,
			]);
		}

		$current_count = isset($config['musicshare_index_feed_count']) ? (int) $config['musicshare_index_feed_count'] : 20;
		$known = false;

		foreach (array(10, 20, 30, 50, 100, 200, 300, 500, 1000) as $choice)
		{
			$template->assign_block_vars('feed_counts', array(
				'VALUE'			=> $choice,
				'LABEL'			=> $choice,
				'S_SELECTED'	=> ($choice === $current_count),
			));

			$known = $known || ($choice === $current_count);
		}

		// "tutti": nessun tetto al numero di brani mostrati
		$template->assign_block_vars('feed_counts', array(
			'VALUE'			=> 0,
			'LABEL'			=> $user->lang('MUSICSHARE_FEED_COUNT_ALL'),
			'S_SELECTED'	=> ($current_count === 0),
		));

		// un valore salvato in precedenza e non più fra le scelte non
		// deve sparire in silenzio al primo salvataggio
		if (!$known && $current_count > 0)
		{
			$template->assign_block_vars('feed_counts', array(
				'VALUE'			=> $current_count,
				'LABEL'			=> $current_count,
				'S_SELECTED'	=> true,
			));
		}

		$stored = json_decode((string) $phpbb_container->get('config_text')->get('musicshare_feed_title'), true);
		$stored = is_array($stored) ? $stored : array();

		$sql = 'SELECT lang_iso, lang_local_name FROM ' . LANG_TABLE . ' ORDER BY lang_english_name ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$iso = (string) $row['lang_iso'];

			$template->assign_block_vars('feed_title_langs', array(
				'ISO'	=> $iso,
				'NAME'	=> $row['lang_local_name'],
				'VALUE'	=> isset($stored[$iso]) ? $stored[$iso] : '',
			));
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'U_ACTION'						=> $this->u_action,
			'MUSICSHARE_STORAGE_PATH'		=> $config['musicshare_storage_path'],
			'MUSICSHARE_ALLOWED_EXT'		=> $config['musicshare_allowed_ext'],
			'MUSICSHARE_WAVEFORM_ENABLED'	=> (bool) $config['musicshare_waveform_enabled'],
			'MUSICSHARE_SONGS_PER_PAGE'	=> $config['musicshare_songs_per_page'],
			'MUSICSHARE_REQUIRE_APPROVAL'	=> (bool) $config['musicshare_require_approval'],
			'MUSICSHARE_PERSIST_PLAYER'	=> (bool) $config['musicshare_persist_player'],
			'S_PLAYER_SCOPE_ALL'		=> (isset($config['musicshare_player_scope']) && $config['musicshare_player_scope'] === 'all'),
			'MUSICSHARE_SHOW_STATS'		=> !isset($config['musicshare_show_stats']) || (bool) $config['musicshare_show_stats'],
			'MUSICSHARE_VOTES_ENABLED'	=> !isset($config['musicshare_votes_enabled']) || (bool) $config['musicshare_votes_enabled'],
			'MUSICSHARE_DESCRIPTIONS'	=> !isset($config['musicshare_descriptions']) || (bool) $config['musicshare_descriptions'],
			'MUSICSHARE_BBCODE'			=> !isset($config['musicshare_bbcode']) || (bool) $config['musicshare_bbcode'],
			'MUSICSHARE_CLEANUP_ENABLED'	=> !isset($config['musicshare_cleanup_enabled']) || (bool) $config['musicshare_cleanup_enabled'],
			'MUSICSHARE_CLEANUP_DAYS'	=> isset($config['musicshare_cleanup_days']) ? (int) $config['musicshare_cleanup_days'] : 30,
			'MUSICSHARE_CLEANUP_COUNT'	=> $phpbb_container->get('salvocortesiano.musicshare.notification_cleaner')->count_removable(0),
			'L_MUSICSHARE_CLEANUP_STATE'	=> $user->lang(
				'MUSICSHARE_CLEANUP_STATE',
				(int) $phpbb_container->get('salvocortesiano.musicshare.notification_cleaner')->count_removable(0),
				!empty($config['musicshare_cleanup_last'])
					? $user->format_date((int) $config['musicshare_cleanup_last'])
					: $user->lang('MUSICSHARE_CLEANUP_NEVER')
			),
			'MUSICSHARE_DESCRIPTION_MAX'	=> (int) $config['musicshare_description_max'] ?: 300,
			'MUSICSHARE_TOAST_ENABLED'	=> !isset($config['musicshare_toast_enabled']) || (bool) $config['musicshare_toast_enabled'],
			'MUSICSHARE_TOAST_INTERVAL'	=> (int) $config['musicshare_toast_interval'] ?: 90,
			'MUSICSHARE_TOAST_SELF'		=> !empty($config['musicshare_toast_self']),
			'MUSICSHARE_TOAST_SOUND'	=> !isset($config['musicshare_toast_sound']) || (bool) $config['musicshare_toast_sound'],
			'MUSICSHARE_TOAST_VOLUME'	=> isset($config['musicshare_toast_volume']) ? (int) $config['musicshare_toast_volume'] : 30,
			'MUSICSHARE_NOTIFY_PM'		=> !isset($config['musicshare_notify_pm']) || (bool) $config['musicshare_notify_pm'],
			'MUSICSHARE_RECENT_COUNT'	=> (int) $config['musicshare_recent_count'] ?: 8,
			'MUSICSHARE_INDEX_FEED'		=> (int) $config['musicshare_index_feed'],
			'MUSICSHARE_INDEX_FEED_COUNT'	=> (int) $config['musicshare_index_feed_count'] ?: 6,
			'MUSICSHARE_INDEX_FEED_PER_USER'	=> (int) $config['musicshare_index_feed_per_user'],
			'MUSICSHARE_FEED_SCROLL_AFTER'		=> isset($config['musicshare_feed_scroll_after'])
				? (int) $config['musicshare_feed_scroll_after']
				: 20,
			'PHP_UPLOAD_LIMIT'				=> ini_get('upload_max_filesize'),
			'S_STORAGE_WRITABLE'			=> $writable,
			'S_STORAGE_EXPOSED'			=> $web_exposed,
			'MUSICSHARE_ALLOW_DOWNLOAD'	=> !empty($config['musicshare_allow_download']),
			'MUSICSHARE_BLOCK_DUPLICATES'	=> !isset($config['musicshare_block_duplicates']) || (bool) $config['musicshare_block_duplicates'],
			'S_FOLDER_BY_USERNAME'			=> !isset($config['musicshare_folder_naming']) || $config['musicshare_folder_naming'] !== 'id',
			'STORAGE_FULL_PATH'			=> $storage_helper->get_storage_path(),
		));
	}

	/**
	 * Variabili dei distintivi informativi mostrati in ACP: versione
	 * dell'estensione, versioni di phpBB e PHP con l'indicazione se
	 * soddisfano i requisiti dichiarati, licenza.
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container
	 * @param \phpbb\config\config $config
	 * @return array
	 */
	/**
	 * Scheda del riconoscimento: credenziali, prova della chiave e
	 * gruppi soggetti al controllo.
	 */
	/**
	 * Scheda "Manutenzione e verifiche": esegue tutti i controlli e
	 * mostra, accanto a ciascuno, il rimedio quando qualcosa non va.
	 */
	protected function handle_maintenance($phpbb_container, $request, $template, $user, $config)
	{
		$diag = $phpbb_container->get('salvocortesiano.musicshare.diagnostics');
		add_form_key('musicshare_maintenance');

		$cleaner = $phpbb_container->get('salvocortesiano.musicshare.notification_cleaner');

		if ($request->is_set_post('clean_notifications'))
		{
			if (!check_form_key('musicshare_maintenance'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$rimosse = $cleaner->clean(0, 200);
			$config->set('musicshare_cleanup_last', time());

			trigger_error($user->lang('MUSICSHARE_CLEANUP_DONE', (int) $rimosse) . adm_back_link($this->u_action));
		}

		// Rimozione totale: comprende le notifiche non ancora lette,
		// quindi passa da una conferma esplicita.
		if ($request->is_set_post('purge_notifications') || $request->is_set('confirm_purge'))
		{
			if (confirm_box(true))
			{
				$rimosse = $cleaner->purge_all(400);
				$config->set('musicshare_cleanup_last', time());

				trigger_error($user->lang('MS_PURGE_DONE', (int) $rimosse) . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang('MS_PURGE_CONFIRM', $cleaner->count_all()), build_hidden_fields(array(
					'i'					=> $request->variable('i', ''),
					'mode'				=> 'maintenance',
					'confirm_purge'		=> 1,
				)));
			}
		}

		$sezioni = array(
			'MS_SEC_ENV'		=> $diag->check_environment(),
			'MS_SEC_STORAGE'	=> $diag->check_storage(),
			'MS_SEC_DB'			=> $diag->check_database(),
			'MS_SEC_INTEGRITY'	=> $diag->check_integrity(),
			'MS_SEC_NOTIF'		=> $diag->check_notifications(),
			'MS_SEC_FEATURES'	=> $diag->check_features(),
		);

		$totali = array('ok' => 0, 'avviso' => 0, 'errore' => 0);

		foreach ($sezioni as $titolo => $controlli)
		{
			$template->assign_block_vars('sections', array(
				'TITLE'	=> $user->lang($titolo),
			));

			foreach ($controlli as $c)
			{
				$totali[$c['stato']]++;

				// il rimedio si mostra solo quando serve davvero
				$rimedio = '';

				if ($c['stato'] !== 'ok' && $c['rimedio'] !== '')
				{
					$rimedio = empty($c['param'])
						? $user->lang($c['rimedio'])
						: $user->lang($c['rimedio'], ...$c['param']);
				}

				$template->assign_block_vars('sections.checks', array(
					'LABEL'		=> $user->lang($c['etichetta']),
					'VALUE'		=> $c['valore'],
					'STATE'		=> $c['stato'],
					'FIX'		=> $rimedio,
				));
			}
		}

		$template->assign_vars(array(
			'U_ACTION'		=> $this->u_action,
			'MS_TOT_OK'		=> $totali['ok'],
			'MS_TOT_WARN'	=> $totali['avviso'],
			'MS_TOT_ERROR'	=> $totali['errore'],
			'S_ALL_GOOD'	=> ($totali['avviso'] === 0 && $totali['errore'] === 0),
			'MS_CLEANUP_COUNT'	=> $cleaner->count_removable(0),
			'MS_NOTIF_TOTAL'	=> $cleaner->count_all(),
		));
	}

	protected function handle_recognition($phpbb_container, $request, $template, $user, $config)
	{
		global $db;

		$recognizer = $phpbb_container->get('salvocortesiano.musicshare.recognizer');
		$config_text = $phpbb_container->get('config_text');
		$form_key = 'musicshare_recognition';
		add_form_key($form_key);

		// prova delle credenziali
		if ($request->is_set_post('test_key'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$result = $recognizer->test_credentials();

			// Il messaggio può arrivare dal servizio esterno: va reso
			// innocuo e accorciato, altrimenti l'HTML che contiene si
			// mangia il resto della pagina, compreso il collegamento per
			// tornare indietro.
			$raw = (string) $result['message'];
			$message = ($user->lang($raw) !== $raw)
				? $user->lang($raw)
				: htmlspecialchars(utf8_substr($raw, 0, 300), ENT_QUOTES, 'UTF-8');

			if ($result['ok'])
			{
				trigger_error($message . adm_back_link($this->u_action));
			}

			trigger_error(
				$user->lang('MUSICSHARE_RECO_TEST_FAILED', $message) . adm_back_link($this->u_action),
				E_USER_WARNING
			);
		}

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$service = $request->variable('musicshare_reco_service', '');
			$config->set('musicshare_reco_service', in_array($service, array('audd', 'acrcloud'), true) ? $service : '');

			$config->set('musicshare_audd_token', $request->variable('musicshare_audd_token', ''));
			$config->set('musicshare_acr_host', $request->variable('musicshare_acr_host', ''));
			$config->set('musicshare_acr_key', $request->variable('musicshare_acr_key', ''));
			$config->set('musicshare_acr_secret', $request->variable('musicshare_acr_secret', ''));
			$config->set('musicshare_reco_seconds', max(5, min(30, $request->variable('musicshare_reco_seconds', 15))));

			$groups = array_map('intval', $request->variable('reco_groups', array(0)));
			$config_text->set('musicshare_reco_groups', implode(',', array_filter($groups)));

			trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
		}

		$selected = $recognizer->get_groups();

		$sql = 'SELECT group_id, group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
			ORDER BY group_type DESC, group_name ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$colour = trim((string) $row['group_colour']);

			$template->assign_block_vars('groups', array(
				'GROUP_ID'		=> (int) $row['group_id'],
				'GROUP_NAME'	=> ($row['group_type'] == GROUP_SPECIAL)
					? $user->lang('G_' . $row['group_name'])
					: $row['group_name'],
				'GROUP_COLOUR'	=> preg_match('/^([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $colour) ? '#' . $colour : '',
				'S_SPECIAL'		=> ($row['group_type'] == GROUP_SPECIAL),
				'S_SELECTED'	=> in_array((int) $row['group_id'], $selected, true),
			));
		}
		$db->sql_freeresult($result);

		$service = isset($config['musicshare_reco_service']) ? (string) $config['musicshare_reco_service'] : '';

		$template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			'S_RECO_OFF'			=> ($service === ''),
			'S_RECO_AUDD'			=> ($service === 'audd'),
			'S_RECO_ACRCLOUD'		=> ($service === 'acrcloud'),
			'MUSICSHARE_AUDD_TOKEN'	=> (string) $config['musicshare_audd_token'],
			'MUSICSHARE_ACR_HOST'	=> (string) $config['musicshare_acr_host'],
			'MUSICSHARE_ACR_KEY'	=> (string) $config['musicshare_acr_key'],
			'MUSICSHARE_ACR_SECRET'	=> (string) $config['musicshare_acr_secret'],
			'MUSICSHARE_RECO_SECONDS'	=> (int) $config['musicshare_reco_seconds'] ?: 15,
			'S_RECO_CONFIGURED'		=> $recognizer->is_enabled(),
		));
	}

	protected function identity_vars($phpbb_container, $config)
	{
		global $phpbb_root_path;

		$version = '';
		$php_required = '';
		$phpbb_required = '';
		$license = 'GPL-2.0';

		try
		{
			$metadata = $phpbb_container->get('ext.manager')
				->create_extension_metadata_manager('salvocortesiano/musicshare')
				->get_metadata();

			$version = isset($metadata['version']) ? (string) $metadata['version'] : '';
			$license = isset($metadata['license']) ? (string) $metadata['license'] : $license;
			$php_required = isset($metadata['require']['php']) ? (string) $metadata['require']['php'] : '';
			$phpbb_required = isset($metadata['extra']['soft-require']['phpbb/phpbb'])
				? (string) $metadata['extra']['soft-require']['phpbb/phpbb']
				: '';
		}
		catch (\Exception $e)
		{
			// metadati illeggibili: i distintivi mostrano quel che si sa
			// e la pagina resta comunque utilizzabile
		}

		$css = $phpbb_root_path . 'ext/salvocortesiano/musicshare/adm/style/musicshare_acp.css';

		return array(
			// la data del file costringe il browser a rileggere il foglio
			// dopo un aggiornamento, senza aspettare la scadenza della cache
			'U_MUSICSHARE_ACP_CSS'	=> $css . '?v=' . (int) @filemtime($css),
			'MUSICSHARE_VERSION'		=> $version,
			'MUSICSHARE_LICENSE'		=> $license,
			'MUSICSHARE_PHP_VERSION'	=> PHP_VERSION,
			'MUSICSHARE_PHP_REQUIRED'	=> $php_required,
			'S_MUSICSHARE_PHP_OK'		=> ($php_required === '' || $this->satisfies(PHP_VERSION, $php_required)),
			'MUSICSHARE_PHPBB_VERSION'	=> (string) $config['version'],
			'MUSICSHARE_PHPBB_REQUIRED'	=> $phpbb_required,
			'S_MUSICSHARE_PHPBB_OK'		=> ($phpbb_required === '' || $this->satisfies((string) $config['version'], $phpbb_required)),
		);
	}

	/**
	 * Una versione soddisfa un vincolo del tipo ">=7.4" oppure
	 * ">=3.3.0,<4.0.0@dev"? Si interpreta la forma usata nei metadati
	 * delle estensioni senza tirare dentro il risolutore di Composer:
	 * qui serve solo accendere un pallino verde o rosso.
	 *
	 * @param string $version
	 * @param string $constraint
	 * @return bool
	 */
	protected function satisfies($version, $constraint)
	{
		$constraint = trim($constraint);

		if ($constraint === '')
		{
			return true;
		}

		foreach (explode(',', $constraint) as $part)
		{
			$part = trim($part);

			// le annotazioni di stabilita' non incidono sul confronto
			$part = preg_replace('/@[a-z]+$/i', '', $part);

			if ($part === '')
			{
				continue;
			}

			if (preg_match('/^(>=|<=|!=|<>|>|<|=)?\s*(.+)$/', $part, $m))
			{
				$operator = ($m[1] !== '') ? $m[1] : '>=';
				$operator = ($operator === '<>') ? '!=' : $operator;

				if (!version_compare($version, trim($m[2]), $operator))
				{
					return false;
				}
			}
		}

		return true;
	}
}
