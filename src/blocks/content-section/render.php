<?php
/**
 * Content Section — Server-side render.
 *
 * Free-form InnerBlocks content with prose typography.
 * For non-default languages: uses chunk-based translations for text blocks,
 * while always rendering custom blocks (snel/*) in their original position.
 *
 * @var array    $attributes
 * @var string   $content     The rendered InnerBlocks content.
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$lang    = snel_get_lang();
$default = snel_get_default_lang();

$bg_map   = [
	'white'         => 'bg-white',
	'surface'       => 'bg-surface',
	'surface-light' => 'bg-surface-light',
	'dark'          => 'bg-gray-900 text-text-light',
];
$bg_key   = $attributes['backgroundColor'] ?? 'white';
$bg_class = $bg_map[ $bg_key ] ?? 'bg-white';

$max_width = ( $attributes['maxWidth'] ?? 'narrow' ) === 'wide' ? 'max-w-5xl' : 'max-w-3xl';

$vertical_padding = ! empty( $attributes['verticalPadding'] );
$padding_class    = $vertical_padding ? 'py-16 md:py-24' : 'py-0';

// Blocks that should be skipped during translation (no translatable text) — rendered as-is.
$skip_blocks = array( 'core/image', 'core/gallery', 'core/separator', 'core/spacer' );

// Check for chunk-based translations.
$translations = $attributes['contentTranslations'] ?? array();
$chunks       = ! empty( $translations[ $lang ] ) ? $translations[ $lang ] : null;

?>

<section data-seo-content class="snel-content-section <?php echo esc_attr( "$padding_class px-4 md:px-16 lg:px-24 $bg_class" ); ?>">
	<div class="<?php echo esc_attr( $max_width ); ?> mx-auto">
		<div class="prose prose-lg max-w-none">
			<?php
			if ( $lang === $default || ! $chunks ) {
				echo $content;
			} elseif ( is_array( $chunks ) && ! empty( $block->inner_blocks ) ) {
				$text_index = 0;
				foreach ( $block->inner_blocks as $inner_block ) {
					if ( strpos( $inner_block->name, 'snel/' ) === 0 || in_array( $inner_block->name, $skip_blocks, true ) ) {
						echo render_block( $inner_block->parsed_block );
					} else {
						if ( isset( $chunks[ $text_index ] ) && $chunks[ $text_index ] !== '' ) {
							echo wp_kses_post( $chunks[ $text_index ] );
						} else {
							echo render_block( $inner_block->parsed_block );
						}
						$text_index++;
					}
				}
			}
			?>
		</div>
	</div>
</section>
