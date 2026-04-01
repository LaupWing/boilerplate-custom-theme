/**
 * Shared Background Color control for block sidebars.
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';

export const BG_OPTIONS = [
	{ label: 'White', value: 'white' },
	{ label: 'Surface', value: 'surface' },
	{ label: 'Surface Light', value: 'surface-light' },
	{ label: 'Dark', value: 'dark' },
];

export const BG_CLASSES = {
	'white': 'bg-white',
	'surface': 'bg-surface',
	'surface-light': 'bg-surface-light',
	'dark': 'bg-gray-900',
};

export const BG_EDITOR_STYLES = {
	'white': '#ffffff',
	'surface': '#f0f4fa',
	'surface-light': '#f5f7fc',
	'dark': '#111827',
};

export function getBgClass( value ) {
	return BG_CLASSES[ value ] || BG_CLASSES.white;
}

export default function BgColorControl( { value, onChange } ) {
	return (
		<PanelBody title={ __( 'Background', 'snel' ) } initialOpen={ false }>
			<SelectControl
				label={ __( 'Background Color', 'snel' ) }
				value={ value }
				options={ BG_OPTIONS }
				onChange={ onChange }
			/>
		</PanelBody>
	);
}
