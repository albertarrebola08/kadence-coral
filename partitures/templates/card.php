<?php
$dades = get_field( 'dades_de_la_partitura' );
$pdf = $dades['pdf'] ?? null;

$autor = $dades['autor'] ?? '';
$harmonitzacio = $dades['harmonitzacio'] ?? '';

$genere = get_the_terms( get_the_ID(), 'genere' );
$origen = get_the_terms( get_the_ID(), 'origen' );
?>

<article class="partitura-card">

	<div class="partitura-header">
		<h3><?php the_title(); ?></h3>
	</div>

	<?php if ( $autor ) : ?>
		<p><strong>Autor:</strong> <?= esc_html( $autor ); ?></p>
	<?php endif; ?>

	<?php if ( $harmonitzacio ) : ?>
		<p><strong>Harmonització:</strong> <?= esc_html( $harmonitzacio ); ?></p>
	<?php endif; ?>

	<?php if ( $genere && ! is_wp_error( $genere ) ) : ?>
		<p><strong>Gènere:</strong>
			<?= esc_html( implode( ', ', wp_list_pluck( $genere, 'name' ) ) ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $pdf ) : ?>
		<div class="partitura-actions">
			<a class="btn-preview" href="#" data-pdf="<?= esc_url( $pdf['url'] ); ?>">
				Previsualitzar
			</a>
			<a class="btn-pdf" href="<?= esc_url( $pdf['url'] ); ?>" target="_blank">
				Descarregar PDF
			</a>
		</div>
	<?php endif; ?>

</article>