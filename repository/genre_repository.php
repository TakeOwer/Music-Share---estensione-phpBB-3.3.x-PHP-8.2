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

class genre_repository
{
	protected $db;
	protected $genres_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $genres_table)
	{
		$this->db = $db;
		$this->genres_table = $genres_table;
	}

	public function get_all()
	{
		$sql = 'SELECT * FROM ' . $this->genres_table . '
			ORDER BY genre_category ASC, genre_order ASC, genre_name ASC';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Restituisce i generi raggruppati per categoria:
	 * ['Rock' => [riga, riga, ...], ...]
	 *
	 * @return array
	 */
	public function get_all_grouped()
	{
		$grouped = [];

		foreach ($this->get_all() as $genre)
		{
			$category = ($genre['genre_category'] !== '') ? $genre['genre_category'] : '';
			$grouped[$category][] = $genre;
		}

		return $grouped;
	}

	/**
	 * Elenco delle categorie esistenti, utile per i suggerimenti in ACP.
	 *
	 * @return array
	 */
	public function get_categories()
	{
		$sql = 'SELECT DISTINCT genre_category FROM ' . $this->genres_table . "
			WHERE genre_category <> ''
			ORDER BY genre_category ASC";
		$result = $this->db->sql_query($sql);

		$categories = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$categories[] = $row['genre_category'];
		}
		$this->db->sql_freeresult($result);

		return $categories;
	}

	public function get_one($genre_id)
	{
		$sql = 'SELECT * FROM ' . $this->genres_table . ' WHERE genre_id = ' . (int) $genre_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	public function add($name, $category = '', $order = 0)
	{
		$sql_ary = [
			'genre_name'		=> (string) $name,
			'genre_category'	=> (string) $category,
			'genre_order'		=> (int) $order,
		];

		$sql = 'INSERT INTO ' . $this->genres_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	public function edit($genre_id, $name, $category = '', $order = 0)
	{
		$sql_ary = [
			'genre_name'		=> (string) $name,
			'genre_category'	=> (string) $category,
			'genre_order'		=> (int) $order,
		];

		$sql = 'UPDATE ' . $this->genres_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE genre_id = ' . (int) $genre_id;
		$this->db->sql_query($sql);
	}

	public function delete($genre_id)
	{
		$sql = 'DELETE FROM ' . $this->genres_table . ' WHERE genre_id = ' . (int) $genre_id;
		$this->db->sql_query($sql);
	}
}
