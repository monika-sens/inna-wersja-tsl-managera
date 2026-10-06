<?php
/**
 * Wpisy → Import z .docx: artykuł redakcji z pliku Word (np. pobranego z Dysku
 * Google) zamieniony na wpis z ramkami, tabelami, FAQ, źródłami, kategorią,
 * formatem i polami SEO — według tych samych zasad co artykuły wbudowane
 * we wtyczkę (tools/build-articles.py).
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Odczyt pliku .docx jako prostej listy bloków.
 *
 * Bloki: title, h (level), p (bold = cały akapit pogrubiony), li (ordered),
 * table (rows → cells → akapity). Tekst w linii ma postać markdown:
 * [tekst](https://adres) dla linków i **tekst** dla pogrubień.
 */
class TSL_Docx_Reader {

	const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
	const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

	/** @var array<string,string> rId → adres linku */
	private $links = array();
	/** @var array<string,string> styleId → nazwa stylu */
	private $styles = array();
	/** @var array<string,bool> numId → czy lista numerowana */
	private $ordered = array();
	/** @var DOMXPath */
	private $xp;
	/** @var string Stopka z datą („TSL Manager • … • stan: …”), jeśli jest w stopce strony. */
	private $footer = '';

	/**
	 * @param string $file Ścieżka do pliku .docx.
	 * @return array|WP_Error
	 */
	public static function read( $file ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'tsl_docx', 'Serwer nie obsługuje plików ZIP (brak rozszerzenia PHP zip).' );
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $file, ZipArchive::RDONLY ) ) {
			return new WP_Error( 'tsl_docx', 'To nie jest poprawny plik .docx (uszkodzone archiwum).' );
		}
		$check = self::check_structure( $zip );
		if ( is_wp_error( $check ) ) {
			$zip->close();
			return $check;
		}
		$document = $zip->getFromName( 'word/document.xml' );
		if ( false === $document ) {
			$zip->close();
			return new WP_Error( 'tsl_docx', 'W pliku brakuje treści dokumentu (word/document.xml).' );
		}
		$reader = new self();
		$reader->load_parts( $zip );
		$zip->close();
		return $reader->parse( $document );
	}

	/**
	 * Struktura pliku Word: wymagane części, brak makr, rozsądne rozmiary
	 * (ochrona przed „bombą ZIP”). Z dokumentu czytamy wyłącznie XML —
	 * żadna zawartość aktywna (makra, obiekty osadzone, skrypty) nie jest
	 * uruchamiana ani zapisywana.
	 *
	 * @param ZipArchive $zip Plik.
	 * @return true|WP_Error
	 */
	private static function check_structure( $zip ) {
		if ( $zip->numFiles < 1 || $zip->numFiles > 2000 ) {
			return new WP_Error( 'tsl_docx', 'Nieprawidłowa struktura pliku .docx.' );
		}
		$total = 0;
		$names = array();
		for ( $k = 0; $k < $zip->numFiles; $k++ ) {
			$stat = $zip->statIndex( $k );
			if ( ! $stat ) {
				return new WP_Error( 'tsl_docx', 'Nie udało się odczytać pliku .docx.' );
			}
			$names[ $stat['name'] ] = true;
			$total                 += (int) $stat['size'];
			if ( preg_match( '#(^|/)vbaProject\.bin$#i', $stat['name'] ) ) {
				return new WP_Error( 'tsl_docx', 'Plik zawiera makra — zapisz go jako zwykły dokument Word (.docx) bez makr.' );
			}
		}
		if ( empty( $names['[Content_Types].xml'] ) || empty( $names['word/document.xml'] ) ) {
			return new WP_Error( 'tsl_docx', 'To nie jest dokument Word: brakuje [Content_Types].xml albo word/document.xml.' );
		}
		$doc = $zip->statName( 'word/document.xml' );
		if ( $total > 100 * MB_IN_BYTES || ( $doc && $doc['size'] > 30 * MB_IN_BYTES ) ) {
			return new WP_Error( 'tsl_docx', 'Dokument jest zbyt duży po rozpakowaniu.' );
		}
		return true;
	}

	/**
	 * Linki, style i listy z pozostałych części pliku.
	 *
	 * @param ZipArchive $zip Plik.
	 */
	private function load_parts( $zip ) {
		$rels = $this->dom( $zip->getFromName( 'word/_rels/document.xml.rels' ) );
		if ( $rels ) {
			foreach ( $rels->getElementsByTagName( 'Relationship' ) as $rel ) {
				$this->links[ $rel->getAttribute( 'Id' ) ] = $rel->getAttribute( 'Target' );
			}
		}
		$styles = $this->dom( $zip->getFromName( 'word/styles.xml' ) );
		if ( $styles ) {
			foreach ( $styles->getElementsByTagNameNS( self::W, 'style' ) as $style ) {
				$name = $style->getElementsByTagNameNS( self::W, 'name' )->item( 0 );
				$this->styles[ $style->getAttributeNS( self::W, 'styleId' ) ] = strtolower( $name ? $name->getAttributeNS( self::W, 'val' ) : '' );
			}
		}
		for ( $k = 0; $k < $zip->numFiles; $k++ ) {
			$name = $zip->getNameIndex( $k );
			if ( preg_match( '#^word/footer\d*\.xml$#', $name ) ) {
				$text = trim( wp_strip_all_tags( str_replace( '</w:p>', "\n", (string) $zip->getFromName( $name ) ) ) );
				if ( preg_match( '/TSL Manager •[^\n]*stan:[^\n]*/u', $text, $m ) ) {
					$this->footer = trim( $m[0] );
				}
			}
		}
		$numbering = $this->dom( $zip->getFromName( 'word/numbering.xml' ) );
		if ( $numbering ) {
			$abstract = array();
			foreach ( $numbering->getElementsByTagNameNS( self::W, 'abstractNum' ) as $an ) {
				$fmt = $an->getElementsByTagNameNS( self::W, 'numFmt' )->item( 0 );
				$abstract[ $an->getAttributeNS( self::W, 'abstractNumId' ) ] = $fmt && 'bullet' !== $fmt->getAttributeNS( self::W, 'val' );
			}
			foreach ( $numbering->getElementsByTagNameNS( self::W, 'num' ) as $num ) {
				$ref = $num->getElementsByTagNameNS( self::W, 'abstractNumId' )->item( 0 );
				$this->ordered[ $num->getAttributeNS( self::W, 'numId' ) ] = $ref ? ( $abstract[ $ref->getAttributeNS( self::W, 'val' ) ] ?? false ) : false;
			}
		}
	}

	/**
	 * @param string|false $xml XML.
	 * @return DOMDocument|null
	 */
	private function dom( $xml ) {
		if ( ! $xml || false !== stripos( $xml, '<!DOCTYPE' ) ) {
			return null; // Dokumenty Word nie mają DOCTYPE — odrzucamy encje zewnętrzne (XXE).
		}
		$dom = new DOMDocument();
		return $dom->loadXML( $xml, LIBXML_NONET ) ? $dom : null;
	}

	/**
	 * @param string $xml word/document.xml.
	 * @return array
	 */
	private function parse( $xml ) {
		$dom = $this->dom( $xml );
		if ( ! $dom ) {
			return new WP_Error( 'tsl_docx', 'Nie udało się odczytać treści dokumentu.' );
		}
		$this->xp = new DOMXPath( $dom );
		$this->xp->registerNamespace( 'w', self::W );
		$body = $this->xp->query( '/w:document/w:body' )->item( 0 );
		$blocks = $body ? $this->children( $body ) : array();
		if ( $this->footer ) {
			$blocks[] = array( 't' => 'p', 'text' => $this->footer, 'bold' => false );
		}
		return $blocks;
	}

	/**
	 * Bloki z akapitów i tabel (także wewnątrz kontrolek treści w:sdt).
	 *
	 * @param DOMNode $node Rodzic.
	 * @return array
	 */
	private function children( $node ) {
		$blocks = array();
		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType || self::W !== $child->namespaceURI ) {
				continue;
			}
			if ( 'p' === $child->localName ) {
				$block = $this->paragraph( $child );
				if ( $block ) {
					$blocks[] = $block;
				}
			} elseif ( 'tbl' === $child->localName ) {
				$blocks[] = $this->table( $child );
			} elseif ( 'sdt' === $child->localName ) {
				foreach ( $this->xp->query( 'w:sdtContent', $child ) as $content ) {
					$blocks = array_merge( $blocks, $this->children( $content ) );
				}
			}
		}
		return $blocks;
	}

	/**
	 * @param DOMElement $p Akapit.
	 * @return array|null
	 */
	private function paragraph( $p ) {
		$segments = array();
		$this->runs( $p, $segments, '' );
		$plain = trim( implode( '', array_column( $segments, 0 ) ) );
		if ( '' === $plain ) {
			return null;
		}
		$all_bold = true;
		foreach ( $segments as $seg ) {
			if ( '' !== trim( $seg[0] ) && ! $seg[1] ) {
				$all_bold = false;
			}
		}
		$text = '';
		foreach ( $this->merge( $segments ) as $seg ) {
			list( $t, $bold, $url ) = $seg;
			$core = trim( $t );
			if ( '' === $core ) {
				$text .= $t;
				continue;
			}
			$lead  = substr( $t, 0, strlen( $t ) - strlen( ltrim( $t ) ) );
			$trail = substr( $t, strlen( rtrim( $t ) ) );
			if ( $url ) {
				$core = '[' . $core . '](' . $url . ')';
			}
			if ( $bold && ! $all_bold ) {
				$core = '**' . $core . '**';
			}
			$text .= $lead . $core . $trail;
		}
		$text = trim( preg_replace( '/[ \t]+/', ' ', $text ) );

		$style = $this->xp->query( 'w:pPr/w:pStyle/@w:val', $p )->item( 0 );
		$name  = $style ? ( $this->styles[ $style->value ] ?? strtolower( $style->value ) ) : '';
		if ( 'title' === $name ) {
			return array( 't' => 'title', 'text' => $text );
		}
		if ( preg_match( '/^(?:heading|nagłówek)\s*(\d)$/', $name, $m ) ) {
			return array( 't' => 'h', 'level' => (int) $m[1], 'text' => $text );
		}
		$num = $this->xp->query( 'w:pPr/w:numPr/w:numId/@w:val', $p )->item( 0 );
		if ( $num && '0' !== $num->value ) {
			return array( 't' => 'li', 'ordered' => ! empty( $this->ordered[ $num->value ] ), 'text' => $text );
		}
		return array( 't' => 'p', 'text' => $text, 'bold' => $all_bold );
	}

	/**
	 * Fragmenty tekstu akapitu: [tekst, pogrubiony, adres linku].
	 *
	 * @param DOMElement $node     Element.
	 * @param array      $segments Wynik.
	 * @param string     $url      Link, w którym jesteśmy.
	 */
	private function runs( $node, &$segments, $url ) {
		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType || self::W !== $child->namespaceURI ) {
				continue;
			}
			switch ( $child->localName ) {
				case 'hyperlink':
					$id     = $child->getAttributeNS( self::R, 'id' );
					$target = $id && isset( $this->links[ $id ] ) ? $this->links[ $id ] : '';
					$this->runs( $child, $segments, preg_match( '#^https?://#', $target ) ? $target : $url );
					break;
				case 'r':
					$bold = $this->xp->query( 'w:rPr/w:b[not(@w:val="0") and not(@w:val="false")]', $child )->length > 0;
					$text = '';
					foreach ( $child->childNodes as $part ) {
						if ( 't' === $part->localName ) {
							$text .= $part->textContent;
						} elseif ( in_array( $part->localName, array( 'tab', 'br', 'cr' ), true ) ) {
							$text .= ' ';
						}
					}
					if ( '' !== $text ) {
						$segments[] = array( $text, $bold, $url );
					}
					break;
				case 'ins':
				case 'smartTag':
				case 'fldSimple':
				case 'sdt':
				case 'sdtContent':
					$this->runs( $child, $segments, $url );
					break;
			}
		}
	}

	/**
	 * Łączy sąsiednie fragmenty o tym samym formatowaniu.
	 *
	 * @param array $segments Fragmenty.
	 * @return array
	 */
	private function merge( $segments ) {
		$out = array();
		foreach ( $segments as $seg ) {
			$last = count( $out ) - 1;
			if ( $last >= 0 && $out[ $last ][1] === $seg[1] && $out[ $last ][2] === $seg[2] ) {
				$out[ $last ][0] .= $seg[0];
			} else {
				$out[] = $seg;
			}
		}
		return $out;
	}

	/**
	 * @param DOMElement $tbl Tabela.
	 * @return array
	 */
	private function table( $tbl ) {
		$rows = array();
		foreach ( $this->xp->query( 'w:tr', $tbl ) as $tr ) {
			$cells = array();
			foreach ( $this->xp->query( 'w:tc', $tr ) as $tc ) {
				$paras = array();
				foreach ( $this->children( $tc ) as $block ) {
					if ( isset( $block['text'] ) ) {
						$paras[] = $block['text'];
					}
				}
				$cells[] = $paras;
			}
			$rows[] = $cells;
		}
		return array( 't' => 'table', 'rows' => $rows );
	}
}

