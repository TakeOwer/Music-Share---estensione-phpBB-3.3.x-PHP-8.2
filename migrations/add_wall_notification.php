<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\migrations;

/**
 * Registra la notifica dei commenti sulla bacheca.
 *
 * Sta in una migrazione a parte e non dentro add_walls perche' quella
 * potrebbe essere gia' stata eseguita: phpBB non ripete una migrazione
 * gia' registrata, quindi le aggiunte successive vanno in un passo
 * nuovo.
 */
class add_wall_notification extends \phpbb\db\migration\migration
{
	protected static $types = [
		'salvocortesiano.musicshare.notification.type.wall_comment',
	];

	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_walls'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'enable_notifications']]],
		];
	}

	/**
	 * @return void
	 */
	public function enable_notifications()
	{
		global $phpbb_container;

		$manager = $phpbb_container->get('notification_manager');

		foreach (self::$types as $type)
		{
			$manager->enable_notifications($type);
		}
	}
}
