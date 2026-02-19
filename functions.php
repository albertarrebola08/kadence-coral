<?php
// CSS del tema fill
add_action( 'wp_enqueue_scripts', function () {

	wp_enqueue_style(
		'kadence-coral-style',
		get_stylesheet_uri(),
		[ 'kadence-global' ],
		wp_get_theme()->get( 'Version' )
	);

}, 20 );

// Carregar el sistema de partitures
require get_stylesheet_directory() . '/partitures/partitures.php';


//css login 
/* =========================
 * LOGIN: carregar CSS
 * ========================= */
add_action( 'login_enqueue_scripts', function () {
	wp_enqueue_style(
		'coral-login',
		get_stylesheet_directory_uri() . '/assets/css/login.css',
		[],
		'1.0'
	);
} );


// Link del logo del login
add_filter( 'login_headerurl', function () {
	return home_url();
} );

// Text del logo (SEO + accessibilitat)
add_filter( 'login_headertext', function () {
	return 'Intranet de la coral';
} );


add_filter( 'login_redirect', function ( $redirect_to, $request, $user ) {

	if ( isset( $user->roles ) && is_array( $user->roles ) ) {

		// Admins → dashboard
		if ( in_array( 'administrator', $user->roles, true ) ) {
			return admin_url();
		}

		// Membres → partitures
		return home_url( '/area-privada/' );
	}

	return $redirect_to;
}, 10, 3 );


// Carregar el sistema de concerts

require get_stylesheet_directory() . '/concerts/concerts.php';

/**
 * Botó d'accés / tancar sessió per al header.
 * - Si NO està loguejat: "Àrea privada" -> wp-login
 * - Si està loguejat: "Tancar sessió" -> wp-logout (amb redirect)
 */
function coral_get_header_access_button_html(): string {

	// NO loguejat: login
	if ( ! is_user_logged_in() ) {
		$login_url = wp_login_url( home_url( '/area-privada/' ) ); // on vols anar després de login
		return '<a target="__blank" class="coral-access-btn coral-access-btn--login" href="' . esc_url( $login_url ) . '">Àrea privada</a>';
	}

	// Loguejat: logout
	$logout_url = wp_logout_url( home_url( '/' ) ); // on vols anar després de logout
	return '<a class="coral-access-btn coral-access-btn--logout" href="' . esc_url( $logout_url ) . '">Tancar sessió</a>';
}

add_shortcode( 'coral_access_button', function () {
	return coral_get_header_access_button_html();
} );



// shortcode calendari concerts

/**
 * Retorna la URL del PDF del calendari més recent (CPT: calendaris).
 */
function coral_get_latest_calendar_pdf_url(): string {

	$q = new WP_Query( [
		'post_type' => 'calendaris',
		'posts_per_page' => 1,
		'orderby' => 'date',
		'order' => 'DESC',
		'post_status' => 'publish',
	] );

	if ( ! $q->have_posts() ) {
		return '';
	}

	$post_id = $q->posts[0]->ID;

	// ACF file field (pot ser array, url o ID segons configuració)
	$file = get_field( 'calendari_arxiu_pdf', $post_id );

	if ( empty( $file ) )
		return '';

	if ( is_array( $file ) && ! empty( $file['url'] ) ) {
		return $file['url'];
	}

	if ( is_numeric( $file ) ) {
		$url = wp_get_attachment_url( (int) $file );
		return $url ? $url : '';
	}

	if ( is_string( $file ) ) {
		return $file;
	}

	return '';
}
/**
 * Parseja temps -> [any_inici, any_fi]
 */
function coral_parse_temps_to_years( $temps_raw ) {
	$t = trim( (string) $temps_raw );
	if ( $t === '' )
		return [ null, null ];

	// normalitza guions i caràcters rars
	$t = str_replace( [ "–", "—", "−", "´", "’", "`", "/" ], [ "-", "-", "-", "'", "'", "'", "-" ], $t );

	// extreu anys (3-4 digits)
	preg_match_all( '/\b(\d{3,4})\b/', $t, $m );
	$years = array_values( array_filter( array_map( 'intval', $m[1] ?? [] ) ) );

	if ( count( $years ) === 1 ) {
		return [ $years[0], $years[0] ];
	}

	if ( count( $years ) >= 2 ) {
		$a = $years[0];
		$b = $years[1];
		return [ min( $a, $b ), max( $a, $b ) ];
	}

	return [ null, null ];
}

/**
 * En guardar la partitura, calcula i desa any_inici/any_fi.
 */
add_action( 'acf/save_post', function ( $post_id ) {

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) )
		return;
	if ( get_post_type( $post_id ) !== 'partitura' )
		return;

	// IMPORTANT: subcamp dins group => meta: dades_de_la_partitura_temps
	$temps = get_post_meta( $post_id, 'dades_de_la_partitura_temps', true );

	[ $start, $end ] = coral_parse_temps_to_years( $temps );
	if ( $start === null || $end === null )
		return;

	update_post_meta( $post_id, 'dades_de_la_partitura_any_inici', $start );
	update_post_meta( $post_id, 'dades_de_la_partitura_any_fi', $end );

}, 20 );




/**
 * Shortcode: [coral_calendari_pdf]
 * Mostra el PDF en iframe + link de descàrrega.
 */
add_shortcode( 'coral_calendari_pdf', function () {

	$pdf_url = coral_get_latest_calendar_pdf_url();

	if ( ! $pdf_url ) {
		return '<p>No hi ha cap calendari publicat encara.</p>';
	}

	// iframe: a alguns mòbils pot no incrustar bé; deixem link de backup.
	$html = '<div class="coral-pdf-embed">';
	$html .= '<iframe src="' . esc_url( $pdf_url ) . '" width="100%" height="800" style="border:0;" loading="lazy"></iframe>';
	$html .= '<p><a href="' . esc_url( $pdf_url ) . '" target="_blank" rel="noopener">Obrir / descarregar PDF</a></p>';
	$html .= '</div>';

	return $html;
} );


