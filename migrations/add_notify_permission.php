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

class add_notify_permission extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_bbcode'];
	}

	public function update_data()
	{
		return [
			// Chi riceve la notifica dei nuovi brani.
			//
			// Prima la notifica andava a chiunque potesse vedere la
			// sezione: su un forum con molti iscritti significa decine di
			// migliaia di righe per ogni brano caricato. Ora serve un
			// permesso dedicato, che si concede ai soli gruppi voluti.
			['permission.add', ['u_musicshare_notify']],

			// Concesso solo allo staff: e' un punto di partenza prudente,
			// l'amministratore allarga da ACP se vuole.
			['permission.permission_set', ['ADMINISTRATORS', 'u_musicshare_notify', 'group']],
			['permission.permission_set', ['GLOBAL_MODERATORS', 'u_musicshare_notify', 'group']],

			// Pulizia periodica delle notifiche gia' lette
			['config.add', ['musicshare_cleanup_enabled', 1]],
			['config.add', ['musicshare_cleanup_days', 30]],
			['config.add', ['musicshare_cleanup_last', 0]],
		];
	}

	public function revert_data()
	{
		return [
			['permission.remove', ['u_musicshare_notify']],
			['config.remove', ['musicshare_cleanup_enabled']],
			['config.remove', ['musicshare_cleanup_days']],
			['config.remove', ['musicshare_cleanup_last']],
		];
	}
}
