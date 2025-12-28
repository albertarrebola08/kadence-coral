<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/* =====================================================
 * SHORTCODE: [concerts_proxims]
 * ===================================================== */
function coral_concerts_proxims_shortcode() {

	wp_enqueue_style(
		'coral-concerts',
		get_stylesheet_directory_uri() . '/concerts/concerts.css',
		[],
		'1.0'
	);

	// Data actual (necessària!)
	$avui = current_time( 'Y-m-d H:i:s' );

	$query = new WP_Query( [
		'post_type' => 'concert',
		'posts_per_page' => 6,
		'post_status' => 'publish',

		/* ORDRE PER DATA (més proper primer) */
		'meta_key' => 'dades_dels_concerts_data_i_hora',
		'orderby' => 'meta_value',
		'order' => 'ASC',

		/* FILTRES */
		'meta_query' => [
			'relation' => 'AND',
			[
				'key' => 'dades_dels_concerts_mostrar_home',
				'value' => '1',
			],
			[
				'key' => 'dades_dels_concerts_data_i_hora',
				'value' => $avui,
				'compare' => '>=',
				'type' => 'DATETIME',
			],
		],
	] );

	ob_start();

	if ( ! $query->have_posts() ) {
		echo '<p>No hi ha concerts pròximament.</p>';
	} else {

		echo '<section class="concerts-home">';
		echo '<h2 class="concerts-title">Pròxims concerts</h2>';
		echo '<div class="concerts-grid">';

		while ( $query->have_posts() ) {
			$query->the_post();
			include get_stylesheet_directory() . '/concerts/templates/card.php';
		}

		echo '</div>';
		echo '</section>';
	}

	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'concerts_proxims', 'coral_concerts_proxims_shortcode' );
