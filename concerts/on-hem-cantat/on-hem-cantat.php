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
 * Retorna els concerts vinculats a un lloc concret.
 *
 * IMPORTANT:
 * El meta_key depèn del nom real del group ACF de concerts.
 * Si el group es diu dades_dels_concerts i el camp intern es diu ubicacio_mapa,
 * el meta_key és dades_dels_concerts_ubicacio_mapa.
 */
function coral_get_concerts_by_lloc_cantat( $lloc_id ) {

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
				'titol' => get_the_title(),
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
			$popup .= esc_html( get_the_title() );
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
			$concerts_lloc = coral_get_concerts_by_lloc_cantat( $lloc_id );

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