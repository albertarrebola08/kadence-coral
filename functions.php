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
		return home_url( '/partitures/' );
	}

	return $redirect_to;
}, 10, 3 );


// Carregar el sistema de concerts

require get_stylesheet_directory() . '/concerts/concerts.php';



