/**
 * InspectorOptions — Shared Layout + Background inspector controls.
 *
 * Used inside TranslatableWrapper and BasicWrapper so the sidebar panels
 * are defined in one place.
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { BG_OPTIONS } from './BgColorControl';

const LAYOUT_WIDTH_OPTIONS = [
	{ label: __( 'Wide (6xl)', 'snel' ), value: 'wide' },
	{ label: __( 'Narrow (4xl)', 'snel' ), value: 'narrow' },
];

export default function InspectorOptions( { attributes, setAttributes } ) {
	const layoutWidth = attributes?.layoutWidth || 'wide';
	const bgColor = attributes?.bgColor || 'white';

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Layout', 'snel' ) } initialOpen={ false }>
				<SelectControl
					label={ __( 'Content width', 'snel' ) }
					value={ layoutWidth }
					options={ LAYOUT_WIDTH_OPTIONS }
					onChange={ ( value ) => setAttributes( { layoutWidth: value } ) }
					__nextHasNoMarginBottom
				/>
				{ typeof attributes?.disableTopPadding !== 'undefined' && (
					<ToggleControl
						label={ __( 'Remove top spacing', 'snel' ) }
						checked={ !! attributes.disableTopPadding }
						onChange={ ( value ) => setAttributes( { disableTopPadding: value } ) }
						__nextHasNoMarginBottom
					/>
				) }
			</PanelBody>
			<PanelBody title={ __( 'Background', 'snel' ) } initialOpen={ false }>
				<SelectControl
					label={ __( 'Background color', 'snel' ) }
					value={ bgColor }
					options={ BG_OPTIONS }
					onChange={ ( value ) => setAttributes( { bgColor: value } ) }
					__nextHasNoMarginBottom
				/>
			</PanelBody>
		</InspectorControls>
	);
}
