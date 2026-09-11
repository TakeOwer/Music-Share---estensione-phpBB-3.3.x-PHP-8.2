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
}
