/**
 * Shared language helpers for multilingual block attributes.
 *
 * getLang(val, lang)          — read a language value from a multilingual object
 * setLang(val, lang, text)    — return a new object with the language value updated
 * translateTexts(texts, lang) — call the snel translate AJAX endpoint
 * translateBlockAttributes()  — translate block attributes by key
 */

/**
 * Read a language value from a multilingual object.
 *
 * @param {object|string} val  Either {nl: '...', en: '...'} or a plain string.
 * @param {string}        lang Language code (e.g., 'en').
 * @returns {string}
 */
export function getLang(val, lang) {
	if (typeof val === 'object' && val !== null && !Array.isArray(val)) {
		return val[lang] || '';
	}
	const defaultLang = window.snelTranslate?.default || 'nl';
	return typeof val === 'string' ? (lang === defaultLang ? val : '') : '';
}

/**
 * Return a new multilingual object with one language value updated.
 * If val is a plain string, it initializes a proper multilingual object first.
 *
 * @param {object|string} val  Current value.
 * @param {string}        lang Language code to update.
 * @param {string}        text New text for that language.
 * @returns {object}
 */
export function setLang(val, lang, text) {
	const langs = window.snelTranslate?.langs || ['nl', 'en'];

	let obj;
	if (typeof val === 'object' && val !== null && !Array.isArray(val)) {
		obj = { ...val };
	} else {
		// Initialize empty object for all supported languages
		obj = {};
		langs.forEach((l) => {
			obj[l] = '';
		});
		// If val was a plain string, assign it to the default language
		if (typeof val === 'string') {
			const defaultLang = window.snelTranslate?.default || 'nl';
			obj[defaultLang] = val;
		}
	}

	obj[lang] = text;
	return obj;
}

/**
 * Translate an array of strings to the target language via AJAX.
 *
 * @param {string[]} texts      Array of source strings (default language).
 * @param {string}   targetLang Target language code (e.g., 'en').
 * @returns {Promise<string[]>} Array of translated strings (same order).
 */
export async function translateTexts(texts, targetLang) {
	if (!texts.length) return [];

	const defaultLang = window.snelTranslate?.default || 'nl';

	const formData = new FormData();
	formData.append('action', 'snel_translate');
	formData.append('nonce', window.snelTranslate?.nonce || '');
	formData.append('source', defaultLang);
	formData.append('target', targetLang);
	texts.forEach((t) => formData.append('texts[]', t));

	const res = await fetch(window.snelTranslate?.ajaxUrl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	});
	const data = await res.json();

	if (!data.success || !data.data.translations) {
		throw new Error(data.data || 'Translation failed');
	}

	return data.data.translations;
}

/**
 * Translate block attributes by key. Handles fresh state reads to avoid stale
 * props when translating multiple languages sequentially.
 *
 * @param {string[]}  attrKeys      Attribute keys to translate (e.g. ['heading', 'subtext']).
 * @param {string}    targetLang    Target language code.
 * @param {string}    clientId      Block clientId for reading fresh attributes.
 * @param {Function}  setAttributes Block setAttributes function.
 */
export async function translateBlockAttributes(attrKeys, targetLang, clientId, setAttributes) {
	const { select } = wp.data;
	const attrs = select('core/block-editor').getBlockAttributes(clientId);

	const texts = [];
	const keys = [];

	attrKeys.forEach((key) => {
		const nl = getLang(attrs[key], 'nl');
		if (nl) {
			texts.push(nl);
			keys.push(key);
		}
	});

	if (texts.length === 0) return;

	const tr = await translateTexts(texts, targetLang);

	// Re-read fresh attrs after async API call.
	const freshAttrs = select('core/block-editor').getBlockAttributes(clientId);
	const updates = {};
	tr.forEach((translated, i) => {
		updates[keys[i]] = setLang(freshAttrs[keys[i]], targetLang, translated);
	});

	setAttributes(updates);
}
