<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalitza coordenades per Leaflet.
 * ACF pot mostrar decimals amb coma segons la configuració regional.
 */
function coral_normalize_coordinate( $value ) {
	$value = trim( (string) $value );

	// Normalitza signes menys estranys a "-"
	$value = str_replace(
		[ '−', '–', '—', '‒' ],
		'-',
		$value
	);

	// Elimina espais normals i salts
	$value = str_replace(
		[ ' ', "\t", "\n", "\r" ],
		'',
		$value
	);

	// Converteix coma decimal a punt
	$value = str_replace( ',', '.', $value );

	// Deixa només números, punt i signe negatiu
	$value = preg_replace( '/[^0-9\.\-]/', '', $value );

	return $value;
}

/**
 * Posa ordre a fragments de text importats amb puntuació i espais irregulars.
 */
function coral_normalize_concert_popup_text( $value ) {
	$value = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
	$value = preg_replace( '/\s+/u', ' ', $value );
	$value = preg_replace_callback(
		'/\s*\.\s*(Al|A la|A les|A l[’\']?)\s+/u',
		static function ( $matches ) {
			return ' ' . mb_strtolower( trim( $matches[1] ), 'UTF-8' ) . ' ';
		},
		$value
	);
	$value = preg_replace( '/\s+([,;:!?])/u', '$1', $value );
	$value = preg_replace( '/\s*\.\s*/u', '. ', $value );
	$value = preg_replace( '/\s+/u', ' ', $value );

	return trim( $value );
}

/**
 * Equivalent senzill de mb_ucfirst per mantenir accents.
 */
function coral_mb_ucfirst( $value ) {
	$value = (string) $value;

	if ( $value === '' ) {
		return '';
	}

	$first = mb_substr( $value, 0, 1, 'UTF-8' );
	$rest  = mb_substr( $value, 1, null, 'UTF-8' );

	return mb_strtoupper( $first, 'UTF-8' ) . $rest;
}

/**
 * Clau normalitzada per comparar fragments sense accents ni signes.
 */
function coral_concert_popup_text_key( $value ) {
	$value = remove_accents( coral_normalize_concert_popup_text( $value ) );
	$value = mb_strtolower( $value, 'UTF-8' );
	$value = preg_replace( '/[^a-z0-9]+/u', ' ', $value );

	return trim( $value );
}

/**
 * Elimina connectors inicials quan el títol en realitat és només un lloc.
 */
function coral_strip_leading_concert_popup_connectors( $title ) {
	$title = ltrim( coral_normalize_concert_popup_text( $title ), ". \t\n\r\0\x0B" );
	$title = preg_replace( '/^(?:al|a la|a les)\s+/iu', '', $title, 1 );
	$title = preg_replace( '/^a l[’\']?/iu', '', $title, 1 );
	$title = preg_replace( '/^l[’\']+/iu', '', $title, 1 );

	return coral_mb_ucfirst( trim( $title ) );
}

/**
 * Completa fragments com "Castellar del" amb el context del lloc actual.
 */
function coral_expand_concert_popup_fragment( $title, $label ) {
	$title_key = coral_concert_popup_text_key( $title );
	$label_key = coral_concert_popup_text_key( $label );

	if ( $title_key === '' || $label_key === '' ) {
		return $title;
	}

	if ( str_starts_with( $label_key, $title_key ) || str_ends_with( $label_key, $title_key ) ) {
		return coral_normalize_concert_popup_text( $label );
	}

	return $title;
}

/**
 * Afegeix només la part del context que encara no surt al final del títol.
 */
function coral_append_concert_popup_completion( $title, $completion ) {
	$title_words      = preg_split( '/\s+/u', coral_normalize_concert_popup_text( $title ) );
	$completion_words = preg_split( '/\s+/u', coral_normalize_concert_popup_text( $completion ) );
	$max_overlap      = min( count( $title_words ), count( $completion_words ) );

	for ( $size = $max_overlap; $size >= 1; $size-- ) {
		$title_tail      = implode( ' ', array_slice( $title_words, -$size ) );
		$completion_head = implode( ' ', array_slice( $completion_words, 0, $size ) );

		if ( coral_concert_popup_text_key( $title_tail ) === coral_concert_popup_text_key( $completion_head ) ) {
			$remaining = implode( ' ', array_slice( $completion_words, $size ) );

			return $remaining ? trim( $title . ' ' . $remaining ) : $title;
		}
	}

	return trim( $title . ' ' . $completion );
}

/**
 * Dona una versió més llegible del títol dins del popup del mapa.
 */
