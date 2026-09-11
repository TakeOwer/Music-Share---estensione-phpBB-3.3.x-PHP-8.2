/**
 * Music Share - avviso a comparsa sui nuovi brani
 *
 * Interroga a intervalli regolari l'elenco dei brani caricati dopo
 * l'ultimo controllo e mostra un breve avviso. Non fa nulla se la
 * funzione è disattivata da ACP, se l'utente non è collegato o se la
 * scheda del browser non è in primo piano.
 */
(function () {
	'use strict';

	var cfg = window.musicshareConfig || {};
	var audioCtx = null;

	if (!cfg.toastEnabled || !cfg.ajaxRecent || !cfg.loggedIn || !window.fetch) {
		return;
	}

	var lang = cfg.lang || {};
	var STORAGE_KEY = 'musicshare_last_seen';
	var interval = Math.max(30, parseInt(cfg.toastInterval, 10) || 90) * 1000;
	var container = null;
	var timer = null;

	function getStorage() {
		try {
			var s = window.localStorage;
			s.setItem('musicshare_toast_test', '1');
			s.removeItem('musicshare_toast_test');
			return s;
		} catch (e) {
			return null;
		}
	}

	var storage = getStorage();

	function readLastSeen() {
		if (!storage) {
			return 0;
		}

		var value = parseInt(storage.getItem(STORAGE_KEY), 10);

		return isNaN(value) ? 0 : value;
	}

	function writeLastSeen(value) {
		if (storage) {
			try {
				storage.setItem(STORAGE_KEY, String(value));
			} catch (e) {
				// spazio esaurito: la funzione è accessoria
			}
		}
	}

	function getContainer() {
		if (container) {
			return container;
		}

		container = document.createElement('div');
		container.className = 'musicshare-toasts';
		document.body.appendChild(container);

		return container;
	}

	/**
	 * Breve segnale acustico all'arrivo di un nuovo brano.
	 *
	 * Il suono viene sintetizzato con la Web Audio API invece di
	 * caricare un file: non c'è nulla da scaricare, non serve un
	 * formato compatibile con tutti i browser e il volume resta
	 * sotto controllo. Due note brevi, discrete.
	 *
	 * @return void
	 */
	function playSound() {
		if (!cfg.toastSound) {
			return;
		}

		var Audio = window.AudioContext || window.webkitAudioContext;

		if (!Audio) {
			return;
		}

		try {
			if (!audioCtx) {
				audioCtx = new Audio();
			}

			// i browser sospendono il contesto finché non c'è
			// un'interazione: in quel caso si rinuncia in silenzio
			if (audioCtx.state === 'suspended') {
				audioCtx.resume();
			}

			var volume = Math.max(0, Math.min(1, (cfg.toastVolume || 30) / 100));
			var adesso = audioCtx.currentTime;

			[[880, 0], [1174.7, 0.12]].forEach(function (nota) {
				var osc = audioCtx.createOscillator();
				var gain = audioCtx.createGain();

				osc.type = 'sine';
				osc.frequency.value = nota[0];

				// attacco e rilascio morbidi: un'onda troncata di netto
				// produce un clic fastidioso
				gain.gain.setValueAtTime(0, adesso + nota[1]);
				gain.gain.linearRampToValueAtTime(volume, adesso + nota[1] + 0.02);
				gain.gain.exponentialRampToValueAtTime(0.001, adesso + nota[1] + 0.22);

				osc.connect(gain);
				gain.connect(audioCtx.destination);
				osc.start(adesso + nota[1]);
				osc.stop(adesso + nota[1] + 0.24);
			});
		} catch (e) {
			// audio non disponibile: l'avviso resta comunque visibile
		}
	}

	function showToast(song) {
		var toast = document.createElement('div');
		toast.className = 'musicshare-toast';

		var cover = document.createElement('div');
		cover.className = 'musicshare-toast-cover';

		if (song.cover) {
			var img = document.createElement('img');
			img.src = song.cover;
			img.alt = '';
			cover.appendChild(img);
		} else {
			cover.textContent = '\u266B';
		}

		var body = document.createElement('div');
		body.className = 'musicshare-toast-body';

		var head = document.createElement('div');
		head.className = 'musicshare-toast-head';
		head.textContent = (lang.newUpload || 'Nuovo brano');

		var text = document.createElement('div');
		text.className = 'musicshare-toast-text';
		// niente innerHTML: i nomi arrivano dagli utenti
		text.textContent = song.uploader + ' \u2014 ' + song.title + (song.artist ? ' (' + song.artist + ')' : '');

		body.appendChild(head);
		body.appendChild(text);

		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'musicshare-toast-close';
		close.innerHTML = '&#10005;';
		close.addEventListener('click', function (e) {
			e.stopPropagation();
			dismiss(toast);
		});

		toast.appendChild(cover);
		toast.appendChild(body);
		toast.appendChild(close);

		if (song.url) {
			toast.classList.add('musicshare-toast-clickable');
			toast.addEventListener('click', function () {
				window.location.href = song.url;
			});
		}

		getContainer().appendChild(toast);

		// avvia la transizione di entrata dopo l'inserimento nel documento
		window.setTimeout(function () {
			toast.classList.add('musicshare-toast-visible');
		}, 20);

		window.setTimeout(function () {
			dismiss(toast);
		}, 9000);
	}

	function dismiss(toast) {
		if (!toast || !toast.parentNode) {
			return;
		}

		toast.classList.remove('musicshare-toast-visible');

		window.setTimeout(function () {
			if (toast.parentNode) {
				toast.parentNode.removeChild(toast);
			}
		}, 350);
	}

	function check() {
		if (document.hidden) {
			return;
		}

		var since = readLastSeen();

		fetch(cfg.ajaxRecent + (cfg.ajaxRecent.indexOf('?') === -1 ? '?' : '&') + 'since=' + since, {
			credentials: 'same-origin'
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (!data || !data.success) {
					return;
				}

				if (data.now) {
					writeLastSeen(data.now);
				}

				if (data.songs && data.songs.length) {
					// un solo segnale acustico per ciclo, anche se i brani
					// nuovi sono più di uno
					playSound();

					data.songs.forEach(function (song, i) {
						window.setTimeout(function () {
							showToast(song);
						}, i * 450);
					});
				}
			})
			.catch(function () {
				// rete non disponibile: si riprova al giro successivo
			});
	}

	// primo controllo dopo qualche secondo, per non pesare sul caricamento
	window.setTimeout(check, 4000);
	timer = window.setInterval(check, interval);

	// riprende subito quando si torna sulla scheda
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			check();
		}
	});

	window.addEventListener('pagehide', function () {
		if (timer) {
			window.clearInterval(timer);
		}
	});
})();
