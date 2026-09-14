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

class metadata_extractor
{
	protected $storage_helper;
	protected $getid3;
	protected $ext_root_path;

	public function __construct(storage_helper $storage_helper, $ext_root_path = '')
	{
		$this->storage_helper = $storage_helper;
		$this->ext_root_path = $ext_root_path;
	}

	protected function get_getid3()
	{
		if ($this->getid3 !== null)
		{
			return $this->getid3;
		}

		// getID3 è incluso nell'estensione (vendor/getid3), così l'estensione
		// funziona anche senza aver lanciato "composer install".
		if (!class_exists('getID3'))
		{
			$bundled = $this->ext_root_path . 'vendor/getid3/getid3.php';

			if (is_file($bundled))
			{
				require_once $bundled;
			}
		}

		if (class_exists('getID3'))
		{
			$this->getid3 = new \getID3();
		}

		return $this->getid3;
	}

	/**
	 * Analizza un file audio e restituisce titolo, artista, album, anno,
	 * durata (secondi) e, se presente, i byte dell'immagine di copertina
	 * incorporata nei tag (ID3/Vorbis Comment/ecc, gestita da getID3).
	 *
	 * @param string $file_path percorso assoluto del file
	 * @return array
	 */
	public function extract($file_path)
	{
		$info = array(
			'title'			=> '',
			'artist'		=> '',
			'album'			=> '',
			'year'			=> 0,
			'duration'		=> 0,
			'bitrate'		=> 0,
			'bitrate_mode'	=> '',
			'samplerate'	=> 0,
			'channels'		=> 0,
			'cover_data'	=> false,
			'cover_mime'	=> false,
		);

		$getid3 = $this->get_getid3();

		if (!$getid3)
		{
			return $info;
		}

		$file_info = $getid3->analyze($file_path);

		if (isset($file_info['playtime_seconds']))
		{
			$info['duration'] = (int) round($file_info['playtime_seconds']);
		}

		// Dati tecnici: getID3 li ricava dall'intestazione del file, non
		// dai tag, quindi ci sono anche quando il file non ha tag.
		if (!empty($file_info['audio']))
		{
			$audio = $file_info['audio'];

			// il bitrate arriva in bit al secondo
			if (!empty($audio['bitrate']))
			{
				$info['bitrate'] = (int) round($audio['bitrate'] / 1000);
			}

			if (!empty($audio['bitrate_mode']))
			{
				$modo = strtolower((string) $audio['bitrate_mode']);
				$info['bitrate_mode'] = in_array($modo, array('vbr', 'cbr', 'abr'), true) ? $modo : '';
			}

			if (!empty($audio['sample_rate']))
			{
				$info['samplerate'] = (int) $audio['sample_rate'];
			}

			if (!empty($audio['channels']))
			{
				$info['channels'] = (int) $audio['channels'];
			}
		}

		if (!empty($file_info['comments']))
		{
			$comments = $file_info['comments'];
			$info['title'] = isset($comments['title'][0]) ? (string) $comments['title'][0] : '';
			$info['artist'] = isset($comments['artist'][0]) ? (string) $comments['artist'][0] : '';
			$info['album'] = isset($comments['album'][0]) ? (string) $comments['album'][0] : '';
			$info['year'] = isset($comments['year'][0]) ? (int) $comments['year'][0] : 0;
		}

		if (!empty($file_info['comments']['picture'][0]['data']))
		{
			$info['cover_data'] = $file_info['comments']['picture'][0]['data'];
			$info['cover_mime'] = isset($file_info['comments']['picture'][0]['image_mime'])
				? $file_info['comments']['picture'][0]['image_mime']
				: 'image/jpeg';
		}

		return $info;
	}

	/**
	 * Riga dei dati tecnici dell'audio, per come si mostra sotto il
	 * titolo: "320 kbit/s CBR &middot; 44,1 kHz &middot; Stereo".
	 *
	 * Le parti mancanti si saltano: i brani caricati prima di questa
	 * funzione non hanno questi dati finche' non vengono ricaricati, e
	 * mostrare "0 kbit/s" sarebbe peggio che non mostrare nulla.
	 *
	 * Statica e non legata a un oggetto perche' la stessa riga serve
	 * al controller, al listener del riquadro nell'indice e al Pannello
	 * di Controllo Utente: tre punti che non condividono altro.
	 *
	 * @param array $song
	 * @param \phpbb\user $user
	 * @return string
	 */
	public static function quality_label(array $song, \phpbb\user $user)
	{
		$pezzi = array();

		if (!empty($song['song_bitrate']))
		{
			$bitrate = (int) $song['song_bitrate'] . ' ' . $user->lang('MUSICSHARE_KBITS');

			if (!empty($song['song_bitrate_mode']))
			{
				$bitrate .= ' ' . strtoupper((string) $song['song_bitrate_mode']);
			}

			$pezzi[] = $bitrate;
		}

		if (!empty($song['song_samplerate']))
		{
			// 44100 Hz si legge meglio come 44,1 kHz
			$khz = (int) $song['song_samplerate'] / 1000;
			$pezzi[] = rtrim(rtrim(number_format($khz, 1, $user->lang('MUSICSHARE_DECIMAL'), ''), '0'), $user->lang('MUSICSHARE_DECIMAL'))
				. ' ' . $user->lang('MUSICSHARE_KHZ');
		}

		$canali = (int) (isset($song['song_channels']) ? $song['song_channels'] : 0);

		if ($canali > 0)
		{
			// oltre il 5.1 si dice quanti sono e basta, invece di
			// inventare un nome per ogni combinazione
			if ($canali === 1)
			{
				$pezzi[] = $user->lang('MUSICSHARE_MONO');
			}
			else if ($canali === 2)
			{
				$pezzi[] = $user->lang('MUSICSHARE_STEREO');
			}
			else if ($canali === 6)
			{
				$pezzi[] = '5.1';
			}
			else
			{
				$pezzi[] = $user->lang('MUSICSHARE_CHANNELS_N', $canali);
			}
		}

		return implode(' &middot; ', $pezzi);
	}
}