/**
 * Zamiana bloków dokumentu na artykuł (te same zasady co tools/build-articles.py).
 */
class TSL_Docx_Article {

	const MONTHS = array(
		'stycznia' => 1, 'lutego' => 2, 'marca' => 3, 'kwietnia' => 4, 'maja' => 5, 'czerwca' => 6,
		'lipca' => 7, 'sierpnia' => 8, 'września' => 9, 'października' => 10, 'listopada' => 11, 'grudnia' => 12,
	);

	/** Nazwy podkategorii z tekstów, które różnią się od nazw w strukturze serwisu. */
	const ALIASES = array(
		'umowy i kontrakty'                     => 'umowy i odpowiedzialność',
		'automatyzacja magazynu'                => 'wms i technologie magazynowe',
		'systemy tms i oprogramowanie'          => 'tms',
		'transport drogowy'                     => 'transport/drogowy',
		'transport morski'                      => 'transport/morski',
		'transport kolejowy'                    => 'transport/kolejowy',
		'transport lotniczy'                    => 'transport/lotniczy',
		'transport intermodalny i multimodalny' => 'transport/intermodalny i multimodalny',
	);

	const LOWER = 'a-ząćęłńóśźż';
	const UPPER = 'A-ZĄĆĘŁŃÓŚŹŻ';

