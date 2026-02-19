<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

$dades = get_field( 'dades_de_la_partitura' ) ?: [];
$pdf = $dades['pdf'] ?? null;

$numero = $dades['numero'] ?? '';
$autor = $dades['autor'] ?? '';
$harmonitzacio = $dades['harmonitzacio'] ?? '';
$adaptacio = $dades['adaptacio'] ?? '';
$traduccio = $dades['traduccio_lletra'] ?? '';
$temps = $dades['temps'] ?? '';
$any_inici = $dades['any_inici'] ?? '';
$any_fi = $dades['any_fi'] ?? '';
$llibre = $dades['llibre'] ?? '';

// Taxonomies
$genere = get_the_terms( get_the_ID(), 'genere' );
$tradicional = get_the_terms( get_the_ID(), 'tradicional' );

// Helper: format rang anys (si existeix)
$rang_anys = '';
if ( $any_inici !== '' && $any_fi !== '' ) {
	$rang_anys = ( (int) $any_inici === (int) $any_fi )
		? (string) (int) $any_inici
		: ( (int) $any_inici . '–' . (int) $any_fi );
}
?>

<article class="partitura-card">

	<header class="partitura-header">
		<h3 class="partitura-title">
			<?php if ( $numero ) : ?>
				<span class="partitura-num">
					<?php echo esc_html( $numero ); ?>
				</span>
			<?php endif; ?>
			<?php the_title(); ?>
		</h3>

		<?php
		// Badges (gènere + tradicional)
		$badges = [];

		if ( $genere && ! is_wp_error( $genere ) ) {
			foreach ( $genere as $t )
				$badges[] = $t->name;
		}
		if ( $tradicional && ! is_wp_error( $tradicional ) ) {
			foreach ( $tradicional as $t )
				$badges[] = $t->name;
		}

		if ( ! empty( $badges ) ) : ?>
			<div class="partitura-badges">
				<?php foreach ( $badges as $b ) : ?>
					<span class="badge">
						<?php echo esc_html( $b ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</header>

	<div class="partitura-body">

		<?php if ( $autor ) : ?>
			<p><strong>Autor:</strong>
				<?php echo esc_html( $autor ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $harmonitzacio ) : ?>
			<p><strong>Harmonització:</strong>
				<?php echo esc_html( $harmonitzacio ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $adaptacio ) : ?>
			<p><strong>Adaptació:</strong>
				<?php echo esc_html( $adaptacio ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $traduccio ) : ?>
			<p><strong>Traducció/Lletra:</strong>
				<?php echo esc_html( $traduccio ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $llibre ) : ?>
			<p><strong>Llibre:</strong>
				<?php echo esc_html( $llibre ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $temps || $rang_anys ) : ?>
			<p>
				<strong>Temps:</strong>
				<?php
				// Mostra primer el rang net si existeix, sinó mostra el text original.
				echo esc_html( $rang_anys ? $rang_anys : $temps );
				?>
				<?php if ( $rang_anys && $temps && $temps !== $rang_anys ) : ?>
					<span class="partitura-muted">(
						<?php echo esc_html( $temps ); ?>)
					</span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

	</div>

	<?php if ( $pdf && ! empty( $pdf['url'] ) ) : ?>
		<div class="partitura-actions">
			<a class="btn-preview" href="#" data-pdf="<?php echo esc_url( $pdf['url'] ); ?>">
				Previsualitzar
			</a>
			<a class="btn-pdf" href="<?php echo esc_url( $pdf['url'] ); ?>" target="_blank" rel="noopener">
				Descarregar PDF
			</a>
		</div>
	<?php endif; ?>

</article>