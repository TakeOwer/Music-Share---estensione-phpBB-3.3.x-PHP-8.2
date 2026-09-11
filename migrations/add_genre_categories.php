<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\migrations;

class add_genre_categories extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_modules'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_genres'	=> [
					'genre_category'	=> ['VCHAR_UNI:100', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_genres'	=> ['genre_category'],
			],
		];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'insert_default_genres']]],
		];
	}

	/**
	 * Inserisce l'elenco dei generi predefiniti, saltando quelli
	 * gia' presenti (per non creare duplicati se l'amministratore
	 * ne ha gia' aggiunti a mano con lo stesso nome).
	 */
	public function insert_default_genres()
	{
		$table = $this->table_prefix . 'musicshare_genres';

		// Nomi gia' presenti in tabella
		$existing = [];
		$sql = 'SELECT genre_name FROM ' . $table;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$existing[utf8_clean_string($row['genre_name'])] = true;
		}
		$this->db->sql_freeresult($result);

		$rows = [];
		foreach ($this->get_default_genres() as $genre)
		{
			list($category, $name, $order) = $genre;

			if (isset($existing[utf8_clean_string($name)]))
			{
				continue;
			}

			$rows[] = [
				'genre_name'		=> $name,
				'genre_category'	=> $category,
				'genre_order'		=> (int) $order,
			];
		}

		if (!empty($rows))
		{
			$this->db->sql_multi_insert($table, $rows);
		}
	}

	/**
	 * Elenco dei generi predefiniti: [categoria, nome, ordine]
	 *
	 * @return array
	 */
	protected function get_default_genres()
	{
		return [
			['Dance ed Elettronica', 'House (Deep, Tech, Progressive, Electro)', 101],
			['Dance ed Elettronica', 'Techno (Minimal, Acid, Peak Time)', 102],
			['Dance ed Elettronica', 'Trance (Psytrance, Uplifting)', 103],
			['Dance ed Elettronica', 'Eurodance / Italodance', 104],
			['Dance ed Elettronica', 'Drum and Bass / Jungle', 105],
			['Dance ed Elettronica', 'Dubstep / Bass Music', 106],
			['Dance ed Elettronica', 'Synthwave / Vaporwave', 107],
			['Dance ed Elettronica', 'Ambient / Chillout', 108],
			['Hip-Hop e Urban', 'Boom Bap / Classic East Coast', 209],
			['Hip-Hop e Urban', 'West Coast / G-Funk', 210],
			['Hip-Hop e Urban', 'Trap', 211],
			['Hip-Hop e Urban', 'Drill (UK, Chicago, Brooklyn)', 212],
			['Hip-Hop e Urban', 'Conscious Hip-Hop', 213],
			['Hip-Hop e Urban', 'Cloud Rap', 214],
			['Hip-Hop e Urban', 'Lo-Fi Hip-Hop', 215],
			['Hip-Hop e Urban', 'R&B / Neo-Soul', 216],
			['Pop', 'Synthpop', 317],
			['Pop', 'Electropop', 318],
			['Pop', 'Dance-Pop', 319],
			['Pop', 'K-Pop / J-Pop', 320],
			['Pop', 'Indie Pop', 321],
			['Pop', 'Teen Pop', 322],
			['Rock', 'Classic Rock / Hard Rock', 423],
			['Rock', 'Alternative Rock / Indie Rock', 424],
			['Rock', 'Punk Rock / Pop Punk', 425],
			['Rock', 'Grunge', 426],
			['Rock', 'Post-Rock', 427],
			['Rock', 'Rock Progressivo', 428],
			['Rock', 'Psychedelic Rock', 429],
			['Rock', 'Garage Rock', 430],
			['Metal', 'Heavy Metal', 531],
			['Metal', 'Thrash Metal', 532],
			['Metal', 'Death Metal', 533],
			['Metal', 'Black Metal', 534],
			['Metal', 'Power Metal', 535],
			['Metal', 'Nu Metal', 536],
			['Metal', 'Metalcore / Deathcore', 537],
			['Metal', 'Doom Metal / Sludge', 538],
			['Popolare, Tradizionale e Musica Italiana', 'Musica Leggera Italiana', 639],
			['Popolare, Tradizionale e Musica Italiana', 'Cantautorato', 640],
			['Popolare, Tradizionale e Musica Italiana', 'Pop Folk / Stornello', 641],
			['Popolare, Tradizionale e Musica Italiana', 'Liscio', 642],
			['Popolare, Tradizionale e Musica Italiana', 'Tarantella / Pizzica', 643],
			['Musica Latina e Caraibica', 'Reggaeton', 744],
			['Musica Latina e Caraibica', 'Salsa / Bachata / Merengue', 745],
			['Musica Latina e Caraibica', 'Reggae / Dub / Dancehall', 746],
			['Musica Latina e Caraibica', 'Samba / Bossa Nova', 747],
			['Musica Latina e Caraibica', 'Latin Trap', 748],
			['Musica Latina e Caraibica', 'Mambo / Cumbia', 749],
			['Jazz e Blues', 'Delta Blues / Chicago Blues', 850],
			['Jazz e Blues', 'Swing / Big Band', 851],
			['Jazz e Blues', 'Bebop / Hard Bop', 852],
			['Jazz e Blues', 'Cool Jazz', 853],
			['Jazz e Blues', 'Jazz Fusion', 854],
			['Jazz e Blues', 'Smooth Jazz', 855],
			['Jazz e Blues', 'Acid Jazz', 856],
			['Soul, Funk e Disco', 'Motown / Classic Soul', 957],
			['Soul, Funk e Disco', 'Funk', 958],
			['Soul, Funk e Disco', 'Disco 70s / Italo Disco', 959],
			['Soul, Funk e Disco', 'Neo-Soul', 960],
			['Soul, Funk e Disco', 'Gospel', 961],
			['Musica Classica e Colonne Sonore', 'Musica Antica / Rinascimentale', 1062],
			['Musica Classica e Colonne Sonore', 'Barocca', 1063],
			['Musica Classica e Colonne Sonore', 'Classica (Periodo Classico)', 1064],
			['Musica Classica e Colonne Sonore', 'Romantica', 1065],
			['Musica Classica e Colonne Sonore', 'Contemporanea / Minimalismo', 1066],
			['Musica Classica e Colonne Sonore', 'Colonne Sonore (Epic, Cinematic)', 1067],
			['Folk, Country e Radici', 'Country (Traditional, Modern, Outlaw)', 1168],
			['Folk, Country e Radici', 'Bluegrass', 1169],
			['Folk, Country e Radici', 'Folk Tradizionale / Protest Folk', 1170],
			['Folk, Country e Radici', 'Americana', 1171],
			['Folk, Country e Radici', 'Celtic Music', 1172],
			['Musica Globale ed Etnica', 'Afrobeats / Afro-House', 1273],
			['Musica Globale ed Etnica', 'Musica Araba / Raï', 1274],
			['Musica Globale ed Etnica', 'Flamenco', 1275],
			['Musica Globale ed Etnica', 'Highlife / Makossa', 1276],
			['Musica Globale ed Etnica', 'Balkan Beat', 1277],
		];
	}
}