	/** @var string[] Uwagi dla redakcji. */
	public $warnings = array();

	/**
	 * @param array  $blocks   Bloki z TSL_Docx_Reader.
	 * @param string $filename Nazwa pliku (ID na początku nazwy).
	 * @return array|WP_Error Artykuł w formacie tsl_article_save().
	 */
	public function build( array $blocks, $filename ) {
		$info  = array();
		$path  = '';
		$i     = 0;
		$count = count( $blocks );

		// Metryczka: tabela „Ścieżka / ID / Filar / Format…” albo linie tekstu.
		if ( $count && 'table' === $blocks[0]['t'] && preg_match( '/^ścieżka$/iu', $this->cell( $blocks[0]['rows'][0][0] ?? array() ) ) ) {
			foreach ( $blocks[0]['rows'] as $row ) {
				if ( count( $row ) >= 2 ) {
					$info[ $this->cell( $row[0] ) ] = $this->cell( $row[1] );
				}
			}
			$path = $info['Ścieżka'] ?? '';
			$i    = 1;
		}
		$rest = array();
		for ( ; $i < $count; $i++ ) {
			$b = $blocks[ $i ];
			if ( 'p' === $b['t'] && '' === $path && preg_match( '/^TSL Manager\s*>/u', $this->plain( $b['text'] ) ) ) {
				$path = $this->plain( $b['text'] );
				continue;
			}
			if ( 'p' === $b['t'] && empty( $seen_heading ) && preg_match( '/^(Filar|ID|Autor|Data publikacji|Format):\s*(.+)$/u', $this->plain( $b['text'] ), $m ) ) {
				$info[ $m[1] ] = trim( $m[2] );
				continue;
			}
			if ( 'h' === $b['t'] ) {
				$seen_heading = true;
			}
			$rest[] = $b;
		}

		// ID: z nazwy pliku albo z metryczki.
		$code = function_exists( 'tsl_article_code_from_name' ) ? tsl_article_code_from_name( $filename ) : '';
		if ( ! $code && ! empty( $info['ID'] ) ) {
			$code = tsl_article_code_from_name( $info['ID'] );
		}
		if ( ! $code ) {
			return new WP_Error( 'tsl_docx', 'Brak ID artykułu: nazwa pliku powinna zaczynać się od ID, np. „ZF-07-1_Tytuł.docx”.' );
		}

		// Kategoria i format.
		$segments = array_map( 'trim', preg_split( '/>/u', $path ) );
		array_shift( $segments ); // „TSL Manager”.
		$pillar = ! empty( $info['Filar'] ) ? $info['Filar'] : ( $segments[0] ?? '' );
		if ( $segments && $this->norm( $segments[0] ) === $this->norm( $pillar ) ) {
			array_shift( $segments );
		}
		$category = $this->category( $pillar, $segments );
		$format   = $info['Format'] ?? '';

		// Tytuł i lead.
		$title = '';
		foreach ( $rest as $k => $b ) {
			if ( 'title' === $b['t'] ) {
				$title = $this->plain( $b['text'] );
				unset( $rest[ $k ] );
				break;
			}
		}
		$rest = array_values( $rest );
		if ( '' === $title && $rest && in_array( $rest[0]['t'], array( 'h', 'p' ), true ) ) {
			$title = $this->plain( $rest[0]['text'] );
			array_shift( $rest );
		}
		if ( '' === $title ) {
			return new WP_Error( 'tsl_docx', 'Nie znaleziono tytułu artykułu.' );
		}
		$lead = '';
		if ( $rest && 'p' === $rest[0]['t'] && mb_strlen( $rest[0]['text'] ) > 60 ) {
			$lead = $this->plain( $rest[0]['text'] );
			array_shift( $rest );
		}

		// Treść / pakiet dystrybucyjny / stopka z datą.
		$body    = array();
		$package = array();
		$date    = ! empty( $info['Data publikacji'] ) ? $this->date( $info['Data publikacji'] ) : null;
		$target  = 'body';
		foreach ( $rest as $b ) {
			$text = isset( $b['text'] ) ? $this->plain( $b['text'] ) : '';
			if ( in_array( $b['t'], array( 'h', 'p' ), true ) && preg_match( '/^(Pakiet dystrybucyjny|Materiały towarzyszące)/u', $text ) ) {
				$target = 'package';
				continue;
			}
			if ( 'p' === $b['t'] && 0 === strpos( $text, 'TSL Manager •' ) ) {
				$date = $this->date( $text ) ? $this->date( $text ) : $date;
				continue;
			}
			if ( 'body' === $target ) {
				$body[] = $b;
			} else {
				$package[] = $b;
			}
		}

		$meta = array( 'tsl_format_label' => $format );
		if ( ! empty( $info['Decyzja / wartość dla odbiorcy'] ) ) {
			$meta['tsl_decision'] = $info['Decyzja / wartość dla odbiorcy'];
		}
		$slug = $this->package( $package, $meta );
		list( $content, $sources ) = $this->body( $body );

		if ( ! $category ) {
			$this->warnings[] = 'Nie rozpoznano kategorii ze ścieżki „' . $path . '” — wybierz ją ręcznie.';
		}
		if ( ! $format ) {
			$this->warnings[] = 'Brak pola „Format” — ustawiono „Poradnik operacyjny”.';
		}

		return array(
			'code'     => $code,
			'title'    => $title,
			'slug'     => $slug ? $slug : $this->slug( $title ),
			'category' => $category,
			'format'   => $this->format( $format, $category ),
			'lead'     => $lead ? $lead : ( $info['Decyzja / wartość dla odbiorcy'] ?? '' ),
			'date'     => $date,
			'content'  => $content,
			'sources'  => $sources,
			'meta'     => $meta,
		);
	}

	// ---------- Treść ----------

