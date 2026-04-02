/**
 * Content Section — Editor Component
 *
 * Free-form InnerBlocks content section.
 * Translations stored as arrays of chunks per language in contentTranslations.
 * Custom blocks (snel/*) always render — only text blocks get translated.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect, select } from '@wordpress/data';
import { serialize } from '@wordpress/blocks';
import { RawHTML } from '@wordpress/element';
import TranslatableWrapper from '../components/TranslatableWrapper';
import BgColorControl, { getBgClass, BG_EDITOR_STYLES } from '../components/BgColorControl';
import { translateTexts } from '../components/lang-helpers';

const MAX_WIDTH_OPTIONS = [
	{ label: 'Narrow (prose)', value: 'narrow' },
	{ label: 'Wide', value: 'wide' },
];

const ALLOWED_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/image',
	'core/gallery',
	'core/table',
	'core/list',
	'core/list-item',
	'core/separator',
	'core/spacer',
	'core/columns',
	'core/column',
	'core/quote',
	'core/pullquote',
	'core/group',
	'core/buttons',
	'core/button',
	'core/media-text',
];

const SKIP_BLOCKS = new Set([
	'core/image',
	'core/gallery',
	'core/separator',
	'core/spacer',
]);

function isSkippedBlock(block) {
	return block.name.startsWith('snel/') || SKIP_BLOCKS.has(block.name);
}

export default function Edit({ attributes, setAttributes, clientId }) {
	const { backgroundColor, maxWidth, contentTranslations, verticalPadding } = attributes;

	const maxWidthClass = maxWidth === 'wide' ? 'max-w-5xl' : 'max-w-3xl';
	const paddingClass = verticalPadding ? 'py-16 md:py-24' : 'py-0';

	const blockProps = useBlockProps({
		className: `relative ${paddingClass} px-4 md:px-16 lg:px-24`,
		style: { backgroundColor: BG_EDITOR_STYLES[backgroundColor] || BG_EDITOR_STYLES['white'] },
	});

	const innerBlocks = useSelect(
		(select) => select('core/block-editor').getBlocks(clientId),
		[clientId]
	);

	const handleTranslate = async (targetLang) => {
		const texts = [];
		const textBlocks = innerBlocks.filter((b) => !isSkippedBlock(b));

		textBlocks.forEach((block) => {
			const html = serialize([block])
				.replace(/<!--.*?-->/gs, '')
				.replace(/\n+/g, ' ')
				.replace(/\s+/g, ' ')
				.trim();
			if (html) {
				texts.push(html);
			}
		});

		if (texts.length === 0) return;

		let tr;
		try {
			tr = await translateTexts(texts, targetLang);
		} catch (err) {
			console.error('[ContentSection] Translation error:', err);
			return;
		}

		if (!tr || tr.length === 0) return;

		const freshAttrs = select('core/block-editor').getBlockAttributes(clientId);
		const newTranslations = { ...(freshAttrs.contentTranslations || {}) };
		newTranslations[targetLang] = tr;

		setAttributes({ contentTranslations: newTranslations });
	};

	const getChunks = (lang) => {
		if (contentTranslations && contentTranslations[lang]) {
			return contentTranslations[lang];
		}
		return null;
	};

	const renderTranslatedView = (lang) => {
		const chunks = getChunks(lang);

		if (!chunks || chunks.length === 0) {
			return (
				<p className="text-gray-400 italic">
					{__('No translation yet. Click "Translate" to generate.', 'snel')}
				</p>
			);
		}

		const elements = [];
		let textIndex = 0;

		innerBlocks.forEach((block, i) => {
			if (isSkippedBlock(block)) {
				elements.push(
					<div key={`custom-${i}`} className="my-4 p-3 border border-dashed border-gray-300 rounded bg-gray-50 text-center text-sm text-gray-500">
						{block.name.replace('snel/', '')}
					</div>
				);
			} else {
				if (chunks[textIndex]) {
					elements.push(
						<RawHTML key={`chunk-${textIndex}`}>{chunks[textIndex]}</RawHTML>
					);
				}
				textIndex++;
			}
		});

		return elements;
	};

	return (
		<>
			<InspectorControls>
				<BgColorControl
					value={backgroundColor}
					onChange={(v) => setAttributes({ backgroundColor: v })}
				/>
				<PanelBody title={__('Layout', 'snel')} initialOpen={false}>
					<SelectControl
						label={__('Content Width', 'snel')}
						value={maxWidth}
						options={MAX_WIDTH_OPTIONS}
						onChange={(v) => setAttributes({ maxWidth: v })}
					/>
					<ToggleControl
						label={__('Vertical Padding', 'snel')}
						checked={verticalPadding}
						onChange={(v) => setAttributes({ verticalPadding: v })}
					/>
				</PanelBody>
			</InspectorControls>
			<TranslatableWrapper blockProps={blockProps} label="Content Section" onTranslate={handleTranslate} fullWidth>
				{({ currentLang }) => (
					<div className={`${maxWidthClass} mx-auto`}>
						<div className="prose prose-lg max-w-none">
							{currentLang === 'nl' ? (
								<InnerBlocks
									allowedBlocks={ALLOWED_BLOCKS}
									templateLock={false}
								/>
							) : (
								renderTranslatedView(currentLang)
							)}
						</div>
					</div>
				)}
			</TranslatableWrapper>
		</>
	);
}
