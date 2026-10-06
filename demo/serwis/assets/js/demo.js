/**
 * TSL Manager Editorial — dodatki wersji demonstracyjnej (statyczny eksport).
 *
 * - pasek „wersja demonstracyjna” (ukrywany na czas pokazu),
 * - formularze: walidacja jak w serwisie, zamiast wysyłki komunikat demo,
 * - wyszukiwarka i filtry (filar, format, rodzaj transportu) działające
 *   w przeglądarce na indeksie materiałów (szukaj/indeks.js),
 * - filtr formatów w działach — bez przeładowania strony.
 *
 * Nic nie jest wysyłane ani zapisywane poza przeglądarką.
 */
( function () {
	'use strict';

	const root = document.documentElement.getAttribute( 'data-demo' ) || './';
	const DEMO_MSG = 'To wersja demonstracyjna serwisu — formularz działa, ale nie wysyła ani nie zapisuje danych.';

	/* Pasek demo. */
	const bar = document.getElementById( 'demo-bar' );
	const barMsg = document.getElementById( 'demo-bar-msg' );
	try {
		if ( sessionStorage.getItem( 'tslDemoBar' ) === '0' && bar ) {
			bar.hidden = true;
		}
	} catch ( e ) {}
	const hide = document.getElementById( 'demo-bar-hide' );
	if ( hide ) {
		hide.addEventListener( 'click', () => {
			bar.hidden = true;
			try {
				sessionStorage.setItem( 'tslDemoBar', '0' );
			} catch ( e ) {}
		} );
	}
	const say = ( text ) => {
		if ( bar && barMsg ) {
			barMsg.textContent = text;
			bar.hidden = false;
		}
	};

	/* Formularze: wyszukiwanie → strona wyników; pozostałe → komunikat demo. */
	window.addEventListener( 'submit', ( event ) => {
		const form = event.target;
		const search = form.matches( '[role="search"]' );
		if ( search ) {
			event.preventDefault();
			event.stopImmediatePropagation();
			const params = new URLSearchParams();
			new FormData( form ).forEach( ( value, key ) => {
				if ( value || key === 's' ) {
					params.set( key, value );
				}
			} );
			window.location.href = root + 'szukaj/index.html?' + params.toString();
			return;
		}
		// Niepoprawne pola: walidację i komunikaty pokazuje skrypt serwisu (jak na serwerze).
		if ( ! form.checkValidity() ) {
			return;
		}
		event.preventDefault();
		event.stopImmediatePropagation();
		const status = form.querySelector( '.form-status' );
		if ( status ) {
			status.textContent = DEMO_MSG;
		} else {
			say( DEMO_MSG );
		}
		form.querySelectorAll( '[aria-invalid]' ).forEach( ( el ) => el.removeAttribute( 'aria-invalid' ) );
		form.querySelectorAll( '.field-error' ).forEach( ( el ) => {
			el.textContent = '';
		} );
	}, true );

	/* Polska odmiana: 1 materiał, 2 materiały, 5 materiałów. */
	const plural = ( n, one, few, many ) => {
		if ( n === 1 ) {
			return one;
		}
		const d = n % 10;
		const t = n % 100;
		return d >= 2 && d <= 4 && ( t < 12 || t > 14 ) ? few : many;
	};
	const fold = ( text ) => text.toLowerCase().replace( /ł/g, 'l' ).normalize( 'NFKD' ).replace( /[̀-ͯ]/g, '' );

	/* Wyszukiwarka (strona szukaj/). */
	if ( window.TSL_SEARCH ) {
		const params = new URLSearchParams( window.location.search );
		const query = ( params.get( 's' ) || '' ).trim();
		const filters = { category_name: params.get( 'category_name' ) || '', format: params.get( 'format' ) || '', transport: params.get( 'transport' ) || '' };
		const input = document.getElementById( 'search-page-input' );
		if ( input ) {
			input.value = query;
		}
		Object.keys( filters ).forEach( ( name ) => {
			const select = document.querySelector( '.search-filters select[name="' + name + '"]' );
			if ( select ) {
				select.value = filters[ name ];
			}
		} );
		document.querySelectorAll( '.edition-bar ~ * input[type="search"], .search-form input[type="search"]' ).forEach( ( el ) => {
			el.value = query;
		} );

		const terms = fold( query ).split( /\s+/ ).filter( Boolean );
		const results = window.TSL_SEARCH.filter( ( item ) =>
			terms.every( ( t ) => item.t.indexOf( t ) !== -1 ) &&
			( ! filters.category_name || item.p === filters.category_name ) &&
			( ! filters.format || item.f.split( ' ' ).indexOf( filters.format ) !== -1 ) &&
			( ! filters.transport || item.r.split( ' ' ).indexOf( filters.transport ) !== -1 )
		);

		const box = document.querySelector( '[data-search-term]' );
		if ( box ) {
			box.setAttribute( 'data-search-term', query );
			box.setAttribute( 'data-search-count', String( results.length ) );
			if ( results.length ) {
				box.innerHTML = '<p class="result-count" role="status">Znaleziono ' + results.length + ' ' + plural( results.length, 'materiał', 'materiały', 'materiałów' ) + '</p>' +
					'<h2 class="screen-reader-text">Wyniki wyszukiwania</h2><div class="post-list">' + results.map( ( r ) => r.h ).join( '' ) + '</div>';
			} else {
				box.innerHTML = window.TSL_EMPTY || '<div class="empty-state"><p class="empty-state-title">Brak wyników dla tego zapytania</p></div>';
			}
		}
		if ( query ) {
			document.title = 'Wyniki wyszukiwania: ' + query + ' – TSL Manager';
		}
	}

	/* Filtr formatów w działach (w serwisie: ?format=… przeładowuje listę). */
	document.querySelectorAll( '[data-format-filter]' ).forEach( ( nav ) => {
		const main = nav.closest( '.dept-main' ) || document;
		const list = main.querySelector( '.post-list' );
		const featured = main.querySelector( '.dept-featured' );
		const original = main.querySelector( '.empty-state' );
		const originalHtml = original ? original.innerHTML : '';
		nav.addEventListener( 'click', ( event ) => {
			const link = event.target.closest( 'a[data-format]' );
			if ( ! link ) {
				return;
			}
			event.preventDefault();
			const format = link.getAttribute( 'data-format' );
			nav.querySelectorAll( 'a' ).forEach( ( a ) => a.removeAttribute( 'aria-current' ) );
			link.setAttribute( 'aria-current', 'true' );
			if ( featured ) {
				featured.hidden = !! format;
			}
			let shown = 0;
			if ( list ) {
				list.querySelectorAll( '.story' ).forEach( ( story ) => {
					const match = ! format || ( story.getAttribute( 'data-format' ) || '' ).split( ' ' ).indexOf( format ) !== -1;
					story.hidden = ! match;
					shown += match ? 1 : 0;
				} );
				list.hidden = ! shown;
			}
			let empty = main.querySelector( '.empty-state' );
			if ( format && ! shown ) {
				if ( ! empty ) {
					empty = document.createElement( 'div' );
					empty.className = 'empty-state';
					( list || nav ).insertAdjacentElement( 'afterend', empty );
				}
				empty.innerHTML = '<p class="empty-state-title">Brak materiałów w wybranym formacie</p><p>Wybierz „Wszystkie”, aby zobaczyć pozostałe materiały działu.</p>';
				empty.hidden = false;
			} else if ( empty ) {
				if ( empty === original ) {
					empty.innerHTML = originalHtml;
				} else {
					empty.hidden = true;
				}
			}
		} );
	} );
}() );
