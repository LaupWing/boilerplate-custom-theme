/**
 * TranslatableWrapper — Shared wrapper for all custom Gutenberg blocks.
 *
 * Provides:
 * - blockProps on the outer div
 * - Editor notice badge with block label
 * - Language toggle (NL / EN)
 * - "Generate Translation" button with animated per-language progress
 * - Layout width + background color in Inspector Controls
 * - Full-width bg section with max-width content container
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { BG_EDITOR_STYLES } from './BgColorControl';
import InspectorOptions from './InspectorOptions';
import '../../shared/editor-theme.css';

const LANGS = [
	{ code: 'nl', label: 'NL' },
	{ code: 'en', label: 'EN' },
];

const LAYOUT_WIDTH_CLASSES = {
	wide: 'max-w-6xl mx-auto px-4 sm:px-6 lg:px-8',
	narrow: 'max-w-4xl mx-auto px-4 sm:px-6 lg:px-8',
};

export default function TranslatableWrapper({ blockProps, label, subtitle, onTranslate, attributes, setAttributes, fullWidth, children }) {
	const [currentLang, setCurrentLang] = useState('nl');
	const [isTranslating, setIsTranslating] = useState(false);
	const [buttonState, setButtonState] = useState(null);
	const [animStyle, setAnimStyle] = useState({});

	const layoutWidth = attributes?.layoutWidth || 'wide';
	const bgColor = attributes?.bgColor || 'white';
	const bgStyle = BG_EDITOR_STYLES[bgColor] || BG_EDITOR_STYLES.white;

	const animateButtonText = (text, phase) => {
		return new Promise((resolve) => {
			setAnimStyle({ transform: 'translateY(-100%)', opacity: 0, transition: 'all 0.2s ease-in' });
			setTimeout(() => {
				setButtonState({ text, phase });
				setAnimStyle({ transform: 'translateY(100%)', opacity: 0, transition: 'none' });
				requestAnimationFrame(() => {
					requestAnimationFrame(() => {
						setAnimStyle({ transform: 'translateY(0)', opacity: 1, transition: 'all 0.25s ease-out' });
						setTimeout(resolve, 250);
					});
				});
			}, 200);
		});
	};

	const handleTranslate = async () => {
		if (!onTranslate || isTranslating) return;
		setIsTranslating(true);

		try {
			if (currentLang === 'nl') {
				const langs = LANGS.filter(l => l.code !== 'nl');
				for (const { code, label: langLabel } of langs) {
					await animateButtonText(`Translating ${langLabel}...`, 'translating');
					await onTranslate(code);
					await animateButtonText(`${langLabel} ✓`, 'done');
					await new Promise(r => setTimeout(r, 600));
				}
				await animateButtonText('All done ✓', 'done');
				await new Promise(r => setTimeout(r, 1200));
			} else {
				const langLabel = LANGS.find(l => l.code === currentLang)?.label || currentLang.toUpperCase();
				await animateButtonText(`Translating ${langLabel}...`, 'translating');
				await onTranslate(currentLang);
				await animateButtonText(`${langLabel} ✓`, 'done');
				await new Promise(r => setTimeout(r, 1000));
			}
		} catch (err) {
			console.error('[TranslatableWrapper] Translation error:', err);
		} finally {
			setButtonState(null);
			setAnimStyle({});
			setIsTranslating(false);
		}
	};

	const content = typeof children === 'function'
		? children({ currentLang })
		: children;

	// Full-width blocks or blocks without attributes skip the section/max-width container
	const inner = (fullWidth || !attributes) ? content : (
		<section style={{ backgroundColor: bgStyle, padding: '4rem 0' }}>
			<div className={LAYOUT_WIDTH_CLASSES[layoutWidth] || ''}>
				{content}
			</div>
		</section>
	);

	return (
		<>
			{setAttributes && !fullWidth && (
				<InspectorOptions attributes={attributes} setAttributes={setAttributes} />
			)}

			<div {...blockProps} className={`${blockProps.className || ''} border-2 border-dashed border-gray-300 !px-0`}>
				{inner}

				{/* Editor Badge — top right */}
				<div className="absolute top-4 right-4 z-50 pointer-events-auto flex items-center gap-2 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 shadow-sm text-[13px]">
					<span className="font-medium text-gray-800">
						{label}{subtitle ? ` — ${subtitle}` : ''}
					</span>

					<span className="w-px h-4 bg-gray-300 inline-block" />

					{ LANGS.length <= 2 ? (
						LANGS.map(({ code, label: langLabel }) => (
							<button
								key={code}
								type="button"
								onClick={() => setCurrentLang(code)}
								className={`px-2 py-1 rounded text-xs font-bold cursor-pointer transition-colors ${
									currentLang === code
										? 'bg-gray-800 text-white'
										: 'text-gray-500 hover:text-gray-800 hover:bg-gray-100'
								}`}
							>
								{langLabel}
							</button>
						))
					) : (
						<SelectControl
							value={currentLang}
							options={LANGS.map(({ code, label: langLabel }) => ({ label: langLabel, value: code }))}
							onChange={setCurrentLang}
							__nextHasNoMarginBottom
							style={{ minWidth: '70px', height: '30px', minHeight: 'unset' }}
						/>
					) }

					{onTranslate && (
						<>
							<span className="w-px h-4 bg-gray-300 inline-block" />
							<button
								type="button"
								onClick={handleTranslate}
								disabled={isTranslating}
								className={`min-w-[120px] px-2.5 py-1 rounded text-[11px] font-semibold text-white bg-blue-600 hover:bg-blue-700 border-none overflow-hidden h-[26px] inline-flex items-center justify-center transition-colors ${
									isTranslating ? 'cursor-wait' : 'cursor-pointer'
								}`}
							>
								{buttonState ? (
									<span
										className={`inline-block ${buttonState.phase === 'translating' ? 'animate-pulse' : ''}`}
										style={animStyle}
									>
										{buttonState.phase === 'translating' && '✦ '}
										{buttonState.text}
									</span>
								) : (
									<span>
										{currentLang === 'nl'
											? `✦ ${__('Translate All', 'snel')}`
											: `✦ ${__('Translate', 'snel')}`
										}
									</span>
								)}
							</button>
						</>
					)}
				</div>
			</div>
		</>
	);
}
