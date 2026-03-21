/**
 * Translation Sidebar — PluginSidebar for translating all blocks on a page.
 *
 * Shows all translatable fields from all blocks in one panel.
 * Pick a language tab → see/edit all translations for that language.
 * "Translate All" fills in missing translations via AI.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { PanelBody, TextareaControl, Button, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { translateTexts } from '../../blocks/components/lang-helpers';

/**
 * Build LANGS array dynamically from bpTranslate.langs.
 */
function getLangs() {
	const langs = window.bpTranslate?.langs || ['nl', 'en'];
	const defaultLang = window.bpTranslate?.default || 'nl';
	return langs.map((code) => ({
		label: code === defaultLang ? `${code.toUpperCase()} (bron)` : code.toUpperCase(),
		value: code,
		disabled: code === defaultLang,
	}));
}

/**
 * Check if a value is a translatable object: { nl: '...', en: '...', ... }
 */
function isTranslatable(val) {
	if (!val || typeof val !== 'object' || Array.isArray(val)) return false;
	const defaultLang = window.bpTranslate?.default || 'nl';
	return typeof val[defaultLang] === 'string';
}

/**
 * Strip HTML tags for display (keep it readable in sidebar).
 */
function stripTags(html) {
	if (!html) return '';
	const div = document.createElement('div');
	div.innerHTML = html;
	return div.textContent || '';
}

/**
 * Get a human-readable block name from blockName.
 * "boilerplate/content-section" → "Content Section"
 */