	/**
	 * @param array $blocks Bloki treści.
	 * @return array{0:string,1:string[]} HTML bloków, źródła.
	 */
	private function body( array $blocks ) {
		$out      = array();
		$sources  = array();
		$levels   = array();
		$has_list = false;
		foreach ( $blocks as $b ) {
			if ( 'h' === $b['t'] && ! in_array( mb_strtolower( $this->plain( $b['text'] ) ), array( 'źródła', 'wnioski', 'faq', 'cta', 'powiązania' ), true ) ) {
				$levels[] = $b['level'];
			}
			$has_list = $has_list || 'li' === $b['t'];
		}
		$marked  = (bool) $levels;
		$base    = $levels ? min( $levels ) : 1;
		$section = '';
		$paras   = array();
		$list    = null; // [ordered, items].

		$flush = function () use ( &$out, &$paras, &$list, &$section, $has_list ) {
			if ( $paras ) {
				$out   = array_merge( $out, $this->paragraphs( $paras, $section, ! $has_list ) );
				$paras = array();
			}
			if ( $list ) {
				$out[] = $this->b_list( $list[1], $list[0] );
				$list  = null;
			}
		};

		foreach ( $blocks as $b ) {
			if ( 'table' === $b['t'] ) {
				$flush();
				$html = $this->table( $b['rows'] );
				if ( $html ) {
					$out[] = $html;
				}
				continue;
			}
			$text  = $b['text'];
			$plain = $this->plain( $text );
			$name  = mb_strtolower( $plain );
			$known = in_array( $name, array( 'źródła', 'wnioski', 'faq', 'cta', 'powiązania' ), true );

			$free       = 'p' === $b['t'] && ! in_array( $section, array( 'źródła', 'powiązania' ), true ) && $this->looks_like_heading( $plain );
			$is_heading = 'h' === $b['t'] || ( 'p' === $b['t'] && ( $known || ( $free && ( ! $marked || $b['bold'] ) ) ) );
			if ( $is_heading ) {
				$flush();
				$level = 'h' === $b['t'] ? max( 2, min( 4, 2 + $b['level'] - $base ) ) : ( $marked ? 3 : 2 );
				if ( in_array( $name, array( 'źródła', 'cta', 'powiązania' ), true ) ) {
					$section = $name;
					if ( 'powiązania' === $name ) {
						$out[] = $this->b_heading( 'Czytaj także', 2 );
					}
					continue;
				}
				if ( 'faq' === $name || 2 === $level ) {
					$section = $name;
				}
				$out[] = $this->b_heading( $this->inline( $plain ), 'faq' === $name ? 2 : $level );
				continue;
			}

			if ( 'źródła' === $section ) {
				if ( 0 === strpos( $plain, 'Uwaga redakcyjna' ) ) {
					continue;
				}
				if ( 0 === strpos( $plain, 'Uwaga' ) ) {
					$flush();
					$out[] = $this->b_paragraph( $this->inline( $text ), 'tsl-note' );
					continue;
				}
				$sources[] = preg_replace( '/^\d+\.\s*/', '', $text );
				continue;
			}
			if ( 'cta' === $section ) {
				continue;
			}
			if ( 'powiązania' === $section ) {
				$out[] = '{{related:' . $plain . '}}';
				continue;
			}
			if ( 0 === strpos( $plain, 'Uwaga redakcyjna' ) ) {
				continue;
			}
			if ( 'faq' === $section && 'li' !== $b['t'] && '?' === mb_substr( $plain, -1 ) && mb_strlen( $plain ) < 160 ) {
				$flush();
				$out[] = $this->b_heading( $this->inline( $text ), 3 );
				continue;
			}

			if ( 'li' === $b['t'] ) {
				if ( $paras ) {
					$out   = array_merge( $out, $this->paragraphs( $paras, $section, ! $has_list ) );
					$paras = array();
				}
				if ( $list && $list[0] !== $b['ordered'] ) {
					$out[] = $this->b_list( $list[1], $list[0] );
					$list  = null;
				}
				if ( ! $list ) {
					$list = array( $b['ordered'], array() );
				}
				$list[1][] = $this->inline( preg_replace( '/^\d+\.\s+/', '', $text ) );
				continue;
			}
			if ( $list ) {
				$out[] = $this->b_list( $list[1], $list[0] );
				$list  = null;
			}
			$paras[] = $text;
		}
		$flush();

		return array( implode( "\n\n", $out ), $sources );
	}

	/**
	 * Akapity; gdy dokument nie ma prawdziwych list, rozpoznaje listy, które
	 * w eksporcie straciły punktory.
	 *
	 * @param string[] $paras     Akapity.
	 * @param string   $section   Bieżąca sekcja.
	 * @param bool     $heuristic Czy zgadywać listy.
	 * @return string[]
	 */
	private function paragraphs( array $paras, $section, $heuristic ) {
		$out = array();
		$n   = count( $paras );
		$i   = 0;
		while ( $i < $n ) {
			$p     = $paras[ $i ];
			$plain = $this->plain( $p );
			if ( $heuristic ) {
				// „1. …”, „2. …”.
				if ( preg_match( '/^\d+\.\s/', $plain ) && $i + 1 < $n && preg_match( '/^\d+\.\s/', $this->plain( $paras[ $i + 1 ] ) ) ) {
					$items = array();
					while ( $i < $n && preg_match( '/^\d+\.\s/', $this->plain( $paras[ $i ] ) ) ) {
						$items[] = $this->inline( preg_replace( '/^\d+\.\s*/', '', $paras[ $i ] ) );
						++$i;
					}
					$out[] = $this->b_list( $items, true );
					continue;
				}
				$intro = $i > 0 && ':' === mb_substr( $this->plain( $paras[ $i - 1 ] ), -1 );
				$j     = $i;
				while ( $j < $n && ( $this->item_like( $paras[ $j ] ) || ( $intro && $this->labelled( $paras[ $j ] ) && $j - $i < 12 ) ) ) {
					++$j;
				}
				if ( $j - $i >= 2 || ( 1 === $j - $i && $intro && $this->item_like( $p ) ) ) {
					$out[] = $this->b_list( array_map( array( $this, 'inline' ), array_slice( $paras, $i, $j - $i ) ) );
					$i     = $j;
					continue;
				}
				$j = $i;
				while ( $j < $n && '?' === mb_substr( $this->plain( $paras[ $j ] ), -1 ) && mb_strlen( $paras[ $j ] ) < 200 ) {
					++$j;
				}
				if ( $j - $i >= 3 ) {
					$out[] = $this->b_list( array_map( array( $this, 'inline' ), array_slice( $paras, $i, $j - $i ) ) );
					$i     = $j;
					continue;
				}
				if ( 'wnioski' === $section ) {
					$j = $i;
					while ( $j < $n && mb_strlen( $paras[ $j ] ) < 220 && ':' !== mb_substr( $this->plain( $paras[ $j ] ), -1 ) ) {
						++$j;
					}
					if ( $j - $i >= 2 ) {
						$out[] = $this->b_list( array_map( array( $this, 'inline' ), array_slice( $paras, $i, $j - $i ) ) );
						$i     = $j;
						continue;
					}
				}
			}
			$out[] = $this->b_paragraph( $this->inline( $p ), 0 === strpos( $plain, 'Uwaga' ) ? 'tsl-note' : '' );
			++$i;
		}
		return $out;
	}

	/**
	 * Tabela: jedna kolumna = ramka („W skrócie”, „Decyzja po lekturze”), więcej = tabela.
	 *
	 * @param array $rows Wiersze → komórki → akapity.
	 * @return string
	 */
	private function table( array $rows ) {
		if ( ! $rows ) {
			return '';
		}
		if ( 1 === count( $rows[0] ) ) {
			$first = $rows[0][0];
			if ( count( $rows ) > 1 ) {
				$title = $this->plain( implode( ' ', $first ) );
				$texts = array();
				foreach ( array_slice( $rows, 1 ) as $row ) {
					$texts = array_merge( $texts, $row[0] ?? array() );
				}
			} elseif ( count( $first ) > 1 && mb_strlen( $this->plain( $first[0] ) ) <= 45 ) {
				$title = $this->plain( $first[0] );
				$texts = array_slice( $first, 1 );
			} else {
				list( $title, $text ) = $this->split_box( implode( ' ', $first ) );
				$texts                = array( $text );
			}
			if ( 0 === stripos( (string) $title, 'CTA' ) ) {
				return '';
			}
			return $this->b_box( $title, $texts );
		}
		$head = '';
		foreach ( $rows[0] as $cell ) {
			$head .= '<th>' . $this->cell_html( $cell ) . '</th>';
		}
		$body = '';
		foreach ( array_slice( $rows, 1 ) as $row ) {
			$body .= '<tr>';
			foreach ( $row as $cell ) {
				$body .= '<td>' . $this->cell_html( $cell ) . '</td>';
			}
			$body .= '</tr>';
		}
		return "<!-- wp:table {\"hasFixedLayout\":false} -->\n<figure class=\"wp-block-table\"><table><thead><tr>" . $head . '</tr></thead><tbody>' . $body . "</tbody></table></figure>\n<!-- /wp:table -->";
	}

