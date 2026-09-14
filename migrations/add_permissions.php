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

class add_permissions extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\install_schema'];
	}

	public function update_data()
	{
		return [
			// Permesso utente: caricare brani
			['permission.add', ['u_musicshare_upload']],

			// Permesso utente: creare playlist personali
			['permission.add', ['u_musicshare_playlist']],

			// Permesso moderatore: gestire (modificare/eliminare) i brani di tutti
			['permission.add', ['m_musicshare_manage']],

			// Concede i permessi di base al gruppo REGISTERED
			// (l'amministratore può poi modificarli da ACP -> Permessi)
			['permission.permission_set', ['REGISTERED', 'u_musicshare_upload', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_musicshare_playlist', 'group']],

			// Concede la gestione completa ai ruoli di amministrazione/moderazione
			['permission.permission_set', ['ROLE_ADMIN_FULL', 'm_musicshare_manage']],
			['permission.permission_set', ['ROLE_MOD_FULL', 'm_musicshare_manage']],
		];
	}

	public function revert_data()
	{
		return [
			['permission.remove', ['u_musicshare_upload']],
			['permission.remove', ['u_musicshare_playlist']],
			['permission.remove', ['m_musicshare_manage']],
		];
	}
}