function prettyBlockName(blockName) {
	const short = blockName.replace(/^[^/]+\//, '');
	return short
		.split('-')
		.map((w) => w.charAt(0).toUpperCase() + w.slice(1))
		.join(' ');
}

function TranslationSidebarContent() {
	const LANGS = getLangs();
	const defaultLang = window.bpTranslate?.default || 'nl';
	const firstNonDefault = LANGS.find((l) => l.value !== defaultLang);
	const [targetLang, setTargetLang] = useState(firstNonDefault?.value || 'en');
	const [isTranslating, setIsTranslating] = useState(false);
	const [status, setStatus] = useState('');

	const { updateBlockAttributes } = useDispatch('core/block-editor');

	// Get all blocks and find translatable fields
	const translatableBlocks = useSelect((select) => {
		const blocks = select('core/block-editor').getBlocks();
		const result = [];

		blocks.forEach((block) => {
			const fields = [];
			const attrs = block.attributes || {};

			Object.keys(attrs).forEach((key) => {
				if (isTranslatable(attrs[key])) {
					fields.push({ key, value: attrs[key] });
				}

				// Check arrays (like slides) for nested translatable objects
				if (Array.isArray(attrs[key])) {
					attrs[key].forEach((item, idx) => {
						if (!item || typeof item !== 'object') return;
						Object.keys(item).forEach((subKey) => {
							if (isTranslatable(item[subKey])) {
								fields.push({
									key: `${key}[${idx}].${subKey}`,
									arrayKey: key,
									arrayIndex: idx,
									subKey,
									value: item[subKey],
								});
							}
						});
					});
				}
			});

			if (fields.length > 0) {
				result.push({ clientId: block.clientId, name: block.name, attributes: attrs, fields });
			}
		});

		return result;
	}, []);

	// Update a field value
	const updateField = (block, field, lang, newText) => {
		if (field.arrayKey) {
			const arr = [...block.attributes[field.arrayKey]];
			arr[field.arrayIndex] = {
				...arr[field.arrayIndex],
				[field.subKey]: { ...field.value, [lang]: newText },
			};
			updateBlockAttributes(block.clientId, { [field.arrayKey]: arr });
		} else {
			updateBlockAttributes(block.clientId, {
				[field.key]: { ...field.value, [lang]: newText },
			});
		}
	};

	// Translate all missing fields
	const handleTranslateAll = async () => {
		setIsTranslating(true);
		setStatus('');

		const toTranslate = [];

		translatableBlocks.forEach((block) => {
			block.fields.forEach((field) => {
				const srcText = field.value[defaultLang] || '';
				const targetText = field.value[targetLang] || '';
				if (srcText && !targetText) {
					toTranslate.push({ block, field, srcText });
				}
			});
		});

		if (toTranslate.length === 0) {
			setStatus(__('All fields are already translated!', 'boilerplate'));
			setIsTranslating(false);
			return;
		}

		try {
			const texts = toTranslate.map((t) => t.srcText);
			const translated = await translateTexts(texts, targetLang);

			translated.forEach((text, i) => {
				const { block, field } = toTranslate[i];
				updateField(block, field, targetLang, text);
			});

			setStatus(`${translated.length} ${__('fields translated!', 'boilerplate')}`);
		} catch (err) {
			setStatus(`${__('Error:', 'boilerplate')} ${err.message}`);
		}

		setIsTranslating(false);
	};

	// Count missing translations
	let totalFields = 0;
	let missingFields = 0;
	translatableBlocks.forEach((block) => {
		block.fields.forEach((field) => {
			if (field.value[defaultLang]) {
				totalFields++;
				if (!field.value[targetLang]) missingFields++;
			}
		});
	});

	return (
		<>
			<PanelBody title={__('Language', 'boilerplate')} initialOpen>
				<SelectControl
					value={targetLang}
					options={LANGS}
					onChange={setTargetLang}
					__nextHasNoMarginBottom
				/>
				<div style={{ marginTop: '8px', display: 'flex', alignItems: 'center', gap: '8px' }}>
					<Button
						variant="primary"
						onClick={handleTranslateAll}
						isBusy={isTranslating}
						disabled={isTranslating || missingFields === 0}
					>
						{isTranslating
							? __('Translating...', 'boilerplate')
							: `${__('Translate All Missing', 'boilerplate')} (${missingFields})`}
					</Button>
				</div>
				{status && (
					<p style={{ marginTop: '8px', fontSize: '12px', color: '#666' }}>{status}</p>
				)}
				<p style={{ marginTop: '8px', fontSize: '12px', color: '#999' }}>
					{totalFields} {__('fields', 'boilerplate')}, {missingFields} {__('missing', 'boilerplate')}
				</p>
			</PanelBody>

			{translatableBlocks.map((block) => (
				<PanelBody
					key={block.clientId}
					title={prettyBlockName(block.name)}
					initialOpen={false}
				>
					{block.fields.map((field) => {
						const srcText = field.value[defaultLang] || '';
						const currentText = field.value[targetLang] || '';
						const fieldLabel = field.subKey || field.key;

						if (!srcText) return null;

						return (
							<div key={field.key} style={{ marginBottom: '16px' }}>
								<p style={{ fontSize: '11px', fontWeight: 600, color: '#1e1e1e', marginBottom: '4px', textTransform: 'uppercase' }}>
									{fieldLabel}
								</p>
								<p style={{ fontSize: '12px', color: '#757575', marginBottom: '6px', fontStyle: 'italic' }}>
									{defaultLang.toUpperCase()}: {stripTags(srcText).substring(0, 100)}{stripTags(srcText).length > 100 ? '...' : ''}
								</p>
								<TextareaControl
									value={currentText}
									onChange={(val) => updateField(block, field, targetLang, val)}
									placeholder={__('Translation...', 'boilerplate')}
									rows={Math.min(Math.max(Math.ceil(srcText.length / 60), 2), 6)}
									__nextHasNoMarginBottom
								/>
							</div>
						);
					})}
				</PanelBody>
			))}

			{translatableBlocks.length === 0 && (
				<PanelBody>
					<p style={{ color: '#999' }}>{__('No translatable blocks found on this page.', 'boilerplate')}</p>
				</PanelBody>
			)}
		</>
	);
}

registerPlugin('bp-translation-sidebar', {
	render: () => (
		<PluginSidebar
			name="bp-translation-sidebar"
			title={__('Translations', 'boilerplate')}
			icon="translation"
		>
			<TranslationSidebarContent />
		</PluginSidebar>
	),
});
