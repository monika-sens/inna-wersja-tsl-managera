/**
 * Panel „Szczegóły” w edytorze blokowym.
 *
 * Pola są budowane z opisu w includes/fields.php (window.tslEditor), więc
 * dodanie pola w PHP automatycznie dodaje je do panelu. Skrypt nie wymaga
 * kompilacji — korzysta z bibliotek dostarczanych przez WordPress.
 */
( function ( wp ) {
	'use strict';

	const config = window.tslEditor;
	if ( ! config || ! wp.plugins ) {
		return;
	}

	const { createElement: el, Fragment, useState, useEffect } = wp.element;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const {
		TextControl, TextareaControl, SelectControl, ToggleControl,
		CheckboxControl, ComboboxControl, BaseControl, Button,
	} = wp.components;
	const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { addQueryArgs } = wp.url;
	const decode = ( s ) => ( wp.htmlEntities ? wp.htmlEntities.decodeEntities( s ) : s );
	const Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );

	const STATUSES = 'publish,future,draft,pending,private';

	/* Powiązane materiały: lista wybranych + wyszukiwarka. */
	function RelationField( { field, value, onChange } ) {
		const ids = ( value || [] ).map( Number ).filter( Boolean );
		const [ options, setOptions ] = useState( [] );
		const base = config.targets[ field.target ];

		const records = useSelect(
			( select ) => ( ids.length
				? select( 'core' ).getEntityRecords( 'postType', field.target, { include: ids, per_page: ids.length, status: STATUSES } )
				: [] ),
			[ ids.join( ',' ) ]
		);
		const titles = {};
		( records || [] ).forEach( ( r ) => {
			titles[ r.id ] = decode( ( r.title && ( r.title.raw || r.title.rendered ) ) || '(bez tytułu)' );
		} );

		function search( term ) {
			wp.apiFetch( {
				path: addQueryArgs( '/wp/v2/' + base, { search: term || undefined, per_page: 20, status: STATUSES, _fields: 'id,title' } ),
			} ).then( ( found ) => {
				setOptions( found.map( ( p ) => ( { value: String( p.id ), label: decode( p.title.rendered || '(bez tytułu)' ) } ) ) );
			} ).catch( () => setOptions( [] ) );
		}
		useEffect( () => search( '' ), [] );

		return el( BaseControl, { label: field.label, help: field.help, __nextHasNoMarginBottom: true },
			ids.length ? el( 'ul', { className: 'tsl-rel-list' },
				ids.map( ( id ) => el( 'li', { key: id },
					el( 'span', null, titles[ id ] || '#' + id ),
					el( Button, {
						variant: 'link', isDestructive: true, size: 'small',
						onClick: () => onChange( ids.filter( ( x ) => x !== id ) ),
						'aria-label': 'Usuń powiązanie: ' + ( titles[ id ] || id ),
					}, 'Usuń' )
				) )
			) : null,
			el( ComboboxControl, {
				label: 'Dodaj',
				hideLabelFromVision: true,
				placeholder: 'Wyszukaj i dodaj…',
				value: null,
				options: options.filter( ( o ) => ! ids.includes( Number( o.value ) ) ),
				onFilterValueChange: search,
				onChange: ( v ) => v && onChange( ids.concat( Number( v ) ) ),
				__nextHasNoMarginBottom: true,
			} )
		);
	}

	/* Kontekst dodatkowy: maksymalnie 2 filary. */
	function PillarsField( { field, value, onChange } ) {
		const ids = ( value || [] ).map( Number );
		return el( BaseControl, { label: field.label, help: field.help, __nextHasNoMarginBottom: true },
			config.pillars.map( ( p ) => el( CheckboxControl, {
				key: p.id,
				label: p.name,
				checked: ids.includes( p.id ),
				disabled: ! ids.includes( p.id ) && ids.length >= 2,
				onChange: ( on ) => onChange( on ? ids.concat( p.id ) : ids.filter( ( x ) => x !== p.id ) ),
				__nextHasNoMarginBottom: true,
			} ) )
		);
	}

	/* Plik z biblioteki mediów. */
	function MediaField( { field, value, onChange } ) {
		const media = useSelect( ( select ) => ( value ? select( 'core' ).getMedia( value ) : null ), [ value ] );
		const name = media ? ( media.source_url || '' ).split( '/' ).pop() : '';
		return el( BaseControl, { label: field.label, help: field.help, __nextHasNoMarginBottom: true },
			value ? el( 'p', { className: 'tsl-media-name' }, name || 'Plik #' + value ) : null,
			el( MediaUploadCheck, null,
				el( MediaUpload, {
					onSelect: ( m ) => onChange( m.id ),
					value,
					render: ( { open } ) => el( Button, { variant: 'secondary', onClick: open }, value ? 'Zmień plik' : 'Wybierz plik' ),
				} )
			),
			value ? el( Button, { variant: 'link', isDestructive: true, onClick: () => onChange( 0 ) }, 'Usuń plik' ) : null
		);
	}

	function Field( { field, meta, setMeta } ) {
		const value = meta[ field.key ];
		const set = ( v ) => setMeta( { ...meta, [ field.key ]: v } );
		const common = { label: field.label, help: field.help, __nextHasNoMarginBottom: true, __next40pxDefaultSize: true };

		switch ( field.type ) {
			case 'relation':
				return el( RelationField, { field, value, onChange: set } );
			case 'pillars':
				return el( PillarsField, { field, value, onChange: set } );
			case 'media':
				return el( MediaField, { field, value, onChange: set } );
			case 'select':
				return el( SelectControl, {
					...common,
					value: value || '',
					options: Object.keys( field.options ).map( ( k ) => ( { value: k, label: field.options[ k ] } ) ),
					onChange: set,
				} );
			case 'textarea':
			case 'lines':
				return el( TextareaControl, { label: field.label, help: field.help, value: value || '', rows: field.type === 'lines' ? 4 : 3, onChange: set, __nextHasNoMarginBottom: true } );
			case 'toggle':
				return el( ToggleControl, { label: field.label, help: field.help, checked: !! value, onChange: set, __nextHasNoMarginBottom: true } );
			case 'date':
				return el( TextControl, { ...common, type: 'date', value: value || '', onChange: set } );
			case 'datetime':
				return el( TextControl, { ...common, type: 'datetime-local', value: value || '', onChange: set } );
			case 'url':
				return el( TextControl, { ...common, type: 'url', value: value || '', onChange: set } );
			default:
				return el( TextControl, { ...common, value: value || '', onChange: set } );
		}
	}

	/* Panel wysyłki wydania newslettera. */
	function SendPanel() {
		const { postId, published, dirty } = useSelect( ( select ) => {
			const ed = select( 'core/editor' );
			return {
				postId: ed.getCurrentPostId(),
				published: ed.getEditedPostAttribute( 'status' ) === 'publish' && ed.getCurrentPostAttribute( 'status' ) === 'publish',
				dirty: ed.isEditedPostDirty(),
			};
		}, [] );
		const [ st, setSt ] = useState( null );
		const [ busy, setBusy ] = useState( false );
		const [ note, setNote ] = useState( null );

		function load() {
			wp.apiFetch( { path: '/tsl/v1/newsletter/' + postId } ).then( setSt ).catch( () => {} );
		}
		useEffect( load, [ postId, published ] );
		useEffect( () => {
			if ( ! st || st.status !== 'sending' ) {
				return undefined;
			}
			const t = setInterval( load, 5000 );
			return () => clearInterval( t );
		}, [ st && st.status ] );

		function call( path, confirmText ) {
			if ( confirmText && ! window.confirm( confirmText ) ) {
				return;
			}
			setBusy( true );
			setNote( null );
			wp.apiFetch( { path: '/tsl/v1/newsletter/' + postId + path, method: 'POST' } )
				.then( ( r ) => {
					setNote( { ok: true, text: r.message || 'Wysyłka rozpoczęta.' } );
					load();
				} )
				.catch( ( e ) => setNote( { ok: false, text: ( e && e.message ) || 'Wystąpił błąd.' } ) )
				.finally( () => setBusy( false ) );
		}

		if ( ! st ) {
			return el( 'p', null, 'Wczytywanie…' );
		}
		const pct = st.total ? Math.round( ( st.sent / st.total ) * 100 ) : 0;
		const hours = Math.max( 1, Math.ceil( st.audience / Math.max( 1, st.hourLimit ) ) );
		const canSend = published && ! dirty && st.status === 'draft' && st.audience > 0;

		return el( 'div', { className: 'tsl-send' },
			! st.smtpActive ? el( 'p', { className: 'tsl-warn' }, 'Poczta SMTP nie jest skonfigurowana (Newsletter → Ustawienia). Wiadomości mogą trafiać do spamu.' ) : null,
			st.status === 'draft' ? el( 'p', null,
				'Aktywni subskrybenci: ', el( 'strong', null, String( st.audience ) ), '. ',
				st.audience > st.hourLimit ? 'Przy limicie ' + st.hourLimit + '/h wysyłka potrwa ok. ' + hours + ' h.' : ''
			) : null,
			st.status === 'sending' ? el( Fragment, null,
				el( 'p', null, 'Wysyłanie w tle: ', el( 'strong', null, st.sent + ' / ' + st.total ) ),
				el( 'div', { className: 'tsl-progress' }, el( 'span', { style: { width: pct + '%' } } ) ),
				el( 'p', { className: 'tsl-note' }, 'Możesz zamknąć edytor — wysyłka trwa dalej, partiami co 5 minut.' )
			) : null,
			st.status === 'sent' ? el( 'p', null, '✓ Wysłano do ', el( 'strong', null, String( st.sent ) ), ' osób', st.finished ? ' (' + st.finished.slice( 0, 16 ) + ').' : '.' ) : null,
			st.failed ? el( 'p', { className: 'tsl-warn' }, 'Nieudane: ' + st.failed ) : null,
			st.lastError ? el( 'p', { className: 'tsl-warn' }, 'Wstrzymano partię: ' + st.lastError + ' Ponowienie automatycznie za kilka minut.' ) : null,
			el( Button, { variant: 'secondary', isBusy: busy, disabled: busy, onClick: () => call( '/test' ) }, 'Wyślij test na mój adres' ),
			st.status === 'draft' ? el( 'div', { style: { marginTop: '12px' } },
				el( Button, {
					variant: 'primary', isBusy: busy, disabled: busy || ! canSend,
					onClick: () => call( '/send', 'Wysłać to wydanie do ' + st.audience + ' subskrybentów? Tej operacji nie można cofnąć.' ),
				}, 'Wyślij do subskrybentów' ),
				! published ? el( 'p', { className: 'tsl-note' }, 'Najpierw opublikuj wydanie.' ) : ( dirty ? el( 'p', { className: 'tsl-note' }, 'Zapisz zmiany przed wysyłką.' ) : null )
			) : null,
			note ? el( 'p', { className: note.ok ? 'tsl-ok' : 'tsl-warn' }, note.text ) : null
		);
	}

	function DetailsPanel() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		if ( ! meta ) {
			return null;
		}
		const fields = config.fields.filter( ( f ) => f.type !== 'relation' && f.type !== 'send' );
		const hasSend = config.fields.some( ( f ) => f.type === 'send' );
		const relations = config.fields.filter( ( f ) => f.type === 'relation' );

		return el( Fragment, null,
			hasSend ? el( Panel, { name: 'tsl-send', title: 'Wysyłka', className: 'tsl-panel' }, el( SendPanel ) ) : null,
			fields.length ? el( Panel, { name: 'tsl-details', title: 'Szczegóły', className: 'tsl-panel' },
				fields.map( ( f ) => el( 'div', { key: f.key, className: 'tsl-field' }, el( Field, { field: f, meta, setMeta } ) ) )
			) : null,
			relations.length ? el( Panel, { name: 'tsl-relations', title: 'Powiązania', className: 'tsl-panel' },
				el( 'p', { className: 'tsl-note' }, 'Powiązania są obustronne — wystarczy połączyć materiały po jednej stronie.' ),
				relations.map( ( f ) => el( 'div', { key: f.key, className: 'tsl-field' }, el( Field, { field: f, meta, setMeta } ) ) )
			) : null
		);
	}

	wp.plugins.registerPlugin( 'tsl-details', { render: DetailsPanel } );

	/* Drobne style panelu. */
	const css = '.tsl-field{margin-bottom:16px}.tsl-rel-list{margin:4px 0 8px;padding:0;list-style:none}' +
		'.tsl-rel-list li{display:flex;justify-content:space-between;gap:8px;align-items:center;padding:6px 8px;margin-bottom:4px;background:#f0f0f1;border-radius:2px}' +
		'.tsl-note{color:#757575;font-size:12px;margin-top:0}.tsl-media-name{margin:4px 0 8px;word-break:break-all}' +
		'.tsl-warn{color:#b32d2e}.tsl-ok{color:#1e6b3a}.tsl-progress{height:8px;background:#e6e8eb;border-radius:4px;overflow:hidden;margin:6px 0}' +
		'.tsl-progress span{display:block;height:100%;background:#B8734A}';
	const style = document.createElement( 'style' );
	style.textContent = css;
	document.head.appendChild( style );
}( window.wp ) );