	/**
	 * @param string[] $paras Akapity komórki.
	 */
	private function cell_html( array $paras ) {
		return implode( '<br>', array_map( array( $this, 'inline' ), $paras ) );
	}

	/**
	 * „W skrócieDobrego TMS…” → [„W skrócie”, „Dobrego TMS…”].
	 *
	 * @param string $text Tekst komórki.
	 * @return array{0:string,1:string}
	 */
	private function split_box( $text ) {
		if ( preg_match( '/^\*\*(.{2,60}?)\*\*\s*(.*)$/su', $text, $m ) ) {
			return array( trim( $m[1] ), $m[2] );
		}
		if ( 0 === strpos( $text, 'CTA' ) && ' ' !== substr( $text, 3, 1 ) ) {
			return array( 'CTA', substr( $text, 3 ) );
		}
		$head = mb_substr( $text, 0, 46 );
		if ( preg_match( '/[' . self::LOWER . ')](?=[' . self::UPPER . '0-9])/u', $head, $m, PREG_OFFSET_CAPTURE ) ) {
			$at = $m[0][1] + strlen( $m[0][0] );
			return array( substr( $text, 0, $at ), substr( $text, $at ) );
		}
		return array( '', $text );
	}

	// ---------- Pakiet dystrybucyjny ----------

	/**
	 * Tytuł SEO, opis, fraza, newsletter, CTA. Zwraca proponowany slug (lub '').
	 *
	 * @param array $blocks Bloki pakietu.
	 * @param array $meta   Metadane (uzupełniane).
	 * @return string
	 */
	private function package( array $blocks, array &$meta ) {
		$pairs   = array();
		$section = '';
		$nl_text = array();
		foreach ( $blocks as $b ) {
			if ( 'table' === $b['t'] ) {
				if ( 1 === count( $b['rows'][0] ) ) {
					$first = $b['rows'][0][0];
					if ( count( $b['rows'] ) > 1 && 0 === stripos( $this->plain( implode( ' ', $first ) ), 'CTA' ) ) {
						$meta['tsl_biz_cta'] = implode( ' ', $b['rows'][1][0] ?? array() );
					} elseif ( count( $first ) > 1 && 0 === stripos( $this->plain( $first[0] ), 'CTA' ) ) {
						$meta['tsl_biz_cta'] = implode( ' ', array_slice( $first, 1 ) );
					} else {
						list( $title, $text ) = $this->split_box( implode( ' ', $first ) );
						if ( 'CTA' === strtoupper( (string) $title ) ) {
							$meta['tsl_biz_cta'] = $text;
						}
					}
					continue;
				}
				foreach ( array_slice( $b['rows'], 1 ) as $row ) {
					if ( count( $row ) >= 2 ) {
						$pairs[ $this->cell( $row[0] ) ] = $this->cell( $row[1] );
					}
				}
				continue;
			}
			$plain = $this->plain( $b['text'] );
			if ( 'h' === $b['t'] || ( mb_strlen( $plain ) < 60 && ! preg_match( '/[:.]/u', $plain ) ) ) {
				$section = mb_strtolower( $plain );
				continue;
			}
			if ( preg_match( '/^(Tytuł SEO|Meta description|Główna fraza|Temat|Preheader|Proponowany adres URL):\s*(.+)$/u', $plain, $m ) ) {
				$pairs[ $m[1] ] = $m[2];
				continue;
			}
			if ( 0 === strpos( $plain, 'Wariant newsletterowy' ) ) {
				$section = 'newsletter';
				continue;
			}
			if ( 0 === strpos( $plain, 'Wariant' ) ) {
				$section = 'social';
				continue;
			}
			if ( false !== strpos( $section, 'newsletter' ) ) {
				$nl_text[] = $plain;
			}
		}

		$seo = preg_replace( '/\s*[|–-]\s*TSL Manager\s*$/u', '', $pairs['Tytuł SEO'] ?? '' );
		if ( $seo ) {
			$meta['tsl_seo_title'] = $seo;
		}
		$map = array(
			'Meta description' => 'tsl_seo_desc',
			'Główna fraza'     => 'tsl_keyphrase',
			'Temat'            => 'tsl_nl_subject',
			'Preheader'        => 'tsl_nl_preheader',
		);
		foreach ( $map as $label => $key ) {
			if ( ! empty( $pairs[ $label ] ) ) {
				$meta[ $key ] = $this->plain( $pairs[ $label ] );
			}
		}
		if ( $nl_text ) {
			$meta['tsl_nl_teaser'] = implode( "\n\n", $nl_text );
		}
		$url = trim( $this->plain( $pairs['Proponowany adres URL'] ?? '' ), '/ ' );
		return $url ? sanitize_title( basename( $url ) ) : '';
	}

	// ---------- Pomocnicze ----------

	/**
	 * Ścieżka kategorii (slug/slug/…) na podstawie filaru i dalszych segmentów.
	 *
	 * @param string   $pillar_name Filar.
	 * @param string[] $segments    Podkategorie.
	 * @return string Pusty, gdy nie rozpoznano.
	 */
	private function category( $pillar_name, array $segments ) {
		$structure = require TSL_CORE_DIR . 'includes/data/structure.php';
		$pillar    = null;
		foreach ( $structure as $node ) {
			if ( $this->norm( $node['name'] ) === $this->norm( $pillar_name ) ) {
				$pillar = $node;
			}
		}
		if ( ! $pillar ) {
			return '';
		}
		$names = array();
		foreach ( $segments as $seg ) {
			$alias = self::ALIASES[ $this->norm( $seg ) ] ?? '';
			$names = array_merge( $names, $alias ? explode( '/', $alias ) : array( $seg ) );
		}
		$path  = array( $pillar['slug'] );
		$nodes = $pillar['children'] ?? array();
		foreach ( $names as $name ) {
			$found = null;
			foreach ( $nodes as $node ) {
				if ( $this->norm( $node['name'] ) === $this->norm( $name ) ) {
					$found = $node;
				}
			}
			if ( ! $found ) {
				$this->warnings[] = 'Nie znaleziono podkategorii „' . $name . '” — artykuł trafił do filaru ' . $pillar['name'] . '.';
				break;
			}
			$path[] = $found['slug'];
			$nodes  = $found['children'] ?? array();
		}
		return implode( '/', $path );
	}

