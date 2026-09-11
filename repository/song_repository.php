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

class song_repository
{
	protected $db;
	protected $songs_table;
	protected $song_genre_table;
	protected $genres_table;
	protected $votes_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $songs_table, $song_genre_table, $genres_table, $votes_table = '')
	{
		$this->db = $db;
		$this->songs_table = $songs_table;
		$this->song_genre_table = $song_genre_table;
		$this->genres_table = $genres_table;
		$this->votes_table = $votes_table;
	}

	/**
	 * Registra o annulla il voto di un utente su un brano. Rivotare lo
	 * stesso valore equivale a togliere il voto, come nei social.
	 *
	 * @param int $song_id
	 * @param int $user_id
	 * @param int $value 1 oppure -1
	 * @return array likes, dislikes e voto dell'utente dopo l'operazione
	 */
	public function set_vote($song_id, $user_id, $value)
	{
		$song_id = (int) $song_id;
		$user_id = (int) $user_id;
		$value = ($value > 0) ? 1 : -1;

		$sql = 'SELECT vote FROM ' . $this->votes_table . '
			WHERE song_id = ' . $song_id . ' AND user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$current = $row ? (int) $row['vote'] : 0;

		if ($current === $value)
		{
			// stesso voto premuto di nuovo: si annulla
			$sql = 'DELETE FROM ' . $this->votes_table . '
				WHERE song_id = ' . $song_id . ' AND user_id = ' . $user_id;
			$this->db->sql_query($sql);
			$new_vote = 0;
		}
		else if ($current !== 0)
		{
			$sql = 'UPDATE ' . $this->votes_table . '
				SET vote = ' . $value . ', vote_time = ' . time() . '
				WHERE song_id = ' . $song_id . ' AND user_id = ' . $user_id;
			$this->db->sql_query($sql);
			$new_vote = $value;
		}
		else
		{
			$sql_ary = array(
				'song_id'	=> $song_id,
				'user_id'	=> $user_id,
				'vote'		=> $value,
				'vote_time'	=> time(),
			);
			$sql = 'INSERT INTO ' . $this->votes_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
			$new_vote = $value;
		}

		$counts = $this->recount_votes($song_id);
		$counts['vote'] = $new_vote;

		return $counts;
	}

	/**
	 * Ricalcola i contatori del brano dalla tabella dei voti e li salva.
	 *
	 * @param int $song_id
	 * @return array
	 */
	public function recount_votes($song_id)
	{
		$song_id = (int) $song_id;

		$sql = 'SELECT vote, COUNT(*) AS cnt FROM ' . $this->votes_table . '
			WHERE song_id = ' . $song_id . '
			GROUP BY vote';
		$result = $this->db->sql_query($sql);

		$likes = 0;
		$dislikes = 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ((int) $row['vote'] > 0)
			{
				$likes = (int) $row['cnt'];
			}
			else
			{
				$dislikes = (int) $row['cnt'];
			}
		}
		$this->db->sql_freeresult($result);

		$this->update_song($song_id, array(
			'song_likes'	=> $likes,
			'song_dislikes'	=> $dislikes,
		));

		return array('likes' => $likes, 'dislikes' => $dislikes);
	}

	/**
	 * Voti espressi da un utente sui brani indicati, in una sola query.
	 *
	 * @param array $song_ids
	 * @param int $user_id
	 * @return array song_id => 1 | -1
	 */
	public function get_user_votes(array $song_ids, $user_id)
	{
		$song_ids = array_filter(array_map('intval', $song_ids));

		if (empty($song_ids) || (int) $user_id <= 0 || $this->votes_table === '')
		{
			return array();
		}

		$sql = 'SELECT song_id, vote FROM ' . $this->votes_table . '
			WHERE user_id = ' . (int) $user_id . '
				AND ' . $this->db->sql_in_set('song_id', $song_ids);
		$result = $this->db->sql_query($sql);

		$out = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[(int) $row['song_id']] = (int) $row['vote'];
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	/**
	 * Elimina i voti di un brano cancellato.
	 *
	 * @param int $song_id
	 * @return void
	 */
	public function delete_votes($song_id)
	{
		if ($this->votes_table === '')
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->votes_table . ' WHERE song_id = ' . (int) $song_id;
		$this->db->sql_query($sql);
	}

	public function add_song(array $data, array $genre_ids = array())
	{
		$sql = 'INSERT INTO ' . $this->songs_table . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
		$song_id = (int) $this->db->sql_nextid();

		$this->set_genres($song_id, $genre_ids);

		return $song_id;
	}

	public function set_genres($song_id, array $genre_ids)
	{
		$sql = 'DELETE FROM ' . $this->song_genre_table . ' WHERE song_id = ' . (int) $song_id;
		$this->db->sql_query($sql);

		$genre_ids = array_filter(array_map('intval', $genre_ids));

		if (!empty($genre_ids))
		{
			$sql_ary = array();
			foreach ($genre_ids as $genre_id)
			{
				$sql_ary[] = array(
					'song_id'	=> (int) $song_id,
					'genre_id'	=> (int) $genre_id,
				);
			}
			$this->db->sql_multi_insert($this->song_genre_table, $sql_ary);
		}
	}

	public function update_song($song_id, array $data)
	{
		$sql = 'UPDATE ' . $this->songs_table . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . ' WHERE song_id = ' . (int) $song_id;
		$this->db->sql_query($sql);
	}

	public function get_song($song_id)
	{
		$sql = 'SELECT * FROM ' . $this->songs_table . ' WHERE song_id = ' . (int) $song_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	public function get_song_genres($song_id)
	{
		$sql = 'SELECT g.genre_id, g.genre_name
			FROM ' . $this->genres_table . ' g, ' . $this->song_genre_table . ' sg
			WHERE sg.song_id = ' . (int) $song_id . '
				AND sg.genre_id = g.genre_id
			ORDER BY g.genre_name ASC';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Generi di più brani in una sola interrogazione, per non farne una
	 * per ogni riga dell'elenco.
	 *
	 * @param array $song_ids
	 * @return array song_id => elenco di nomi di genere
	 */
	public function get_genres_for_songs(array $song_ids)
	{
		$song_ids = array_filter(array_map('intval', $song_ids));

		if (empty($song_ids))
		{
			return [];
		}

		$sql = 'SELECT sg.song_id, g.genre_name
			FROM ' . $this->song_genre_table . ' sg, ' . $this->genres_table . ' g
			WHERE g.genre_id = sg.genre_id
				AND ' . $this->db->sql_in_set('sg.song_id', $song_ids) . '
			ORDER BY g.genre_name ASC';
		$result = $this->db->sql_query($sql);

		$out = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[(int) $row['song_id']][] = $row['genre_name'];
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	public function get_songs_by_genre($genre_id, $start = 0, $limit = 25, $approved_only = true)
	{
		$sql = 'SELECT s.*, u.username, u.user_colour
			FROM ' . $this->songs_table . ' s, ' . $this->song_genre_table . ' sg, ' . USERS_TABLE . ' u
			WHERE sg.genre_id = ' . (int) $genre_id . '
				AND sg.song_id = s.song_id
				AND u.user_id = s.user_id' .
				($approved_only ? ' AND s.song_approved = 1' : '') . '
			ORDER BY s.upload_time DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function count_songs_by_genre($genre_id, $approved_only = true)
	{
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->songs_table . ' s, ' . $this->song_genre_table . ' sg
			WHERE sg.genre_id = ' . (int) $genre_id . '
				AND sg.song_id = s.song_id' .
				($approved_only ? ' AND s.song_approved = 1' : '');
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['cnt'];
	}

	/**
	 * Brani approvati di un utente, per la pagina pubblica del suo profilo
	 * musicale. Include il nome per coerenza con le altre liste.
	 */
	public function get_public_songs_by_user($user_id, $start = 0, $limit = 25)
	{
		$sql = 'SELECT s.*, u.username, u.user_colour
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE s.user_id = ' . (int) $user_id . '
				AND u.user_id = s.user_id
				AND s.song_approved = 1
			ORDER BY s.upload_time DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function get_songs_by_user($user_id)
	{
		$sql = 'SELECT * FROM ' . $this->songs_table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY upload_time DESC';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Verifica se l'utente ha gia' caricato un file con la stessa impronta.
	 *
	 * @param string $hash
	 * @param int $user_id
	 * @return bool
	 */
	public function hash_exists_for_user($hash, $user_id)
	{
		if ($hash === '')
		{
			return false;
		}

		$sql = 'SELECT song_id FROM ' . $this->songs_table . "
			WHERE file_hash = '" . $this->db->sql_escape($hash) . "'
				AND user_id = " . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	/**
	 * Numero di brani approvati caricati da ciascuno degli utenti indicati.
	 * Una sola query per l'intera pagina, così non si appesantisce
	 * viewtopic con una interrogazione per messaggio.
	 *
	 * @param array $user_ids
	 * @return array  user_id => conteggio
	 */
	public function count_songs_for_users(array $user_ids)
	{
		$user_ids = array_filter(array_map('intval', $user_ids));

		if (empty($user_ids))
		{
			return [];
		}

		$sql = 'SELECT user_id, COUNT(*) AS cnt FROM ' . $this->songs_table . '
			WHERE song_approved = 1
				AND ' . $this->db->sql_in_set('user_id', $user_ids) . '
			GROUP BY user_id';
		$result = $this->db->sql_query($sql);

		$counts = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$counts[(int) $row['user_id']] = (int) $row['cnt'];
		}
		$this->db->sql_freeresult($result);

		return $counts;
	}

	/**
	 * Statistiche di un singolo utente: brani caricati e ascolti totali.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_stats($user_id)
	{
		$sql = 'SELECT COUNT(*) AS songs, SUM(play_count) AS plays, SUM(song_duration) AS duration
			FROM ' . $this->songs_table . '
			WHERE song_approved = 1
				AND user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return [
			'songs'		=> (int) $row['songs'],
			'plays'		=> (int) $row['plays'],
			'duration'	=> (int) $row['duration'],
		];
	}

	public function get_user_total_size($user_id)
	{
		$sql = 'SELECT SUM(file_size) AS total_size FROM ' . $this->songs_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['total_size'];
	}

	public function delete_song($song_id)
	{
		$sql = 'DELETE FROM ' . $this->songs_table . ' WHERE song_id = ' . (int) $song_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->song_genre_table . ' WHERE song_id = ' . (int) $song_id;
		$this->db->sql_query($sql);

		$this->delete_votes($song_id);
	}

	public function increment_play_count($song_id)
	{
		$sql = 'UPDATE ' . $this->songs_table . ' SET play_count = play_count + 1 WHERE song_id = ' . (int) $song_id;
		$this->db->sql_query($sql);
	}

	/**
	 * Ultimi brani approvati, per il feed dei caricamenti recenti e per
	 * l'avviso a comparsa.
	 *
	 * @param int $limit
	 * @param int $since        solo i brani caricati dopo questo momento (0 = tutti)
	 * @param int $exclude_user esclude i brani di questo utente (0 = nessuno)
	 * @return array
	 */
	public function get_recent_songs($limit = 10, $since = 0, $exclude_user = 0)
	{
		$where = '';

		if ($since > 0)
		{
			$where .= ' AND s.upload_time > ' . (int) $since;
		}

		if ($exclude_user > 0)
		{
			$where .= ' AND s.user_id <> ' . (int) $exclude_user;
		}

		$sql = 'SELECT s.*, u.username, u.user_colour
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE s.song_approved = 1
				AND u.user_id = s.user_id' . $where . '
			ORDER BY s.upload_time DESC';

		// limite 0 = nessun tetto: si legge tutto l'elenco
		$result = ($limit > 0)
			? $this->db->sql_query_limit($sql, $limit)
			: $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Brani recenti con un tetto per singolo utente, così un solo utente
	 * molto attivo non riempie l'intero riquadro.
	 *
	 * MySQL 5.x non offre un modo semplice per limitare le righe per
	 * gruppo, quindi si legge un blocco più ampio e si filtra in PHP:
	 * il costo resta trascurabile perché la lettura è comunque limitata.
	 *
	 * @param int $total    quanti brani restituire in tutto
	 * @param int $per_user quanti al massimo per ciascun utente (0 = nessun limite)
	 * @return array
	 */
	public function get_recent_songs_capped($total = 10, $per_user = 2)
	{
		$total = (int) $total;
		$per_user = (int) $per_user;

		// $total <= 0 significa "tutti i brani"
		$unlimited = ($total <= 0);

		if ($per_user <= 0)
		{
			return $this->get_recent_songs($unlimited ? 0 : $total);
		}

		// margine di lettura: sufficiente anche se pochi utenti dominano
		$fetch = $unlimited ? 0 : min(1000, $total * max(3, $per_user + 2));
		$candidates = $this->get_recent_songs($fetch);

		$out = [];
		$per_user_count = [];

		foreach ($candidates as $song)
		{
			$uid = (int) $song['user_id'];
			$used = isset($per_user_count[$uid]) ? $per_user_count[$uid] : 0;

			if ($used >= $per_user)
			{
				continue;
			}

			$per_user_count[$uid] = $used + 1;
			$out[] = $song;

			if (!$unlimited && count($out) >= $total)
			{
				break;
			}
		}

		return $out;
	}

	/**
	 * Elenco degli utenti che hanno caricato almeno un brano, con i
	 * rispettivi totali. Una sola interrogazione, con ricerca facoltativa
	 * su nome utente ed email.
	 *
	 * @param int $start
	 * @param int $limit
	 * @param string $keywords
	 * @param string $order  songs | plays | recent | name
	 * @return array
	 */
	public function get_uploaders($start = 0, $limit = 25, $keywords = '', $order = 'songs')
	{
		$where = $this->uploader_where($keywords);

		$orders = array(
			'songs'		=> 'songs DESC, u.username_clean ASC',
			'plays'		=> 'plays DESC, songs DESC',
			'recent'	=> 'last_upload DESC',
			'name'		=> 'u.username_clean ASC',
		);
		$order_by = isset($orders[$order]) ? $orders[$order] : $orders['songs'];

		$sql = 'SELECT s.user_id, u.username, u.user_colour,
				COUNT(*) AS songs,
				SUM(s.play_count) AS plays,
				SUM(s.song_likes) AS likes,
				MAX(s.upload_time) AS last_upload
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE u.user_id = s.user_id
				AND s.song_approved = 1' . $where . '
			GROUP BY s.user_id, u.username, u.user_colour, u.username_clean
			ORDER BY ' . $order_by;
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Ultimo brano caricato da ciascun utente indicato.
	 *
	 * Riceve le coppie utente/data gia' calcolate dall'elenco, cosi' la
	 * ricerca e' esatta e la condizione resta piccola: una sola
	 * interrogazione per l'intera pagina, non una per utente.
	 *
	 * @param array $coppie user_id => upload_time
	 * @return array user_id => riga del brano
	 */
	public function get_last_songs(array $coppie)
	{
		if (empty($coppie))
		{
			return array();
		}

		$condizioni = array();

		foreach ($coppie as $user_id => $quando)
		{
			$condizioni[] = '(s.user_id = ' . (int) $user_id . ' AND s.upload_time = ' . (int) $quando . ')';
		}

		$sql = 'SELECT s.song_id, s.user_id, s.song_title, s.song_artist, s.song_album, s.upload_time
			FROM ' . $this->songs_table . ' s
			WHERE s.song_approved = 1
				AND (' . implode(' OR ', $condizioni) . ')';
		$result = $this->db->sql_query($sql);

		$out = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			// se due brani condividono lo stesso istante si tiene il primo
			if (!isset($out[(int) $row['user_id']]))
			{
				$out[(int) $row['user_id']] = $row;
			}
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	/**
	 * Quanti utenti hanno caricato almeno un brano.
	 *
	 * @param string $keywords
	 * @return int
	 */
	public function count_uploaders($keywords = '')
	{
		$where = $this->uploader_where($keywords);

		$sql = 'SELECT COUNT(DISTINCT s.user_id) AS cnt
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE u.user_id = s.user_id
				AND s.song_approved = 1' . $where;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['cnt'];
	}

	/**
	 * Condizione di ricerca su nome utente ed email.
	 *
	 * @param string $keywords
	 * @return string
	 */
	protected function uploader_where($keywords)
	{
		$keywords = trim((string) $keywords);

		if ($keywords === '')
		{
			return '';
		}

		$like = $this->db->sql_like_expression(
			$this->db->get_any_char() . $keywords . $this->db->get_any_char()
		);

		return " AND (u.username {$like} OR u.user_email {$like})";
	}

	public function get_top_songs($limit = 10, $approved_only = true)
	{
		$sql = 'SELECT s.*, u.username, u.user_colour
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE u.user_id = s.user_id' .
			($approved_only ? ' AND s.song_approved = 1' : '') . '
			ORDER BY s.play_count DESC, s.upload_time DESC';
		$result = $this->db->sql_query_limit($sql, $limit);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function search($keywords, $start = 0, $limit = 25, $genre_id = 0)
	{
		$sql = 'SELECT s.*, u.username, u.user_colour
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u' . $this->search_join($genre_id) . '
			WHERE s.song_approved = 1
				AND u.user_id = s.user_id
				AND (' . $this->build_search_where($keywords, 's.') . ')' . $this->search_genre_where($genre_id) . '
			ORDER BY s.upload_time DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function count_search($keywords, $genre_id = 0)
	{
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->songs_table . ' s' . $this->search_join($genre_id) . '
			WHERE s.song_approved = 1
				AND (' . $this->build_search_where($keywords, 's.') . ')' . $this->search_genre_where($genre_id);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['cnt'];
	}

	protected function search_join($genre_id)
	{
		return $genre_id ? ', ' . $this->song_genre_table . ' sg' : '';
	}

	protected function search_genre_where($genre_id)
	{
		if (!$genre_id)
		{
			return '';
		}

		return ' AND sg.song_id = s.song_id AND sg.genre_id = ' . (int) $genre_id;
	}

	protected function build_search_where($keywords, $prefix = '')
	{
		$keywords = trim((string) $keywords);
		$like = $this->db->sql_like_expression($this->db->get_any_char() . $keywords . $this->db->get_any_char());

		return "{$prefix}song_title {$like} OR {$prefix}song_artist {$like} OR {$prefix}song_album {$like}";
	}

	/**
	 * Condizione di ricerca del pannello di moderazione: titolo, artista,
	 * album e nome dell'utente che ha caricato il brano.
	 *
	 * @param string $keywords
	 * @return string
	 */
	protected function build_moderation_where($keywords)
	{
		$keywords = trim((string) $keywords);

		if ($keywords === '')
		{
			return '';
		}

		$like = $this->db->sql_like_expression($this->db->get_any_char() . $keywords . $this->db->get_any_char());

		return ' AND (s.song_title ' . $like
			. ' OR s.song_artist ' . $like
			. ' OR s.song_album ' . $like
			. ' OR u.username ' . $like . ')';
	}

	/**
	 * Elenco di tutti i brani (di ogni utente) con il nome dell'autore,
	 * usato dal pannello di gestione in ACP.
	 */
	public function get_all_songs($start = 0, $limit = 25, $keywords = '')
	{
		$sql = 'SELECT s.*, u.username, u.user_colour
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE u.user_id = s.user_id' . $this->build_moderation_where($keywords) . '
			ORDER BY s.upload_time DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Conteggio con gli stessi criteri dell'elenco.
	 *
	 * Prima il conteggio non univa la tabella degli utenti e usava una
	 * condizione diversa: con una ricerca il totale poteva non
	 * corrispondere alle righe mostrate.
	 */
	public function count_all_songs($keywords = '')
	{
		$sql = 'SELECT COUNT(*) AS cnt
			FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE u.user_id = s.user_id' . $this->build_moderation_where($keywords);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['cnt'];
	}

	public function set_approved($song_id, $approved)
	{
		$this->update_song($song_id, array('song_approved' => $approved ? 1 : 0));
	}

	public function get_pending_songs($start = 0, $limit = 25)
	{
		$sql = 'SELECT s.*, u.username FROM ' . $this->songs_table . ' s, ' . USERS_TABLE . ' u
			WHERE s.song_approved = 0
				AND u.user_id = s.user_id
			ORDER BY s.upload_time ASC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function count_pending_songs()
	{
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->songs_table . ' WHERE song_approved = 0';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['cnt'];
	}

	public function approve_song($song_id)
	{
		$this->update_song($song_id, array('song_approved' => 1));
	}
}
