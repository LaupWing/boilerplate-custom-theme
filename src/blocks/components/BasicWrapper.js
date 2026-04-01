/**
 * BasicWrapper — Lightweight wrapper for blocks without editable content.
 *
 * Provides:
 * - blockProps on the outer div
 * - Dashed border (matching TranslatableWrapper)
 * - Small label badge
 * - Layout width + background color in Inspector Controls
 * - Full-width bg section with max-width content container
 */
import { BG_EDITOR_STYLES } from './BgColorControl';
import InspectorOptions from './InspectorOptions';
import '../../editor.css';

const LAYOUT_WIDTH_CLASSES = {
	wide: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
	narrow: 'max-w-4xl mx-auto px-4 sm:px-6 lg:px-8',
};

export default function BasicWrapper( { blockProps, label, attributes, setAttributes, fullWidth, children } ) {
	const layoutWidth = attributes?.layoutWidth || 'wide';
	const bgColor = attributes?.bgColor || 'white';
	const bgStyle = BG_EDITOR_STYLES[bgColor] || BG_EDITOR_STYLES.white;

	const inner = fullWidth ? children : (
		<section style={{ backgroundColor: bgStyle, padding: '4rem 0' }}>
			<div className={LAYOUT_WIDTH_CLASSES[layoutWidth] || ''}>
				{ children }
			</div>
		</section>
	);

	return (
		<>
			{setAttributes && attributes && !fullWidth && (
				<InspectorOptions attributes={attributes} setAttributes={setAttributes} />
			)}

			<div { ...blockProps } className={ `${ blockProps.className || '' } border-2 border-dashed border-gray-300 !px-0` }>
				{ inner }

				<div
					className="absolute top-4 right-4 z-50 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 shadow-sm"
					style={ { fontSize: '13px' } }
				>
					<span className="font-medium text-gray-800">{ label }</span>
				</div>
			</div>
		</>
	);
}