	/**
	 * @param string $label    Format z metryczki.
	 * @param string $category Ścieżka kategorii.
	 */
	private function format( $label, $category ) {
		$low = mb_strtolower( $label );
		$map = array(
			'ile to'               => 'ile-to-kosztuje',
			'szkoda pod lupą'      => 'szkoda-pod-lupa',
			'decyzja pod lupą'     => 'decyzja-pod-lupa',
			'matryca'              => 'decyzja-pod-lupa',
			'pierwsze 24'          => 'pierwsze-24-godziny',
			'przed kontrolą'       => 'przed-kontrola',
			'rozmowa z praktykiem' => 'rozmowa-z-praktykiem',
		);
		foreach ( $map as $needle => $slug ) {
			if ( false !== strpos( $low, $needle ) ) {
				return $slug;
			}
		}
		if ( false !== strpos( $low, 'prawn' ) || ( 0 === strpos( $category, 'prawo' ) && 0 === strpos( $low, 'analiza' ) ) ) {
			return 'analiza-prawna';
		}
		return 0 === strpos( $low, 'analiza' ) ? 'analiza-menedzerska' : 'poradnik-operacyjny';
	}

	/**
	 * @param string $text Np. „stan: 1 października 2026”.
	 * @return string|null Y-m-d 08:00:00.
	 */
	private function date( $text ) {
		if ( ! preg_match( '/(\d{1,2})\s+(' . implode( '|', array_keys( self::MONTHS ) ) . ')\s+(\d{4})/u', $text, $m ) ) {
			return null;
		}
		return sprintf( '%s-%02d-%02d 08:00:00', $m[3], self::MONTHS[ $m[2] ], (int) $m[1] );
	}

	/**
	 * Adres z tytułu: pierwsze 8 słów bez polskich znaków.
	 *
	 * @param string $title Tytuł.
	 */
	private function slug( $title ) {
		$words = explode( '-', sanitize_title( remove_accents( $title ) ) );
		return implode( '-', array_slice( array_filter( $words ), 0, 8 ) );
	}

	private function norm( $text ) {
		return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', (string) $text ) ) );
	}

	/** @param string[] $paras Akapity komórki. */
	private function cell( array $paras ) {
		return $this->plain( implode( ' ', $paras ) );
	}

	/** Tekst bez znaczników markdown. */
	private function plain( $text ) {
		$text = preg_replace( '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/u', '$1', (string) $text );
		return trim( str_replace( '**', '', $text ) );
	}

	/** Markdown w linii → HTML. */
	private function inline( $text ) {
		$out = '';
		$pos = 0;
		preg_match_all( '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/u', $text, $all, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );
		foreach ( $all as $m ) {
			$out .= esc_html( substr( $text, $pos, $m[0][1] - $pos ) );
			$out .= '<a href="' . esc_url( $m[2][0] ) . '">' . esc_html( $m[1][0] ) . '</a>';
			$pos  = $m[0][1] + strlen( $m[0][0] );
		}
		$out .= esc_html( substr( $text, $pos ) );
		$out  = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $out );
		// Znaczniki linków wewnętrznych zostają do rozwiązania po imporcie.
		return preg_replace( '/\[LINK WEWNĘTRZNY:\s*(.+?)\]/u', '{{find:$1}}', $out );
	}

	private function looks_like_heading( $text ) {
		$len = mb_strlen( $text );
		if ( $len > 95 || preg_match( '/[.,;:!?…”"]$/u', $text ) ) {
			return false;
		}
		if ( ! preg_match( '/^[' . self::UPPER . '0-9]/u', $text ) || 0 === strpos( $text, 'Uwaga' ) ) {
			return false;
		}
		return true;
	}

	private function labelled( $text ) {
		$t = $this->plain( $text );
		return preg_match( '/^([^.:,]{3,60})[.:]\s+\S/u', $t, $m ) && count( preg_split( '/\s+/', trim( $m[1] ) ) ) <= 6 && preg_match( '/^[' . self::UPPER . ']/u', $m[1] );
	}

	private function item_like( $text ) {
		$t = $this->plain( $text );
		return (bool) preg_match( '/^[' . self::LOWER . ']/u', $t ) || ';' === mb_substr( $t, -1 );
	}

	// ---------- Bloki Gutenberga ----------

	private function b_paragraph( $html, $cls = '' ) {
		if ( $cls ) {
			return "<!-- wp:paragraph {\"className\":\"$cls\"} -->\n<p class=\"$cls\">$html</p>\n<!-- /wp:paragraph -->";
		}
		return "<!-- wp:paragraph -->\n<p>$html</p>\n<!-- /wp:paragraph -->";
	}

	private function b_heading( $html, $level = 2 ) {
		$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
		return "<!-- wp:heading$attrs -->\n<h$level class=\"wp-block-heading\">$html</h$level>\n<!-- /wp:heading -->";
	}

	private function b_list( array $items, $ordered = false ) {
		$tag   = $ordered ? 'ol' : 'ul';
		$attrs = $ordered ? ' {"ordered":true}' : '';
		$lis   = '';
		foreach ( $items as $item ) {
			$lis .= "<!-- wp:list-item -->\n<li>$item</li>\n<!-- /wp:list-item -->";
		}
		return "<!-- wp:list$attrs -->\n<$tag class=\"wp-block-list\">$lis</$tag>\n<!-- /wp:list -->";
	}

	private function b_box( $title, array $texts ) {
		$low  = mb_strtolower( (string) $title );
		$kind = false !== strpos( $low, 'skrócie' ) ? 'summary' : ( false !== strpos( $low, 'decyzja' ) ? 'decision' : ( false !== strpos( $low, 'wzór' ) || false !== strpos( $low, 'progi' ) ? 'formula' : 'info' ) );
		$cls  = 'tsl-box tsl-box-' . $kind;
		if ( $title && mb_strtoupper( $title ) === $title ) {
			$title = mb_strtoupper( mb_substr( $title, 0, 1 ) ) . mb_strtolower( mb_substr( $title, 1 ) );
		}
		$inner = '';
		if ( $title ) {
			$inner .= "\n\n" . $this->b_paragraph( esc_html( $title ), 'tsl-box-title' );
		}
		foreach ( $texts as $text ) {
			$inner .= "\n\n" . $this->b_paragraph( $this->inline( $text ) );
		}
		return "<!-- wp:group {\"className\":\"$cls\"} -->\n<div class=\"wp-block-group $cls\">$inner\n\n</div>\n<!-- /wp:group -->";
	}
}

/**
 * Artykuł najlepiej pasujący do tytułu (≥ 75% słów zapytania w tytule).
 *
 * @param string $query   Tytuł lub jego fragment.
 * @param int    $exclude Pomijany wpis.
 * @return WP_Post|null
 */
function tsl_article_find_by_title( $query, $exclude = 0 ) {
	$stems = function ( $text ) {
		preg_match_all( '/[\p{L}0-9]{3,}/u', mb_strtolower( $text ), $m );
		return array_unique( array_map( fn( $w ) => mb_substr( $w, 0, 5 ), $m[0] ) );
	};
	$q = $stems( $query );
	if ( ! $q ) {
		return null;
	}
	$best  = null;
	$score = 0;
	foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'future' ), 'posts_per_page' => 500, 'exclude' => array( $exclude ) ) ) as $post ) {
		$s = count( array_intersect( $q, $stems( $post->post_title ) ) ) / count( $q );
		if ( $s > $score ) {
			$best  = $post;
			$score = $s;
		}
	}
	return $score >= 0.75 ? $best : null;
}

