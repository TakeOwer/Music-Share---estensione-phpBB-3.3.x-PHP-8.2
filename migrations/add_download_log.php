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

class add_download_log extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\fix_stale_topics'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_downloads'	=> [
					'COLUMNS'	=> [
						'dl_id'			=> ['UINT', null, 'auto_increment'],
						'song_id'		=> ['UINT', 0],
						'user_id'		=> ['UINT', 0],
						'session_id'	=> ['VCHAR:32', ''],
						'dl_time'		=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'dl_id',
					'KEYS'	=> [
						'ms_dl_time'	=> ['INDEX', 'dl_time'],
						'ms_dl_who'		=> ['INDEX', ['user_id', 'song_id', 'dl_time']],
					],
				],
			],
		];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'reset_download_counts']]],
		];
	}

	/**
	 * Azzera i contatori dei download raccolti finora.
	 *
	 * Finche' si contavano le richieste HTTP invece delle persone, un
	 * solo download poteva valerne cinque: quei numeri non descrivono
	 * nulla di reale e non sono correggibili a posteriori. Si riparte da
	 * zero con un conteggio affidabile, invece di conservare un dato
	 * sbagliato.
	 *
	 * @return void
	 */
	public function reset_download_counts()
	{
		$this->db->sql_query('UPDATE ' . $this->table_prefix . 'musicshare_songs
			SET download_count = 0
			WHERE download_count > 0');
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [$this->table_prefix . 'musicshare_downloads'],
		];
	}
}
