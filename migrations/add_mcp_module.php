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

class add_mcp_module extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_maintenance_module'];
	}

	public function effectively_installed()
	{
		$sql = 'SELECT module_id FROM ' . MODULES_TABLE . "
			WHERE module_class = 'mcp'
				AND module_basename = '\\salvocortesiano\\musicshare\\mcp\\main_module'";
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function update_data()
	{
		return [
			// categoria nel Pannello di Controllo Moderatore
			['module.add', ['mcp', 0, 'MCP_MUSICSHARE_TITLE']],

			['module.add', [
				'mcp',
				'MCP_MUSICSHARE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\musicshare\mcp\main_module',
					'modes'				=> ['pending', 'songs'],
				],
			]],
		];
	}

	public function revert_data()
	{
		return [
			['module.remove', ['mcp', 0, 'MCP_MUSICSHARE_TITLE']],
		];
	}
}
