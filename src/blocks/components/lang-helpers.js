/**
 * Shared language helpers for multilingual block attributes.
 *
 * getLang(val, lang)  — read a language value from a multilingual object
 * setLang(val, lang, text) — return a new object with the language value updated
 * translateTexts(texts, targetLang) — call the AW translate AJAX endpoint
 */

export function getLang(val, lang) {
	if (typeof val === 'object' && val !== null && !Array.isArray(val)) return val[lang] || '';
	return typeof val === 'string' ? (lang === 'nl' ? val : '') : '';
}

export function setLang(val, lang, text) {
	const obj = typeof val === 'object' && val !== null && !Array.isArray(val)
		? { ...val }
		: { nl: typeof val === 'string' ? val : '', en: '' };
	obj[lang] = text;
	return obj;
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

/**
 * Translate an array of NL strings to the target language via AJAX.
 *
 * @param {string[]} texts     Array of source (NL) strings.
 * @param {string}   targetLang  'en' or 'de'.
 * @returns {Promise<string[]>} Array of translated strings (same order).
 */
export async function translateTexts(texts, targetLang) {
	console.log(`[translateTexts] Called. targetLang=${targetLang}, texts count=${texts.length}`);
	console.log('[translateTexts] Input texts:', texts);
	if (!texts.length) {
		console.log('[translateTexts] No texts, returning empty array');
		return [];
	}

	const nonce = window.snelTranslate?.nonce || '';
	const ajaxUrl = window.snelTranslate?.ajaxUrl || '/wp-admin/admin-ajax.php';
	console.log(`[translateTexts] nonce=${nonce ? 'present' : 'MISSING'}, ajaxUrl=${ajaxUrl}`);

	const formData = new FormData();
	formData.append('action', 'snel_translate');
	formData.append('nonce', nonce);
	formData.append('source', 'nl');
	formData.append('target', targetLang);
	texts.forEach((t) => formData.append('texts[]', t));

	console.log('[translateTexts] Sending fetch request...');
	const res = await fetch(ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	});
	console.log(`[translateTexts] Fetch response status: ${res.status}`);

	const data = await res.json();
	console.log('[translateTexts] Response data:', data);

	if (!data.success || !data.data.translations) {
		console.error('[translateTexts] FAILED:', data.data || 'No translations in response');
		throw new Error(data.data || 'Translation failed');
	}

	console.log(`[translateTexts] Success! Got ${data.data.translations.length} translations`);
	console.log('[translateTexts] Translations:', data.data.translations);
	return data.data.translations;
}
