<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/* =====================================================
 * SHORTCODE [partitures]
 * Sidebar esquerra + grid dreta
 * ===================================================== */
function coral_partitures_shortcode() {

	if ( ! is_user_logged_in() ) {
		return '<p>Has d\'iniciar sessió per veure les partitures.</p>';
	}

	// CSS
	wp_enqueue_style(
		'coral-partitures',
		get_stylesheet_directory_uri() . '/partitures/partitures.css',
		[],
		'1.0'
	);

	// JS
	wp_enqueue_script(
		'coral-partitures',
		get_stylesheet_directory_uri() . '/partitures/partitures.js',
		[ 'jquery' ],
		'1.0',
		true
	);

	wp_localize_script(
		'coral-partitures',
		'coralPartitures',
		[
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		]
	);

	$taxonomies = [
		'genere' => 'Tots els gèneres',
		'llibre' => 'Tots els llibres',
		'tradicional' => 'Totes (tradicional)',
	];

	ob_start(); ?>

	<section class="partitures-app">
		<?php if ( is_user_logged_in() ) : ?>
			<div class="partitures-logout mb-3">
				<a class="btn-logout" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
					Sortir / Tancar sessió
				</a>
			</div>
		<?php endif; ?>

		<div class="partitures-layout">

			<!-- SIDEBAR -->
			<aside class="partitures-sidebar">
				<form id="partitures-filtres" class="partitures-filtres">



					<input type="hidden" name="action" value="coral_get_partitures">
					<input type="hidden" name="paged" value="1">
					<input type="hidden" name="orderby" value="">
					<input type="hidden" name="order" value="ASC">

					<label class="partitures-label">Cercador</label>
					<input type="text" name="search" placeholder="Cerca per obra o autor…" autocomplete="off">

					<?php foreach ( $taxonomies as $tax => $placeholder ) : ?>
						<label class="partitures-label"><?php echo esc_html( ucfirst( $tax ) ); ?></label>
						<select name="<?php echo esc_attr( $tax ); ?>">
							<option value=""><?php echo esc_html( $placeholder ); ?></option>
							<?php
							$terms = get_terms( [
								'taxonomy' => $tax,
								'hide_empty' => false,
							] );

							if ( ! is_wp_error( $terms ) ) :
								foreach ( $terms as $term ) :
									echo '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
								endforeach;
							endif;
							?>
						</select>
					<?php endforeach; ?>

				</form>
			</aside>

			<!-- CONTINGUT -->

			<main class="partitures-main">
				<button id="btn-order-title" class="btn-order" type="button">
					A–Z
				</button>

				<div class="partitures-topbar">
					<div id="partitures-count" class="partitures-count">Carregant…</div>
					<div id="partitures-loader" class="partitures-loader" style="display:none;">Carregant…</div>
				</div>

				<div id="partitures-grid" class="partitures-grid">
					<p>Carregant partitures…</p>
				</div>

				<!-- MODAL PDF -->
				<div id="pdf-modal" class="pdf-modal">
					<div id="pdf-modal-backdrop" class="pdf-modal-backdrop"></div>
					<div class="pdf-modal-panel">
						<button id="pdf-modal-close" class="pdf-modal-close" type="button">Tancar</button>
						<iframe src="" title="Preview PDF"></iframe>
					</div>
				</div>

			</main>

		</div>
	</section>

	<?php
	return ob_get_clean();
}
add_shortcode( 'partitures', 'coral_partitures_shortcode' );


/* =====================================================
 * AJAX: obtenir partitures
 * - Cerca: OBRA (títol) OR AUTOR (ACF)
 * - Filtres: taxonomies (AND)
 * - Ordre: A–Z / Z–A
 * ===================================================== */
add_action( 'wp_ajax_coral_get_partitures', 'coral_get_partitures' );
add_action( 'wp_ajax_nopriv_coral_get_partitures', 'coral_get_partitures' );

function coral_get_partitures() {

	if ( ! is_user_logged_in() )
		wp_die();

	$search = sanitize_text_field( $_POST['search'] ?? '' );
	$paged = max( 1, intval( $_POST['paged'] ?? 1 ) );
	$orderby = sanitize_text_field( $_POST['orderby'] ?? '' );
	$order = ( $_POST['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

	$per_page = 12;
	$taxonomies = [ 'genere', 'llibre', 'tradicional' ];

	/* =========================
	   TAX QUERY
	========================= */
	$tax_query = [ 'relation' => 'AND' ];
	foreach ( $taxonomies as $tax ) {
		$val = sanitize_text_field( $_POST[ $tax ] ?? '' );
		if ( $val ) {
			$tax_query[] = [
				'taxonomy' => $tax,
				'field' => 'slug',
				'terms' => $val,
			];
		}
	}
	$use_tax_query = count( $tax_query ) > 1 ? $tax_query : [];

	/* =========================
	   CAS AMB CERCA
	========================= */
	if ( $search ) {

		$ids = [];

		// Títol (OBRA)
		$q_title = new WP_Query( [
			'post_type' => 'partitura',
			'posts_per_page' => -1,
			'fields' => 'ids',
			's' => $search,
			'tax_query' => $use_tax_query,
		] );
		$ids = array_merge( $ids, $q_title->posts );

		// Autor (ACF)
		$q_author = new WP_Query( [
			'post_type' => 'partitura',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => [
				[
					'key' => 'dades_de_la_partitura_autor',
					'value' => $search,
					'compare' => 'LIKE',
				]
			],
			'tax_query' => $use_tax_query,
		] );
		$ids = array_merge( $ids, $q_author->posts );
		$ids = array_values( array_unique( $ids ) );

		if ( empty( $ids ) ) {
			echo '<p>No hi ha resultats.</p>';
			wp_die();
		}

		// Ordre manual per títol
		if ( $orderby === 'title' ) {
			usort( $ids, function ( $a, $b ) use ( $order ) {
				$t1 = get_the_title( $a );
				$t2 = get_the_title( $b );
				return $order === 'ASC'
					? strcasecmp( $t1, $t2 )
					: strcasecmp( $t2, $t1 );
			} );
		}

		$query = new WP_Query( [
			'post_type' => 'partitura',
			'posts_per_page' => $per_page,
			'paged' => $paged,
			'post__in' => $ids,
			'orderby' => 'post__in',
		] );

	}
	/* =========================
	   CAS SENSE CERCA
	========================= */ else {

		$args = [
			'post_type' => 'partitura',
			'posts_per_page' => $per_page,
			'paged' => $paged,
			'tax_query' => $use_tax_query,
		];

		if ( $orderby === 'title' ) {
			$args['orderby'] = 'title';
			$args['order'] = $order;
		}

		$query = new WP_Query( $args );
	}

	/* =========================
	   RENDER
	========================= */
	if ( ! $query->have_posts() ) {
		echo '<p>No hi ha resultats.</p>';
		wp_die();
	}

	while ( $query->have_posts() ) {
		$query->the_post();
		include get_stylesheet_directory() . '/partitures/templates/card.php';
	}

	/* =========================
	   PAGINACIÓ
	========================= */
	$total_pages = (int) $query->max_num_pages;
	if ( $total_pages > 1 ) {
		echo '<div class="partitures-pagination">';

		if ( $paged > 1 ) {
			echo '<button class="pagination-prev" type="button">← Anterior</button>';
		}
		if ( $paged < $total_pages ) {
			echo '<button class="pagination-next" type="button">Següent →</button>';
		}

		echo '</div>';
	}

	wp_reset_postdata();
	wp_die();
}