function coral_get_concert_popup_title( $title, $lloc_context = [] ) {
	$title      = coral_normalize_concert_popup_text( $title );
	$lloc_titol = coral_normalize_concert_popup_text( $lloc_context['lloc_titol'] ?? '' );
	$municipi   = coral_normalize_concert_popup_text( $lloc_context['municipi'] ?? '' );

	if ( $title === '' ) {
		return '';
	}

	if ( preg_match( '/^[\.\s]*(?:al|a la|a les|a l[’\']?|l[’\'])\b/iu', $title ) ) {
		$title = coral_strip_leading_concert_popup_connectors( $title );
	}

	if ( preg_match( '/\b(?:al|a la|a les|a l)$/iu', $title ) ) {
		$completion = $lloc_titol ?: $municipi;
		if ( $completion ) {
			$title = preg_replace( '/\bA l$/u', 'al', $title );
			$title = coral_append_concert_popup_completion( $title, $completion );
		}
	} elseif ( preg_match( '/\b(?:de|del|d[’\'])$/iu', $title ) ) {
		$completion = $municipi ?: $lloc_titol;
		if ( $completion ) {
			$title = coral_expand_concert_popup_fragment( $title, $completion );

			if ( coral_concert_popup_text_key( $title ) !== coral_concert_popup_text_key( $completion ) ) {
				$title = coral_append_concert_popup_completion( $title, $completion );
			}
		}
	}

	if ( $municipi ) {
		$title = coral_expand_concert_popup_fragment( $title, $municipi );
	}

	if ( $lloc_titol ) {
		$title = coral_expand_concert_popup_fragment( $title, $lloc_titol );
	}

	$title = coral_normalize_concert_popup_text( $title );
	$title = trim( $title, ". \t\n\r\0\x0B" );

	return $title;
}

/**
 * Recupera el context del lloc vinculat a un concert.
 */
function coral_get_concert_location_context( $lloc_id ) {
	$lloc_id = absint( $lloc_id );

	if ( ! $lloc_id ) {
		return [];
	}

	$info = get_field( 'informacio_llocs_on_hem_cantat', $lloc_id );

	return [
		'lloc_titol' => get_the_title( $lloc_id ),
		'municipi'   => is_array( $info ) ? ( $info['municipi'] ?? '' ) : '',
	];
}

/**
 * Retorna el títol que s'hauria de mostrar al frontend per a un concert.
 */
function coral_get_concert_display_title( $concert_id = 0, $dades = null ) {
	$concert_id = $concert_id ? absint( $concert_id ) : get_the_ID();

	if ( ! $concert_id ) {
		return '';
	}

	$title = get_post_field( 'post_title', $concert_id );

	if ( ! is_array( $dades ) ) {
		$dades = get_field( 'dades_dels_concerts', $concert_id );
	}

	$context = [];

	if ( is_array( $dades ) && ! empty( $dades['ubicacio_mapa'] ) ) {
		$context = coral_get_concert_location_context( $dades['ubicacio_mapa'] );
	}

	return coral_get_concert_popup_title( $title, $context );
}

add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( ! is_singular( 'concert' ) ) {
			return $parts;
		}

		$parts['title'] = coral_get_concert_display_title( get_queried_object_id() );

		return $parts;
	}
);

/**
 * Retorna els concerts vinculats a un lloc concret.
 *
 * IMPORTANT:
 * El meta_key depèn del nom real del group ACF de concerts.
 * Si el group es diu dades_dels_concerts i el camp intern es diu ubicacio_mapa,
 * el meta_key és dades_dels_concerts_ubicacio_mapa.
 */
function coral_get_concerts_by_lloc_cantat( $lloc_id, $lloc_context = [] ) {

	$query = new WP_Query(
		[
			'post_type' => 'concert',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => [
				[
					'key' => 'dades_dels_concerts_ubicacio_mapa',
					'value' => $lloc_id,
					'compare' => '=',
				],
			],
			'meta_key' => 'dades_dels_concerts_data_i_hora',
			'orderby' => 'meta_value',
			'order' => 'DESC',
		]
	);

	$concerts = [];

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			$dades = get_field( 'dades_dels_concerts' );

			$data_raw = '';

			if ( is_array( $dades ) ) {
				$data_raw = $dades['data_i_hora'] ?? '';
			}

			$data_formatejada = '';

			if ( $data_raw ) {
				try {
					$date = new DateTime( $data_raw );
					$data_formatejada = $date->format( 'd/m/Y' );
				} catch (Exception $e) {
					$data_formatejada = '';
				}
			}

			$concerts[] = [
				'titol' => coral_get_concert_display_title( get_the_ID(), $dades ),
				'url' => get_permalink(),
				'data' => $data_formatejada,
			];
		}
	}

	wp_reset_postdata();

	return $concerts;
}

/**
 * Shortcode: [on_hem_cantat_mapa]
 *
 * Ús:
 * [on_hem_cantat_mapa]
 * [on_hem_cantat_mapa zona="catalunya"]
 * [on_hem_cantat_mapa zona="resta_espanya"]
 * [on_hem_cantat_mapa zona="resta_del_mon"]
 */