/**
 * Po imporcie: „{{find:…}}” (link wewnętrzny) i „{{related:…}}” (Czytaj także).
 *
 * @param int[] $ids Zaimportowane wpisy.
 */
function tsl_docx_resolve_links( array $ids ) {
	foreach ( $ids as $id ) {
		$content = get_post_field( 'post_content', $id );
		$content = preg_replace_callback(
			'/\{\{find:(.+?)\}\}/u',
			function ( $m ) use ( $id ) {
				$target = tsl_article_find_by_title( html_entity_decode( $m[1] ), $id );
				return $target ? '<a href="' . esc_url( get_permalink( $target ) ) . '">' . $m[1] . '</a>' : $m[1];
			},
			$content
		);
		$related = array();
		preg_match_all( '/\{\{related:(.+?)\}\}/u', $content, $all );
		foreach ( $all[1] as $title ) {
			$target = tsl_article_find_by_title( $title, $id );
			if ( $target ) {
				$related[] = "<!-- wp:list-item -->\n<li><a href=\"" . esc_url( get_permalink( $target ) ) . '">' . esc_html( $target->post_title ) . "</a></li>\n<!-- /wp:list-item -->";
			}
		}
		$content = preg_replace( '/(\n\n)?\{\{related:.+?\}\}/u', '', $content );
		$heading = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Czytaj także</h2>\n<!-- /wp:heading -->";
		if ( $related ) {
			$content = str_replace( $heading, $heading . "\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\">" . implode( '', $related ) . "</ul>\n<!-- /wp:list -->", $content );
		} else {
			$content = str_replace( "\n\n" . $heading, '', $content );
		}
		kses_remove_filters();
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ) );
		kses_init_filters();
	}
}

/**
 * Maksymalny rozmiar pliku .docx (bajty).
 */
const TSL_DOCX_MAX_BYTES = 10 * MB_IN_BYTES;

/**
 * Kontrola przesłanego pliku: błąd uploadu, rozszerzenie, rozmiar, typ MIME.
 *
 * @param string $name  Nazwa pliku.
 * @param string $tmp   Plik tymczasowy.
 * @param int    $error Kod błędu PHP.
 * @param int    $size  Rozmiar.
 * @return string Komunikat błędu albo '' gdy plik jest w porządku.
 */
function tsl_docx_check_upload( $name, $tmp, $error, $size ) {
	$messages = array(
		UPLOAD_ERR_INI_SIZE   => 'Plik przekracza limit rozmiaru ustawiony na serwerze.',
		UPLOAD_ERR_FORM_SIZE  => 'Plik przekracza dopuszczalny rozmiar.',
		UPLOAD_ERR_PARTIAL    => 'Plik został przesłany tylko częściowo — spróbuj ponownie.',
		UPLOAD_ERR_NO_FILE    => 'Nie wybrano pliku.',
		UPLOAD_ERR_NO_TMP_DIR => 'Błąd serwera: brak katalogu tymczasowego.',
		UPLOAD_ERR_CANT_WRITE => 'Błąd serwera: nie udało się zapisać pliku.',
		UPLOAD_ERR_EXTENSION  => 'Rozszerzenie serwera zablokowało przesyłanie pliku.',
	);
	if ( UPLOAD_ERR_OK !== $error ) {
		return $messages[ $error ] ?? 'Nie udało się przesłać pliku.';
	}
	if ( ! $tmp || ! is_uploaded_file( $tmp ) ) {
		return 'Plik nie dotarł na serwer.';
	}
	if ( ! preg_match( '/\.docx$/i', $name ) ) {
		return 'To nie jest plik .docx. Na Dysku Google: Plik → Pobierz → Microsoft Word (.docx).';
	}
	$size = $size ? $size : (int) filesize( $tmp );
	if ( $size <= 0 ) {
		return 'Plik jest pusty.';
	}
	if ( $size > TSL_DOCX_MAX_BYTES ) {
		return 'Plik jest większy niż ' . size_format( TSL_DOCX_MAX_BYTES ) . '.';
	}
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime  = $finfo ? (string) finfo_file( $finfo, $tmp ) : '';
		if ( $finfo ) {
			finfo_close( $finfo );
		}
		$allowed = array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream' );
		if ( $mime && ! in_array( $mime, $allowed, true ) ) {
			return 'Plik nie jest dokumentem Word (wykryty typ: ' . $mime . ').';
		}
	}
	return '';
}

/**
 * Import jednego pliku.
 *
 * @param string $file      Ścieżka do pliku.
 * @param string $name      Oryginalna nazwa pliku.
 * @param string $status    draft | publish.
 * @param bool   $overwrite Nadpisz artykuł o tym samym ID.
 * @return array{file:string, code?:string, title?:string, id?:int, action:string, warnings:string[]}
 */
function tsl_docx_import_file( $file, $name, $status = 'draft', $overwrite = false ) {
	$result = array( 'file' => $name, 'action' => 'error', 'warnings' => array() );
	$blocks = TSL_Docx_Reader::read( $file );
	if ( is_wp_error( $blocks ) ) {
		$result['warnings'][] = $blocks->get_error_message();
		return $result;
	}
	$builder = new TSL_Docx_Article();
	$item    = $builder->build( $blocks, $name );
	if ( is_wp_error( $item ) ) {
		$result['warnings'][] = $item->get_error_message();
		return $result;
	}
	$result['code']     = $item['code'];
	$result['title']    = $item['title'];
	$result['warnings'] = $builder->warnings;

	$existing = tsl_article_by_code( $item['code'] );
	if ( $existing && ! $overwrite ) {
		$result['id']         = $existing;
		$result['action']     = 'skipped';
		$result['warnings'][] = 'Artykuł o tym ID już istnieje — nie został zmieniony.';
		return $result;
	}
	if ( $existing && ! current_user_can( 'edit_post', $existing ) ) {
		$result['id']         = $existing;
		$result['action']     = 'skipped';
		$result['warnings'][] = 'Brak uprawnień do edycji istniejącego artykułu o tym ID (np. cudzy wpis).';
		return $result;
	}
	if ( 'publish' === $status && ! current_user_can( 'publish_posts' ) ) {
		$status = 'draft';
	}
	$id = tsl_article_save( $item, $status, $existing );
	if ( ! $id ) {
		$result['warnings'][] = 'Nie udało się zapisać wpisu.';
		return $result;
	}
	$result['id']     = $id;
	$result['action'] = $existing ? 'updated' : 'created';
	return $result;
}

// ---------- Ekran w panelu: Wpisy → Import z .docx ----------

add_action(
	'admin_menu',
	function () {
		$hook = add_posts_page( 'Import artykułów z plików .docx', 'Import z .docx', 'tsl_import_articles', 'tsl-docx-import', 'tsl_docx_render_page' );
		add_action( 'load-' . $hook, 'tsl_docx_handle_upload' );
	}
);

