/**
 * Snelstack Editor Sidebar — Tabbed sidebar for all Snel plugins.
 *
 * Tabs:
 * - Translations: language picker, translate all, modal for detailed editing
 * - (More tabs added later: SEO, etc.)
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { PanelBody, TextareaControl, Button, SelectControl, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { translateTexts } from '../../blocks/components/lang-helpers';
import '../../blocks/components/content-extractor';

// ─── Helpers ────────────────────────────────────────────────────────────────

function getLangs() {
	const langs = window.snelTranslate?.langs || ['nl', 'en'];
	const defaultLang = window.snelTranslate?.default || 'nl';
	return langs.map((code) => ({
		label: code === defaultLang ? `${code.toUpperCase()} (bron)` : code.toUpperCase(),
		value: code,
		disabled: code === defaultLang,
	}));
}

function isTranslatable(val) {
	if (!val || typeof val !== 'object' || Array.isArray(val)) return false;
	return typeof val.nl === 'string';
}

function stripTags(html) {
	if (!html) return '';
	const div = document.createElement('div');
	div.innerHTML = html;
	return div.textContent || '';
}

function prettyBlockName(blockName) {
	const short = blockName.replace('snel/', '').replace('core/', '');
	return short.split('-').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

// ─── Hooks ──────────────────────────────────────────────────────────────────

function useTranslatableBlocks() {
	return useSelect((select) => {
		const blocks = select('core/block-editor').getBlocks();
		const result = [];

		blocks.forEach((block) => {
			if (!block.name.startsWith('snel/')) return;

			const fields = [];
			const attrs = block.attributes || {};

			Object.keys(attrs).forEach((key) => {
				if (isTranslatable(attrs[key])) {
					fields.push({ key, value: attrs[key] });
				}

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
}

function useUpdateField() {
	const { updateBlockAttributes } = useDispatch('core/block-editor');

	return (block, field, lang, newText) => {
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
}

// ─── Translation Modal ──────────────────────────────────────────────────────

function TranslationModal({ targetLang, translatableBlocks, updateField, onClose }) {
	return (
		<Modal title={__('Block Translations', 'snel')} onRequestClose={onClose} isFullScreen>
			<div style={{ maxWidth: 900, margin: '0 auto', padding: '16px 0' }}>
				{translatableBlocks.length === 0 && (
					<p style={{ color: '#999', textAlign: 'center', padding: '40px 0' }}>
						{__('No translatable blocks found on this page.', 'snel')}
					</p>
				)}

				{translatableBlocks.map((block) => (
					<div key={block.clientId} style={{ marginBottom: 32, border: '1px solid #e0e0e0', borderRadius: 8, overflow: 'hidden' }}>
						<div style={{ padding: '12px 20px', background: '#f9f9f9', borderBottom: '1px solid #e0e0e0' }}>
							<strong style={{ fontSize: 14 }}>{prettyBlockName(block.name)}</strong>
							<span style={{ marginLeft: 8, fontSize: 12, color: '#999' }}>
								{block.fields.filter((f) => f.value.nl).length} fields
							</span>
						</div>

						<div style={{ padding: 20 }}>
							{block.fields.map((field) => {
								const nlText = field.value.nl || '';
								const currentText = field.value[targetLang] || '';
								const fieldLabel = field.subKey || field.key;

								if (!nlText) return null;

								return (
									<div key={field.key} style={{ marginBottom: 20, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, alignItems: 'start' }}>
										<div>
											<p style={{ fontSize: 11, fontWeight: 600, color: '#999', marginBottom: 4, textTransform: 'uppercase' }}>
												{fieldLabel} — NL
											</p>
											<div style={{ padding: '10px 12px', background: '#f5f5f5', borderRadius: 6, fontSize: 13, color: '#333', lineHeight: 1.5, minHeight: 40 }}>
												{stripTags(nlText)}
											</div>
										</div>
										<div>
											<p style={{ fontSize: 11, fontWeight: 600, color: '#3b82f6', marginBottom: 4, textTransform: 'uppercase' }}>
												{fieldLabel} — {targetLang.toUpperCase()}
											</p>
											<TextareaControl
												value={currentText}
												onChange={(val) => updateField(block, field, targetLang, val)}
												placeholder={__('Translation...', 'snel')}
												rows={Math.min(Math.max(Math.ceil(nlText.length / 60), 2), 6)}
												__nextHasNoMarginBottom
											/>
										</div>
									</div>
								);
							})}
						</div>
					</div>
				))}
			</div>
		</Modal>
	);
}

// ─── Translations Tab ───────────────────────────────────────────────────────

function TranslationsTab() {
	const [targetLang, setTargetLang] = useState('en');
	const [isTranslating, setIsTranslating] = useState(false);
	const [status, setStatus] = useState('');
	const [isModalOpen, setIsModalOpen] = useState(false);

	const translatableBlocks = useTranslatableBlocks();
	const updateField = useUpdateField();

	let totalFields = 0;
	let missingFields = 0;
	translatableBlocks.forEach((block) => {
		block.fields.forEach((field) => {
			if (field.value.nl) {
				totalFields++;
				if (!field.value[targetLang]) missingFields++;
			}
		});
	});

	const handleTranslateAll = async () => {
		setIsTranslating(true);
		setStatus('');

		const toTranslate = [];
		translatableBlocks.forEach((block) => {
			block.fields.forEach((field) => {
				const nlText = field.value.nl || '';
				const targetText = field.value[targetLang] || '';
				if (nlText && !targetText) {
					toTranslate.push({ block, field, nlText });
				}
			});
		});

		if (toTranslate.length === 0) {
			setStatus(__('All fields are already translated!', 'snel'));
			setIsTranslating(false);
			return;
		}

		try {
			const texts = toTranslate.map((t) => t.nlText);
			const translated = await translateTexts(texts, targetLang);

			translated.forEach((text, i) => {
				const { block, field } = toTranslate[i];
				updateField(block, field, targetLang, text);
			});

			setStatus(`${translated.length} ${__('fields translated!', 'snel')}`);
		} catch (err) {
			setStatus(`${__('Error:', 'snel')} ${err.message}`);
		}

		setIsTranslating(false);
	};

	return (
		<div style={{ padding: '0' }}>
			<SelectControl
				label={__('Target Language', 'snel')}
				value={targetLang}
				options={getLangs()}
				onChange={setTargetLang}
				__nextHasNoMarginBottom
			/>
			<p style={{ marginTop: 4, marginBottom: 12, fontSize: 12, color: '#999' }}>
				{totalFields} {__('fields', 'snel')}, {missingFields} {__('missing', 'snel')}
			</p>

			<Button
				variant="primary"
				onClick={handleTranslateAll}
				isBusy={isTranslating}
				disabled={isTranslating || missingFields === 0}
				style={{ width: '100%', justifyContent: 'center', marginBottom: 8 }}
			>
				{isTranslating
					? __('Translating...', 'snel')
					: `✦ ${__('Translate All Missing', 'snel')} (${missingFields})`}
			</Button>

			<Button
				variant="secondary"
				onClick={() => setIsModalOpen(true)}
				style={{ width: '100%', justifyContent: 'center' }}
				disabled={translatableBlocks.length === 0}
			>
				{__('Open Translations', 'snel')}
			</Button>

			{status && (
				<p style={{ marginTop: 8, fontSize: 12, color: '#666' }}>{status}</p>
			)}

			{translatableBlocks.length === 0 && (
				<p style={{ color: '#999', fontSize: 13, marginTop: 12 }}>
					{__('No translatable blocks found on this page.', 'snel')}
				</p>
			)}

			{isModalOpen && (
				<TranslationModal
					targetLang={targetLang}
					translatableBlocks={translatableBlocks}
					updateField={updateField}
					onClose={() => setIsModalOpen(false)}
				/>
			)}
		</div>
	);
}

// ─── Sidebar Content ────────────────────────────────────────────────────────

function SidebarContent() {
	return (
		<>
			<PanelBody title={__('Translations', 'snel')} initialOpen icon="translation">
				<TranslationsTab />
			</PanelBody>
		</>
	);
}

// ─── Icon & Plugin Registration ─────────────────────────────────────────────

const SnelIcon = () => (
	<span className="snel-editor-icon" style={{ display: 'inline-block', width: 20, height: 20, borderRadius: '50%', position: 'relative', overflow: 'hidden', background: 'linear-gradient(135deg, #3b82f6, #7c3aed)' }}>
		<span className="snel-gradient-ring" style={{ display: 'none', position: 'absolute', top: '50%', left: '50%', width: 30, height: 30, background: 'conic-gradient(from 0deg, #06b6d4, #3b82f6, #8b5cf6, #d946ef, #f43f5e, #f97316, #eab308, #22c55e, #06b6d4)', animation: 'snel-editor-gradient-spin 3s linear infinite', zIndex: 1 }} />
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: 14, height: 14, zIndex: 2 }}>
			<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" fill="#fff" />
		</svg>
	</span>
);

registerPlugin('snel-editor-sidebar', {
	render: () => (
		<>
			<style>{`
				@keyframes snel-editor-gradient-spin {
					0%   { transform: translate(-50%, -50%) rotate(0deg); }
					100% { transform: translate(-50%, -50%) rotate(360deg); }
				}
				button.components-button[aria-label="Snel Stack"].is-pressed .snel-editor-icon,
				button.components-button[aria-label="Snel Stack"][aria-pressed="true"] .snel-editor-icon,
				button.components-button[aria-label="Snel Stack"][aria-expanded="true"] .snel-editor-icon {
					overflow: hidden;
				}
				button.components-button[aria-label="Snel Stack"].is-pressed .snel-editor-icon .snel-gradient-ring,
				button.components-button[aria-label="Snel Stack"][aria-pressed="true"] .snel-editor-icon .snel-gradient-ring,
				button.components-button[aria-label="Snel Stack"][aria-expanded="true"] .snel-editor-icon .snel-gradient-ring {
					display: block !important;
				}
			`}</style>
			<PluginSidebar
				name="snel-editor-sidebar"
				title={__('Snel Stack', 'snel')}
				icon={<SnelIcon />}
				isPinnable={true}
			>
				<SidebarContent />
			</PluginSidebar>
		</>
	),
});
