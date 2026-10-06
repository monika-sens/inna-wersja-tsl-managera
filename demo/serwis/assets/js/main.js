/**
 * TSL Manager Editorial — interakcje.
 *
 * Zachowane z motywu 0.10.0: panele działów (mega menu), wyszukiwarka,
 * menu pełnoekranowe z pułapką fokusu, akordeony, zakładki ARIA,
 * newsletter i formularze (REST), checklisty (postęp, localStorage, druk).
 * Nowe: zwijana winieta, chowanie nagłówka przy czytaniu na telefonie,
 * pasek postępu czytania, podpowiedź przewijania tabel, kalkulator motywu.
 *
 * Panele otwierają się kliknięciem lub klawiaturą (bez hover),
 * zamykają klawiszem Esc i kliknięciem poza nagłówkiem.
 */
( function () {
	'use strict';

	const header = document.getElementById( 'site-header' );
	const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const wide = window.matchMedia( '(min-width: 1080px)' );

	const panelOf = ( button ) => document.getElementById( button.getAttribute( 'aria-controls' ) );

	function setOpen( button, open ) {
		const panel = panelOf( button );
		if ( ! panel ) {
			return;
		}
		// Kilka przycisków może sterować tym samym panelem (np. wyszukiwarka w winiecie i w pasku działów).
		document.querySelectorAll( '[aria-controls="' + panel.id + '"]' ).forEach( ( b ) => {
			b.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
		panel.hidden = ! open;
	}

	if ( header ) {
		/* Panele działów i wyszukiwarka: najwyżej jeden otwarty panel naraz. */
		const headerToggles = Array.from( header.querySelectorAll( '.section-btn[aria-controls], .search-toggle' ) );

		const closeHeaderPanels = ( except ) => {
			headerToggles.forEach( ( b ) => {
				if ( except && b.getAttribute( 'aria-controls' ) === except.getAttribute( 'aria-controls' ) ) {
					return;
				}
				if ( b.getAttribute( 'aria-expanded' ) === 'true' ) {
					setOpen( b, false );
				}
			} );
		};

		headerToggles.forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const open = button.getAttribute( 'aria-expanded' ) !== 'true';
				closeHeaderPanels( button );
				setOpen( button, open );
				if ( open && button.classList.contains( 'search-toggle' ) ) {
					const input = panelOf( button ).querySelector( 'input[type="search"]' );
					if ( input ) {
						input.focus();
					}
				}
			} );
		} );

		/* Panel zamyka się, gdy fokus klawiatury opuści przycisk i panel (Tab dalej). */
		headerToggles.forEach( ( button ) => {
			const panel = panelOf( button );
			const owner = button.closest( '.section-item' );
			[ button, panel ].forEach( ( el ) => {
				if ( ! el ) {
					return;
				}
				el.addEventListener( 'focusout', ( event ) => {
					const next = event.relatedTarget;
					if ( ! next || button.getAttribute( 'aria-expanded' ) !== 'true' ) {
						return;
					}
					if ( next === button || ( panel && panel.contains( next ) ) || ( owner && owner.contains( next ) ) || next.getAttribute( 'aria-controls' ) === button.getAttribute( 'aria-controls' ) ) {
						return;
					}
					setOpen( button, false );
				} );
			} );
		} );

		/*
		 * Menu pełnoekranowe: reszta strony jest wyłączona z fokusu (inert),
		 * Tab krąży między przyciskiem menu a elementami menu, Esc zamyka menu
		 * i zwraca fokus na przycisk.
		 */
		const menuToggle = header.querySelector( '.menu-toggle' );
		const mobileNav = document.getElementById( 'mobile-nav' );
		const outside = Array.from( document.body.children ).filter( ( el ) => el !== header && el.tagName !== 'SCRIPT' );
		const masthead = header.querySelector( '.masthead-main' );
		const focusables = () => Array.from( mobileNav.querySelectorAll( 'a[href], button, input, select, textarea' ) ).filter( ( el ) => ! el.disabled && el.offsetParent !== null );
		const menuIsOpen = () => menuToggle && menuToggle.getAttribute( 'aria-expanded' ) === 'true';

		function setMenu( open, returnFocus ) {
			if ( open ) {
				document.body.classList.remove( 'hide-header' );
				document.documentElement.style.setProperty( '--hdr-bottom', Math.max( 0, masthead.getBoundingClientRect().bottom ) + 'px' );
			}
			setOpen( menuToggle, open );
			document.body.classList.toggle( 'nav-open', open );
			outside.forEach( ( el ) => {
				el.inert = open;
			} );
			if ( ! open && returnFocus ) {
				menuToggle.focus();
			}
		}

		if ( menuToggle && mobileNav ) {
			menuToggle.addEventListener( 'click', () => {
				closeHeaderPanels();
				setMenu( ! menuIsOpen(), false );
			} );
			header.addEventListener( 'keydown', ( event ) => {
				if ( event.key !== 'Tab' || ! menuIsOpen() ) {
					return;
				}
				const items = focusables();
				if ( ! items.length ) {
					return;
				}
				const first = items[ 0 ];
				const last = items[ items.length - 1 ];
				const active = document.activeElement;
				if ( event.shiftKey && ( active === menuToggle || ! mobileNav.contains( active ) ) ) {
					event.preventDefault();
					last.focus();
				} else if ( event.shiftKey && active === first ) {
					event.preventDefault();
					menuToggle.focus();
				} else if ( ! event.shiftKey && active === last ) {
					event.preventDefault();
					menuToggle.focus();
				} else if ( ! event.shiftKey && active !== menuToggle && ! mobileNav.contains( active ) ) {
					event.preventDefault();
					menuToggle.focus();
				}
			} );
		}

		/* Akordeony w menu. */
		header.querySelectorAll( '.accordion-toggle' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				setOpen( button, button.getAttribute( 'aria-expanded' ) !== 'true' );
			} );
		} );

		/* Esc i kliknięcie poza nagłówkiem zamykają panele. */
		document.addEventListener( 'keydown', ( event ) => {
			if ( event.key !== 'Escape' ) {
				return;
			}
			const open = headerToggles.find( ( b ) => b.getAttribute( 'aria-expanded' ) === 'true' && b.offsetParent !== null );
			closeHeaderPanels();
			if ( menuIsOpen() ) {
				setMenu( false, true );
			} else if ( open ) {
				open.focus();
			}
		} );

		document.addEventListener( 'click', ( event ) => {
			if ( ! header.contains( event.target ) ) {
				closeHeaderPanels();
			}
		} );

		/* Po przejściu do szerokiego widoku zamknij menu pełnoekranowe. */
		wide.addEventListener( 'change', ( mq ) => {
			if ( mq.matches && menuIsOpen() ) {
				setMenu( false, false );
			}
			measure();
		} );

		/*
		 * Winieta: na szerokim ekranie chowa się przy przewijaniu, a pasek działów
		 * zostaje u góry (CSS: top = −wysokość winiety). Na tablecie i telefonie
		 * nagłówek chowa się przy czytaniu w dół i wraca przy przewinięciu w górę.
		 */
		let mhHeight = 0;
		function measure() {
			mhHeight = masthead ? masthead.offsetHeight : 0;
			document.documentElement.style.setProperty( '--mh-h', mhHeight + 'px' );
		}
		measure();
		window.addEventListener( 'resize', measure, { passive: true } );
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( measure );
		}

		let lastY = window.scrollY;
		let ticking = false;
		const headerTop = () => header.getBoundingClientRect().top + window.scrollY;
		let startY = headerTop();

		function onScroll() {
			const y = window.scrollY;
			const stuck = y > startY + mhHeight - 1;
			header.classList.toggle( 'is-stuck', stuck );

			if ( ! wide.matches ) {
				const busy = menuIsOpen() || headerToggles.some( ( b ) => b.getAttribute( 'aria-expanded' ) === 'true' );
				if ( busy || y < startY + header.offsetHeight + 40 || y < lastY - 4 ) {
					document.body.classList.remove( 'hide-header' );
				} else if ( y > lastY + 4 ) {
					document.body.classList.add( 'hide-header' );
				}
			} else {
				document.body.classList.remove( 'hide-header' );
			}
			lastY = y;
			ticking = false;
		}
		window.addEventListener( 'scroll', () => {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( onScroll );
			}
		}, { passive: true } );
		window.addEventListener( 'resize', () => {
			startY = header.classList.contains( 'is-stuck' ) ? startY : headerTop();
		}, { passive: true } );
		onScroll();

		/* Pasek działów (telefon): bieżący dział widoczny w przewijanej liście. */
		const current = header.querySelector( '.sections-list .is-current' );
		if ( current && ! wide.matches ) {
			const list = current.parentElement;
			list.scrollLeft = Math.max( 0, current.offsetLeft - 24 );
		}
	}

	/* Pasek postępu czytania (artykuł, raport). */
	const progress = document.querySelector( '.reading-progress span' );
	const article = document.querySelector( '.entry-content' );
	if ( progress && article ) {
		let pending = false;
		const update = () => {
			const rect = article.getBoundingClientRect();
			const total = rect.height - window.innerHeight * 0.6;
			const done = Math.min( 1, Math.max( 0, -rect.top / ( total > 0 ? total : 1 ) ) );
			progress.style.transform = 'scaleX(' + done.toFixed( 3 ) + ')';
			pending = false;
		};
		window.addEventListener( 'scroll', () => {
			if ( ! pending ) {
				pending = true;
				window.requestAnimationFrame( update );
			}
		}, { passive: true } );
		update();
	}

	/* Tabele szersze niż kolumna: cień-wskazówka i informacja o przewijaniu. */
	const tables = Array.from( document.querySelectorAll( '.entry-content .wp-block-table' ) );
	if ( tables.length ) {
		tables.forEach( ( figure ) => {
			const hint = document.createElement( 'p' );
			hint.className = 'table-hint';
			hint.setAttribute( 'aria-hidden', 'true' );
			hint.textContent = 'Tabelę można przewinąć w bok →';
			figure.insertAdjacentElement( 'afterend', hint );
		} );
		const check = () => tables.forEach( ( figure ) => {
			figure.classList.toggle( 'is-scrollable', figure.scrollWidth > figure.clientWidth + 2 );
		} );
		check();
		window.addEventListener( 'resize', check, { passive: true } );
	}

	/* Zakładki (moduł „Transport i operacje”), wzorzec ARIA tabs. */
	document.querySelectorAll( '[data-tabs]' ).forEach( ( root ) => {
		const tabs = Array.from( root.querySelectorAll( '[role="tab"]' ) );

		function select( tab ) {
			tabs.forEach( ( t ) => {
				const selected = t === tab;
				t.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
				t.tabIndex = selected ? 0 : -1;
				panelOf( t ).hidden = ! selected;
			} );
		}

		tabs.forEach( ( tab, i ) => {
			tab.addEventListener( 'click', () => select( tab ) );
			tab.addEventListener( 'keydown', ( event ) => {
				let next = null;
				if ( event.key === 'ArrowRight' ) {
					next = tabs[ ( i + 1 ) % tabs.length ];
				} else if ( event.key === 'ArrowLeft' ) {
					next = tabs[ ( i - 1 + tabs.length ) % tabs.length ];
				} else if ( event.key === 'Home' ) {
					next = tabs[ 0 ];
				} else if ( event.key === 'End' ) {
					next = tabs[ tabs.length - 1 ];
				}
				if ( next ) {
					event.preventDefault();
					select( next );
					next.focus();
				}
			} );
		} );
	} );

	/* Newsletter: zapis przez REST API, potwierdzenie linkiem z e-maila. */
	document.querySelectorAll( '[data-tsl-newsletter]' ).forEach( ( form ) => {
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			const status = form.querySelector( '.form-status' );
			const email = form.querySelector( 'input[type="email"]' );
			const consent = form.querySelector( 'input[type="checkbox"]' );
			const honeypot = form.querySelector( 'input[name="website"]' );
			const button = form.querySelector( 'button[type="submit"]' );

			if ( ! email.value || ! email.checkValidity() ) {
				status.textContent = 'Podaj poprawny adres e-mail.';
				email.focus();
				return;
			}
			if ( consent && ! consent.checked ) {
				status.textContent = 'Zaznacz zgodę, aby zapisać się do newslettera.';
				consent.focus();
				return;
			}

			button.disabled = true;
			status.textContent = 'Zapisywanie…';
			fetch( form.dataset.endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( {
					email: email.value,
					consent: !! ( consent && consent.checked ),
					source: window.location.href,
					website: honeypot ? honeypot.value : '',
				} ),
			} )
				.then( ( r ) => r.json().then( ( data ) => ( { ok: r.ok, data } ) ) )
				.then( ( { ok, data } ) => {
					status.textContent = data.message || ( ok ? 'Gotowe.' : 'Nie udało się zapisać. Spróbuj ponownie.' );
					if ( ok ) {
						form.reset();
						if ( window.tslTrack ) {
							window.tslTrack( 'Newsletter', 'zapis', window.location.pathname );
						}
					}
				} )
				.catch( () => {
					status.textContent = 'Brak połączenia. Spróbuj ponownie za chwilę.';
				} )
				.finally( () => {
					button.disabled = false;
				} );
		} );
	} );

	/* Formularze (kontakt, rejestracja, pobranie): wysyłka przez REST API. */
	document.querySelectorAll( '[data-tsl-form]' ).forEach( ( form ) => {
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			const status = form.querySelector( '.form-status' );
			const button = form.querySelector( 'button[type="submit"]' );

			form.querySelectorAll( '.field-error' ).forEach( ( e ) => {
				e.textContent = '';
			} );
			form.querySelectorAll( '[aria-invalid]' ).forEach( ( e ) => e.removeAttribute( 'aria-invalid' ) );

			const showError = ( name, message ) => {
				const input = form.querySelector( '[name="' + name + '"]' );
				if ( ! input ) {
					return;
				}
				input.setAttribute( 'aria-invalid', 'true' );
				const box = document.getElementById( input.id + '-error' );
				if ( box ) {
					box.textContent = message;
					input.setAttribute( 'aria-describedby', box.id );
				}
			};

			// Walidacja w przeglądarce.
			let firstInvalid = null;
			form.querySelectorAll( 'input, select, textarea' ).forEach( ( input ) => {
				if ( input.name === 'website' || input.checkValidity() ) {
					return;
				}
				const msg = input.type === 'checkbox' ? 'To pole jest wymagane.' : ( input.validity.typeMismatch ? 'Podaj poprawny adres e-mail.' : 'To pole jest wymagane.' );
				showError( input.name, input.name === 'privacy' ? 'Potwierdź zapoznanie się z informacją o przetwarzaniu danych.' : msg );
				firstInvalid = firstInvalid || input;
			} );
			if ( firstInvalid ) {
				status.textContent = 'Popraw zaznaczone pola.';
				firstInvalid.focus();
				return;
			}

			const data = { source: window.location.href };
			new FormData( form ).forEach( ( value, key ) => {
				data[ key ] = value;
			} );
			data.privacy = !! form.querySelector( '[name="privacy"]:checked' );
			data.newsletter = !! form.querySelector( '[name="newsletter"]:checked' );

			button.disabled = true;
			status.textContent = 'Wysyłanie…';
			fetch( form.dataset.endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( data ),
			} )
				.then( ( r ) => r.json().then( ( body ) => ( { ok: r.ok, body } ) ) )
				.then( ( { ok, body } ) => {
					status.textContent = body.message || ( ok ? 'Wysłano.' : 'Nie udało się wysłać. Spróbuj ponownie.' );
					if ( ok ) {
						if ( window.tslTrack ) {
							window.tslTrack( 'Formularz', 'wysłano', form.dataset.endpoint.split( '/' ).pop() + ( data.newsletter ? ' + newsletter' : '' ) );
						}
						form.reset();
						form.classList.add( 'is-sent' );
					} else if ( body.data && body.data.fields ) {
						Object.keys( body.data.fields ).forEach( ( name ) => showError( name, body.data.fields[ name ] ) );
					}
				} )
				.catch( () => {
					status.textContent = 'Brak połączenia. Spróbuj ponownie za chwilę.';
				} )
				.finally( () => {
					button.disabled = false;
				} );
		} );
	} );

	/*
	 * Checklisty: odhaczanie, postęp, punkty krytyczne, wydruk / PDF.
	 * Stan (zaznaczenia i uwagi) zapisuje się tylko w przeglądarce czytelnika.
	 */
	document.querySelectorAll( '[data-checklist]' ).forEach( ( list ) => {
		const key = list.dataset.checklist;
		const boxes = Array.from( list.querySelectorAll( 'input[type="checkbox"][data-item]' ) );
		const notes = list.querySelector( '[data-notes]' );
		const doneEl = list.querySelector( '[data-done]' );
		const bar = list.querySelector( '[data-bar]' );
		const alertEl = list.querySelector( '[data-alert]' );
		const completeEl = list.querySelector( '[data-complete]' );
		let reported = false;

		const load = () => {
			try {
				return JSON.parse( window.localStorage.getItem( key ) || '{}' );
			} catch ( e ) {
				return {};
			}
		};
		const save = () => {
			const state = { items: boxes.filter( ( b ) => b.checked ).map( ( b ) => b.dataset.item ), notes: notes ? notes.value : '' };
			try {
				window.localStorage.setItem( key, JSON.stringify( state ) );
			} catch ( e ) {}
		};

		const update = () => {
			const done = boxes.filter( ( b ) => b.checked ).length;
			const criticalOpen = boxes.filter( ( b ) => b.hasAttribute( 'data-critical' ) && ! b.checked ).length;
			doneEl.textContent = done;
			bar.style.width = ( boxes.length ? Math.round( ( done / boxes.length ) * 100 ) : 0 ) + '%';
			// Ostrzeżenie dopiero po rozpoczęciu pracy z listą, gdy krytyczne punkty zostają otwarte.
			alertEl.hidden = ! ( done > 0 && criticalOpen > 0 && done >= boxes.length - criticalOpen );
			completeEl.hidden = done !== boxes.length;
			if ( done === boxes.length && ! reported && window.tslTrack ) {
				reported = true;
				window.tslTrack( 'Narzędzie', 'checklista ukończona', list.dataset.title );
			}
		};

		const state = load();
		( state.items || [] ).forEach( ( id ) => {
			const box = list.querySelector( '[data-item="' + id + '"]' );
			if ( box ) {
				box.checked = true;
			}
		} );
		if ( notes && state.notes ) {
			notes.value = state.notes;
		}
		update();

		list.addEventListener( 'change', () => {
			save();
			update();
		} );
		if ( notes ) {
			notes.addEventListener( 'input', save );
		}

		list.querySelector( '[data-reset]' ).addEventListener( 'click', () => {
			boxes.forEach( ( b ) => {
				b.checked = false;
			} );
			if ( notes ) {
				notes.value = '';
			}
			reported = false;
			save();
			update();
		} );

		list.querySelector( '[data-print]' ).addEventListener( 'click', () => {
			if ( window.tslTrack ) {
				window.tslTrack( 'Narzędzie', 'checklista druk', list.dataset.title );
			}
			// Do druku trafia tylko kopia checklisty — reszta strony nie zajmuje miejsca na kartkach.
			const root = document.createElement( 'div' );
			root.className = 'print-root';
			const copy = list.cloneNode( true );
			copy.removeAttribute( 'id' );
			copy.querySelectorAll( '[id]' ).forEach( ( el ) => el.removeAttribute( 'id' ) );
			const sourceBoxes = list.querySelectorAll( 'input[type="checkbox"]' );
			copy.querySelectorAll( 'input[type="checkbox"]' ).forEach( ( box, i ) => {
				box.toggleAttribute( 'checked', sourceBoxes[ i ].checked );
			} );
			const copyNotes = copy.querySelector( '[data-notes]' );
			if ( copyNotes && notes ) {
				copyNotes.textContent = notes.value;
			}
			root.appendChild( copy );
			document.body.appendChild( root );
			document.body.classList.add( 'print-checklist' );
			window.print();
		} );
		window.addEventListener( 'afterprint', () => {
			document.body.classList.remove( 'print-checklist' );
			document.querySelectorAll( '.print-root' ).forEach( ( el ) => el.remove() );
		} );
	} );

	/*
	 * Kalkulator motywu (gdy Cost Calculator Builder nie jest włączony):
	 * pola i wzory z definicji wtyczki, liczone w przeglądarce.
	 */
	document.querySelectorAll( '[data-tsl-calc]' ).forEach( ( root ) => {
		let spec;
		try {
			spec = JSON.parse( root.dataset.tslCalc );
		} catch ( e ) {
			return;
		}
		const fn = {};
		spec.totals.forEach( ( t ) => {
			fn[ t.a ] = new Function( 'v', 'return ' + t.f + ';' ); // eslint-disable-line no-new-func
		} );
		const num = ( x ) => {
			const n = parseFloat( String( x ).replace( /\s/g, '' ).replace( ',', '.' ) );
			return isFinite( n ) ? Math.abs( n ) : 0;
		};
		const fmt = ( x ) => x.toLocaleString( 'pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 } ) + ' zł';
		const run = () => {
			const v = {};
			spec.fields.forEach( ( f ) => {
				const el = root.querySelector( '[data-alias="' + f.a + '"]' );
				if ( ! el ) {
					return;
				}
				v[ f.a ] = Math.min( num( el.value ), f.max || Infinity );
				if ( el.type === 'range' ) {
					const out = el.parentElement.querySelector( 'output' );
					if ( out ) {
						out.textContent = el.value + ' ' + ( out.dataset.sign || '' );
					}
				}
			} );
			spec.totals.forEach( ( t ) => {
				let r = 0;
				try {
					r = fn[ t.a ]( v );
				} catch ( e ) {
					r = NaN;
				}
				v[ t.a ] = r;
				const cell = root.querySelector( '[data-total="' + t.a + '"]' );
				if ( cell ) {
					cell.textContent = r && isFinite( r ) ? fmt( r ) : '—';
				}
			} );
		};
		root.addEventListener( 'input', run );
		run();
	} );

	if ( reduceMotion ) {
		document.documentElement.classList.add( 'reduce-motion' );
	}
}() );
