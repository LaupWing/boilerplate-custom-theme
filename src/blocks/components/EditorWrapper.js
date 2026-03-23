/**
 * EditorWrapper — Shared wrapper for all custom Gutenberg blocks.
 *
 * Provides:
 * - blockProps on the outer div
 * - Editor notice badge with block label
 * - Language toggle (reads from snelTranslate.langs)
 * - "Generate Translation" button with animated per-language progress
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

// Build language list from snelTranslate global (set by translate.php)
const LANGS = (window.snelTranslate?.langs || ['nl', 'en']).map((code) => ({
	code,
	label: code.toUpperCase(),
}));

const DEFAULT_LANG = window.snelTranslate?.default || 'nl';

const MAX_WIDTH_CLASSES = {
	narrow: 'max-w-3xl mx-auto',
	wide: 'max-w-5xl mx-auto',
};

export default function EditorWrapper({ blockProps, label, subtitle, onTranslate, maxWidth, children }) {
	const [currentLang, setCurrentLang] = useState(DEFAULT_LANG);
	const [isTranslating, setIsTranslating] = useState(false);
	const [buttonState, setButtonState] = useState(null);
	const [animStyle, setAnimStyle] = useState({});

	const animateButtonText = (text, phase) => {
		return new Promise((resolve) => {
			// Slide out upward
			setAnimStyle({ transform: 'translateY(-100%)', opacity: 0, transition: 'all 0.2s ease-in' });
			setTimeout(() => {
				// Set new text, position below
				setButtonState({ text, phase });
				setAnimStyle({ transform: 'translateY(100%)', opacity: 0, transition: 'none' });
				requestAnimationFrame(() => {
					requestAnimationFrame(() => {
						// Slide in from below
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
			if (currentLang === DEFAULT_LANG) {
				const langs = LANGS.filter(l => l.code !== DEFAULT_LANG);
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
			console.error('[EditorWrapper] Translation error:', err);
		} finally {
			setButtonState(null);
			setAnimStyle({});
			setIsTranslating(false);
		}
	};

	const content = typeof children === 'function'
		? children({ currentLang })
		: children;

	const inner = maxWidth ? (
		<div className={MAX_WIDTH_CLASSES[maxWidth] || ''}>{content}</div>
	) : content;

	return (
		<div {...blockProps} className={`${blockProps.className || ''} border-2 border-dashed border-border-light !px-0`}>
			{inner}

			{/* Editor Badge — top right */}
			<div className="absolute top-4 right-4 z-50 pointer-events-auto flex items-center gap-2 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 shadow-sm text-[13px]">
				{/* Block label */}
				<span className="font-medium text-gray-800">
					{label}{subtitle ? ` — ${subtitle}` : ''}
				</span>

				<span className="w-px h-4 bg-gray-300 inline-block" />

				{/* Language select */}
				<SelectControl
					value={currentLang}
					options={LANGS.map(({ code, label: langLabel }) => ({ label: langLabel, value: code }))}
					onChange={setCurrentLang}
					__nextHasNoMarginBottom
					style={{ minWidth: '70px', height: '30px', minHeight: 'unset' }}
				/>

				{/* Translate button */}
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
									{currentLang === DEFAULT_LANG
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
	);
}