function coral_on_hem_cantat_mapa_shortcode( $atts ) {

	$atts = shortcode_atts(
		[
			'zona' => 'tots',
		],
		$atts,
		'on_hem_cantat_mapa'
	);

	$zona = sanitize_text_field( $atts['zona'] );

	wp_enqueue_style(
		'coral-on-hem-cantat',
		get_stylesheet_directory_uri() . '/on-hem-cantat/on-hem-cantat.css',
		[],
		'1.0'
	);

	$query = new WP_Query(
		[
			'post_type' => 'lloc_cantat',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC',
		]
	);

	$markers = '';
	$llocs_mapa = [];

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			$lloc_id = get_the_ID();
			$lloc_titol = get_the_title();

			$info = get_field( 'informacio_llocs_on_hem_cantat' );

			if ( ! is_array( $info ) ) {
				continue;
			}

			$municipi = $info['municipi'] ?? '';
			$zona_lloc = $info['zona'] ?? '';
			$latitud = coral_normalize_coordinate( $info['latitud'] ?? '' );
			$longitud = coral_normalize_coordinate( $info['longitud'] ?? '' );
			$descripcio = $info['descripcio'] ?? '';

			if ( ! is_numeric( $latitud ) || ! is_numeric( $longitud ) ) {
				continue;
			}

			if ( $zona !== 'tots' && $zona_lloc !== $zona ) {
				continue;
			}

			$llocs_mapa[] = [
				'latitud' => $latitud,
				'longitud' => $longitud,
			];

			/**
			 * Popup del marcador
			 */
			$popup = '<div class="on-hem-cantat-popup-content">';

			$popup .= '<strong class="on-hem-cantat-popup-title">';
			$popup .= esc_html( $lloc_titol );
			$popup .= '</strong>';

			if ( $municipi ) {
				$popup .= '<p class="on-hem-cantat-popup-municipi">';
				$popup .= esc_html( $municipi );
				$popup .= '</p>';
			}

			if ( $descripcio ) {
				$popup .= '<div class="on-hem-cantat-popup-desc">';
				$popup .= wp_kses_post( wpautop( $descripcio ) );
				$popup .= '</div>';
			}

			/**
			 * Concerts vinculats a aquest lloc
			 */
			$concerts_lloc = coral_get_concerts_by_lloc_cantat(
				$lloc_id,
				[
					'lloc_titol' => $lloc_titol,
					'municipi'   => $municipi,
				]
			);

			if ( ! empty( $concerts_lloc ) ) {
				$popup .= '<div class="on-hem-cantat-popup-concerts">';
				$popup .= '<h6>Concerts realitzats aquí</h6>';
				$popup .= '<ul>';

				foreach ( $concerts_lloc as $concert ) {
					$popup .= '<li>';

					$popup .= '<a href="' . esc_url( $concert['url'] ) . '">';
					$popup .= esc_html( $concert['titol'] );
					$popup .= '</a>';

					if ( ! empty( $concert['data'] ) ) {
						$popup .= '<span> — ' . esc_html( $concert['data'] ) . '</span>';
					}

					$popup .= '</li>';
				}

				$popup .= '</ul>';
				$popup .= '</div>';
			} else {
				$popup .= '<p class="on-hem-cantat-popup-no-concerts">';
				$popup .= 'Encara no hi ha concerts vinculats a aquest lloc.';
				$popup .= '</p>';
			}

			$popup .= '</div>';

			/**
			 * Marker del plugin Leaflet Map.
			 * Important: aquí ja passem lat/lng normalitzats amb punt decimal.
			 */
			$markers .= sprintf(
				'[leaflet-marker lat=%s lng=%s]%s[/leaflet-marker]',
				esc_attr( $latitud ),
				esc_attr( $longitud ),
				$popup
			);
		}
	}

	wp_reset_postdata();

	ob_start();
	?>

	<div class="on-hem-cantat-map-wrap">
		<?php
		if ( $markers ) {

			/**
			 * Mapa base segons la zona.
			 * Evitem dependre sempre de fitbounds perquè amb pocs marcadors pot allunyar massa el zoom.
			 */
			if ( $zona === 'catalunya' ) {

				$map_shortcode = '[leaflet-map lat=41.75 lng=1.8 zoom=8 height=560 zoomcontrol scrollwheel]';

			} elseif ( $zona === 'resta_espanya' ) {

				$map_shortcode = '[leaflet-map lat=40.4 lng=-3.7 zoom=6 height=560 zoomcontrol scrollwheel]';

			} elseif ( $zona === 'resta_del_mon' ) {

				$map_shortcode = '[leaflet-map lat=30 lng=0 zoom=2 height=560 zoomcontrol scrollwheel]';

			} else {

				$map_shortcode = '[leaflet-map fitbounds height=560 zoomcontrol scrollwheel]';

			}

			echo do_shortcode( $map_shortcode . $markers );

		} else {
			echo '<p class="on-hem-cantat-empty">Encara no hi ha llocs disponibles al mapa.</p>';
		}
		?>
	</div>

	<?php
	return ob_get_clean();
}

add_shortcode( 'on_hem_cantat_mapa', 'coral_on_hem_cantat_mapa_shortcode' );
