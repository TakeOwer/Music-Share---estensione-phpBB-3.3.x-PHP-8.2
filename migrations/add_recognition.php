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

class add_recognition extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_notifications'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					// esito del riconoscimento al momento del caricamento
					'recognized'		=> ['BOOL', 0],
					'recognized_info'	=> ['VCHAR_UNI:255', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> ['recognized', 'recognized_info'],
			],
		];
	}

	public function update_data()
	{
		return [
			// '' = controllo disattivato, 'audd' oppure 'acrcloud'
			['config.add', ['musicshare_reco_service', '']],
			['config.add', ['musicshare_audd_token', '']],
			['config.add', ['musicshare_acr_host', 'identify-eu-west-1.acrcloud.com']],
			['config.add', ['musicshare_acr_key', '']],
			['config.add', ['musicshare_acr_secret', '']],
			// secondi di audio inviati al servizio
			['config.add', ['musicshare_reco_seconds', 15]],
			// gruppi soggetti al controllo (elenco di id separati da virgola)
			['config_text.add', ['musicshare_reco_groups', '']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_reco_service']],
			['config.remove', ['musicshare_audd_token']],
			['config.remove', ['musicshare_acr_host']],
			['config.remove', ['musicshare_acr_key']],
			['config.remove', ['musicshare_acr_secret']],
			['config.remove', ['musicshare_reco_seconds']],
			['config_text.remove', ['musicshare_reco_groups']],
		];
	}
}
