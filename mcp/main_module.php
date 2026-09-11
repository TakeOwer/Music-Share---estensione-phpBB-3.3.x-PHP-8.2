<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\mcp;

/**
 * Moderazione dei brani dal Pannello di Controllo Moderatore.
 *
 * Le stesse operazioni della scheda ACP, ma raggiungibili da chi modera
 * senza avere accesso amministrativo: fino a ora un moderatore con il
 * permesso di gestire i brani non aveva alcun modo di approvarli.
 */
class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container, $user, $template, $request, $auth, $config;

		$user->add_lang_ext('salvocortesiano/musicshare', 'common');

		if (!$auth->acl_get('m_musicshare_manage'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$song_repository = $phpbb_container->get('salvocortesiano.musicshare.song_repository');
		$storage_helper = $phpbb_container->get('salvocortesiano.musicshare.storage_helper');
		$notifier = $phpbb_container->get('salvocortesiano.musicshare.notifier');
		$controller_helper = $phpbb_container->get('controller.helper');

		$this->tpl_name = 'mcp_musicshare';
		$this->page_title = ($mode === 'pending') ? 'MCP_MUSICSHARE_PENDING' : 'MCP_MUSICSHARE_SONGS';

		$action = $request->variable('action', '');
		$song_id = $request->variable('song_id', 0);

		// indirizzo di ritorno con parametro variabile: senza, il browser
		// puo' servire dalla cache l'elenco precedente, in cui il brano
		// appena approvato risulta ancora in attesa
		$ritorno = $this->u_action . '&amp;t=' . time();

		if ($action !== '' && $song_id)
		{
			$this->handle_action(
				$action, $song_id, $ritorno,
				$song_repository, $storage_helper, $notifier,
				$user, $request
			);
		}

		$this->page_title = ($mode === 'pending') ? 'MCP_MUSICSHARE_PENDING' : 'MCP_MUSICSHARE_SONGS';

		$per_pagina = 20;
		$start = $request->variable('start', 0);
		$keywords = $request->variable('q', '', true);

		if ($mode === 'pending')
		{
			$totale = $song_repository->count_pending_songs();
			$start = $this->normalizza_start($start, $totale, $per_pagina);
			$brani = $song_repository->get_pending_songs($start, $per_pagina);
			$base_url = $this->u_action;
		}
		else
		{
			$totale = $song_repository->count_all_songs($keywords);
			$start = $this->normalizza_start($start, $totale, $per_pagina);
			$brani = $song_repository->get_all_songs($start, $per_pagina, $keywords);
			$base_url = $this->u_action . ($keywords !== '' ? '&amp;q=' . urlencode($keywords) : '');
		}

		foreach ($brani as $song)
		{
			$template->assign_block_vars('songs', array(
				'SONG_ID'		=> (int) $song['song_id'],
				'TITLE'			=> $song['song_title'],
				'ARTIST'		=> $song['song_artist'],
				'ALBUM'			=> $song['song_album'],
				'USERNAME'		=> get_username_string('full', (int) $song['user_id'], $song['username'],
					isset($song['user_colour']) ? $song['user_colour'] : ''),
				'UPLOAD_DATE'	=> $user->format_date((int) $song['upload_time']),
				'FILE_SIZE'		=> round($song['file_size'] / 1048576, 2),
				'PLAY_COUNT'	=> (int) $song['play_count'],
				'S_APPROVED'	=> (bool) $song['song_approved'],
				'S_RECOGNIZED'	=> !empty($song['recognized']),
				'RECOGNIZED_INFO'	=> isset($song['recognized_info']) ? (string) $song['recognized_info'] : '',
				'U_SONG'		=> $controller_helper->route('salvocortesiano_musicshare_user',
					array('user_id' => (int) $song['user_id'])),
				'U_APPROVE'		=> $this->u_action . '&amp;action=approve&amp;song_id=' . (int) $song['song_id'],
				'U_UNAPPROVE'	=> $this->u_action . '&amp;action=unapprove&amp;song_id=' . (int) $song['song_id'],
				'U_DELETE'		=> $this->u_action . '&amp;action=delete&amp;song_id=' . (int) $song['song_id'],
			));
		}

		$phpbb_container->get('pagination')->generate_template_pagination(
			$base_url, 'pagination', 'start', $totale, $per_pagina, $start
		);

		$template->assign_vars(array(
			'U_ACTION'		=> $this->u_action,
			'S_PENDING_MODE'	=> ($mode === 'pending'),
			'TOTAL_SONGS'	=> $totale,
			'SEARCH_QUERY'	=> $keywords,
			'S_IN_SEARCH'	=> ($keywords !== ''),
			'U_RESET_SEARCH'	=> $this->u_action,
			'L_TOTAL_SONGS'	=> $user->lang('MCP_MUSICSHARE_TOTAL', (int) $totale),
			// phpBB la fornisce gia' tramite page_header(), ma assegnarla
			// qui rende il template indipendente da quel dettaglio
			'PAGE_TITLE'	=> $user->lang($this->page_title),
		));
	}

	/**
	 * Collegamento di ritorno all'elenco.
	 *
	 * Nel Pannello di Controllo Moderatore non esiste adm_back_link():
	 * quella funzione vive in functions_acp.php e viene caricata solo
	 * nell'area amministrativa. Si usa la stessa formula dei moduli di
	 * moderazione di phpBB.
	 *
	 * @param string $url
	 * @return string
	 */
	protected function back($url)
	{
		global $user;

		return '<br /><br />' . $user->lang('RETURN_PAGE', '<a href="' . $url . '">', '</a>');
	}

	/**
	 * Riporta l'inizio pagina entro i limiti dell'elenco.
	 *
	 * Serve quando si approva l'ultimo brano di una pagina che cosi'
	 * sparisce: senza, resterebbe un elenco vuoto.
	 *
	 * @param int $start
	 * @param int $totale
	 * @param int $per_pagina
	 * @return int
	 */
	protected function normalizza_start($start, $totale, $per_pagina)
	{
		$start = (int) $start;

		if ($start <= 0 || $start < $totale)
		{
			return max(0, $start);
		}

		$start = max(0, $totale - $per_pagina);

		return $start - ($start % $per_pagina);
	}

	/**
	 * Approvazione, revoca ed eliminazione.
	 *
	 * Ogni operazione controlla prima lo stato reale del brano: un
	 * secondo clic sullo stesso collegamento non deve inviare all'autore
	 * un avviso doppio.
	 */
	protected function handle_action($action, $song_id, $ritorno, $song_repository, $storage_helper, $notifier, $user, $request)
	{
		$song = $song_repository->get_song($song_id);

		if (!$song)
		{
			trigger_error($user->lang('MUSICSHARE_SONG_NOT_FOUND') . $this->back($ritorno), E_USER_WARNING);
		}

		if ($action === 'approve')
		{
			if (!empty($song['song_approved']))
			{
				trigger_error($user->lang('MUSICSHARE_SONG_ALREADY_APPROVED') . $this->back($ritorno));
			}

			$song_repository->approve_song($song_id);
			$notifier->song_approved($song);
			$notifier->song_new($song);

			trigger_error($user->lang('MUSICSHARE_SONG_APPROVED') . $this->back($ritorno));
		}

		if ($action === 'unapprove')
		{
			if (empty($song['song_approved']))
			{
				trigger_error($user->lang('MUSICSHARE_SONG_ALREADY_UNAPPROVED') . $this->back($ritorno));
			}

			$song_repository->set_approved($song_id, false);
			$notifier->song_rejected($song);

			trigger_error($user->lang('MUSICSHARE_SONG_UNAPPROVED') . $this->back($ritorno));
		}

		if ($action === 'delete')
		{
			// l'eliminazione cancella anche il file: va confermata
			if (confirm_box(true))
			{
				$notifier->song_rejected($song, $request->variable('reject_reason', '', true));
				$notifier->purge_song_notifications($song_id);
				$storage_helper->delete_song_files($song);
				$song_repository->delete_song($song_id);

				trigger_error($user->lang('MUSICSHARE_SONG_REJECTED') . $this->back($ritorno));
			}

			confirm_box(false, $user->lang('MCP_MUSICSHARE_CONFIRM_DELETE', $song['song_title']), build_hidden_fields(array(
				'i'			=> $request->variable('i', ''),
				'mode'		=> $request->variable('mode', ''),
				'action'	=> 'delete',
				'song_id'	=> (int) $song_id,
			)));
		}
	}
}
