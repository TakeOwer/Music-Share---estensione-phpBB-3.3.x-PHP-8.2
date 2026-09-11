<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\notification\type;

/**
 * Avvisa gli utenti abilitati che è stato caricato un nuovo brano.
 */
class song_new extends base_song
{
	public function get_type()
	{
		return 'salvocortesiano.musicshare.notification.type.song_new';
	}

	public static $notification_option = array(
		'lang'	=> 'MUSICSHARE_NOTIFICATION_SONG_NEW',
		'group'	=> 'MUSICSHARE_NOTIFICATION_GROUP',
	);

	/**
	 * Tutti gli utenti che possono vedere la sezione Musica, escluso
	 * l'autore del caricamento: sa già di averlo fatto.
	 */
	public function find_users_for_notification($data, $options = array())
	{
		$options = array_merge(array(
			'ignore_users'	=> array(),
		), $options);

		// acl_get_list restituisce [forum_id][permesso] => elenco utenti;
		// per un permesso globale il forum è lo 0
		// Permesso dedicato, non quello di vedere la sezione: quest'ultimo
		// ce l'hanno praticamente tutti gli iscritti, e la notifica
		// finirebbe per generare una riga per ogni utente del forum a
		// ogni caricamento.
		$list = $this->auth->acl_get_list(false, 'u_musicshare_notify', 0);
		$users = isset($list[0]['u_musicshare_notify']) ? $list[0]['u_musicshare_notify'] : array();

		// l'autore non riceve la notifica del proprio caricamento
		$users = array_diff(array_map('intval', $users), array((int) $data['uploader_id'], ANONYMOUS));

		if (empty($users))
		{
			return array();
		}

		return $this->check_user_notification_options($users, $options);
	}

	public function users_to_query()
	{
		return array((int) $this->get_data('uploader_id'));
	}

	public function get_title()
	{
		$username = $this->user_loader->get_username((int) $this->get_data('uploader_id'), 'no_profile');

		return $this->language->lang(
			'MUSICSHARE_NOTIFICATION_NEW_TITLE',
			$username,
			$this->get_data('song_title')
		);
	}

	public function get_avatar()
	{
		return $this->user_loader->get_avatar((int) $this->get_data('uploader_id'), false, true);
	}

	public function get_email_template()
	{
		return '@salvocortesiano_musicshare/song_new';
	}
}
