/**
 * EditorWrapper — Shared wrapper for all custom Gutenberg blocks.
 *
 * Provides:
 * - blockProps on the outer div
 * - Editor badge with block label
 * - Language toggle (reads from snelTranslate.langs)
 * - "Translate" button with loading state
 *
 * Usage (render props pattern):
 *   <EditorWrapper
 *       blockProps={blockProps}
 *       label="Hero Block"
 *       subtitle="Slide 1/5"
 *       onTranslate={handleTranslate}
 *   >
 *       {({ currentLang }) => (
 *           <RichText value={getLang(heading, currentLang)} ... />
 *       )}
 *   </EditorWrapper>
 *
 * Usage (simple, no language):
 *   <EditorWrapper blockProps={blockProps} label="My Block">
 *       <div>content</div>
 *   </EditorWrapper>
 */
import { useState } from '@wordpress/element';

// Build language list from snelTranslate global (set by translate.php)
const LANGS = (window.snelTranslate?.langs || ['nl', 'en']).map((code) => ({
	code,
	label: code.toUpperCase(),
}));

const DEFAULT_LANG = window.snelTranslate?.default || 'nl';

export default function EditorWrapper({ blockProps, label, subtitle, onTranslate, children }) {
	const [currentLang, setCurrentLang] = useState(DEFAULT_LANG);
	const [isTranslating, setIsTranslating] = useState(false);

	const handleTranslate = async () => {
		if (!onTranslate || isTranslating) return;
		setIsTranslating(true);
		try {
			await onTranslate(currentLang);
		} catch (err) {
			console.error('Translation failed:', err);
		} finally {
			setIsTranslating(false);
		}
	};

	// Support both render props (function) and regular children
	const content = typeof children === 'function'
		? children({ currentLang })
		: children;

	return (
		<div {...blockProps} style={{ ...blockProps.style, border: '2px dashed #d4d2cd', paddingLeft: 0, paddingRight: 0 }}>
			{content}

			{/* Editor Badge — top right */}
			<div
				className="absolute top-4 right-4 z-50 pointer-events-auto flex items-center gap-2 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 shadow-sm"
				style={{ fontSize: '13px' }}
			>
				{/* Block label */}
				<span className="font-medium text-gray-800">
					{label}{subtitle ? ` — ${subtitle}` : ''}
				</span>

				{/* Divider */}
				<span className="w-px h-4 bg-gray-300 inline-block" />

				{/* Language toggle */}
				{LANGS.map(({ code, label: langLabel }) => (
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
				))}

				{/* Translate button — only shows on non-default language */}
				{onTranslate && currentLang !== DEFAULT_LANG && (
					<>
						<span className="w-px h-4 bg-gray-300 inline-block" />
						<button
							type="button"
							onClick={handleTranslate}
							disabled={isTranslating}
							className={`px-2 py-1 rounded text-xs font-bold cursor-pointer transition-colors ${
								isTranslating
									? 'text-gray-400 cursor-wait'
									: 'text-blue-600 hover:bg-blue-50'
							}`}
						>
							{isTranslating ? 'Translating...' : '⟳ Translate'}
						</button>
					</>
				)}
			</div>
		</div>
	);
}
