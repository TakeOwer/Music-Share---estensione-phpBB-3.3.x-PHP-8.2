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
 * Licenze d'uso dichiarabili per un brano.
 *
 * Non e' un vincolo legale e non sostituisce un accordo: e' la
 * dichiarazione dell'autore su cosa consente, messa per iscritto accanto
 * al brano. Per un forum dedicato a loop e musica royalty free e'
 * l'informazione che serve a chi vuole usare quel materiale.
 */
class license_helper
{
	/**
	 * Codice => chiave di lingua. Il codice finisce nel database, quindi
	 * non va cambiato una volta in uso.
	 */
	const LICENZE = array(
		''			=> 'MUSICSHARE_LIC_NONE',
		'cc0'		=> 'MUSICSHARE_LIC_CC0',
		'ccby'		=> 'MUSICSHARE_LIC_CCBY',
		'ccbysa'	=> 'MUSICSHARE_LIC_CCBYSA',
		'ccbync'	=> 'MUSICSHARE_LIC_CCBYNC',
		'free'		=> 'MUSICSHARE_LIC_FREE',
		'ask'		=> 'MUSICSHARE_LIC_ASK',
		'allrights'	=> 'MUSICSHARE_LIC_ALLRIGHTS',
	);

	/**
	 * Le dodici note, in notazione anglosassone. Sono anche i codici che
	 * finiscono nel database: brevi e uguali in tutte le lingue.
	 *
	 * L'ordine corrisponde a quello dei nomi in MUSICSHARE_KEY_NOTES,
	 * quindi i due elenchi non si possono riordinare separatamente.
	 */
	const NOTE = array('C', 'C#', 'D', 'Eb', 'E', 'F', 'F#', 'G', 'Ab', 'A', 'Bb', 'B');

	protected $user;

	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
	}

	/**
	 * Tonalita' suggerite: le dodici maggiori e le dodici minori.
	 *
	 * Il valore e' il codice anglosassone che finisce nel database,
	 * l'etichetta il nome nella lingua del forum. Chi non conosce la
	 * notazione sceglie "la minore" e salva "Am"; chi la conosce puo'
	 * continuare a scrivere a mano, perche' il campo resta libero.
	 *
	 * @return array elenco di array con value e label
	 */
	public function get_key_options()
	{
		$nomi = array_map('trim', explode(',', (string) $this->user->lang('MUSICSHARE_KEY_NOTES')));

		// se la traduzione manca o non ha dodici voci si usano i codici
		// anche come etichette, invece di mostrare accoppiamenti sbagliati
		if (count($nomi) !== count(self::NOTE))
		{
			$nomi = self::NOTE;
		}

		$maggiore = $this->user->lang('MUSICSHARE_KEY_MAJOR');
		$minore = $this->user->lang('MUSICSHARE_KEY_MINOR');

		$out = array();

		foreach (self::NOTE as $i => $codice)
		{
			$out[] = array('VALUE' => $codice, 'LABEL' => $nomi[$i] . ' ' . $maggiore);
		}

		foreach (self::NOTE as $i => $codice)
		{
			$out[] = array('VALUE' => $codice . 'm', 'LABEL' => $nomi[$i] . ' ' . $minore);
		}

		return $out;
	}

	/**
	 * Il codice è fra quelli previsti?
	 *
	 * @param string $codice
	 * @return string codice valido, stringa vuota se non riconosciuto
	 */
	public function sanitize($codice)
	{
		$codice = strtolower(trim((string) $codice));

		return isset(self::LICENZE[$codice]) ? $codice : '';
	}

	/**
	 * Nome leggibile della licenza.
	 *
	 * @param string $codice
	 * @return string stringa vuota se non indicata
	 */
	public function label($codice)
	{
		$codice = $this->sanitize($codice);

		return ($codice === '') ? '' : $this->user->lang(self::LICENZE[$codice]);
	}

	/**
	 * Elenco per i menu a tendina.
	 *
	 * @param string $selezionata
	 * @return array
	 */
	public function get_options($selezionata = '')
	{
		$selezionata = $this->sanitize($selezionata);
		$out = array();

		foreach (self::LICENZE as $codice => $chiave)
		{
			$out[] = array(
				'CODE'			=> $codice,
				'NAME'			=> $this->user->lang($chiave),
				'S_SELECTED'	=> ($codice === $selezionata),
			);
		}

		return $out;
	}

	/**
	 * Tonalità ripulita: lettera, eventuale alterazione, maggiore o minore.
	 *
	 * @param string $tonalita
	 * @return string
	 */
	public function sanitize_key($tonalita)
	{
		$tonalita = trim((string) $tonalita);

		if ($tonalita === '')
		{
			return '';
		}

		// si accettano sia la notazione anglosassone sia quella italiana
		if (!preg_match('/^[A-Ga-g][#b]?m?$|^(do|re|mi|fa|sol|la|si)[#b]?( ?(maggiore|minore|magg|min|m))?$/iu', $tonalita))
		{
			return '';
		}

		return utf8_substr($tonalita, 0, 16);
	}
}
