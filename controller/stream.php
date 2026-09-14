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

use salvocortesiano\musicshare\repository\song_repository;
use salvocortesiano\musicshare\service\storage_helper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class stream
{
	protected $auth;
	protected $user;
	protected $request;
	protected $config;
	protected $cache;
	protected $song_repository;
	protected $storage_helper;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\user $user,
		\phpbb\request\request $request,
		\phpbb\config\config $config,
		\phpbb\cache\driver\driver_interface $cache,
		song_repository $song_repository,
		storage_helper $storage_helper
	)
	{
		$this->auth = $auth;
		$this->user = $user;
		$this->request = $request;
		$this->config = $config;
		$this->cache = $cache;
		$this->song_repository = $song_repository;
		$this->storage_helper = $storage_helper;
	}

	public function stream($song_id)
	{
		$song = $this->song_repository->get_song($song_id);

		if (!$song || !$this->can_listen($song))
		{
			return new Response('Not found', 404);
		}

		$file = $this->storage_helper->get_song_file($song);

		if (!is_file($file))
		{
			return new Response('Not found', 404);
		}

		if ($this->should_count_play($song))
		{
			$this->song_repository->increment_play_count($song_id);

			// riga datata, per le classifiche a periodo
			$this->song_repository->log_play(
				$song_id,
				(int) $this->user->data['user_id'],
				(string) $this->user->data['session_id']
			);

			// il riquadro dei brani recenti mostra il contatore ed è tenuto
			// in cache: senza invalidarla resterebbe fermo fino alla
			// scadenza. Gli ascolti sono molto meno frequenti delle
			// visite alle pagine, quindi la cache resta comunque utile.
			$this->cache->destroy(
				\salvocortesiano\musicshare\event\listener::feed_cache_key_from_config($this->config)
			);
		}

		return $this->send_file($file, $this->get_mime($song['file_ext']), true);
	}

	/**
	 * Download del file originale, se l'amministratore lo consente.
	 */
	public function download($song_id)
	{
		if (empty($this->config['musicshare_allow_download']))
		{
			return new Response('Forbidden', 403);
		}

		$song = $this->song_repository->get_song($song_id);

		if (!$song || !$this->can_listen($song))
		{
			return new Response('Not found', 404);
		}

		// l'autore può vietare il download del proprio brano; resta
		// scaricabile da lui stesso e da chi modera
		if (isset($song['allow_download']) && !$song['allow_download'])
		{
			$is_owner = ((int) $song['user_id'] === (int) $this->user->data['user_id']);

			if (!$is_owner && !$this->auth->acl_get('m_musicshare_manage'))
			{
				return new Response('Forbidden', 403);
			}
		}

		$file = $this->storage_helper->get_song_file($song);

		if (!is_file($file))
		{
			return new Response('Not found', 404);
		}

		// Conteggio del download.
		//
		// Si conta la persona, non la richiesta: un gestore di download
		// apre piu' connessioni in parallelo sullo stesso file, e
		// contando le richieste HTTP un unico download ne valeva cinque.
		// Gli scaricamenti dell'autore sul proprio brano non contano.
		if ((int) $song['user_id'] !== (int) $this->user->data['user_id']
			&& !$this->is_partial_request())
		{
			$ore = isset($this->config['musicshare_play_interval'])
				? (int) $this->config['musicshare_play_interval']
				: 12;

			$contato = $this->song_repository->log_download(
				$song_id,
				(int) $this->user->data['user_id'],
				(string) $this->user->data['session_id'],
				$ore
			);

			if ($contato)
			{
				// il riquadro dei brani recenti mostra i contatori ed è
				// tenuto in cache: senza invalidarla resterebbe fermo
				$this->cache->destroy(
					\salvocortesiano\musicshare\event\listener::feed_cache_key_from_config($this->config)
				);
			}
		}

		$name = $song['song_artist'] !== ''
			? $song['song_artist'] . ' - ' . $song['song_title']
			: $song['song_title'];

		// nome file sicuro per l'intestazione HTTP
		$name = preg_replace('/[^\w\s.\-]/u', '', $name);
		$name = trim($name) !== '' ? trim($name) : 'song';
		$name .= '.' . $song['file_ext'];

		$response = $this->send_file($file, $this->get_mime($song['file_ext']), false);
		$response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '"');

		return $response;
	}

	public function cover($song_id)
	{
		$song = $this->song_repository->get_song($song_id);

		if (!$song)
		{
			return new Response('Not found', 404);
		}

		$file = $this->storage_helper->get_cover_file($song);

		if (!$file || !is_file($file))
		{
			return new Response('Not found', 404);
		}

		$mime = (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'png') ? 'image/png' : 'image/jpeg';

		return $this->send_file($file, $mime, false);
	}

	/**
	 * Un brano è ascoltabile se è approvato, oppure se chi lo richiede ne
	 * è l'autore o ha il permesso di moderazione: senza questa eccezione
	 * l'autore non potrebbe riascoltare un proprio brano in attesa di
	 * approvazione, pur vedendolo elencato in "I miei brani".
	 *
	 * @param array $song
	 * @return bool
	 */
	protected function can_listen(array $song)
	{
		$is_owner = ((int) $song['user_id'] === (int) $this->user->data['user_id']);

		// senza permesso di accesso alla sezione non si ascolta nulla,
		// nemmeno conoscendo l'indirizzo diretto del file
		if (!$this->auth->acl_get('u_musicshare_view') && !$is_owner)
		{
			return false;
		}

		if (!empty($song['song_approved']))
		{
			return true;
		}

		if ($is_owner)
		{
			return true;
		}

		return (bool) $this->auth->acl_get('m_musicshare_manage');
	}

	protected function get_mime($ext)
	{
		$map = array(
			'mp3'	=> 'audio/mpeg',
			'ogg'	=> 'audio/ogg',
			'oga'	=> 'audio/ogg',
			'flac'	=> 'audio/flac',
			'wav'	=> 'audio/wav',
			'm4a'	=> 'audio/mp4',
			'aac'	=> 'audio/aac',
		);

		return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
	}

	/**
	 * Invia un file supportando le richieste Range, necessario per il seek
	 * nella barra di avanzamento del player HTML5 anche su file grandi.
	 */
	/**
	 * Questa richiesta va contata come un ascolto?
	 *
	 * Il contatore misurava le richieste HTTP, non gli ascolti. Ogni
	 * spostamento nella barra di avanzamento fa chiedere al browser un
	 * altro pezzo del file, e ogni pezzo valeva un ascolto in piu': un
	 * utente che ascoltava un brano una volta sola, spostandosi tre
	 * volte, ne generava quattro.
	 *
	 * Si contano quindi solo le richieste iniziali, e non piu' di una
	 * per persona e per brano entro l'intervallo scelto in ACP.
	 *
	 * @param array $song
	 * @return bool
	 */
	/**
	 * La richiesta e' la continuazione di un trasferimento gia' avviato?
	 *
	 * Vale sia per lo spostamento nella barra del lettore sia per le
	 * connessioni parallele dei gestori di download.
	 *
	 * @return bool
	 */
	protected function is_partial_request()
	{
		$range = (string) $this->request->server('HTTP_RANGE', '');

		if ($range !== '' && preg_match('/bytes=(\d*)-/', $range, $m))
		{
			return (($m[1] === '') ? 0 : (int) $m[1]) > 0;
		}

		return false;
	}

	protected function should_count_play(array $song)
	{
		// gli ascolti di prova dell'autore non gonfiano il contatore
		if ((int) $song['user_id'] === (int) $this->user->data['user_id'])
		{
			return false;
		}

		// Richiesta parziale che riparte da un punto diverso dall'inizio:
		// e' la continuazione di un ascolto gia' contato, non uno nuovo.
		if ($this->is_partial_request())
		{
			return false;
		}

		$ore = isset($this->config['musicshare_play_interval'])
			? (int) $this->config['musicshare_play_interval']
			: 12;

		if ($ore <= 0)
		{
			return true;
		}

		return !$this->song_repository->played_recently(
			(int) $song['song_id'],
			(int) $this->user->data['user_id'],
			(string) $this->user->data['session_id'],
			$ore
		);
	}

	protected function send_file($file, $mime, $support_range)
	{
		$size = filesize($file);
		$start = 0;
		$end = $size - 1;
		$status = 200;
		$length = $size;

		$headers = array(
			'Content-Type'	=> $mime,
			'Cache-Control'	=> 'public, max-age=86400',
		);

		if ($support_range)
		{
			$headers['Accept-Ranges'] = 'bytes';

			$range_header = (string) $this->request->server('HTTP_RANGE', '');

			if ($range_header && preg_match('/bytes=(\d*)-(\d*)/', $range_header, $matches))
			{
				$start = ($matches[1] === '') ? 0 : (int) $matches[1];
				$end = ($matches[2] === '') ? $size - 1 : (int) $matches[2];
				$end = min($end, $size - 1);

				if ($start > $end)
				{
					return new Response('', 416);
				}

				$status = 206;
				$length = $end - $start + 1;
				$headers['Content-Range'] = sprintf('bytes %d-%d/%d', $start, $end, $size);
			}
		}

		$headers['Content-Length'] = $length;

		$response = new StreamedResponse();
		$response->setStatusCode($status);
		$response->headers->add($headers);

		$response->setCallback(function () use ($file, $start, $length)
		{
			$handle = fopen($file, 'rb');
			fseek($handle, $start);
			$remaining = $length;
			$chunk_size = 8192;

			while ($remaining > 0 && !feof($handle))
			{
				$read_size = min($chunk_size, $remaining);
				echo fread($handle, $read_size);
				$remaining -= $read_size;
				flush();
			}

			fclose($handle);
		});

		return $response;
	}
}
