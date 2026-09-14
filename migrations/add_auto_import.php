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

class add_auto_import extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_attach_import'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_auto_import']);
	}

	public function update_data()
	{
		return [
			// Spenta di partenza, e senza alcun forum selezionato: una
			// funzione che copia in libreria tutto quello che viene
			// allegato non deve attivarsi da sola dopo un aggiornamento.
			['config.add', ['musicshare_auto_import', 0]],

			// I brani importati restano in attesa di approvazione anche
			// se l'approvazione generale e' disattivata: nessuno li ha
			// scelti uno per uno, quindi un occhio umano serve.
			['config.add', ['musicshare_auto_pending', 1]],

			['config_text.add', ['musicshare_auto_forums', '']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_auto_import']],
			['config.remove', ['musicshare_auto_pending']],
			['config_text.remove', ['musicshare_auto_forums']],
		];
	}
}
