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

class add_modules extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_config'];
	}

	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_MUSICSHARE_TITLE',
			]],
			['module.add', [
				'acp',
				'ACP_MUSICSHARE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\musicshare\acp\main_module',
					'modes'				=> ['genres', 'settings'],
				],
			]],
			['module.add', [
				'ucp',
				'UCP_MAIN',
				[
					'module_basename'	=> '\salvocortesiano\musicshare\ucp\main_module',
					'modes'				=> ['songs', 'upload', 'playlists'],
				],
			]],
		];
	}

	public function revert_data()
	{
		return [
			['module.remove', [
				'ucp',
				'UCP_MAIN',
				[
					'module_basename'	=> '\salvocortesiano\musicshare\ucp\main_module',
					'modes'				=> ['songs', 'upload', 'playlists'],
				],
			]],
			['module.remove', [
				'acp',
				'ACP_MUSICSHARE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\musicshare\acp\main_module',
					'modes'				=> ['genres', 'settings'],
				],
			]],
			['module.remove', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_MUSICSHARE_TITLE',
			]],
		];
	}
}
