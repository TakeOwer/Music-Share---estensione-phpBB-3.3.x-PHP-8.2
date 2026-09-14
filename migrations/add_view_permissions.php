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

class add_view_permissions extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_toast_self'];
	}

	public function update_data()
	{
		return [
			// Accesso alla sezione Musica: pagine e voce di menù
			['permission.add', ['u_musicshare_view']],
			// Riquadro dei brani recenti nelle pagine del forum
			['permission.add', ['u_musicshare_feed']],

			// Si concedono a registrati e ospiti per non cambiare il
			// comportamento di chi aggiorna: l'amministratore puo' poi
			// toglierli dalla scheda "Gruppi autorizzati".
			['permission.permission_set', ['REGISTERED', 'u_musicshare_view', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_musicshare_feed', 'group']],
			['permission.permission_set', ['GUESTS', 'u_musicshare_view', 'group']],
			['permission.permission_set', ['GUESTS', 'u_musicshare_feed', 'group']],
		];
	}

	public function revert_data()
	{
		return [
			['permission.remove', ['u_musicshare_view']],
			['permission.remove', ['u_musicshare_feed']],
		];
	}
}
