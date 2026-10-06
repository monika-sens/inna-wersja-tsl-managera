/**
 * Analityka TSL — jedna warstwa zdarzeń dla Matomo i Google Analytics 4.
 *
 * Użycie w szablonach:
 *   data-track="Kategoria|Akcja|Etykieta"   zdarzenie przy kliknięciu
 *   data-track-module="nazwa"               moduł strony głównej: ekspozycja + kliknięcia
 *   data-search-term / data-search-count    wynik wyszukiwania (brak wyników = zdarzenie)
 * W kodzie:
 *   window.tslTrack( 'Formularz', 'wysłano', 'kontakt' );
 */
( function () {
	'use strict';

	const cfg = window.tslAnalytics || {};
	const KEY = 'tsl_consent';
	const queue = [];

	function consent() {
		try {
			return window.localStorage.getItem( KEY );
		} catch ( e ) {
			return null;
		}
	}

	function setConsent( value ) {
		try {
			window.localStorage.setItem( KEY, value );
		} catch ( e ) {}
	}

	/* --- Adaptery narzędzi --- */

	function sendMatomo( category, action, name, value ) {
		if ( ! cfg.matomo ) {
			return;
		}
		window._paq = window._paq || [];
		const ev = [ 'trackEvent', category, action ];
		if ( name ) {
			ev.push( String( name ).slice( 0, 200 ) );
			if ( typeof value === 'number' ) {
				ev.push( value );
			}
		}
		window._paq.push( ev );
	}

	function sendGa4( category, action, name, value ) {
		if ( ! cfg.ga4 || typeof window.gtag !== 'function' ) {
			return;
		}
		const eventName = ( category + '_' + action ).toLowerCase()
			.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /ł/g, 'l' )
			.replace( /[^a-z0-9]+/g, '_' ).replace( /^_|_$/g, '' ).slice( 0, 40 );
		const params = { event_category: category, event_label: name || '', page_type: cfg.pageType };
		if ( typeof value === 'number' ) {
			params.value = value;
		}
		window.gtag( 'event', eventName, params );
	}

	function loadGa4() {
		if ( ! cfg.ga4 || window.gtag ) {
			return;
		}
		window.dataLayer = window.dataLayer || [];
		window.gtag = function () {
			window.dataLayer.push( arguments );
		};
		window.gtag( 'js', new Date() );
		window.gtag( 'config', cfg.ga4, { page_type: cfg.pageType } );
		const s = document.createElement( 'script' );
		s.async = true;
		s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent( cfg.ga4 );
		document.head.appendChild( s );
	}

	// Czy wolno już wysyłać dane (narzędzie bez wymogu zgody albo zgoda udzielona).
	const granted = () => consent() === 'grant';
	const matomoAllowed = () => cfg.matomo && ( ! cfg.matomoConsent || granted() );

	function send( category, action, name, value ) {
		if ( matomoAllowed() ) {
			sendMatomo( category, action, name, value );
		}
		if ( granted() ) {
			sendGa4( category, action, name, value );
			// Dla Google Tag Managera, jeśli zostanie dodany w przyszłości — też dopiero po zgodzie.
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( { event: 'tsl_event', tsl_category: category, tsl_action: action, tsl_label: name || '' } );
		}
	}

	function grant() {
		if ( cfg.matomo && cfg.matomoConsent ) {
			window._paq = window._paq || [];
			window._paq.push( [ 'setConsentGiven' ] );
		}
		loadGa4();
		while ( queue.length ) {
			const ev = queue.shift();
			if ( cfg.matomoConsent ) {
				sendMatomo.apply( null, ev );
			}
			sendGa4.apply( null, ev );
			window.dataLayer.push( { event: 'tsl_event', tsl_category: ev[ 0 ], tsl_action: ev[ 1 ], tsl_label: ev[ 2 ] || '' } );
		}
	}

	// Wycofanie zgody: Matomo zapomina zgodę, a GA4 nie zostaje wczytane po odświeżeniu strony.
	function revoke() {
		queue.length = 0;
		if ( cfg.matomo && cfg.matomoConsent ) {
			window._paq = window._paq || [];
			window._paq.push( [ 'forgetConsentGiven' ] );
		}
		if ( window.gtag ) {
			window.location.reload();
		}
	}

	/**
	 * Zdarzenie do wszystkich aktywnych narzędzi.
	 *
	 * @param {string} category Kategoria, np. „Formularz”.
	 * @param {string} action   Akcja, np. „wysłano”.
	 * @param {string} [name]   Etykieta, np. tytuł materiału.
	 * @param {number} [value]  Wartość liczbowa.
	 */
	window.tslTrack = function ( category, action, name, value ) {
		if ( cfg.disabled ) {
			return;
		}
		send( category, action, name, value );
		if ( cfg.needsConsent && ! consent() ) {
			queue.push( [ category, action, name, value ] ); // Wyślemy, jeśli użytkownik zaakceptuje w tej wizycie.
		}
	};

	if ( cfg.disabled ) {
		return;
	}

	/* --- Zgoda --- */

	const banner = document.getElementById( 'tsl-consent' );
	if ( cfg.needsConsent ) {
		if ( granted() ) {
			grant();
		} else if ( ! consent() && banner ) {
			banner.hidden = false;
		}
		if ( banner ) {
			let opener = null;
			banner.addEventListener( 'click', ( e ) => {
				const choice = e.target.closest( '[data-consent]' );
				if ( ! choice ) {
					return;
				}
				const before = consent();
				setConsent( choice.dataset.consent );
				banner.hidden = true;
				if ( choice.dataset.consent === 'grant' && before !== 'grant' ) {
					grant();
				} else if ( choice.dataset.consent === 'deny' && before === 'grant' ) {
					revoke();
				}
				if ( opener ) {
					opener.focus();
					opener = null;
				}
			} );
			// „Ustawienia prywatności” w stopce: ponowne otwarcie banera.
			document.querySelectorAll( '[data-tsl-privacy]' ).forEach( ( button ) => {
				button.hidden = false;
				button.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					opener = button;
					const state = banner.querySelector( '[data-consent-state]' );
					if ( state ) {
						state.textContent = granted() ? 'Obecnie: zgoda udzielona.' : ( consent() === 'deny' ? 'Obecnie: zgoda odrzucona.' : '' );
					}
					banner.hidden = false;
					banner.querySelector( '[data-consent="grant"]' ).focus();
				} );
			} );
		}
	}

	/* --- Zdarzenia automatyczne --- */

	// Czytelna etykieta linku: tytuł karty, jeśli jest, w przeciwnym razie tekst linku.
	function label( link ) {
		const title = link.querySelector( '.tool-title, .card-title, .report-title, .pillar-name' );
		return ( title || link ).textContent.replace( /\s+/g, ' ' ).trim().slice( 0, 120 );
	}

	// Kliknięcia w elementy z data-track oraz w moduły strony głównej.
	document.addEventListener( 'click', ( e ) => {
		const link = e.target.closest( 'a, button' );
		if ( ! link ) {
			return;
		}
		const tracked = link.closest( '[data-track]' );
		if ( tracked ) {
			const parts = tracked.dataset.track.split( '|' );
			window.tslTrack( parts[ 0 ], parts[ 1 ] || 'klik', parts[ 2 ] || label( link ) );
		}
		const module = link.closest( '[data-track-module]' );
		if ( module && link.tagName === 'A' ) {
			const href = link.getAttribute( 'href' ) || '';
			const target = /\/narzedzia\//.test( href ) ? 'narzędzie' : ( /\/riskbrief\//.test( href ) ? 'riskbrief' : 'treść' );
			window.tslTrack( 'Strona główna', 'klik: ' + module.dataset.trackModule, target + ' — ' + label( link ) );
		}
		// Pliki do pobrania (PDF, arkusze, dokumenty).
		if ( link.tagName === 'A' && /\.(pdf|xlsx?|docx?|pptx?|zip|csv)(\?|$)/i.test( link.getAttribute( 'href' ) || '' ) ) {
			window.tslTrack( 'Pobranie', 'plik', link.getAttribute( 'href' ).split( '/' ).pop() );
		}
	} );

	// Ekspozycja modułów strony głównej (moduł widoczny co najmniej w połowie).
	if ( 'IntersectionObserver' in window ) {
		const seen = new Set();
		const io = new IntersectionObserver( ( entries ) => {
			entries.forEach( ( entry ) => {
				const name = entry.target.dataset.trackModule;
				if ( entry.isIntersecting && ! seen.has( name ) ) {
					seen.add( name );
					window.tslTrack( 'Strona główna', 'ekspozycja', name );
					io.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.5 } );
		document.querySelectorAll( '[data-track-module]' ).forEach( ( el ) => io.observe( el ) );
	}

	// Wyszukiwarka: brak wyników to sygnał dla redakcji, czego brakuje w serwisie.
	const search = document.querySelector( '[data-search-term]' );
	if ( search && search.dataset.searchTerm ) {
		const count = parseInt( search.dataset.searchCount || '0', 10 );
		window.tslTrack( 'Wyszukiwarka', count ? 'wyniki' : 'brak wyników', search.dataset.searchTerm.toLowerCase(), count );
	}

	if ( cfg.pageType === '404' ) {
		window.tslTrack( 'Błąd', '404', window.location.pathname );
	}
}() );
