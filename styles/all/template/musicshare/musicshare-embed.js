/**
 * Music Share — lettori inseriti nei messaggi con il BBCode
 * [musicshare].
 *
 * Il BBCode produce solo un segnaposto con l'identificativo del brano.
 * Qui si raccolgono tutti i segnaposto della pagina, si chiedono i dati
 * al server con una sola richiesta e si costruisce per ciascuno un
 * lettore completo: riproduci, pausa, ferma, barra di avanzamento
 * cliccabile e trascinabile, tempi.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
(function () {
	'use strict';

	var cfg = window.musicshareConfig || {};
	var lang = cfg.lang || {};

	// un solo brano alla volta in riproduzione, come in un lettore vero
	var attivo = null;

	function formatta(secondi) {
		if (!secondi || isNaN(secondi)) {
			return '0:00';
		}

		var m = Math.floor(secondi / 60);
		var s = Math.floor(secondi % 60);

		return m + ':' + (s < 10 ? '0' : '') + s;
	}

	function bottone(classe, simbolo, etichetta) {
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'musicshare-embed-btn ' + classe;
		b.innerHTML = simbolo;
		b.title = etichetta || '';
		b.setAttribute('aria-label', etichetta || '');

		return b;
	}

	/**
	 * Costruisce il lettore dentro il segnaposto.
	 *
	 * @param HTMLElement box  il segnaposto
	 * @param object brano     dati ricevuti dal server
	 * @return void
	 */
	function costruisci(box, brano) {
		box.classList.add('musicshare-embed-ready');
		box.textContent = '';

		var audio = new window.Audio();
		audio.preload = 'none';
		audio.src = brano.stream;

		// copertina
		var cover = document.createElement('span');
		cover.className = 'musicshare-embed-cover';

		if (brano.cover) {
			var img = document.createElement('img');
			img.src = brano.cover;
			img.alt = '';
			cover.appendChild(img);
		} else {
			cover.innerHTML = '<span class="musicshare-embed-note">&#9835;</span>';
		}

		// informazioni
		var info = document.createElement('span');
		info.className = 'musicshare-embed-info';

		var titolo = document.createElement('a');
		titolo.className = 'musicshare-embed-title';
		titolo.href = brano.url;
		titolo.textContent = brano.title;

		var artista = document.createElement('span');
		artista.className = 'musicshare-embed-artist';
		artista.textContent = brano.artist + (brano.album ? ' \u2014 ' + brano.album : '');

		info.appendChild(titolo);
		info.appendChild(artista);

		if (brano.pending) {
			var attesa = document.createElement('span');
			attesa.className = 'musicshare-embed-pending';
			attesa.textContent = lang.embedPending || '';
			info.appendChild(attesa);
		}

		// comandi
		var comandi = document.createElement('span');
		comandi.className = 'musicshare-embed-controls';

		var play = bottone('musicshare-embed-play', '&#9654;', lang.play || 'Play');
		var stop = bottone('musicshare-embed-stop', '&#9632;', lang.stop || 'Stop');

		comandi.appendChild(play);
		comandi.appendChild(stop);

		// avanzamento
		var barra = document.createElement('span');
		barra.className = 'musicshare-embed-bar';
		barra.setAttribute('role', 'slider');
		barra.setAttribute('aria-label', lang.seek || '');

		var riempita = document.createElement('span');
		riempita.className = 'musicshare-embed-bar-fill';
		barra.appendChild(riempita);

		var tempi = document.createElement('span');
		tempi.className = 'musicshare-embed-time';
		tempi.textContent = '0:00 / ' + formatta(brano.duration);

		var avanzamento = document.createElement('span');
		avanzamento.className = 'musicshare-embed-progress';
		avanzamento.appendChild(barra);
		avanzamento.appendChild(tempi);

		box.appendChild(cover);
		box.appendChild(info);
		box.appendChild(comandi);
		box.appendChild(avanzamento);

		/* --- comportamento --- */

		function aggiorna() {
			var durata = audio.duration || brano.duration || 0;
			var quota = durata > 0 ? (audio.currentTime / durata) : 0;

			riempita.style.width = Math.max(0, Math.min(100, quota * 100)) + '%';
			tempi.textContent = formatta(audio.currentTime) + ' / ' + formatta(durata);
			barra.setAttribute('aria-valuenow', Math.round(quota * 100));
		}

		function fermaAltri() {
			if (attivo && attivo !== audio) {
				attivo.pause();
				attivo.currentTime = 0;
			}
			attivo = audio;
		}

		play.addEventListener('click', function () {
			if (audio.paused) {
				fermaAltri();
				audio.play().catch(function () {});
			} else {
				audio.pause();
			}
		});

		stop.addEventListener('click', function () {
			audio.pause();
			audio.currentTime = 0;
			aggiorna();
		});

		audio.addEventListener('play', function () {
			play.innerHTML = '&#10074;&#10074;';
			play.title = lang.pause || 'Pause';
			play.setAttribute('aria-label', lang.pause || 'Pause');
			box.classList.add('musicshare-embed-playing');
		});

		audio.addEventListener('pause', function () {
			play.innerHTML = '&#9654;';
			play.title = lang.play || 'Play';
			play.setAttribute('aria-label', lang.play || 'Play');
			box.classList.remove('musicshare-embed-playing');
		});

		audio.addEventListener('timeupdate', aggiorna);
		audio.addEventListener('loadedmetadata', aggiorna);
		audio.addEventListener('ended', function () {
			audio.currentTime = 0;
			aggiorna();
		});

		/* --- spostamento nel brano --- */

		var trascina = false;

		function posizione(e) {
			var rect = barra.getBoundingClientRect();

			if (rect.width <= 0) {
				return null;
			}

			var x = (e.touches && e.touches.length) ? e.touches[0].clientX : e.clientX;
			var quota = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
			var durata = audio.duration || brano.duration || 0;

			return durata > 0 ? quota * durata : null;
		}

		function sposta(e) {
			var p = posizione(e);

			if (p === null) {
				return;
			}

			try {
				audio.currentTime = p;
			} catch (err) {
				return;
			}

			aggiorna();
		}

		barra.addEventListener('click', sposta);

		barra.addEventListener('mousedown', function (e) {
			trascina = true;
			e.preventDefault();
		});

		document.addEventListener('mousemove', function (e) {
			if (trascina) {
				sposta(e);
			}
		});

		document.addEventListener('mouseup', function () {
			trascina = false;
		});

		barra.addEventListener('touchmove', function (e) {
			sposta(e);
		}, { passive: true });

		aggiorna();
	}

	/**
	 * Cerca i segnaposto, chiede i dati e costruisce i lettori.
	 */
	function avvia() {
		var box = document.querySelectorAll('.musicshare-embed:not(.musicshare-embed-ready)');

		if (!box.length || !cfg.ajaxEmbed) {
			return;
		}

		var mappa = {};
		var ids = [];

		Array.prototype.forEach.call(box, function (b) {
			var id = b.getAttribute('data-song-id');

			if (!id) {
				return;
			}

			if (!mappa[id]) {
				mappa[id] = [];
				ids.push(id);
			}

			mappa[id].push(b);
			b.textContent = lang.embedLoading || '';
		});

		if (!ids.length) {
			return;
		}

		fetch(cfg.ajaxEmbed + (cfg.ajaxEmbed.indexOf('?') === -1 ? '?' : '&') + 'ids=' + encodeURIComponent(ids.join(',')), {
			credentials: 'same-origin'
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				var trovati = {};

				if (data && data.songs) {
					data.songs.forEach(function (brano) {
						trovati[brano.id] = true;

						(mappa[brano.id] || []).forEach(function (b) {
							costruisci(b, brano);
						});
					});
				}

				// brani inesistenti, rimossi o non visibili all'utente
				ids.forEach(function (id) {
					if (trovati[id]) {
						return;
					}

					mappa[id].forEach(function (b) {
						b.classList.add('musicshare-embed-ready', 'musicshare-embed-missing');
						b.textContent = lang.embedMissing || '';
					});
				});
			})
			.catch(function () {
				ids.forEach(function (id) {
					mappa[id].forEach(function (b) {
						b.classList.add('musicshare-embed-ready', 'musicshare-embed-missing');
						b.textContent = lang.embedMissing || '';
					});
				});
			});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', avvia);
	} else {
		avvia();
	}
}());