/**
 * Obsługa wysłanych plików (przed wysłaniem nagłówków strony).
 */
function tsl_docx_handle_upload() {
	if ( empty( $_POST['tsl_docx_import'] ) ) {
		return;
	}
	if ( ! current_user_can( 'tsl_import_articles' ) ) {
		wp_die( 'Brak uprawnień do importu artykułów.', 403 );
	}
	check_admin_referer( 'tsl_docx_import' );
	// Publikacja tylko po świadomym wyborze (opcja + potwierdzenie) i z uprawnieniem.
	$publish   = isset( $_POST['tsl_status'] ) && 'publish' === $_POST['tsl_status'] && ! empty( $_POST['tsl_publish_confirm'] ) && current_user_can( 'publish_posts' );
	$status    = $publish ? 'publish' : 'draft';
	$overwrite = ! empty( $_POST['tsl_overwrite'] ) && ! empty( $_POST['tsl_overwrite_confirm'] );
	$results   = array();
	$ids       = array();
	$files     = $_FILES['tsl_docx'] ?? array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach ( (array) ( $files['name'] ?? array() ) as $k => $name ) {
		$name = sanitize_file_name( wp_unslash( $name ) );
		$tmp  = $files['tmp_name'][ $k ] ?? '';
		if ( '' === $name ) {
			continue;
		}
		$error = tsl_docx_check_upload( $name, $tmp, (int) ( $files['error'][ $k ] ?? UPLOAD_ERR_NO_FILE ), (int) ( $files['size'][ $k ] ?? 0 ) );
		if ( $error ) {
			$results[] = array( 'file' => $name, 'action' => 'error', 'warnings' => array( $error ) );
			continue;
		}
		$result    = tsl_docx_import_file( $tmp, $name, $status, $overwrite );
		$results[] = $result;
		if ( in_array( $result['action'], array( 'created', 'updated' ), true ) ) {
			$ids[] = $result['id'];
		}
	}
	if ( $ids ) {
		tsl_docx_resolve_links( $ids );
		tsl_articles_attach_existing_images();
	}
	set_transient( 'tsl_docx_results_' . get_current_user_id(), $results, HOUR_IN_SECONDS );
	wp_safe_redirect( add_query_arg( array( 'page' => 'tsl-docx-import', 'gotowe' => 1 ), admin_url( 'edit.php' ) ) );
	exit;
}

/**
 * Strona Wpisy → Import z .docx.
 */
function tsl_docx_render_page() {
	$results = ! empty( $_GET['gotowe'] ) ? get_transient( 'tsl_docx_results_' . get_current_user_id() ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$labels  = array(
		'created' => 'Utworzono',
		'updated' => 'Nadpisano',
		'skipped' => 'Pominięto',
		'error'   => 'Błąd',
	);
	?>
	<div class="wrap">
		<h1>Import artykułów z plików .docx</h1>

		<?php if ( $results ) : ?>
			<?php $sum = array_count_values( array_column( $results, 'action' ) ); ?>
			<div class="notice notice-info"><p>
				<strong>Raport importu:</strong>
				utworzono <?php echo (int) ( $sum['created'] ?? 0 ); ?> ·
				zaktualizowano <?php echo (int) ( $sum['updated'] ?? 0 ); ?> ·
				pominięto <?php echo (int) ( $sum['skipped'] ?? 0 ); ?> ·
				błędy <?php echo (int) ( $sum['error'] ?? 0 ); ?>
			</p></div>
			<table class="widefat striped" style="margin:16px 0 28px;max-width:1100px">
				<thead><tr><th>Plik</th><th>ID</th><th>Artykuł</th><th>Wynik</th><th>Uwagi</th></tr></thead>
				<tbody>
				<?php foreach ( $results as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r['file'] ); ?></td>
						<td><?php echo esc_html( $r['code'] ?? '' ); ?></td>
						<td>
							<?php if ( ! empty( $r['id'] ) ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $r['id'] ) ); ?>"><?php echo esc_html( $r['title'] ?? get_the_title( $r['id'] ) ); ?></a>
								· <a href="<?php echo esc_url( get_preview_post_link( $r['id'] ) ); ?>" target="_blank">podgląd</a>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $labels[ $r['action'] ] ?? $r['action'] ); ?></td>
						<td><?php echo esc_html( implode( ' ', $r['warnings'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<p style="max-width:760px">Wybierz jeden lub kilka plików .docx z artykułami redakcji (na Dysku Google: <strong>Plik → Pobierz → Microsoft Word (.docx)</strong>, albo pobierz cały folder). Nazwa pliku musi zaczynać się od ID artykułu, np. <code>ZF-07-1_Tytuł.docx</code>.</p>
		<p style="max-width:760px">Artykuł dostanie kategorię i format z metryczki, lead, ramki „W skrócie” i „Decyzja po lekturze”, tabele, FAQ, źródła oraz tytuł i opis SEO, zajawkę do newslettera i CTA z pakietu dystrybucyjnego. Ilustracja z tym samym ID wgrana do Mediów sama zostanie obrazkiem artykułu.</p>

		<form method="post" enctype="multipart/form-data" style="margin-top:20px" onsubmit="return !(this.tsl_overwrite.checked &amp;&amp; this.tsl_overwrite_confirm.checked) || confirm('Nadpisać istniejące artykuły o tym samym ID? Poprawki zrobione w panelu zostaną utracone.');">
			<?php wp_nonce_field( 'tsl_docx_import' ); ?>
			<input type="hidden" name="tsl_docx_import" value="1">
			<p><input type="file" id="tsl-docx" name="tsl_docx[]" accept=".docx" multiple required></p>
			<fieldset style="margin:16px 0">
				<label><input type="radio" name="tsl_status" value="draft" checked> Zapisz jako <strong>szkice</strong> (zalecane — opublikujesz po przeczytaniu)</label><br>
				<?php if ( current_user_can( 'publish_posts' ) ) : ?>
					<label><input type="radio" name="tsl_status" value="publish"> Opublikuj od razu</label>
					<label style="margin-left:24px"><input type="checkbox" name="tsl_publish_confirm" value="1"> potwierdzam publikację bez czytania szkiców</label>
				<?php endif; ?>
			</fieldset>
			<div style="margin:16px 0;padding:10px 14px;border-left:4px solid #d63638;background:#fff">
				<p style="margin:0 0 6px"><label><input type="checkbox" name="tsl_overwrite" value="1"> <strong>Nadpisz</strong> istniejące artykuły o tym samym ID</label></p>
				<p style="margin:0"><label><input type="checkbox" name="tsl_overwrite_confirm" value="1"> rozumiem, że poprawki zrobione w panelu w tych artykułach zostaną utracone</label></p>
				<p class="description" style="margin:6px 0 0">Nadpisać można tylko artykuły, które masz prawo edytować. Bez zaznaczenia obu pól istniejące artykuły zostają pominięte.</p>
			</div>
			<p><button class="button button-primary button-hero">Wgraj artykuły</button></p>
		</form>
		<p class="description">Maksymalny rozmiar jednego pliku: <?php echo esc_html( size_format( min( TSL_DOCX_MAX_BYTES, wp_max_upload_size() ) ) ); ?>.</p>
	</div>
	<?php
}
