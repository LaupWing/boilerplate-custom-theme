<?php
/**
 * Article Section — Server-side render.
 *
 * Structured section with tagline, heading, image, and body text.
 *
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$tagline   = snel_attr( $attributes, 'tagline' );
$heading   = snel_attr( $attributes, 'heading' );
$image_id  = $attributes['imageId'] ?? 0;
$image_url = $attributes['imageUrl'] ?? '';
$body      = snel_attr( $attributes, 'content' );

$bg_map   = [
	'white'         => 'bg-white',
	'surface'       => 'bg-surface',
	'surface-light' => 'bg-surface-light',
	'dark'          => 'bg-gray-900 text-text-light',
];
$bg_key   = $attributes['backgroundColor'] ?? 'white';
$bg_class = $bg_map[ $bg_key ] ?? 'bg-white';

$vertical_padding = ! empty( $attributes['verticalPadding'] );
$padding_class    = $vertical_padding ? 'py-16 md:py-24' : 'py-0';

// Get optimized image URL from attachment if available.
if ( $image_id ) {
	$src = wp_get_attachment_image_url( $image_id, 'large' );
	if ( $src ) {
		$image_url = $src;
	}
}
?>

<section class="snel-article-section <?php echo esc_attr( "$padding_class px-4 md:px-16 lg:px-24 $bg_class" ); ?>">
	<div class="max-w-3xl mx-auto">
		<?php if ( ! empty( $tagline ) || ! empty( $heading ) ) : ?>
			<div class="mb-12">
				<?php if ( ! empty( $tagline ) ) : ?>
					<p class="text-sm font-medium tracking-wide text-text-muted uppercase mb-4">
						<?php echo esc_html( $tagline ); ?>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $heading ) ) : ?>
					<h2 class="text-3xl md:text-4xl font-bold text-text-primary mb-6">
						<?php echo wp_kses_post( $heading ); ?>
					</h2>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $image_url ) : ?>
			<div class="mb-12">
				<img
					src="<?php echo esc_url( $image_url ); ?>"
					alt="<?php echo esc_attr( wp_strip_all_tags( $heading ) ); ?>"
					class="w-full aspect-video object-cover rounded-sm"
					loading="lazy"
				/>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $body ) ) : ?>
			<div class="prose prose-lg max-w-none">
				<?php echo wp_kses_post( $body ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
