/**
 * Shared language helpers for multilingual block attributes.
 *
 * getLang(val, lang)          — read a language value from a multilingual object
 * setLang(val, lang, text)    — return a new object with the language value updated
 * translateTexts(texts, lang) — call the BP translate AJAX endpoint
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
	const defaultLang = window.bpTranslate?.default || 'nl';
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
	const langs = window.bpTranslate?.langs || ['nl', 'en'];

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
			const defaultLang = window.bpTranslate?.default || 'nl';
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

	const defaultLang = window.bpTranslate?.default || 'nl';

	const formData = new FormData();
	formData.append('action', 'bp_translate');
	formData.append('nonce', window.bpTranslate?.nonce || '');
	formData.append('source', defaultLang);
	formData.append('target', targetLang);
	texts.forEach((t) => formData.append('texts[]', t));

	const res = await fetch(window.bpTranslate?.ajaxUrl || '/wp-admin/admin-ajax.php', {
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
