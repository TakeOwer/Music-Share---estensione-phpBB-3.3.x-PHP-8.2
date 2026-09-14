/**
 * Music Share - player globale
 *
 * Coda di riproduzione visibile e riordinabile, controlli
 * play/pausa/stop/avanti/indietro, barra di avanzamento con seek,
 * volume, marquee dei tag lunghi, forma d'onda (Web Audio API),
 * dissolvenza leggera tra un brano e l'altro, player riducibile a
 * barra sottile, menu "Aggiungi a playlist" con creazione al volo.
 */
(function () {
	'use strict';

	var player = document.getElementById('musicshare-player');
	if (!player) {
		return;
	}

	var cfg = window.musicshareConfig || {};
	var lang = cfg.lang || {};

	var audio = document.getElementById('musicshare-audio');
	var coverImg = document.getElementById('musicshare-player-cover-img');
	var titleEl = document.getElementById('musicshare-player-title');
	var marqueeWrap = titleEl ? titleEl.parentElement : null;
	var playBtn = document.getElementById('musicshare-play');
	var stopBtn = document.getElementById('musicshare-stop');
	var prevBtn = document.getElementById('musicshare-prev');
	var nextBtn = document.getElementById('musicshare-next');
	var seekBar = document.getElementById('musicshare-seek');
	var volumeBar = document.getElementById('musicshare-volume');
	var timeCurrentEl = document.getElementById('musicshare-time-current');
	var timeTotalEl = document.getElementById('musicshare-time-total');
	var canvas = document.getElementById('musicshare-waveform');
	var canvasCtx = canvas ? canvas.getContext('2d') : null;
	var collapseToggle = document.getElementById('musicshare-collapse-toggle');
	var queueToggle = document.getElementById('musicshare-queue-toggle');
	var shuffleBtn = document.getElementById('musicshare-shuffle');
	var menuToggle = document.getElementById('musicshare-menu-toggle');
	var playerMenu = document.getElementById('musicshare-menu');
	var clearQueueBtn = document.getElementById('musicshare-clear-queue');
	var closeBtn = document.getElementById('musicshare-close');
	var repeatBtn = document.getElementById('musicshare-repeat');
	var queuePanel = document.getElementById('musicshare-queue-panel');
	var queueList = document.getElementById('musicshare-queue-list');
	var playlistMenu = document.getElementById('musicshare-playlist-menu');
	var playlistMenuList = document.getElementById('musicshare-playlist-menu-list');
	var newPlaylistInput = document.getElementById('musicshare-new-playlist-name');
	var newPlaylistBtn = document.getElementById('musicshare-new-playlist-btn');

	var queue = [];
	var queueIndex = -1;
	var isSeeking = false;
	var isFading = false;
	var peaks = null;
	var audioCtx = null;
	var pendingSongIdForPlaylist = null;
	var resumeTime = 0;
	var suppressSave = false;
	var shuffleMode = false;
	var repeatMode = 'off';   // 'off' | 'all' | 'one'
	var draggingBar = null;
	var draggingRow = null;

	// Con l'impostazione "solo pagine Musica" il lettore in basso non
	// compare sulle pagine del forum: lì la riproduzione si comanda dai
	// pulsanti sulla riga, che hanno già play, pausa e avanzamento.
	var showPlayerBar = (cfg.playerScope !== 'music') || cfg.isMusicPage === true;

	/* --- Continuità della riproduzione tra le pagine ---
	   Ogni cambio pagina ricarica il documento e ferma l'audio: non è
	   evitabile senza trasformare il forum in una single page app. Salviamo
	   quindi coda, brano corrente e posizione, e li riprendiamo al
	   caricamento della pagina successiva. */

	var STORAGE_KEY = 'musicshare_player_state';
	var storage = null;
	var storageChecked = false;

	/**
	 * Restituisce uno storage utilizzabile: si preferisce sessionStorage
	 * (stato per scheda), con ripiego su localStorage. In navigazione
	 * privata o con i cookie bloccati l'accesso può lanciare un'eccezione
	 * oppure accettare la scrittura senza conservare nulla, quindi si
	 * verifica con una scrittura di prova.
	 */
	function getStorage() {
		if (storageChecked) {
			return storage;
		}

		storageChecked = true;
		var candidates = [];

		try { candidates.push(window.sessionStorage); } catch (e) { /* non disponibile */ }
		try { candidates.push(window.localStorage); } catch (e) { /* non disponibile */ }

		for (var i = 0; i < candidates.length; i++) {
			var candidate = candidates[i];

			if (!candidate) {
				continue;
			}

			try {
				candidate.setItem('musicshare_test', '1');
				if (candidate.getItem('musicshare_test') === '1') {
					candidate.removeItem('musicshare_test');
					storage = candidate;
					return storage;
				}
			} catch (e) {
				// provo il candidato successivo
			}
		}

		storage = null;
		return storage;
	}

	function persistEnabled() {
		return cfg.persistPlayer !== false && getStorage() !== null;
	}

	function saveState() {
		if (!persistEnabled() || suppressSave) {
			return;
		}

		if (queueIndex < 0 || !queue[queueIndex] || !audio.src) {
			return;
		}

		var payload = {
			queue: queue.map(function (track) {
				return {
					id: track.id,
					title: track.title,
					artist: track.artist,
					album: track.album,
					streamUrl: track.streamUrl,
					coverUrl: track.coverUrl
				};
			}),
			index: queueIndex,
			time: audio.currentTime || 0,
			playing: !audio.paused,
			volume: audio.volume,
			collapsed: player.classList.contains('musicshare-collapsed'),
			shuffle: shuffleMode,
			repeat: repeatMode,
			peaks: peaks ? peaks.map(function (v) { return Math.round(v * 1000) / 1000; }) : null
		};

		try {
			getStorage().setItem(STORAGE_KEY, JSON.stringify(payload));
		} catch (e) {
			// quota piena o storage non disponibile: la continuità è opzionale
		}
	}

	function clearState() {
		if (!persistEnabled()) {
			return;
		}

		try {
			getStorage().removeItem(STORAGE_KEY);
		} catch (e) {
			// nulla da fare
		}
	}

	function readState() {
		if (!persistEnabled()) {
			return null;
		}

		try {
			var raw = getStorage().getItem(STORAGE_KEY);
			if (!raw) {
				return null;
			}

			var data = JSON.parse(raw);
			if (!data || !data.queue || !data.queue.length || typeof data.index !== 'number') {
				return null;
			}

			return data;
		} catch (e) {
			return null;
		}
	}

	/**
	 * Ricollega gli elementi della coda alle righe presenti in questa
	 * pagina, se ci sono: serve a evidenziare il brano in riproduzione
	 * quando si torna su una pagina che lo contiene.
	 */
	function relinkQueueRows() {
		queue.forEach(function (track) {
			track.row = document.querySelector('.musicshare-song-row[data-song-id="' + track.id + '"]');
		});
	}

	function restoreState() {
		var data = readState();

		if (!data) {
			return;
		}

		queue = data.queue.map(function (track) {
			track.row = null;
			return track;
		});
		relinkQueueRows();

		queueIndex = Math.min(data.index, queue.length - 1);
		resumeTime = data.time || 0;

		if (volumeBar && typeof data.volume === 'number') {
			volumeBar.value = Math.round(data.volume * 100);
		}

		shuffleMode = !!data.shuffle;
		repeatMode = data.repeat || 'off';

		if (shuffleBtn && shuffleMode) {
			shuffleBtn.classList.add('musicshare-mode-active');
		}
		if (repeatBtn && repeatMode !== 'off') {
			repeatBtn.classList.add('musicshare-mode-active');
			repeatBtn.textContent = (repeatMode === 'one') ? '\uD83D\uDD02' : '\uD83D\uDD01';
		}

		if (data.collapsed) {
			player.classList.add('musicshare-collapsed');
			if (collapseToggle) {
				collapseToggle.innerHTML = '&#9660;';
			}
		}

		// carico il brano senza avviarlo: la posizione viene applicata
		// quando il browser conosce la durata (evento loadedmetadata)
		loadTrack(queueIndex, false, data.peaks);

		if (data.playing) {
			var attempt = audio.play();

			if (attempt && typeof attempt.catch === 'function') {
				attempt.catch(function () {
					// I browser bloccano la riproduzione automatica finché
					// l'utente non interagisce con la nuova pagina: mostro
					// il player fermo, pronto a riprendere con un clic.
					showResumeHint();
				});
			}
		}
	}

	function showResumeHint() {
		if (!titleEl || !lang.resumeHint) {
			return;
		}

		var hint = document.createElement('span');
		hint.className = 'musicshare-resume-hint';
		hint.textContent = lang.resumeHint;

		if (playBtn && !playBtn.parentNode.querySelector('.musicshare-resume-hint')) {
			playBtn.parentNode.insertBefore(hint, playBtn.nextSibling);

			var remove = function () {
				if (hint.parentNode) {
					hint.parentNode.removeChild(hint);
				}
				audio.removeEventListener('play', remove);
			};
			audio.addEventListener('play', remove);
		}
	}

	/* --- Utility --- */

	/**
	 * Mostra un avviso usando la finestra di dialogo di phpBB, che è
	 * quella già usata dal forum. Se per qualche motivo non fosse
	 * disponibile si ricade sulla finestra del browser, così l'utente
	 * riceve comunque il messaggio.
	 *
	 * @param string testo
	 * @param string titolo facoltativo
	 * @return void
	 */
	function avvisa(testo, titolo) {
		if (window.phpbb && typeof window.phpbb.alert === 'function') {
			window.phpbb.alert(titolo || lang.noticeTitle || '', testo);
			return;
		}

		window.alert(String(testo).replace(/<[^>]+>/g, ''));
	}

	function formatTime(seconds) {
		seconds = Math.floor(seconds || 0);
		var m = Math.floor(seconds / 60);
		var s = seconds % 60;
		return m + ':' + (s < 10 ? '0' : '') + s;
	}

	/**
	 * Costruisce la coda a partire dall'elenco che contiene la riga
	 * cliccata. Prima si prendevano tutte le righe della pagina: dove
	 * convivono più elenchi (per esempio "Caricati di recente" e "I più
	 * ascoltati") lo stesso brano finiva in coda due volte.
	 *
	 * @param HTMLElement clickedRow
	 * @return array
	 */
	function collectQueueFromPage(clickedRow) {
		var scope = null;

		if (clickedRow && clickedRow.closest) {
			scope = clickedRow.closest('.musicshare-song-list');
		}

		var rows = (scope || document).querySelectorAll('.musicshare-song-row');
		var list = [];
		var seen = {};

		rows.forEach(function (row) {
			// per sicurezza niente doppioni nemmeno dentro lo stesso elenco
			var id = row.getAttribute('data-song-id');

			if (seen[id]) {
				return;
			}
			seen[id] = true;

			list.push({
				id: row.getAttribute('data-song-id'),
				title: row.getAttribute('data-title'),
				artist: row.getAttribute('data-artist'),
				album: row.getAttribute('data-album'),
				year: row.getAttribute('data-year'),
				streamUrl: row.getAttribute('data-stream-url'),
				coverUrl: row.getAttribute('data-cover-url'),
				row: row
			});
		});
		return list;
	}

	function highlightActiveRow() {
		document.querySelectorAll('.musicshare-song-row.musicshare-active').forEach(function (row) {
			row.classList.remove('musicshare-active');
		});
		if (queue[queueIndex] && queue[queueIndex].row) {
			queue[queueIndex].row.classList.add('musicshare-active');
		}
		refreshRowButtons();
	}

	/**
	 * Compone la riga informativa del lettore usando tutti i tag
	 * disponibili: titolo, artista, album e anno. Le parti mancanti
	 * vengono semplicemente saltate.
	 *
	 * @param object track
	 * @return string
	 */
	function buildTrackLabel(track) {
		var parts = [];

		if (track.title) {
			parts.push(track.title);
		}
		if (track.artist) {
			parts.push(track.artist);
		}
		if (track.album) {
			var album = track.album;
			if (track.year) {
				album += ' (' + track.year + ')';
			}
			parts.push(album);
		} else if (track.year) {
			parts.push(track.year);
		}

		return parts.join('  \u2014  ');
	}

	/**
	 * Scorrimento continuo delle informazioni del brano, da destra a
	 * sinistra.
	 *
	 * Il testo viene duplicato e l'animazione sposta il contenuto di
	 * esattamente metà della sua larghezza: quando la prima copia esce
	 * di scena la seconda si trova nella stessa posizione di partenza,
	 * e il ciclo riparte senza salti visibili.
	 *
	 * @param string text
	 * @return void
	 */
	function updateMarquee(text) {
		// Attenzione: NON impostare titleEl.style.animation = 'none'.
		// Sarebbe uno stile in linea, che ha la precedenza sul foglio di
		// stile: animation-name resterebbe 'none' e l'animazione definita
		// dalla classe non partirebbe mai. Si rimuovono invece le
		// proprietà in linea, lasciando decidere al CSS.
		titleEl.style.removeProperty('animation');
		titleEl.style.removeProperty('animation-duration');
		titleEl.textContent = '';
		marqueeWrap.classList.remove('musicshare-marquee-active');

		function copia(nascosta) {
			var c = document.createElement('span');
			c.className = 'musicshare-marquee-copy';
			c.textContent = text;

			if (nascosta) {
				// le copie di servizio non vanno lette dai lettori di schermo
				c.setAttribute('aria-hidden', 'true');
			}

			return c;
		}

		titleEl.appendChild(copia(false));

		window.setTimeout(function () {
			var larghezzaCopia = titleEl.firstChild ? titleEl.firstChild.offsetWidth : 0;
			var larghezzaBarra = marqueeWrap.clientWidth;

			if (larghezzaCopia <= 0) {
				return;
			}

			// Quante copie servono a riempire la barra: con un titolo corto
			// una sola copia lascerebbe un vuoto prima che il testo
			// ricompaia da destra.
			var perGruppo = Math.max(1, Math.ceil(larghezzaBarra / larghezzaCopia));

			for (var i = 1; i < perGruppo; i++) {
				titleEl.appendChild(copia(true));
			}

			// Il gruppo viene duplicato: l'animazione sposta di metà del
			// contenuto, così a fine ciclo la seconda metà si trova dove
			// stava la prima e la ripartenza non si vede.
			for (var j = 0; j < perGruppo; j++) {
				titleEl.appendChild(copia(true));
			}

			// velocità costante di circa 45 pixel al secondo: un titolo
			// lungo non scorre più in fretta di uno corto
			var percorso = larghezzaCopia * perGruppo;
			titleEl.style.animationDuration = Math.max(8, percorso / 45) + 's';

			// lettura forzata della geometria: obbliga il browser ad
			// applicare le modifiche prima di riattivare la classe, così
			// l'animazione riparte davvero anche cambiando brano
			void titleEl.offsetWidth;

			marqueeWrap.classList.add('musicshare-marquee-active');
		}, 60);
	}

	/**
	 * Aggiunge a ogni riga il pulsante di riproduzione sopra la copertina e
	 * la barra di avanzamento. Si fa da JavaScript invece che nei template
	 * così i comandi compaiono in ogni lista, ovunque essa sia.
	 */
	function decorateRows() {
		var rows = document.querySelectorAll('.musicshare-song-row');

		Array.prototype.forEach.call(rows, function (row) {
			if (row.getAttribute('data-ms-ready')) {
				return;
			}
			row.setAttribute('data-ms-ready', '1');

			var cover = row.querySelector('.musicshare-cover');

			if (cover) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'musicshare-row-play';
				btn.innerHTML = '&#9654;';
				btn.setAttribute('aria-label', lang.play || 'Play');
				cover.appendChild(btn);
			}

			var info = row.querySelector('.musicshare-info');

			if (info) {
				var progress = document.createElement('span');
				progress.className = 'musicshare-row-progress';
				progress.appendChild(document.createElement('span')).className = 'musicshare-row-progress-bar';
				info.appendChild(progress);
			}

			decorateVotes(row);
		});
	}

	/**
	 * Aggiunge alla riga i due pulsanti di voto con i rispettivi conteggi.
	 * Chi non può votare (ospite, autore del brano, voti disattivati) vede
	 * comunque i numeri, ma non può premere.
	 *
	 * @param HTMLElement row
	 * @return void
	 */
	function decorateVotes(row) {
		if (row.getAttribute('data-likes') === null) {
			return;
		}

		var actions = row.querySelector('.musicshare-row-actions');

		if (!actions) {
			actions = document.createElement('span');
			actions.className = 'musicshare-row-actions';
			row.appendChild(actions);
		}

		var canVote = row.getAttribute('data-canvote') === '1';
		var myVote = parseInt(row.getAttribute('data-myvote'), 10) || 0;

		var box = document.createElement('span');
		box.className = 'musicshare-votes';

		box.appendChild(buildVoteButton(row, 1, row.getAttribute('data-likes'), myVote === 1, canVote));
		box.appendChild(buildVoteButton(row, -1, row.getAttribute('data-dislikes'), myVote === -1, canVote));

		actions.insertBefore(box, actions.firstChild);
	}

	function buildVoteButton(row, value, count, active, canVote) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'musicshare-vote musicshare-vote-' + (value > 0 ? 'up' : 'down');
		btn.setAttribute('data-vote', String(value));

		if (active) {
			btn.classList.add('musicshare-vote-active');
		}

		if (!canVote) {
			btn.disabled = true;
			btn.title = (value > 0 ? (lang.like || '') : (lang.dislike || ''));
		} else {
			btn.title = (value > 0 ? (lang.like || '') : (lang.dislike || ''));
		}

		var icon = document.createElement('span');
		icon.className = 'musicshare-vote-icon';
		icon.textContent = (value > 0) ? '\uD83D\uDC4D' : '\uD83D\uDC4E';

		var num = document.createElement('span');
		num.className = 'musicshare-vote-count';
		num.textContent = String(parseInt(count, 10) || 0);

		btn.appendChild(icon);
		btn.appendChild(num);

		return btn;
	}

	/**
	 * Invia il voto e aggiorna i conteggi su tutte le righe dello stesso
	 * brano presenti nella pagina: lo stesso brano può comparire in più
	 * elenchi contemporaneamente.
	 *
	 * @param HTMLElement row
	 * @param number value
	 * @return void
	 */
	function sendVote(row, value) {
		if (!cfg.ajaxVote) {
			return;
		}

		var songId = row.getAttribute('data-song-id');
		var body = 'song_id=' + encodeURIComponent(songId) +
			'&vote=' + encodeURIComponent(value) +
			'&hash=' + encodeURIComponent(cfg.ajaxHash || '');

		fetch(cfg.ajaxVote, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (!data || !data.success) {
					if (data && data.message) {
						avvisa(data.message);
					}
					return;
				}

				applyVoteResult(songId, data);
			})
			.catch(function () {
				// rete non disponibile: il voto non viene registrato
			});
	}

	function applyVoteResult(songId, data) {
		var rows = document.querySelectorAll('.musicshare-song-row[data-song-id="' + songId + '"]');

		Array.prototype.forEach.call(rows, function (row) {
			row.setAttribute('data-likes', data.likes);
			row.setAttribute('data-dislikes', data.dislikes);
			row.setAttribute('data-myvote', data.vote);

			var up = row.querySelector('.musicshare-vote-up');
			var down = row.querySelector('.musicshare-vote-down');

			if (up) {
				up.querySelector('.musicshare-vote-count').textContent = data.likes;
				up.classList.toggle('musicshare-vote-active', data.vote === 1);
			}
			if (down) {
				down.querySelector('.musicshare-vote-count').textContent = data.dislikes;
				down.classList.toggle('musicshare-vote-active', data.vote === -1);
			}
		});
	}

	/**
	 * Aggiorna la barra di avanzamento della riga in riproduzione.
	 *
	 * @param number ratio valore fra 0 e 1
	 */
	/**
	 * Calcola la posizione nel brano a partire da un punto cliccato o
	 * trascinato sulla barra di avanzamento di una riga.
	 *
	 * @param HTMLElement bar elemento .musicshare-row-progress
	 * @param number clientX
	 * @return number|false secondi, oppure false se non applicabile
	 */
	function seekPositionFromBar(bar, clientX) {
		if (!audio.duration || isNaN(audio.duration)) {
			return false;
		}

		var rect = bar.getBoundingClientRect();

		if (rect.width <= 0) {
			return false;
		}

		var ratio = (clientX - rect.left) / rect.width;
		ratio = Math.max(0, Math.min(1, ratio));

		return ratio * audio.duration;
	}

	/**
	 * Sposta la riproduzione nel punto indicato sulla barra della riga.
	 * Vale solo per la riga del brano in ascolto: le altre non mostrano
	 * nemmeno la barra.
	 *
	 * @param HTMLElement row
	 * @param HTMLElement bar
	 * @param number clientX
	 * @return void
	 */
	function seekFromRow(row, bar, clientX) {
		var track = queue[queueIndex];

		if (!track || track.row !== row) {
			return;
		}

		var position = seekPositionFromBar(bar, clientX);

		if (position === false) {
			return;
		}

		try {
			audio.currentTime = position;
		} catch (e) {
			// il browser non è ancora pronto per lo spostamento
			return;
		}

		updateRowProgress(position / audio.duration, true);
		saveState();
	}

	function updateRowProgress(ratio, fromDrag) {
		var track = queue[queueIndex];

		if (!track || !track.row) {
			return;
		}

		// mentre l'utente trascina, la barra segue il dito e non il brano
		if (draggingBar && !fromDrag) {
			return;
		}

		var bar = track.row.querySelector('.musicshare-row-progress-bar');

		if (bar) {
			// durante il trascinamento la barra deve seguire il dito senza
			// l'attenuazione, che la farebbe sembrare in ritardo
			bar.style.transition = fromDrag ? 'none' : '';
			bar.style.width = Math.max(0, Math.min(100, ratio * 100)) + '%';
		}
	}

	/**
	 * Allinea le icone dei pulsanti allo stato reale della riproduzione.
	 */
	function refreshRowButtons() {
		var rows = document.querySelectorAll('.musicshare-song-row');
		var activeRow = (queue[queueIndex] && queue[queueIndex].row) ? queue[queueIndex].row : null;

		Array.prototype.forEach.call(rows, function (row) {
			var btn = row.querySelector('.musicshare-row-play');

			if (!btn) {
				return;
			}

			if (row === activeRow && !audio.paused) {
				btn.innerHTML = '&#10074;&#10074;';
				btn.setAttribute('aria-label', lang.pause || 'Pause');
				row.classList.add('musicshare-row-playing');
			} else {
				btn.innerHTML = '&#9654;';
				btn.setAttribute('aria-label', lang.play || 'Play');
				row.classList.remove('musicshare-row-playing');
			}

			// azzera la barra delle righe non in riproduzione
			if (row !== activeRow) {
				var bar = row.querySelector('.musicshare-row-progress-bar');
				if (bar) {
					bar.style.width = '0%';
				}
			}
		});
	}

	/* --- Riproduzione --- */

	function loadTrack(index, autoplay, knownPeaks) {
		if (!queue[index]) {
			return;
		}

		queueIndex = index;
		var track = queue[index];

		audio.src = track.streamUrl;
		audio.volume = (volumeBar ? volumeBar.value / 100 : 0.8);
		coverImg.src = track.coverUrl || '';
		coverImg.style.display = track.coverUrl ? '' : 'none';

		updateMarquee(buildTrackLabel(track));

		// Se la riga del brano non è presente in questa pagina, il lettore
		// va mostrato comunque: altrimenti la musica suonerebbe senza che
		// l'utente abbia alcun modo di fermarla.
		var playerVisible = showPlayerBar || !track.row;

		if (playerVisible) {
			player.style.display = 'flex';
		}

		highlightActiveRow();
		renderQueuePanel();
		updateMediaSession(track);

		if (!playerVisible) {
			// senza lettore visibile la forma d'onda non si vede: evito di
			// riscaricare l'intero file audio solo per calcolarla
			peaks = null;
		} else if (knownPeaks && knownPeaks.length) {
			// picchi già calcolati in una pagina precedente: evito di
			// riscaricare l'intero file audio solo per ridisegnare l'onda
			peaks = knownPeaks;
			renderWaveform(0);
		} else {
			peaks = null;
			drawWaveformPlaceholder();
			loadWaveform(track.streamUrl);
		}

		if (autoplay) {
			audio.play().catch(function () {});
		}
	}

	function playByRow(row) {
		// se è la traccia già caricata, il clic vale come pausa/riprendi
		// invece di far ripartire il brano da capo
		if (queue[queueIndex] && queue[queueIndex].row === row && audio.src) {
			togglePlay();
			return;
		}

		queue = collectQueueFromPage(row);
		var index = -1;
		for (var i = 0; i < queue.length; i++) {
			if (queue[i].row === row) {
				index = i;
				break;
			}
		}
		if (index === -1) {
			return;
		}
		loadTrack(index, true);
	}

	function togglePlay() {
		if (!audio.src) {
			return;
		}
		if (audio.paused) {
			audio.play().catch(function () {});
		} else {
			audio.pause();
		}
	}

	function stopPlayback() {
		suppressSave = true;
		audio.pause();
		audio.currentTime = 0;
		updateRowProgress(0);
		clearState();
		suppressSave = false;
	}

	function fadeThenLoad(index, autoplay) {
		if (isFading || audio.paused || !audio.src) {
			loadTrack(index, autoplay);
			return;
		}

		isFading = true;
		var steps = 8;
		var stepTime = 25;
		var startVolume = audio.volume;
		var i = 0;

		var fadeOut = window.setInterval(function () {
			i++;
			audio.volume = Math.max(0, startVolume * (1 - i / steps));
			if (i >= steps) {
				window.clearInterval(fadeOut);
				loadTrack(index, autoplay);
				isFading = false;
			}
		}, stepTime);
	}

	function pickNextIndex() {
		if (repeatMode === 'one') {
			return queueIndex;
		}

		if (shuffleMode && queue.length > 1) {
			var next;
			do {
				next = Math.floor(Math.random() * queue.length);
			} while (next === queueIndex);
			return next;
		}

		if (queueIndex + 1 < queue.length) {
			return queueIndex + 1;
		}

		return (repeatMode === 'all') ? 0 : -1;
	}

	function playNext() {
		var next = pickNextIndex();

		if (next < 0) {
			return;
		}

		if (next === queueIndex && repeatMode === 'one') {
			audio.currentTime = 0;
			audio.play().catch(function () {});
			return;
		}

		fadeThenLoad(next, true);
	}

	function playPrev() {
		// entro i primi secondi "precedente" torna all'inizio del brano,
		// come fanno i lettori musicali
		if (audio.currentTime > 3) {
			audio.currentTime = 0;
			return;
		}

		if (queueIndex > 0) {
			fadeThenLoad(queueIndex - 1, true);
		}
	}

	/**
	 * Espone il brano corrente al sistema operativo: su telefono compare
	 * nella schermata di blocco con copertina e controlli, come una vera
	 * applicazione musicale.
	 */
	function updateMediaSession(track) {
		if (!('mediaSession' in navigator) || !window.MediaMetadata) {
			return;
		}

		try {
			var artwork = [];
			if (track.coverUrl) {
				artwork.push({ src: track.coverUrl, sizes: '512x512', type: 'image/jpeg' });
			}

			navigator.mediaSession.metadata = new window.MediaMetadata({
				title: track.title || '',
				artist: track.artist || '',
				album: track.album || '',
				artwork: artwork
			});

			navigator.mediaSession.setActionHandler('play', function () { audio.play().catch(function () {}); });
			navigator.mediaSession.setActionHandler('pause', function () { audio.pause(); });
			navigator.mediaSession.setActionHandler('previoustrack', playPrev);
			navigator.mediaSession.setActionHandler('nexttrack', playNext);
			navigator.mediaSession.setActionHandler('seekto', function (details) {
				if (details && typeof details.seekTime === 'number') {
					audio.currentTime = details.seekTime;
				}
			});
		} catch (e) {
			// API non completamente supportata: il player resta comunque usabile
		}
	}

	/* --- Coda visibile e riordinabile --- */

	function renderQueuePanel() {
		if (!queueList) {
			return;
		}

		queueList.innerHTML = '';

		if (queue.length === 0) {
			var empty = document.createElement('li');
			empty.className = 'musicshare-queue-empty';
			empty.textContent = lang.queueEmpty || 'Queue is empty';
			queueList.appendChild(empty);
			return;
		}

		queue.forEach(function (track, index) {
			var li = document.createElement('li');
			li.className = 'musicshare-queue-item' + (index === queueIndex ? ' musicshare-queue-active' : '');

			var label = document.createElement('span');
			label.className = 'musicshare-queue-label';
			label.textContent = buildTrackLabel(track);
			label.addEventListener('click', function () {
				fadeThenLoad(index, true);
			});
			li.appendChild(label);

			var upBtn = document.createElement('button');
			upBtn.type = 'button';
			upBtn.textContent = '\u25B2';
			upBtn.disabled = (index === 0);
			upBtn.addEventListener('click', function () {
				moveInQueue(index, index - 1);
			});
			li.appendChild(upBtn);

			var downBtn = document.createElement('button');
			downBtn.type = 'button';
			downBtn.textContent = '\u25BC';
			downBtn.disabled = (index === queue.length - 1);
			downBtn.addEventListener('click', function () {
				moveInQueue(index, index + 1);
			});
			li.appendChild(downBtn);

			var removeBtn = document.createElement('button');
			removeBtn.type = 'button';
			removeBtn.textContent = '\u2715';
			removeBtn.title = lang.removeFromQueue || '';
			removeBtn.setAttribute('aria-label', lang.removeFromQueue || '');
			removeBtn.addEventListener('click', function () {
				removeFromQueue(index);
			});
			li.appendChild(removeBtn);

			queueList.appendChild(li);
		});
	}

	function moveInQueue(from, to) {
		if (to < 0 || to >= queue.length) {
			return;
		}
		var item = queue.splice(from, 1)[0];
		queue.splice(to, 0, item);

		if (queueIndex === from) {
			queueIndex = to;
		} else if (from < queueIndex && to >= queueIndex) {
			queueIndex--;
		} else if (from > queueIndex && to <= queueIndex) {
			queueIndex++;
		}

		renderQueuePanel();
	}

	function removeFromQueue(index) {
		queue.splice(index, 1);

		if (index < queueIndex) {
			queueIndex--;
		} else if (index === queueIndex) {
			stopPlayback();
			queueIndex = -1;
		}

		renderQueuePanel();
	}

	/**
	 * Chiude del tutto il lettore: ferma la riproduzione, libera il file
	 * in corso di scaricamento, azzera la coda e dimentica lo stato salvato,
	 * così alla pagina successiva il player non ricompare.
	 */
	function closePlayer() {
		suppressSave = true;

		audio.pause();
		audio.removeAttribute('src');

		try {
			// interrompe anche il download già avviato del file
			audio.load();
		} catch (e) {
			// alcuni browser lanciano un'eccezione innocua qui
		}

		clearState();

		queue = [];
		queueIndex = -1;
		peaks = null;
		resumeTime = 0;

		highlightActiveRow();
		renderQueuePanel();
		drawWaveformPlaceholder();

		if (queuePanel) {
			queuePanel.style.display = 'none';
		}
		if (playerMenu) {
			playerMenu.style.display = 'none';
		}

		player.style.display = 'none';

		if ('mediaSession' in navigator) {
			try {
				navigator.mediaSession.metadata = null;
				navigator.mediaSession.playbackState = 'none';
			} catch (e) {
				// API non supportata del tutto
			}
		}

		suppressSave = false;
	}

	/**
	 * Svuota la coda mantenendo però il brano attualmente in ascolto.
	 */
	function clearQueue() {
		if (queueIndex < 0 || !queue[queueIndex]) {
			queue = [];
			queueIndex = -1;
		} else {
			queue = [queue[queueIndex]];
			queueIndex = 0;
		}

		relinkQueueRows();
		highlightActiveRow();
		renderQueuePanel();
		saveState();
	}

	/* --- Forma d'onda (Web Audio API) --- */

	function drawWaveformPlaceholder() {
		if (!canvasCtx) {
			return;
		}
		canvasCtx.clearRect(0, 0, canvas.width, canvas.height);
	}

	function getAudioContext() {
		if (!audioCtx) {
			var Ctx = window.AudioContext || window.webkitAudioContext;
			if (Ctx) {
				audioCtx = new Ctx();
			}
		}
		return audioCtx;
	}

	function loadWaveform(url) {
		if (!canvasCtx || !window.fetch) {
			return;
		}
		var ctx = getAudioContext();
		if (!ctx) {
			return;
		}

		var currentTrackUrl = url;

		fetch(url)
			.then(function (res) { return res.arrayBuffer(); })
			.then(function (buf) { return ctx.decodeAudioData(buf); })
			.then(function (audioBuffer) {
				if (audio.src.indexOf(currentTrackUrl) === -1) {
					return;
				}
				peaks = computePeaks(audioBuffer, 300);
				renderWaveform(0);
			})
			.catch(function () {
				// File non decodificabile dal browser o CORS: si prosegue senza waveform
			});
	}

	function computePeaks(audioBuffer, resolution) {
		var data = audioBuffer.getChannelData(0);
		var blockSize = Math.floor(data.length / resolution);
		var result = [];

		for (var i = 0; i < resolution; i++) {
			var start = i * blockSize;
			var max = 0;
			for (var j = 0; j < blockSize; j++) {
				var v = Math.abs(data[start + j] || 0);
				if (v > max) {
					max = v;
				}
			}
			result.push(max);
		}

		return result;
	}

	function renderWaveform(progressRatio) {
		if (!canvasCtx || !peaks) {
			return;
		}

		var width = canvas.clientWidth || canvas.width;
		var height = canvas.clientHeight || canvas.height;
		canvas.width = width;
		canvas.height = height;

		canvasCtx.clearRect(0, 0, width, height);

		var barWidth = width / peaks.length;
		var progressIndex = Math.floor(peaks.length * progressRatio);

		for (var i = 0; i < peaks.length; i++) {
			var barHeight = Math.max(2, peaks[i] * height);
			var x = i * barWidth;
			var y = (height - barHeight) / 2;

			canvasCtx.fillStyle = (i <= progressIndex) ? '#1db954' : '#555566';
			canvasCtx.fillRect(x, y, Math.max(1, barWidth - 1), barHeight);
		}
	}

	/* --- Menu "Aggiungi a playlist" --- */

	function openPlaylistMenu(anchorEl, songId) {
		if (!playlistMenu || !cfg.loggedIn || !cfg.canPlaylist) {
			return;
		}

		pendingSongIdForPlaylist = songId;

		// Il menù va reso visibile prima di misurarlo, altrimenti le sue
		// dimensioni risultano nulle e il calcolo della posizione sbaglia.
		playlistMenu.style.visibility = 'hidden';
		playlistMenu.style.display = 'block';

		var rect = anchorEl.getBoundingClientRect();
		var menuWidth = playlistMenu.offsetWidth || 220;
		var menuHeight = playlistMenu.offsetHeight || 160;
		var margin = 12;

		// orizzontale: allineato al pulsante, ma mai oltre il bordo destro
		var left = rect.left;
		var maxLeft = window.innerWidth - menuWidth - margin;

		if (left > maxLeft) {
			left = maxLeft;
		}
		if (left < margin) {
			left = margin;
		}

		// verticale: sotto al pulsante, oppure sopra se sotto non ci sta
		var top = rect.bottom + 4;

		if (top + menuHeight > window.innerHeight - margin) {
			var above = rect.top - menuHeight - 4;
			top = (above > margin) ? above : Math.max(margin, window.innerHeight - menuHeight - margin);
		}

		playlistMenu.style.left = (window.scrollX + left) + 'px';
		playlistMenu.style.top = (window.scrollY + top) + 'px';
		playlistMenu.style.visibility = '';

		playlistMenuList.innerHTML = '<li class="musicshare-loading">...</li>';

		fetch(cfg.ajaxPlaylists, { credentials: 'same-origin' })
			.then(function (res) { return res.json(); })
			.then(function (data) {
				playlistMenuList.innerHTML = '';

				if (!data.success || !data.playlists || data.playlists.length === 0) {
					var li = document.createElement('li');
					li.className = 'musicshare-loading';
					li.textContent = '\u2014';
					playlistMenuList.appendChild(li);
					return;
				}

				data.playlists.forEach(function (playlist) {
					var li = document.createElement('li');
					li.textContent = playlist.name;
					li.addEventListener('click', function () {
						addSongToPlaylist(playlist.id);
					});
					playlistMenuList.appendChild(li);
				});
			})
			.catch(function () {
				playlistMenuList.innerHTML = '';
			});
	}

	function closePlaylistMenu() {
		if (playlistMenu) {
			playlistMenu.style.display = 'none';
		}
		pendingSongIdForPlaylist = null;
	}

	function addSongToPlaylist(playlistId) {
		var body = 'playlist_id=' + encodeURIComponent(playlistId) +
			'&song_id=' + encodeURIComponent(pendingSongIdForPlaylist) +
			'&hash=' + encodeURIComponent(cfg.ajaxHash || '');

		fetch(cfg.ajaxAddToPlaylist, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				avvisa(data.success ? (lang.addedToPlaylist || 'Added.') : (lang.alreadyInPlaylist || lang.addError || 'Error.'));
			})
			.catch(function () {
				avvisa(lang.addError || 'Error.');
			})
			.then(closePlaylistMenu);
	}

	if (newPlaylistBtn) {
		newPlaylistBtn.addEventListener('click', function () {
			var name = (newPlaylistInput && newPlaylistInput.value || '').trim();
			if (!name) {
				avvisa(lang.newPlaylistNameEmpty || 'Enter a name.');
				return;
			}

			var body = 'playlist_name=' + encodeURIComponent(name) +
				'&song_id=' + encodeURIComponent(pendingSongIdForPlaylist) +
				'&hash=' + encodeURIComponent(cfg.ajaxHash || '');

			fetch(cfg.ajaxCreatePlaylist, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body
			})
				.then(function (res) { return res.json(); })
				.then(function (data) {
					avvisa(data.success ? (lang.addedToPlaylist || 'Added.') : (lang.addError || 'Error.'));
					if (newPlaylistInput) {
						newPlaylistInput.value = '';
					}
				})
				.catch(function () {
					avvisa(lang.addError || 'Error.');
				})
				.then(closePlaylistMenu);
		});
	}

	/* --- Eventi UI --- */

	document.addEventListener('click', function (e) {
		var row = e.target.closest ? e.target.closest('.musicshare-song-row') : null;
		var addBtn = e.target.closest ? e.target.closest('.musicshare-add-playlist') : null;
		var insideMenu = e.target.closest ? e.target.closest('#musicshare-playlist-menu') : null;

		if (addBtn) {
			e.stopPropagation();
			openPlaylistMenu(addBtn, addBtn.getAttribute('data-song-id'));
			return;
		}

		if (!insideMenu) {
			closePlaylistMenu();
		}

		// chiude il menù del lettore cliccando altrove
		if (playerMenu && playerMenu.style.display !== 'none') {
			var insidePlayerMenu = e.target.closest ? e.target.closest('#musicshare-menu, #musicshare-menu-toggle') : null;

			if (!insidePlayerMenu) {
				playerMenu.style.display = 'none';
			}
		}

		if (row) {
			var voteBtn = e.target.closest ? e.target.closest('.musicshare-vote') : null;

			if (voteBtn) {
				e.preventDefault();
				e.stopPropagation();

				if (!voteBtn.disabled) {
					sendVote(row, parseInt(voteBtn.getAttribute('data-vote'), 10));
				}
				return;
			}

			var progressInRow = e.target.closest ? e.target.closest('.musicshare-row-progress') : null;

			if (progressInRow) {
				// il clic sulla barra sposta la riproduzione, non la ferma
				e.preventDefault();
				seekFromRow(row, progressInRow, e.clientX);
				return;
			}

			var playBtnInRow = e.target.closest ? e.target.closest('.musicshare-row-play') : null;

			if (playBtnInRow) {
				e.preventDefault();
				playByRow(row);
				return;
			}

			// I link e i pulsanti dentro la riga (Modifica, Cancella, ...)
			// devono restare cliccabili senza avviare la riproduzione.
			var actionable = e.target.closest ? e.target.closest('a, button') : null;

			if (actionable && row.contains(actionable)) {
				return;
			}

			playByRow(row);
		}
	});

	if (playBtn) {
		playBtn.addEventListener('click', togglePlay);
	}
	if (stopBtn) {
		stopBtn.addEventListener('click', stopPlayback);
	}
	if (nextBtn) {
		nextBtn.addEventListener('click', playNext);
	}
	if (prevBtn) {
		prevBtn.addEventListener('click', playPrev);
	}

	if (collapseToggle) {
		collapseToggle.addEventListener('click', function () {
			player.classList.toggle('musicshare-collapsed');
			collapseToggle.innerHTML = player.classList.contains('musicshare-collapsed') ? '&#9660;' : '&#9650;';
			saveState();
		});
	}

	if (shuffleBtn) {
		shuffleBtn.addEventListener('click', function () {
			shuffleMode = !shuffleMode;
			shuffleBtn.classList.toggle('musicshare-mode-active', shuffleMode);
			saveState();
		});
	}

	if (repeatBtn) {
		repeatBtn.addEventListener('click', function () {
			repeatMode = (repeatMode === 'off') ? 'all' : (repeatMode === 'all' ? 'one' : 'off');
			repeatBtn.classList.toggle('musicshare-mode-active', repeatMode !== 'off');
			repeatBtn.textContent = (repeatMode === 'one') ? '\uD83D\uDD02' : '\uD83D\uDD01';
			repeatBtn.title = (repeatMode === 'one') ? (lang.repeatOne || '') : (repeatMode === 'all' ? (lang.repeatAll || '') : (lang.repeatOff || ''));
			saveState();
		});
	}

	if (menuToggle && playerMenu) {
		menuToggle.addEventListener('click', function (e) {
			e.stopPropagation();
			var visible = playerMenu.style.display !== 'none';
			playerMenu.style.display = visible ? 'none' : 'block';

			if (!visible && queuePanel) {
				// un pannello alla volta, per non sovrapporli
				queuePanel.style.display = 'none';
			}
		});
	}

	if (clearQueueBtn) {
		clearQueueBtn.addEventListener('click', function () {
			clearQueue();

			if (playerMenu) {
				playerMenu.style.display = 'none';
			}
		});
	}

	if (closeBtn) {
		closeBtn.addEventListener('click', closePlayer);
	}

	if (queueToggle && queuePanel) {
		queueToggle.addEventListener('click', function () {
			var visible = queuePanel.style.display !== 'none';

			if (visible) {
				queuePanel.style.display = 'none';
			} else {
				renderQueuePanel();
				queuePanel.style.display = 'block';

				if (playerMenu) {
					playerMenu.style.display = 'none';
				}
			}
		});
	}

	audio.addEventListener('play', function () {
		if (playBtn) {
			playBtn.innerHTML = '&#10074;&#10074;';
		}
		refreshRowButtons();
		saveState();
	});
	audio.addEventListener('pause', function () {
		if (playBtn) {
			playBtn.innerHTML = '&#9654;';
		}
		refreshRowButtons();
		saveState();
	});
	audio.addEventListener('ended', playNext);

	// salvataggio periodico della posizione (non a ogni timeupdate,
	// che scatta molte volte al secondo)
	var lastSave = 0;
	audio.addEventListener('timeupdate', function () {
		var now = Date.now();
		if (now - lastSave > 2000) {
			lastSave = now;
			saveState();
		}
	});

	// ultimo salvataggio prima di lasciare la pagina
	window.addEventListener('pagehide', saveState);
	window.addEventListener('beforeunload', saveState);

	function applyResumeTime() {
		if (resumeTime <= 0) {
			return;
		}

		if (!audio.duration || isNaN(audio.duration)) {
			return;
		}

		if (resumeTime < audio.duration) {
			try {
				audio.currentTime = resumeTime;
			} catch (e) {
				// alcuni browser rifiutano il seek finché il buffer non è pronto:
				// ci riproveremo al prossimo evento utile
				return;
			}
		}

		resumeTime = 0;

		if (timeCurrentEl) {
			timeCurrentEl.textContent = formatTime(audio.currentTime);
		}
	}

	audio.addEventListener('loadedmetadata', function () {
		if (timeTotalEl) {
			timeTotalEl.textContent = formatTime(audio.duration);
		}

		applyResumeTime();
	});

	// reti di sicurezza: se il seek non è andato a buon fine al primo evento
	audio.addEventListener('durationchange', applyResumeTime);
	audio.addEventListener('canplay', applyResumeTime);
	audio.addEventListener('loadeddata', applyResumeTime);

	audio.addEventListener('timeupdate', function () {
		if (isSeeking) {
			return;
		}
		if (timeCurrentEl) {
			timeCurrentEl.textContent = formatTime(audio.currentTime);
		}
		if (audio.duration && seekBar) {
			seekBar.value = Math.floor((audio.currentTime / audio.duration) * 1000);
		}
		if (audio.duration) {
			renderWaveform(audio.currentTime / audio.duration);
			updateRowProgress(audio.currentTime / audio.duration);
		}
	});

	if (seekBar) {
		seekBar.addEventListener('input', function () {
			isSeeking = true;
			if (timeCurrentEl && audio.duration) {
				timeCurrentEl.textContent = formatTime((seekBar.value / 1000) * audio.duration);
			}
		});
		seekBar.addEventListener('change', function () {
			if (audio.duration) {
				audio.currentTime = (seekBar.value / 1000) * audio.duration;
			}
			isSeeking = false;
		});
	}

	if (volumeBar) {
		audio.volume = volumeBar.value / 100;
		volumeBar.addEventListener('input', function () {
			audio.volume = volumeBar.value / 100;
			saveState();
		});
	}

	window.addEventListener('resize', function () {
		if (peaks && audio.duration) {
			renderWaveform(audio.currentTime / audio.duration);
		}
	});

	/* --- Trascinamento sulla barra di avanzamento delle righe --- */

	function pointerX(e) {
		if (e.touches && e.touches.length) {
			return e.touches[0].clientX;
		}
		return e.clientX;
	}

	document.addEventListener('mousedown', function (e) {
		var bar = e.target.closest ? e.target.closest('.musicshare-row-progress') : null;

		if (!bar) {
			return;
		}

		var row = e.target.closest('.musicshare-song-row');

		if (!row || !queue[queueIndex] || queue[queueIndex].row !== row) {
			return;
		}

		draggingBar = bar;
		draggingRow = row;
		e.preventDefault();
	});

	document.addEventListener('mousemove', function (e) {
		if (!draggingBar) {
			return;
		}

		// durante il trascinamento si aggiorna solo la barra: la posizione
		// viene applicata al rilascio, così l'audio non salta di continuo
		var position = seekPositionFromBar(draggingBar, pointerX(e));

		if (position !== false && audio.duration) {
			updateRowProgress(position / audio.duration, true);
		}
	});

	document.addEventListener('mouseup', function (e) {
		if (!draggingBar) {
			return;
		}

		seekFromRow(draggingRow, draggingBar, pointerX(e));
		draggingBar = null;
		draggingRow = null;
	});

	// stessi gesti da telefono
	document.addEventListener('touchstart', function (e) {
		var bar = e.target.closest ? e.target.closest('.musicshare-row-progress') : null;

		if (!bar) {
			return;
		}

		var row = e.target.closest('.musicshare-song-row');

		if (!row || !queue[queueIndex] || queue[queueIndex].row !== row) {
			return;
		}

		draggingBar = bar;
		draggingRow = row;
	}, { passive: true });

	document.addEventListener('touchmove', function (e) {
		if (!draggingBar) {
			return;
		}

		var position = seekPositionFromBar(draggingBar, pointerX(e));

		if (position !== false && audio.duration) {
			updateRowProgress(position / audio.duration, true);
		}
	}, { passive: true });

	document.addEventListener('touchend', function (e) {
		if (!draggingBar) {
			return;
		}

		var x = (e.changedTouches && e.changedTouches.length)
			? e.changedTouches[0].clientX
			: null;

		if (x !== null) {
			seekFromRow(draggingRow, draggingBar, x);
		}

		draggingBar = null;
		draggingRow = null;
	});

	/* --- Copia del codice per inserire il brano in un messaggio --- */

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.musicshare-copy-bbcode') : null;

		if (!btn) {
			return;
		}

		e.preventDefault();

		var codice = '[musicshare]' + btn.getAttribute('data-song-id') + '[/musicshare]';

		// La copia negli appunti richiede una connessione sicura: dove non
		// è disponibile si mostra comunque il codice, così l'utente può
		// selezionarlo a mano invece di restare senza nulla.
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(codice).then(function () {
				avvisa(lang.bbcodeCopied || codice);
			}).catch(function () {
				avvisa((lang.bbcodeManual || '') + '\n\n' + codice);
			});
		} else {
			avvisa((lang.bbcodeManual || '') + '\n\n' + codice);
		}
	});

	// Il testo finisce dentro codice HTML costruito a mano: va protetto,
	// altrimenti una stringa di lingua con un < o una virgoletta
	// romperebbe il riquadro.
	function escapeHtml(valore) {
		return String(valore === null || valore === undefined ? '' : valore)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	/**
	 * Reazione a un commento della bacheca. Stessa forma del voto sui
	 * brani: si manda il tipo, si ricevono i conteggi aggiornati e si
	 * riscrivono i tre pulsanti di quel commento.
	 *
	 * @param HTMLElement gruppo contenitore .musicshare-reactions
	 * @param string tipo 1, 2 o 3
	 * @return void
	 */
	function sendReaction(gruppo, tipo) {
		if (!cfg.ajaxWallReact) {
			return;
		}

		var commentId = gruppo.getAttribute('data-comment-id');
		var body = 'comment_id=' + encodeURIComponent(commentId) +
			'&reaction=' + encodeURIComponent(tipo) +
			'&hash=' + encodeURIComponent(cfg.ajaxHash || '');

		fetch(cfg.ajaxWallReact, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (!data || !data.success) {
					if (data && data.message) {
						avvisa(data.message);
					}
					return;
				}

				applyReactions(gruppo, data);
			})
			.catch(function () {
				// rete non disponibile: la reazione non viene registrata
			});
	}

	function applyReactions(gruppo, data) {
		var conteggi = data.counts || {};
		var mie = data.mine || [];

		Array.prototype.forEach.call(gruppo.querySelectorAll('.musicshare-react'), function (btn) {
			var tipo = btn.getAttribute('data-reaction');
			var numero = btn.querySelector('.musicshare-react-count');

			if (numero) {
				numero.textContent = String(conteggi[tipo] || 0);
			}

			// mie arriva come elenco di numeri, tipo come testo
			btn.classList.toggle('musicshare-react-active', mie.indexOf(parseInt(tipo, 10)) !== -1);
		});
	}

	// Bacheca: i moduli di risposta e di modifica partono nascosti e si
	// aprono al clic. È solo apertura e chiusura, nessuna chiamata al
	// server: se questo codice non parte, i moduli restano visibili e
	// funzionano lo stesso.
	document.addEventListener('click', function (e) {
		var reazione = e.target.closest ? e.target.closest('.musicshare-react') : null;

		if (reazione && !reazione.disabled) {
			var gruppo = reazione.closest('.musicshare-reactions');

			if (gruppo) {
				e.preventDefault();
				sendReaction(gruppo, reazione.getAttribute('data-reaction'));
			}

			return;
		}

		var toggle = e.target.closest ? e.target.closest('[data-musicshare-toggle]') : null;

		if (toggle) {
			// un clic puo' aprire piu' di un blocco: Modifica apre sia il
			// modulo di modifica sia quello di eliminazione, che sono due
			// moduli distinti proprio per non confondere le due azioni
			var nomi = (toggle.getAttribute('data-musicshare-toggle') || '').split(/\s+/);
			var primo = null;

			nomi.forEach(function (nome) {
				var bersaglio = nome ? document.getElementById(nome) : null;

				if (bersaglio) {
					bersaglio.classList.toggle('musicshare-wall-open');

					if (primo === null) {
						primo = bersaglio;
					}
				}
			});

			if (primo) {
				e.preventDefault();
				var campo = primo.querySelector('textarea');

				if (campo && primo.classList.contains('musicshare-wall-open')) {
					campo.focus();
				}
			}

			return;
		}

		// Annulla: chiude il pannello e riporta il campo com'era. Per la
		// modifica significa il testo salvato, per la risposta il vuoto:
		// in entrambi i casi è il valore che il modello ha scritto nel
		// codice, quindi basta rileggerlo.
		var chiudi = e.target.closest ? e.target.closest('[data-musicshare-close]') : null;

		if (chiudi) {
			var pannello = document.getElementById(chiudi.getAttribute('data-musicshare-close'));

			if (pannello) {
				e.preventDefault();
				pannello.classList.remove('musicshare-wall-open');

				var testo = pannello.querySelector('textarea');

				if (testo) {
					testo.value = testo.defaultValue;
				}
			}

			return;
		}

		// Eliminazione di un commento: una conferma, perché sparisce
		// anche tutto quello che gli hanno risposto sotto. Si usa il
		// riquadro di phpBB; window.confirm resta solo come rete di
		// sicurezza, se per qualche motivo il riquadro non c'è.
		var conferma = e.target.closest ? e.target.closest('[data-musicshare-confirm]') : null;

		if (!conferma) {
			return;
		}

		var messaggio = conferma.getAttribute('data-musicshare-confirm');
		var modulo = conferma.form || conferma.closest('form');

		// Il riquadro di phpBB vive dentro #phpbb_confirm, che sta nel piè
		// di pagina dello stile. Se uno stile non lo include, phpbb.confirm
		// non mostrerebbe nulla e l'eliminazione resterebbe bloccata: per
		// questo si controlla che l'elemento ci sia davvero.
		var riquadro = document.getElementById('phpbb_confirm');

		if (riquadro && window.phpbb && typeof phpbb.confirm === 'function' && modulo) {
			e.preventDefault();

			// phpbb.confirm non vuole un testo: vuole il codice completo
			// del modulo, titolo e pulsanti compresi, perché si aggancia
			// agli <input type="button"> che trova dentro. Passandogli
			// solo la frase si ottiene un riquadro bianco senza pulsanti.
			var html = '<form action="#" method="post">' +
				'<h3>' + escapeHtml(lang.confirmTitle || '') + '</h3>' +
				'<p class="musicshare-confirm-text">' + escapeHtml(messaggio) + '</p>' +
				'<fieldset class="submit-buttons">' +
					'<input type="button" name="confirm" class="button2" value="' + escapeHtml(lang.yes || 'OK') + '">&nbsp;' +
					'<input type="button" name="cancel" class="button2" value="' + escapeHtml(lang.no || 'Annulla') + '">' +
				'</fieldset>' +
				'</form>';

			phpbb.confirm(html, function (conferma_data) {
				if (conferma_data) {
					// l'invio diretto salta questo stesso gestore, quindi
					// non si ricasca nella conferma all'infinito
					modulo.submit();
				}
			}, false);

			return;
		}

		if (!window.confirm(messaggio)) {
			e.preventDefault();
		}
	});

	// Comandi e barra di avanzamento su ogni riga presente nella pagina
	decorateRows();

	// Ripristino della riproduzione interrotta dal cambio pagina
	restoreState();
})();
