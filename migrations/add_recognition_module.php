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

class add_recognition_module extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_recognition'];
	}

	public function effectively_installed()
	{
		$sql = 'SELECT module_id FROM ' . MODULES_TABLE . "
			WHERE module_class = 'acp'
				AND module_mode = 'recognition'
				AND module_basename = '\\salvocortesiano\\musicshare\\acp\\main_module'";
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_MUSICSHARE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\musicshare\acp\main_module',
					'modes'				=> ['recognition'],
				],
			]],
		];
	}

	public function revert_data()
	{
		return [
			['module.remove', [
				'acp',
				'ACP_MUSICSHARE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\musicshare\acp\main_module',
					'modes'				=> ['recognition'],
				],
			]],
		];
	}
}
