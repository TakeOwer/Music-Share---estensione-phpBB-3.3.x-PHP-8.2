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
 * Qualcuno ha scritto sulla bacheca di un autore: avvisa l'autore.
 */
class wall_comment extends base_wall
{
	public function get_type()
	{
		return 'salvocortesiano.musicshare.notification.type.wall_comment';
	}

	public static $notification_option = array(
		'lang'	=> 'MUSICSHARE_NOTIFICATION_WALL',
		'group'	=> 'MUSICSHARE_NOTIFICATION_GROUP',
	);

	public function find_users_for_notification($data, $options = array())
	{
		return $this->single_user($data['wall_user_id'], $data['commenter_id'], $options);
	}

	public function get_title()
	{
		return $this->language->lang('MUSICSHARE_NOTIFICATION_WALL_TITLE', $this->commenter_name());
	}
}
