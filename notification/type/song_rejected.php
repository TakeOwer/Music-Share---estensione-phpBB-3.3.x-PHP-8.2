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
 * Avvisa l'autore che il suo brano è stato rifiutato o rimosso.
 */
class song_rejected extends base_song
{
	public function get_type()
	{
		return 'salvocortesiano.musicshare.notification.type.song_rejected';
	}

	public static $notification_option = array(
		'lang'	=> 'MUSICSHARE_NOTIFICATION_SONG_REJECTED',
		'group'	=> 'MUSICSHARE_NOTIFICATION_GROUP',
	);

	public function find_users_for_notification($data, $options = array())
	{
		$options = array_merge(array(
			'ignore_users'	=> array(),
		), $options);

		return $this->check_user_notification_options(
			array((int) $data['uploader_id']),
			$options
		);
	}

	public function get_title()
	{
		return $this->language->lang(
			'MUSICSHARE_NOTIFICATION_REJECTED_TITLE',
			$this->get_data('song_title')
		);
	}

	/**
	 * Il brano non esiste più: si rimanda all'elenco dei propri brani.
	 */
	public function get_url()
	{
		return '';
	}

	public function get_email_template()
	{
		return '@salvocortesiano_musicshare/song_rejected';
	}
}
