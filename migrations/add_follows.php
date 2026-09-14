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

class add_follows extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_play_log'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_follows'	=> [
					'COLUMNS'	=> [
						// chi segue
						'user_id'		=> ['UINT', 0],
						// chi viene seguito
						'author_id'		=> ['UINT', 0],
						'follow_time'	=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> ['user_id', 'author_id'],
					'KEYS'	=> [
						// per trovare in fretta i seguaci di un autore
						'ms_follow_author'	=> ['INDEX', 'author_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [$this->table_prefix . 'musicshare_follows'],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['musicshare_follows_enabled', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_follows_enabled']],
		];
	}
}
