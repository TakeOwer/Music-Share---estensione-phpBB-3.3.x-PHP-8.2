<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\controller;

use salvocortesiano\musicshare\repository\playlist_repository;
use salvocortesiano\musicshare\repository\song_repository;
use salvocortesiano\musicshare\service\upload_handler;
use Symfony\Component\HttpFoundation\JsonResponse;

class ajax
{
	protected $auth;
	protected $user;
	protected $request;
	protected $playlist_repository;
	protected $upload_handler;
	protected $song_repository;
	protected $helper;
	protected $config;
	protected $cache;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\user $user,
		\phpbb\request\request $request,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\cache\driver\driver_interface $cache,
		playlist_repository $playlist_repository,
		song_repository $song_repository,
		upload_handler $upload_handler
	)
	{
		$this->auth = $auth;
		$this->user = $user;
		$this->request = $request;
		$this->config = $config;
		$this->helper = $helper;
		$this->cache = $cache;
		$this->playlist_repository = $playlist_repository;
		$this->song_repository = $song_repository;
		$this->upload_handler = $upload_handler;
	}

	/**
	 * Brani caricati dopo un certo momento, usati dall'avviso a comparsa.
	 * Sola lettura, nessuna modifica di stato: non richiede l'hash CSRF.
	 */
	public function recent()
	{
		if (empty($this->config['musicshare_toast_enabled']))
		{
			return new JsonResponse(['success' => false, 'songs' => []]);
		}

		$since = $this->request->variable('since', 0);
		$now = time();

		// alla prima richiesta non si mostra nulla: si prende solo il
		// momento attuale come riferimento per i controlli successivi
		if ($since <= 0)
		{
			return new JsonResponse(['success' => true, 'now' => $now, 'songs' => []]);
		}

		// evita richieste che risalgano troppo indietro nel tempo
		$since = max($since, $now - 86400);

		// di norma non si avvisa chi ha caricato il brano: saprebbe gia'
		// di averlo fatto. L'amministratore puo' pero' chiedere che
		// l'avviso arrivi anche a lui, come conferma del caricamento.
		$exclude = empty($this->config['musicshare_toast_self'])
			? (int) $this->user->data['user_id']
			: 0;

		$songs = $this->song_repository->get_recent_songs(3, $since, $exclude);

		$out = [];
		foreach ($songs as $song)
		{
			$out[] = [
				'id'		=> (int) $song['song_id'],
				'title'		=> $song['song_title'],
				'artist'	=> $song['song_artist'],
				'uploader'	=> $song['username'],
				'cover'		=> !empty($song['cover_path'])
					? $this->helper->route('salvocortesiano_musicshare_cover', ['song_id' => $song['song_id']])
					: '',
				'url'		=> $this->helper->route('salvocortesiano_musicshare_user', ['user_id' => (int) $song['user_id']]),
			];
		}

		return new JsonResponse(['success' => true, 'now' => $now, 'songs' => $out]);
	}

	/**
	 * Restituisce l'elenco delle playlist dell'utente collegato,
	 * usato dal menu "Aggiungi a playlist" nel player.
	 */
	public function get_playlists()
	{
		if ((int) $this->user->data['user_id'] === ANONYMOUS)
		{
			return new JsonResponse(array('success' => false, 'error' => 'MUSICSHARE_NO_PERMISSION'), 403);
		}

		$playlists = $this->playlist_repository->get_by_user((int) $this->user->data['user_id']);

		$out = array();
		foreach ($playlists as $playlist)
		{
			$out[] = array(
				'id'	=> (int) $playlist['playlist_id'],
				'name'	=> $playlist['playlist_name'],
			);
		}

		return new JsonResponse(array('success' => true, 'playlists' => $out));
	}

	/**
	 * Aggiunge un brano a una playlist esistente dell'utente.
	 */
	public function add_to_playlist()
	{
		if (!$this->check_ajax_access())
		{
			return new JsonResponse(array('success' => false, 'error' => 'MUSICSHARE_NO_PERMISSION'), 403);
		}

		$playlist_id = $this->request->variable('playlist_id', 0);
		$song_id = $this->request->variable('song_id', 0);

		$playlist = $this->playlist_repository->get($playlist_id);

		if (!$playlist || (int) $playlist['user_id'] !== (int) $this->user->data['user_id'])
		{
			return new JsonResponse(array('success' => false, 'error' => 'MUSICSHARE_PLAYLIST_NOT_FOUND'), 404);
		}

		$added = $this->playlist_repository->add_song($playlist_id, $song_id);

		return new JsonResponse(array('success' => (bool) $added));
	}

	/**
	 * Crea al volo una nuova playlist e vi aggiunge subito il brano
	 * (usato dal menu "Aggiungi a playlist" quando l'utente digita un
	 * nome nuovo invece di scegliere una playlist esistente).
	 */
	public function create_playlist_and_add()
	{
		if (!$this->check_ajax_access())
		{
			return new JsonResponse(array('success' => false, 'error' => 'MUSICSHARE_NO_PERMISSION'), 403);
		}

		$name = $this->request->variable('playlist_name', '', true);
		$song_id = $this->request->variable('song_id', 0);

		if ($name === '')
		{
			return new JsonResponse(array('success' => false, 'error' => 'MUSICSHARE_PLAYLIST_NAME_EMPTY'), 400);
		}

		$playlist_id = $this->playlist_repository->add((int) $this->user->data['user_id'], $name, '', false);
		$this->playlist_repository->add_song($playlist_id, $song_id);

		return new JsonResponse(array('success' => true, 'playlist_id' => $playlist_id, 'name' => $name));
	}

	/**
	 * Upload di un brano via AJAX, così il browser può mostrare
	 * l'avanzamento reale del trasferimento senza ricaricare la pagina.
	 */
	public function upload()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		if ((int) $this->user->data['user_id'] === ANONYMOUS || !$this->auth->acl_get('u_musicshare_upload'))
		{
			return new JsonResponse([
				'success'	=> false,
				'type'		=> 'error',
				'message'	=> $this->user->lang('MUSICSHARE_NO_PERMISSION'),
			], 403);
		}

		if (!check_form_key('musicshare_upload'))
		{
			return new JsonResponse([
				'success'	=> false,
				'message'	=> $this->user->lang('FORM_INVALID'),
			], 400);
		}

		$title = $this->request->variable('song_title', '', true);
		$artist = $this->request->variable('song_artist', '', true);
		$genre_ids = array_map('intval', $this->request->variable('genre_ids', [0]));

		$result = $this->upload_handler->handle_upload(
			(int) $this->user->data['user_id'],
			$genre_ids,
			$title,
			$artist,
			$this->request->variable('song_album', '', true),
			$this->request->variable('song_year', 0)
		);

		if (!$result['success'])
		{
			return new JsonResponse([
				'success'	=> false,
				'type'		=> 'error',
				'message'	=> $this->user->lang($result['error']),
			]);
		}

		// Il tipo di esito lo decide il server, che sa se il brano è
		// visibile o resta in attesa: al JavaScript non tocca indovinarlo.
		$in_attesa = !empty($this->config['musicshare_require_approval']) || !empty($result['recognized']);

		return new JsonResponse([
			'success'	=> true,
			'song_id'	=> (int) $result['song_id'],
			'type'		=> $in_attesa ? 'pending' : 'success',
			'message'	=> $in_attesa
				? $this->user->lang('MUSICSHARE_UPLOAD_PENDING')
				: $this->user->lang('MUSICSHARE_UPLOAD_SUCCESS'),
		]);
	}

	/**
	 * Dati dei brani inseriti nei messaggi con il BBCode.
	 *
	 * Riceve più identificativi in una sola richiesta: un messaggio può
	 * contenere diversi brani e non ha senso una chiamata per ciascuno.
	 * I permessi vengono verificati qui, alla visualizzazione.
	 */
	public function embed()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		if (!$this->auth->acl_get('u_musicshare_view'))
		{
			return new JsonResponse(['success' => false, 'songs' => []], 403);
		}

		$ids = array_slice(array_unique(array_filter(array_map(
			'intval',
			explode(',', $this->request->variable('ids', ''))
		))), 0, 20);

		if (empty($ids))
		{
			return new JsonResponse(['success' => true, 'songs' => []]);
		}

		$out = [];

		foreach ($ids as $song_id)
		{
			$song = $this->song_repository->get_song($song_id);

			if (!$song)
			{
				continue;
			}

			$is_owner = ((int) $song['user_id'] === (int) $this->user->data['user_id']);

			// i brani non approvati restano visibili solo all'autore e a
			// chi modera: un messaggio non deve poterli mostrare a tutti
			if (empty($song['song_approved']) && !$is_owner && !$this->auth->acl_get('m_musicshare_manage'))
			{
				continue;
			}

			$out[] = [
				'id'		=> (int) $song['song_id'],
				'title'		=> (string) $song['song_title'],
				'artist'	=> (string) $song['song_artist'],
				'album'		=> (string) $song['song_album'],
				'duration'	=> (int) $song['song_duration'],
				'stream'	=> $this->helper->route('salvocortesiano_musicshare_stream', ['song_id' => $song['song_id']]),
				'cover'		=> !empty($song['cover_path'])
					? $this->helper->route('salvocortesiano_musicshare_cover', ['song_id' => $song['song_id']])
					: '',
				'url'		=> $this->helper->route('salvocortesiano_musicshare_user', ['user_id' => (int) $song['user_id']]),
				'pending'	=> empty($song['song_approved']),
			];
		}

		return new JsonResponse(['success' => true, 'songs' => $out]);
	}

	/**
	 * Registra il voto di un utente su un brano.
	 */
	public function vote()
	{
		$this->user->add_lang_ext('salvocortesiano/musicshare', 'common');

		if (empty($this->config['musicshare_votes_enabled']))
		{
			return new JsonResponse(['success' => false, 'message' => $this->user->lang('MUSICSHARE_VOTES_DISABLED')], 403);
		}

		if ((int) $this->user->data['user_id'] === ANONYMOUS)
		{
			return new JsonResponse(['success' => false, 'message' => $this->user->lang('MUSICSHARE_VOTE_LOGIN')], 403);
		}

		if (!check_link_hash($this->request->variable('hash', ''), 'musicshare_ajax'))
		{
			return new JsonResponse(['success' => false, 'message' => $this->user->lang('FORM_INVALID')], 400);
		}

		$song_id = $this->request->variable('song_id', 0);
		$value = $this->request->variable('vote', 0);

		if ($value !== 1 && $value !== -1)
		{
			return new JsonResponse(['success' => false, 'message' => $this->user->lang('MUSICSHARE_VOTE_ERROR')], 400);
		}

		$song = $this->song_repository->get_song($song_id);

		if (!$song || !$song['song_approved'])
		{
			return new JsonResponse(['success' => false, 'message' => $this->user->lang('MUSICSHARE_SONG_NOT_FOUND')], 404);
		}

		// non si vota il proprio brano
		if ((int) $song['user_id'] === (int) $this->user->data['user_id'])
		{
			return new JsonResponse(['success' => false, 'message' => $this->user->lang('MUSICSHARE_VOTE_OWN')], 403);
		}

		$result = $this->song_repository->set_vote($song_id, (int) $this->user->data['user_id'], $value);

		// il riquadro "Caricati di recente" tiene in cache anche i contatori
		// dei voti: senza questa riga mostrerebbe i valori vecchi fino alla
		// scadenza, e ricaricando la pagina i voti sembrerebbero spariti
		$this->cache->destroy(
			\salvocortesiano\musicshare\event\listener::feed_cache_key_from_config($this->config)
		);

		return new JsonResponse([
			'success'	=> true,
			'likes'		=> $result['likes'],
			'dislikes'	=> $result['dislikes'],
			'vote'		=> $result['vote'],
		]);
	}

	/**
	 * Verifica base anti-CSRF (hash di sessione, vedi event\listener) e
	 * permesso di creare/gestire playlist personali.
	 */
	protected function check_ajax_access()
	{
		if ((int) $this->user->data['user_id'] === ANONYMOUS || !$this->auth->acl_get('u_musicshare_playlist'))
		{
			return false;
		}

		$hash = $this->request->variable('hash', '');

		return check_link_hash($hash, 'musicshare_ajax');
	}
}
