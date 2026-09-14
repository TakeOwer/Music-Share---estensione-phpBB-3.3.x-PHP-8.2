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

class add_song_topic extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_auto_import'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					// argomento aperto automaticamente per il brano
					'topic_id'	=> ['UINT', 0],
					'post_id'	=> ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> ['topic_id', 'post_id'],
			],
		];
	}

	public function update_data()
	{
		return [
			// spenta di partenza: aprire argomenti a nome degli utenti
			// e' un'azione visibile a tutto il forum, va scelta
			['config.add', ['musicshare_topic_enabled', 0]],
			['config.add', ['musicshare_topic_forum', 0]],
			['config_text.add', ['musicshare_topic_title', '%1$s - %2$s']],
			['config_text.add', ['musicshare_topic_message', "{BRANO}\n\n{DESCRIZIONE}"]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_topic_enabled']],
			['config.remove', ['musicshare_topic_forum']],
			['config_text.remove', ['musicshare_topic_title']],
			['config_text.remove', ['musicshare_topic_message']],
		];
	}
}
