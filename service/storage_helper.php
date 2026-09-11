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

class storage_helper
{
	protected $config;
	protected $root_path;

	public function __construct(\phpbb\config\config $config, $root_path)
	{
		$this->config = $config;
		$this->root_path = $root_path;
	}

	public function get_storage_path()
	{
		$path = (string) $this->config['musicshare_storage_path'];
		$path = rtrim($path, '/') . '/';

		// percorso assoluto (unix o windows) -> usalo così com'è,
		// altrimenti è relativo alla root del forum
		if (strpos($path, '/') === 0 || preg_match('#^[A-Za-z]:[\\\\/]#', $path))
		{
			return $path;
		}

		return rtrim($this->root_path, '/') . '/' . $path;
	}

	/**
	 * Cartella dei file di un utente. A seconda dell'impostazione in ACP
	 * viene usato il nome utente (piu' leggibile via FTP) oppure il solo
	 * ID numerico. Il nome viene comunque completato con l'ID, cosi' due
	 * utenti con nomi simili non finiscono nella stessa cartella e un
	 * cambio di nome non provoca collisioni.
	 *
	 * @param int $user_id
	 * @param string $username nome utente, se disponibile
	 * @return string
	 */
	public function get_user_dir($user_id, $username = '')
	{
		return $this->get_storage_path() . $this->get_user_folder($user_id, $username) . '/';
	}

	public function get_user_covers_dir($user_id, $username = '')
	{
		return $this->get_user_dir($user_id, $username) . 'covers/';
	}

	/**
	 * Nome della sottocartella di un utente (senza percorso).
	 *
	 * @param int $user_id
	 * @param string $username
	 * @return string
	 */
	public function get_user_folder($user_id, $username = '')
	{
		$user_id = (int) $user_id;
		$naming = isset($this->config['musicshare_folder_naming'])
			? (string) $this->config['musicshare_folder_naming']
			: 'username';

		if ($naming !== 'username' || $username === '')
		{
			return (string) $user_id;
		}

		$clean = $this->clean_folder_name($username);

		return ($clean !== '') ? $clean . '_' . $user_id : (string) $user_id;
	}

	/**
	 * Rende un nome utente utilizzabile come nome di cartella su qualsiasi
	 * file system: solo lettere, cifre, trattino e trattino basso.
	 *
	 * @param string $username
	 * @return string
	 */
	protected function clean_folder_name($username)
	{
		$name = (string) $username;

		// accenti e caratteri simili ricondotti alla lettera base
		if (function_exists('iconv'))
		{
			$converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $name);
			if ($converted !== false)
			{
				$name = $converted;
			}
		}

		$name = strtolower($name);
		$name = preg_replace('/[^a-z0-9_\-]+/', '-', $name);
		$name = trim((string) $name, '-_');

		return substr($name, 0, 40);
	}

	public function ensure_dir($dir)
	{
		if (!is_dir($dir))
		{
			@mkdir($dir, 0755, true);
		}

		$writable = is_dir($dir) && is_writable($dir);

		if ($writable)
		{
			$this->protect_dir($dir);
		}

		return $writable;
	}

	/**
	 * Deposita nella cartella i file che impediscono l'accesso diretto ai
	 * brani via URL e la navigazione dell'indice. Senza questo, chiunque
	 * conosca il percorso potrebbe scaricare i file scavalcando permessi e
	 * moderazione.
	 *
	 * @param string $dir
	 * @return void
	 */
	public function protect_dir($dir)
	{
		$htaccess = $dir . '.htaccess';

		if (!file_exists($htaccess))
		{
			$rules = "# Generato da Music Share: impedisce l'accesso diretto ai file.\n"
				. "# I brani vengono serviti dall'estensione, che verifica i permessi.\n"
				. "<IfModule mod_authz_core.c>\n"
				. "\tRequire all denied\n"
				. "</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n"
				. "\tOrder Allow,Deny\n"
				. "\tDeny from All\n"
				. "</IfModule>\n";

			@file_put_contents($htaccess, $rules);
		}

		$index = $dir . 'index.html';

		if (!file_exists($index))
		{
			@file_put_contents($index, '');
		}
	}

	/**
	 * Segnala se la cartella di archiviazione si trova dentro la radice del
	 * forum senza alcuna protezione: in quel caso i file potrebbero essere
	 * scaricabili direttamente dal web.
	 *
	 * @return bool
	 */
	public function is_web_exposed()
	{
		$storage = $this->get_storage_path();

		if (!is_dir($storage))
		{
			return false;
		}

		$real_storage = realpath($storage);
		$real_root = realpath($this->root_path);

		// fuori dalla radice del forum non è raggiungibile dal web
		// tramite l'indirizzo del forum stesso
		if ($real_storage === false || $real_root === false || strpos($real_storage, $real_root) !== 0)
		{
			return false;
		}

		// protetta dalla cartella files/ di phpBB, che ha già il suo .htaccess
		$files_dir = realpath($this->root_path . 'files');
		if ($files_dir !== false && strpos($real_storage, $files_dir) === 0)
		{
			return false;
		}

		return !file_exists($storage . '.htaccess');
	}

	public function is_storage_writable()
	{
		return $this->ensure_dir($this->get_storage_path());
	}

	public function get_allowed_extensions()
	{
		$ext = (string) $this->config['musicshare_allowed_ext'];
		$ext = explode(',', $ext);

		return array_values(array_filter(array_map('trim', $ext)));
	}

	public function get_song_file($song_row)
	{
		return $this->get_storage_path() . ltrim($song_row['file_path'], '/');
	}

	public function get_cover_file($song_row)
	{
		if (empty($song_row['cover_path']))
		{
			return false;
		}

		return $this->get_storage_path() . ltrim($song_row['cover_path'], '/');
	}

	/**
	 * Nome casuale per un file salvato nell'archivio.
	 *
	 * Non deriva dall'identificativo del brano: un nome ricavato dall'id,
	 * anche se cifrato, resterebbe calcolabile da chiunque conosca l'id e
	 * quindi indovinabile come un semplice numero progressivo. Servono
	 * byte davvero casuali.
	 *
	 * @param string $ext estensione senza il punto
	 * @return string
	 */
	public function random_filename($ext)
	{
		$token = '';

		if (function_exists('random_bytes'))
		{
			try
			{
				$token = bin2hex(random_bytes(16));
			}
			catch (\Exception $e)
			{
				$token = '';
			}
		}

		if ($token === '')
		{
			// ripiego: generatore di phpBB, comunque non prevedibile
			$token = md5(unique_id() . uniqid('', true));
		}

		$ext = preg_replace('/[^a-z0-9]/', '', strtolower((string) $ext));

		return $token . ($ext !== '' ? '.' . $ext : '');
	}

	public function delete_song_files($song_row)
	{
		$file = $this->get_song_file($song_row);
		if (is_file($file))
		{
			@unlink($file);
		}

		$cover = $this->get_cover_file($song_row);
		if ($cover && is_file($cover))
		{
			@unlink($cover);
		}
	}
}
