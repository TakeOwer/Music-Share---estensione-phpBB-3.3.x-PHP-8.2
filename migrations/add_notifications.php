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

class add_notifications extends \phpbb\db\migration\migration
{
	/**
	 * Tipi di notifica introdotti da questa migrazione.
	 */
	protected static $types = [
		'salvocortesiano.musicshare.notification.type.song_approved',
		'salvocortesiano.musicshare.notification.type.song_rejected',
		'salvocortesiano.musicshare.notification.type.song_new',
	];

	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_scroll_after'];
	}

	public function update_data()
	{
		return [
			// phpBB non ha uno strumento di migrazione per le notifiche:
			// si abilitano tramite il gestore delle notifiche, in un
			// passaggio personalizzato.
			['custom', [[$this, 'enable_notifications']]],

			// Avvisa anche con un messaggio privato le decisioni del
			// moderatore, oltre alla notifica
			['config.add', ['musicshare_notify_pm', 1]],
		];
	}

	public function revert_data()
	{
		// Nota: phpBB non esegue i passaggi 'custom' in senso inverso,
		// quindi la disattivazione dei tipi di notifica non può essere
		// fatta qui. Le notifiche già inviate restano nel database ma
		// non sono più generate una volta rimossa l'estensione.
		return [
			['config.remove', ['musicshare_notify_pm']],
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
