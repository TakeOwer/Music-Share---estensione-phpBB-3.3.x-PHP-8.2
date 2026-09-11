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

class add_config extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_permissions'];
	}

	public function update_data()
	{
		return [
			// Cartella di storage (relativa alla root del forum), configurabile da ACP
			['config.add', ['musicshare_storage_path', 'files/musicshare/']],

			// Estensioni ammesse (elenco separato da virgola, senza il punto)
			['config.add', ['musicshare_allowed_ext', 'mp3,ogg,oga,flac,wav,m4a,aac']],

			// Dimensione massima per singolo file, in byte (default 20 MB)
			['config.add', ['musicshare_max_filesize', 20971520]],

			// Spazio massimo totale per utente, in byte (default 500 MB)
			['config.add', ['musicshare_max_user_space', 524288000]],

			// Generazione della waveform precalcolata all'upload
			['config.add', ['musicshare_waveform_enabled', 1]],

			// Numero di brani per pagina nelle liste
			['config.add', ['musicshare_songs_per_page', 25]],

			// Moderazione: se attiva, i brani caricati restano in attesa di approvazione
			['config.add', ['musicshare_require_approval', 0]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_storage_path']],
			['config.remove', ['musicshare_allowed_ext']],
			['config.remove', ['musicshare_max_filesize']],
			['config.remove', ['musicshare_max_user_space']],
			['config.remove', ['musicshare_waveform_enabled']],
			['config.remove', ['musicshare_songs_per_page']],
			['config.remove', ['musicshare_require_approval']],
		];
	}
}
