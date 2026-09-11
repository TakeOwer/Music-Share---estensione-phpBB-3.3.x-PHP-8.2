<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\repository;

class playlist_repository
{
	protected $db;
	protected $playlists_table;
	protected $playlist_songs_table;
	protected $songs_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $playlists_table, $playlist_songs_table, $songs_table)
	{
		$this->db = $db;
		$this->playlists_table = $playlists_table;
		$this->playlist_songs_table = $playlist_songs_table;
		$this->songs_table = $songs_table;
	}

	public function add($user_id, $name, $desc, $is_public)
	{
		$sql_ary = array(
			'user_id'		=> (int) $user_id,
			'playlist_name'	=> (string) $name,
			'playlist_desc'	=> (string) $desc,
			'is_public'		=> $is_public ? 1 : 0,
			'created_time'	=> time(),
		);

		$sql = 'INSERT INTO ' . $this->playlists_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	public function edit($playlist_id, $name, $desc, $is_public)
	{
		$sql_ary = array(
			'playlist_name'	=> (string) $name,
			'playlist_desc'	=> (string) $desc,
			'is_public'		=> $is_public ? 1 : 0,
		);

		$sql = 'UPDATE ' . $this->playlists_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE playlist_id = ' . (int) $playlist_id;
		$this->db->sql_query($sql);
	}

	public function delete($playlist_id)
	{
		$sql = 'DELETE FROM ' . $this->playlists_table . ' WHERE playlist_id = ' . (int) $playlist_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->playlist_songs_table . ' WHERE playlist_id = ' . (int) $playlist_id;
		$this->db->sql_query($sql);
	}

	public function get($playlist_id)
	{
		$sql = 'SELECT * FROM ' . $this->playlists_table . ' WHERE playlist_id = ' . (int) $playlist_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	public function get_by_user($user_id)
	{
		$sql = 'SELECT * FROM ' . $this->playlists_table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY created_time DESC';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function add_song($playlist_id, $song_id)
	{
		$sql = 'SELECT 1 FROM ' . $this->playlist_songs_table . '
			WHERE playlist_id = ' . (int) $playlist_id . ' AND song_id = ' . (int) $song_id;
		$result = $this->db->sql_query($sql);
		$exists = (bool) $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($exists)
		{
			return false;
		}

		$sql = 'SELECT MAX(song_order) as max_order FROM ' . $this->playlist_songs_table . ' WHERE playlist_id = ' . (int) $playlist_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		$next_order = ((int) $row['max_order']) + 1;

		$sql_ary = array(
			'playlist_id'	=> (int) $playlist_id,
			'song_id'		=> (int) $song_id,
			'song_order'	=> $next_order,
		);

		$sql = 'INSERT INTO ' . $this->playlist_songs_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return true;
	}

	public function remove_song($playlist_id, $song_id)
	{
		$sql = 'DELETE FROM ' . $this->playlist_songs_table . '
			WHERE playlist_id = ' . (int) $playlist_id . ' AND song_id = ' . (int) $song_id;
		$this->db->sql_query($sql);
	}

	public function get_songs($playlist_id)
	{
		$sql = 'SELECT s.*, ps.song_order, u.username, u.user_colour
			FROM ' . $this->songs_table . ' s, ' . $this->playlist_songs_table . ' ps, ' . USERS_TABLE . ' u
			WHERE ps.playlist_id = ' . (int) $playlist_id . '
				AND ps.song_id = s.song_id
				AND u.user_id = s.user_id
			ORDER BY ps.song_order ASC';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function reorder($playlist_id, array $song_ids_in_order)
	{
		$order = 1;
		foreach ($song_ids_in_order as $song_id)
		{
			$sql = 'UPDATE ' . $this->playlist_songs_table . ' SET song_order = ' . (int) $order . '
				WHERE playlist_id = ' . (int) $playlist_id . ' AND song_id = ' . (int) $song_id;
			$this->db->sql_query($sql);
			$order++;
		}
	}
}
