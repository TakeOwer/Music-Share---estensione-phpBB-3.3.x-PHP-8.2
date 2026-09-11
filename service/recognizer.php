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

/**
 * Riconoscimento del brano tramite un servizio esterno di impronta
 * acustica (AudD oppure ACRCloud).
 *
 * Attenzione a cosa fa davvero: NON stabilisce se un brano sia protetto
 * dal diritto d'autore. Confronta l'audio con un archivio di pubblicazioni
 * commerciali e dice se corrisponde a una di esse. Una corrispondenza è
 * un indizio forte che il file non sia opera dell'utente; l'assenza di
 * corrispondenza non significa affatto che il brano sia libero.
 */
class recognizer
{
	protected $config;
	protected $config_text;
	protected $db;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\config\db_text $config_text,
		\phpbb\db\driver\driver_interface $db
	)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->db = $db;
	}

	/**
	 * Servizio configurato, o stringa vuota se il controllo è spento.
	 *
	 * @return string
	 */
	public function get_service()
	{
		$service = isset($this->config['musicshare_reco_service'])
			? (string) $this->config['musicshare_reco_service']
			: '';

		if ($service === 'audd' && trim((string) $this->config['musicshare_audd_token']) === '')
		{
			return '';
		}

		if ($service === 'acrcloud'
			&& (trim((string) $this->config['musicshare_acr_key']) === ''
				|| trim((string) $this->config['musicshare_acr_secret']) === ''))
		{
			return '';
		}

		return in_array($service, array('audd', 'acrcloud'), true) ? $service : '';
	}

	public function is_enabled()
	{
		return $this->get_service() !== '';
	}

	/**
	 * Elenco dei gruppi soggetti al controllo.
	 *
	 * @return array id dei gruppi
	 */
	public function get_groups()
	{
		$raw = (string) $this->config_text->get('musicshare_reco_groups');

		if (trim($raw) === '')
		{
			return array();
		}

		return array_values(array_filter(array_map('intval', explode(',', $raw))));
	}

	/**
	 * Il controllo si applica a questo utente?
	 * Nessun gruppo selezionato significa "nessuno": il controllo va
	 * acceso esplicitamente, per non consumare crediti a sorpresa.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function applies_to($user_id)
	{
		if (!$this->is_enabled())
		{
			return false;
		}

		$groups = $this->get_groups();

		if (empty($groups))
		{
			return false;
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . '
			WHERE user_id = ' . (int) $user_id . '
				AND user_pending = 0
				AND ' . $this->db->sql_in_set('group_id', $groups);
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	/**
	 * Analizza un file audio.
	 *
	 * @param string $file percorso del file
	 * @param int $duration durata in secondi, se nota
	 * @return array matched, title, artist, label, error
	 */
	public function identify($file, $duration = 0)
	{
		$empty = array('matched' => false, 'title' => '', 'artist' => '', 'label' => '', 'error' => '');

		if (!$this->is_enabled() || !is_file($file))
		{
			return $empty;
		}

		$sample = $this->read_sample($file, $duration);

		if ($sample === false)
		{
			return array_merge($empty, array('error' => 'read'));
		}

		return ($this->get_service() === 'audd')
			? $this->identify_audd($sample, $file)
			: $this->identify_acrcloud($sample);
	}

	/**
	 * Legge dall'inizio del file la porzione da inviare. I servizi
	 * analizzano pochi secondi: mandare l'intero file sarebbe inutile e,
	 * su brani lunghi, verrebbe comunque rifiutato.
	 *
	 * @param string $file
	 * @param int $duration
	 * @return string|false
	 */
	protected function read_sample($file, $duration)
	{
		$size = filesize($file);

		if ($size === false || $size <= 0)
		{
			return false;
		}

		$seconds = (int) $this->config['musicshare_reco_seconds'];
		$seconds = ($seconds > 0) ? min(30, $seconds) : 15;

		$bytes = $size;

		if ($duration > 0)
		{
			// dal rapporto fra dimensione e durata si ricava quanti byte
			// corrispondono ai secondi che ci servono
			$per_second = $size / $duration;
			$bytes = (int) ceil($per_second * $seconds);
		}

		// 384 KB si sono dimostrati sufficienti nelle prove sul campo:
		// il brano viene riconosciuto in circa un secondo.
		$bytes = max(65536, min($size, $bytes, 393216));

		return (string) @file_get_contents($file, false, null, 0, $bytes);
	}

	/**
	 * @param string $sample
	 * @param string $file usato solo per il nome
	 * @return array
	 */
	/**
	 * Tipo MIME dedotto dall'estensione del file.
	 *
	 * Va dichiarato per davvero nell'invio: con application/octet-stream
	 * i servizi possono rifiutare il contenuto sostenendo che non è stato
	 * inviato alcun audio.
	 *
	 * @param string $file
	 * @return string
	 */
	protected function mime_for($file)
	{
		$tipi = array(
			'mp3'	=> 'audio/mpeg',
			'ogg'	=> 'audio/ogg',
			'oga'	=> 'audio/ogg',
			'flac'	=> 'audio/flac',
			'wav'	=> 'audio/wav',
			'm4a'	=> 'audio/mp4',
			'aac'	=> 'audio/aac',
		);

		$ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));

		return isset($tipi[$ext]) ? $tipi[$ext] : 'audio/mpeg';
	}

	protected function identify_audd($sample, $file)
	{
		$fields = array(
			'api_token'	=> (string) $this->config['musicshare_audd_token'],
		);

		$response = $this->post_multipart(
			'https://api.audd.io/',
			$fields,
			array('file' => array(
				'name'	=> basename($file),
				'data'	=> $sample,
				'type'	=> $this->mime_for($file),
			))
		);

		return $this->parse_audd($response);
	}

	/**
	 * Interpreta la risposta di AudD.
	 *
	 * @param string|false $response
	 * @return array
	 */
	protected function parse_audd($response)
	{
		$empty = array('matched' => false, 'title' => '', 'artist' => '', 'label' => '', 'error' => '');

		if ($response === false)
		{
			return array_merge($empty, array('error' => 'network'));
		}

		$json = json_decode($response, true);

		if (!is_array($json) || !isset($json['status']))
		{
			return array_merge($empty, array('error' => 'response'));
		}

		if ($json['status'] !== 'success')
		{
			$message = isset($json['error']['error_message']) ? $json['error']['error_message'] : 'api';

			return array_merge($empty, array('error' => $message));
		}

		// result nullo significa "nessuna corrispondenza"
		if (empty($json['result']))
		{
			return $empty;
		}

		$r = $json['result'];

		return array(
			'matched'	=> true,
			'title'		=> isset($r['title']) ? (string) $r['title'] : '',
			'artist'	=> isset($r['artist']) ? (string) $r['artist'] : '',
			'label'		=> isset($r['label']) ? (string) $r['label'] : '',
			'error'		=> '',
		);
	}

	/**
	 * Come identify_audd, ma il file viene scaricato dal servizio a
	 * partire da un indirizzo. Usato dalla prova delle credenziali.
	 *
	 * @param string $url
	 * @return array
	 */
	protected function identify_audd_url($url)
	{
		$response = $this->post_multipart(
			'https://api.audd.io/',
			array(
				'api_token'	=> (string) $this->config['musicshare_audd_token'],
				'url'		=> $url,
			),
			array()
		);

		return $this->parse_audd($response);
	}

	/**
	 * @param string $sample
	 * @return array
	 */
	protected function identify_acrcloud($sample)
	{
		$host = trim((string) $this->config['musicshare_acr_host']);
		$key = (string) $this->config['musicshare_acr_key'];
		$secret = (string) $this->config['musicshare_acr_secret'];
		$timestamp = time();

		// firma richiesta da ACRCloud: HMAC-SHA1 dei parametri, in base64
		$string_to_sign = implode("\n", array('POST', '/v1/identify', $key, 'audio', '1', $timestamp));
		$signature = base64_encode(hash_hmac('sha1', $string_to_sign, $secret, true));

		$fields = array(
			'access_key'		=> $key,
			'data_type'			=> 'audio',
			'signature_version'	=> '1',
			'signature'			=> $signature,
			'sample_bytes'		=> strlen($sample),
			'timestamp'			=> $timestamp,
		);

		$response = $this->post_multipart(
			'https://' . $host . '/v1/identify',
			$fields,
			array('sample' => array(
				'name'	=> 'sample.mp3',
				'data'	=> $sample,
				'type'	=> 'audio/mpeg',
			))
		);

		if ($response === false)
		{
			return array('matched' => false, 'title' => '', 'artist' => '', 'label' => '', 'error' => 'network');
		}

		$json = json_decode($response, true);

		if (!is_array($json) || !isset($json['status']['code']))
		{
			return array('matched' => false, 'title' => '', 'artist' => '', 'label' => '', 'error' => 'response');
		}

		$code = (int) $json['status']['code'];

		// 1001 = nessuna corrispondenza, non è un errore
		if ($code === 1001)
		{
			return array('matched' => false, 'title' => '', 'artist' => '', 'label' => '', 'error' => '');
		}

		if ($code !== 0)
		{
			$message = isset($json['status']['msg']) ? (string) $json['status']['msg'] : 'api';

			return array('matched' => false, 'title' => '', 'artist' => '', 'label' => '', 'error' => $message);
		}

		$music = isset($json['metadata']['music'][0]) ? $json['metadata']['music'][0] : array();

		if (empty($music))
		{
			return array('matched' => false, 'title' => '', 'artist' => '', 'label' => '', 'error' => '');
		}

		$artists = array();
		if (!empty($music['artists']) && is_array($music['artists']))
		{
			foreach ($music['artists'] as $artist)
			{
				if (!empty($artist['name']))
				{
					$artists[] = (string) $artist['name'];
				}
			}
		}

		return array(
			'matched'	=> true,
			'title'		=> isset($music['title']) ? (string) $music['title'] : '',
			'artist'	=> implode(', ', $artists),
			'label'		=> isset($music['label']) ? (string) $music['label'] : '',
			'error'		=> '',
		);
	}

	/**
	 * Prova le credenziali configurate inviando un campione minimo.
	 * Serve al pulsante di verifica in ACP.
	 *
	 * @return array ok, message
	 */
	public function test_credentials()
	{
		if (!$this->is_enabled())
		{
			return array('ok' => false, 'message' => 'MUSICSHARE_RECO_TEST_NOT_CONFIGURED');
		}

		if ($this->get_service() === 'audd')
		{
			// AudD rifiuta i dati che non sono audio vero, quindi un
			// campione di silenzio farebbe fallire la prova anche con un
			// token valido. Si usa il file di esempio pubblicato nella
			// loro documentazione: verifica il token per davvero.
			$result = $this->identify_audd_url('https://audd.tech/example.mp3');
		}
		else
		{
			// ACRCloud accetta il campione e risponde "nessun risultato"
			// (codice 1001) se le credenziali sono valide
			$result = $this->identify_acrcloud(str_repeat("\0", 65536));
		}

		if ($result['error'] === 'network')
		{
			return array('ok' => false, 'message' => 'MUSICSHARE_RECO_TEST_NETWORK');
		}

		if ($result['error'] === 'response')
		{
			return array('ok' => false, 'message' => 'MUSICSHARE_RECO_TEST_RESPONSE');
		}

		if ($result['error'] !== '')
		{
			return array('ok' => false, 'message' => $result['error']);
		}

		return array('ok' => true, 'message' => 'MUSICSHARE_RECO_TEST_OK');
	}

	/**
	 * Invio multipart senza dipendenze esterne: cURL se disponibile,
	 * altrimenti i flussi di PHP.
	 *
	 * @param string $url
	 * @param array $fields
	 * @param array $files nome => array(name, data)
	 * @return string|false
	 */
	protected function post_multipart($url, array $fields, array $files)
	{
		$boundary = '----musicshare' . md5(uniqid('', true));
		$body = '';

		foreach ($fields as $name => $value)
		{
			$body .= "--{$boundary}\r\n";
			$body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
			$body .= $value . "\r\n";
		}

		foreach ($files as $name => $file)
		{
			// Il tipo va dichiarato per davvero: con
			// application/octet-stream i servizi possono rifiutare il
			// contenuto sostenendo che non è stato inviato alcun audio.
			$type = isset($file['type']) ? $file['type'] : 'application/octet-stream';

			$body .= "--{$boundary}\r\n";
			$body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$file['name']}\"\r\n";
			$body .= "Content-Type: {$type}\r\n\r\n";
			$body .= $file['data'] . "\r\n";
		}

		$body .= "--{$boundary}--\r\n";
		$content_type = 'multipart/form-data; boundary=' . $boundary;

		if (function_exists('curl_init'))
		{
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: ' . $content_type));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			$response = curl_exec($ch);
			$failed = ($response === false);
			curl_close($ch);

			return $failed ? false : $response;
		}

		$context = stream_context_create(array(
			'http'	=> array(
				'method'		=> 'POST',
				'header'		=> 'Content-Type: ' . $content_type,
				'content'		=> $body,
				'timeout'		=> 20,
				'ignore_errors'	=> true,
			),
		));

		$response = @file_get_contents($url, false, $context);

		return ($response === false) ? false : $response;
	}
}
