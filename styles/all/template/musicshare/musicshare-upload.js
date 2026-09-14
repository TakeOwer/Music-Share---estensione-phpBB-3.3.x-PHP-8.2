/**
 * Music Share - upload con barra di avanzamento
 *
 * Intercetta l'invio del form di caricamento e lo trasmette via
 * XMLHttpRequest, così da poter mostrare l'avanzamento reale del
 * trasferimento. Se JavaScript non è disponibile il form continua a
 * funzionare normalmente con l'invio classico della pagina.
 */
(function () {
	'use strict';

	var cfg = window.musicshareConfig || {};
	var form = document.getElementById('musicshare_upload');

	if (!form || !cfg.ajaxUpload || !window.FormData || !window.XMLHttpRequest) {
		return;
	}

	var lang = cfg.lang || {};
	var submitBtn = form.querySelector('input[type="submit"]');
	var fileInput = form.querySelector('input[name="song_file"]');

	// Costruisco l'area di avanzamento subito sopra i pulsanti di invio
	var wrap = document.createElement('div');
	wrap.className = 'musicshare-progress-wrap';
	wrap.style.display = 'none';
	wrap.innerHTML =
		'<div class="musicshare-progress-track">' +
			'<div class="musicshare-progress-bar" style="width:0%"></div>' +
		'</div>' +
		'<div class="musicshare-progress-text"></div>';

	var submitRow = submitBtn ? submitBtn.parentNode : form;
	submitRow.parentNode.insertBefore(wrap, submitRow);

	var bar = wrap.querySelector('.musicshare-progress-bar');
	var text = wrap.querySelector('.musicshare-progress-text');

	/**
	 * Il brano corrisponde a una pubblicazione commerciale presente
	 * nell'archivio del servizio di riconoscimento. Non è una prova: si
	 * avvisa l'utente, che resta libero di procedere assumendosene la
	 * responsabilità, e intanto il brano attende l'approvazione.
	 *
	 * @param object res risposta del server
	 * @return void
	 */
	function showRecognitionWarning(res) {
		var dettaglio = [res.match_artist, res.match_title]
			.filter(function (v) { return v; })
			.join(' \u2014 ');

		if (res.match_label) {
			dettaglio += ' (' + res.match_label + ')';
		}

		var testo = (lang.recoWarning || '%s').replace('%s', dettaglio);
		var titolo = lang.recoWarningTitle || '';

		// phpbb.alert è la finestra di avviso nativa del forum
		if (window.phpbb && typeof window.phpbb.alert === 'function') {
			window.phpbb.alert(titolo, testo);
		} else {
			window.alert(titolo + '\n\n' + testo.replace(/<[^>]+>/g, ''));
		}
	}

	/**
	 * Riquadro informativo con icona e colore secondo l'esito.
	 *
	 * @param string message testo da mostrare
	 * @param string tipo    success | pending | error | info
	 * @return void
	 */
	function showMessage(message, tipo) {
		// compatibilità: prima il secondo parametro era un booleano
		if (tipo === true) { tipo = 'error'; }
		if (tipo === false || !tipo) { tipo = 'info'; }

		var icone = {
			success:	'fa-check-circle',
			pending:	'fa-clock-o',
			error:		'fa-exclamation-triangle',
			info:		'fa-info-circle'
		};

		// un solo riquadro per volta: il precedente viene sostituito
		var vecchio = form.parentNode.querySelector('.musicshare-notice');

		if (vecchio) {
			vecchio.parentNode.removeChild(vecchio);
		}

		var box = document.createElement('div');
		box.className = 'musicshare-notice musicshare-notice-' + tipo;
		box.setAttribute('role', tipo === 'error' ? 'alert' : 'status');

		var icona = document.createElement('i');
		icona.className = 'icon fa-fw ' + (icone[tipo] || icone.info);
		icona.setAttribute('aria-hidden', 'true');

		var testo = document.createElement('span');
		testo.className = 'musicshare-notice-text';
		testo.textContent = message;

		box.appendChild(icona);
		box.appendChild(testo);
		form.parentNode.insertBefore(box, form);

		try {
			box.scrollIntoView({ behavior: 'smooth', block: 'center' });
		} catch (e) {
			// browser senza supporto alle opzioni di scrollIntoView
		}
	}

	function formatBytes(bytes) {
		if (bytes >= 1048576) {
			return (bytes / 1048576).toFixed(1) + ' MB';
		}
		return Math.round(bytes / 1024) + ' KB';
	}

	function setBusy(busy) {
		if (submitBtn) {
			submitBtn.disabled = busy;
		}
	}

	form.addEventListener('submit', function (e) {
		// Nessun file scelto: lascio gestire al comportamento normale
		if (fileInput && fileInput.files && fileInput.files.length === 0) {
			return;
		}

		e.preventDefault();

		var data = new FormData(form);
		// il pulsante di invio non viene incluso automaticamente da FormData
		data.append('submit', '1');

		var xhr = new XMLHttpRequest();
		xhr.open('POST', cfg.ajaxUpload, true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.withCredentials = true;

		wrap.style.display = 'block';
		bar.style.width = '0%';
		bar.classList.remove('musicshare-progress-done');
		text.textContent = lang.uploadStarting || '...';
		setBusy(true);

		xhr.upload.addEventListener('progress', function (ev) {
			if (!ev.lengthComputable) {
				return;
			}

			var percent = Math.round((ev.loaded / ev.total) * 100);
			bar.style.width = percent + '%';
			text.textContent = percent + '% — ' + formatBytes(ev.loaded) + ' / ' + formatBytes(ev.total);
		});

		// Trasferimento finito, il server sta ancora elaborando il file
		xhr.upload.addEventListener('load', function () {
			bar.style.width = '100%';
			text.textContent = lang.uploadProcessing || '...';
		});

		xhr.addEventListener('load', function () {
			setBusy(false);

			var res;
			try {
				res = JSON.parse(xhr.responseText);
			} catch (err) {
				wrap.style.display = 'none';
				showMessage(lang.uploadFailed || 'Error', true);
				return;
			}

			if (res.success) {
				bar.classList.add('musicshare-progress-done');
				text.textContent = res.message || '';
				showMessage(res.message || '', res.type || 'success');
				form.reset();

				if (res.recognized) {
					showRecognitionWarning(res);
				}

				window.setTimeout(function () {
					wrap.style.display = 'none';
					bar.style.width = '0%';
				}, 2500);
			} else {
				wrap.style.display = 'none';
				showMessage(res.message || lang.uploadFailed || 'Error', res.type || 'error');
			}
		});

		xhr.addEventListener('error', function () {
			setBusy(false);
			wrap.style.display = 'none';
			showMessage(lang.uploadFailed || 'Error', true);
		});

		xhr.addEventListener('abort', function () {
			setBusy(false);
			wrap.style.display = 'none';
		});

		xhr.send(data);
	});
})();
