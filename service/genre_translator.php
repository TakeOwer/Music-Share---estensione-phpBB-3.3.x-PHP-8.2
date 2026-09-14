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
 * Traduzione dei nomi delle categorie di genere.
 *
 * Le categorie sono righe del database, non stringhe di lingua: sono
 * modificabili dall'amministratore, quindi non possono essere semplici
 * chiavi di traduzione. Quelle inserite dall'estensione, però, hanno
 * nomi noti: se esiste una traduzione per uno di quei nomi la si usa,
 * altrimenti si mostra il nome così com'è stato scritto.
 *
 * Risultato: chi usa il forum in inglese vede "Traditional, Folk and
 * Italian Music", e una categoria creata a mano resta intatta.
 */
class genre_translator
{
	/**
	 * Nome predefinito => chiave di lingua.
	 *
	 * I nomi sono quelli inseriti dalla migrazione dei generi.
	 */
	const CATEGORIE = array(
		'Dance ed Elettronica'						=> 'MUSICSHARE_CAT_DANCE',
		'Hip-Hop e Urban'							=> 'MUSICSHARE_CAT_HIPHOP',
		'Pop'										=> 'MUSICSHARE_CAT_POP',
		'Rock'										=> 'MUSICSHARE_CAT_ROCK',
		'Metal'										=> 'MUSICSHARE_CAT_METAL',
		'Popolare, Tradizionale e Musica Italiana'	=> 'MUSICSHARE_CAT_TRADITIONAL',
		'Musica Latina e Caraibica'					=> 'MUSICSHARE_CAT_LATIN',
		'Jazz e Blues'								=> 'MUSICSHARE_CAT_JAZZ',
		'Soul, Funk e Disco'						=> 'MUSICSHARE_CAT_SOUL',
		'Musica Classica e Colonne Sonore'			=> 'MUSICSHARE_CAT_CLASSICAL',
		'Folk, Country e Radici'					=> 'MUSICSHARE_CAT_FOLK',
		'Musica Globale ed Etnica'					=> 'MUSICSHARE_CAT_WORLD',
	);

	protected $user;

	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
	}

	/**
	 * Nome della categoria nella lingua di chi guarda.
	 *
	 * @param string $nome nome memorizzato nel database
	 * @param string $vuoto chiave da usare se la categoria non è indicata
	 * @return string
	 */
	public function category($nome, $vuoto = 'MUSICSHARE_OTHER_GENRES')
	{
		$nome = trim((string) $nome);

		if ($nome === '')
		{
			return $this->user->lang($vuoto);
		}

		if (!isset(self::CATEGORIE[$nome]))
		{
			// categoria creata o rinominata dall'amministratore: si
			// mostra esattamente quello che ha scritto
			return $nome;
		}

		$chiave = self::CATEGORIE[$nome];

		// se il file di lingua non contiene la chiave, lang() restituisce
		// la chiave stessa: in quel caso è meglio il nome originale
		$tradotto = $this->user->lang($chiave);

		return ($tradotto === $chiave) ? $nome : $tradotto;
	}
}
