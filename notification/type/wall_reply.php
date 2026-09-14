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
 * Qualcuno ha risposto a un commento: avvisa chi l'aveva scritto.
 *
 * Il destinatario arriva nei dati come target_id, perche' non e'
 * il padrone della bacheca ma l'autore del commento a cui si risponde.
 */
class wall_reply extends base_wall
{
	public function get_type()
	{
		return 'salvocortesiano.musicshare.notification.type.wall_reply';
	}

	public static $notification_option = array(
		'lang'	=> 'MUSICSHARE_NOTIFICATION_WALL_REPLY',
		'group'	=> 'MUSICSHARE_NOTIFICATION_GROUP',
	);

	public function find_users_for_notification($data, $options = array())
	{
		return $this->single_user($data['target_id'], $data['commenter_id'], $options);
	}

	public function get_title()
	{
		return $this->language->lang('MUSICSHARE_NOTIFICATION_WALL_REPLY_TITLE', $this->commenter_name());
	}
}
