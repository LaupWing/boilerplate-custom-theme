import './editor.css';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { serialize } from '@wordpress/blocks';
import EditorWrapper from '../components/EditorWrapper';
import { translateTexts } from '../components/lang-helpers';

const ALLOWED_BLOCKS = [
	'core/paragraph',
	'core/list',
	'core/heading',
	'core/image',
	'core/separator',
	'core/columns',
	'core/column',
];

const BG_OPTIONS = [
	{ label: 'White', value: 'white' },
	{ label: 'Light', value: 'light' },
	{ label: 'Dark', value: 'dark' },
];

const BG_COLORS = {
	white: '#ffffff',
	light: '#f5f5f5',
	dark: '#1a1a2e',
};

export default function Edit({ attributes, setAttributes, clientId }) {
	const { contentTranslations, bgMode } = attributes;
	const blockProps = useBlockProps();

	const defaultLang = window.snelTranslate?.default || 'nl';
	const langs = window.snelTranslate?.langs || ['nl', 'en'];
	const nonDefaultLangs = langs.filter((l) => l !== defaultLang);

	// Get inner blocks so we can serialize them for translation
	const innerBlocks = useSelect(
		(select) => select('core/block-editor').getBlocks(clientId),
		[clientId]
	);

	const handleTranslate = async (targetLang) => {
		const html = serialize(innerBlocks);
		if (!html) return;

		const cleanHtml = html.replace(/<!--.*?-->/gs, '').replace(/\n+/g, ' ').replace(/\s+/g, ' ').trim();
		if (!cleanHtml) return;

		const tr = await translateTexts([cleanHtml], targetLang);
		if (tr && tr[0]) {
			setAttributes({
				contentTranslations: {
					...contentTranslations,
					[targetLang]: tr[0],
				},
			});
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title="Background">
					<SelectControl
						label="Background"
						value={bgMode}
						options={BG_OPTIONS}
						onChange={(value) => setAttributes({ bgMode: value })}
					/>
				</PanelBody>
				{nonDefaultLangs.map((lang) => (
					<PanelBody
						key={lang}
						title={`${lang.toUpperCase()} Translation`}
						initialOpen={false}
					>
						<p style={{ fontSize: '12px', color: '#6b7280' }}>
							The {defaultLang.toUpperCase()} content comes from the blocks above.
							Use the translate button or edit the {lang.toUpperCase()} version manually here.
						</p>
						<TextareaControl
							label={`${lang.toUpperCase()} Content (HTML)`}
							value={contentTranslations[lang] || ''}
							onChange={(value) =>
								setAttributes({
									contentTranslations: {
										...contentTranslations,
										[lang]: value,
									},
								})
							}
							rows={10}
							style={{ fontFamily: 'monospace', fontSize: '12px' }}
						/>
					</PanelBody>
				))}
			</InspectorControls>

			<EditorWrapper
				blockProps={blockProps}
				label="Content Section"
				onTranslate={handleTranslate}
			>
				{({ currentLang }) => (
					<section style={{ backgroundColor: BG_COLORS[bgMode] || BG_COLORS.white, padding: '4rem 1rem' }}>
						<div style={{ maxWidth: '48rem', margin: '0 auto' }}>
							{currentLang === defaultLang ? (
								<div className="prose max-w-none">
									<InnerBlocks
										allowedBlocks={ALLOWED_BLOCKS}
										template={[['core/paragraph']]}
									/>
								</div>
							) : (
								<div
									className="prose max-w-none"
									style={{ padding: '1rem', minHeight: '100px' }}
								>
									{contentTranslations[currentLang] ? (
										<div dangerouslySetInnerHTML={{ __html: contentTranslations[currentLang] }} />
									) : (
										<p style={{ color: '#9ca3af', fontStyle: 'italic' }}>
											No {currentLang.toUpperCase()} translation yet. Click &quot;Translate&quot; to generate.
										</p>
									)}
								</div>
							)}
						</div>
					</section>
				)}
			</EditorWrapper>
		</>
	);
}
