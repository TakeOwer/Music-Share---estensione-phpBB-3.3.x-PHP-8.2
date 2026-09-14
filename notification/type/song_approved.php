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
 * Avvisa l'autore che il suo brano è stato approvato.
 */
class song_approved extends base_song
{
	public function get_type()
	{
		return 'salvocortesiano.musicshare.notification.type.song_approved';
	}

	public static $notification_option = array(
		'lang'	=> 'MUSICSHARE_NOTIFICATION_SONG_APPROVED',
		'group'	=> 'MUSICSHARE_NOTIFICATION_GROUP',
	);

	/**
	 * Va solo all'autore del brano.
	 */
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
			'MUSICSHARE_NOTIFICATION_APPROVED_TITLE',
			$this->get_data('song_title')
		);
	}

	public function get_email_template()
	{
		return '@salvocortesiano_musicshare/song_approved';
	}
}
