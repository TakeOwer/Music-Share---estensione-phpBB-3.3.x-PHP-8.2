<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\service;

use salvocortesiano\musicshare\repository\song_repository;

/**
 * Apertura automatica di un argomento per ogni brano caricato.
 *
 * E' la parte che lega la libreria alla vita del forum: il brano non
 * resta in una pagina a parte, ma diventa un argomento dove si discute,
 * si risponde e si citano gli altri, con il lettore gia' incorporato.
 *
 * L'argomento viene aperto al momento del caricamento e non
 * all'approvazione: submit_post() attribuisce sempre il messaggio
 * all'utente collegato, quindi creandolo in fase di moderazione
 * risulterebbe scritto dal moderatore invece che dall'autore del brano.
 */
class topic_creator
{
	protected $config;
	protected $config_text;
	protected $db;
	protected $user;
	protected $song_repository;
	protected $root_path;
	protected $php_ext;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\config\db_text $config_text,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		song_repository $song_repository,
		$root_path,
		$php_ext
	)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->db = $db;
		$this->user = $user;
		$this->song_repository = $song_repository;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * La funzione è attiva e configurata?
	 *
	 * @return bool
	 */
	public function is_enabled()
	{
		return !empty($this->config['musicshare_topic_enabled'])
			&& (int) $this->config['musicshare_topic_forum'] > 0;
	}

	/**
	 * Apre l'argomento per un brano, se non esiste già.
	 *
	 * @param array $song riga del brano
	 * @return int identificativo dell'argomento, 0 se non creato
	 */
	public function create_for_song(array $song)
	{
		if (!$this->is_enabled())
		{
			return 0;
		}

		// gia' fatto: un secondo caricamento della stessa pagina non deve
		// aprire un doppione
		if (!empty($song['topic_id']))
		{
			return 0;
		}

		// l'argomento e' scritto dall'utente collegato: se per qualche
		// motivo non coincide con l'autore del brano si rinuncia, invece
		// di attribuire a qualcuno parole non sue
		if ((int) $song['user_id'] !== (int) $this->user->data['user_id'])
		{
			return 0;
		}

		$forum_id = (int) $this->config['musicshare_topic_forum'];

		if (!$this->forum_exists($forum_id))
		{
			return 0;
		}

		if (!function_exists('submit_post'))
		{
			include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
		}

		if (!class_exists('parse_message'))
		{
			include($this->root_path . 'includes/message_parser.' . $this->php_ext);
		}

		$titolo = $this->build_title($song);
		$testo = $this->build_message($song);

		$parser = new \parse_message($testo);
		$parser->parse(true, true, true, true, false, true, true);

		$poll = array();

		$dati = array(
			'forum_id'			=> $forum_id,
			'topic_id'			=> 0,
			'post_id'			=> 0,
			'icon_id'			=> 0,
			'poster_id'			=> (int) $this->user->data['user_id'],
			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'enable_sig'		=> true,
			'enable_indexing'	=> true,
			'message'			=> $parser->message,
			'message_md5'		=> md5($parser->message),
			'bbcode_bitfield'	=> $parser->bbcode_bitfield,
			'bbcode_uid'		=> $parser->bbcode_uid,
			'post_edit_locked'	=> 0,
			'topic_title'		=> $titolo,
			'notify_set'		=> false,
			'notify'			=> false,
			'post_time'			=> 0,
			'forum_name'		=> '',
			'enable_indexing'	=> true,
			// Nessuna forzatura della visibilita': l'argomento segue le
			// normali regole del forum, esattamente come se l'utente lo
			// avesse scritto a mano.
			//
			// Attenzione: qui NON vanno messi true/false. phpBB li
			// converte in interi e li confronta con le proprie costanti,
			// dove 0 significa "da approvare" e 1 "approvato": passare
			// false equivaleva a mandare ogni argomento in coda di
			// moderazione, anche quelli scritti da un amministratore.
			'topic_status'		=> ITEM_UNLOCKED,
			'topic_time_limit'	=> 0,
			'attachment_data'	=> array(),
			'filename_data'		=> array('filecomment' => '', 'filename' => ''),
			'post_edit_reason'	=> '',
		);

		submit_post('post', $titolo, (string) $this->user->data['username'], POST_NORMAL, $poll, $dati);

		if (empty($dati['topic_id']))
		{
			return 0;
		}

		$this->song_repository->update_song((int) $song['song_id'], array(
			'topic_id'	=> (int) $dati['topic_id'],
			'post_id'	=> (int) $dati['post_id'],
		));

		return (int) $dati['topic_id'];
	}

	/**
	 * Titolo dell'argomento.
	 *
	 * Il modello è impostabile in ACP: %1$s è il titolo del brano,
	 * %2$s l'artista.
	 *
	 * @param array $song
	 * @return string
	 */
	protected function build_title(array $song)
	{
		$modello = trim((string) $this->config_text->get('musicshare_topic_title'));

		if ($modello === '')
		{
			$modello = '%1$s';
		}

		$titolo = sprintf($modello, (string) $song['song_title'], (string) $song['song_artist']);
		$titolo = trim(preg_replace('/\s{2,}/', ' ', $titolo));

		// phpBB limita il titolo a 120 caratteri
		return utf8_substr($titolo, 0, 120);
	}

	/**
	 * Testo del primo messaggio, con il lettore incorporato.
	 *
	 * @param array $song
	 * @return string
	 */
	protected function build_message(array $song)
	{
		$modello = trim((string) $this->config_text->get('musicshare_topic_message'));

		if ($modello === '')
		{
			$modello = '{BRANO}';
		}

		$testo = str_replace(
			array('{TITOLO}', '{ARTISTA}', '{DESCRIZIONE}', '{BRANO}'),
			array(
				(string) $song['song_title'],
				(string) $song['song_artist'],
				(string) (isset($song['song_description']) ? $song['song_description'] : ''),
				'[musicshare]' . (int) $song['song_id'] . '[/musicshare]',
			),
			$modello
		);

		// se il modello non contiene il segnaposto del lettore lo si
		// aggiunge comunque: un argomento senza brano non avrebbe senso
		if (strpos($testo, '[musicshare]') === false)
		{
			$testo .= "\n\n" . '[musicshare]' . (int) $song['song_id'] . '[/musicshare]';
		}

		return trim($testo);
	}

	/**
	 * Il forum indicato esiste ed è una sezione in cui si può scrivere?
	 *
	 * @param int $forum_id
	 * @return bool
	 */
	protected function forum_exists($forum_id)
	{
		$sql = 'SELECT forum_type FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row && (int) $row['forum_type'] === FORUM_POST;
	}
}
