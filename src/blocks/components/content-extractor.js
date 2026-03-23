/**
 * Shared content extractor for multilingual blocks.
 *
 * Extracts plain-text content from Gutenberg blocks for a given language.
 * Handles three patterns:
 *   1. Translation objects: { nl: "...", en: "...", de: "..." }
 *   2. contentTranslations: { en: ["<p>chunk</p>", ...] } (InnerBlocks pattern)
 *   3. Plain strings: core block content attributes
 *
 * Usage:
 *   import { extractContent } from './content-extractor';
 *   const texts = extractContent( blocks, 'en' );
 *
 * Also exposed globally as window.snelExtractContent for plugins.
 */

/**
 * Strip HTML tags and decode entities.
 */
function stripHtml( html ) {
	if ( ! html ) return '';
	const doc = new DOMParser().parseFromString( html, 'text/html' );
	return ( doc.body.textContent || '' ).trim();
}

/**
 * Check if a value is a translation object (has a 'nl' key with a string value).
 */
function isTranslationObject( val ) {
	return typeof val === 'object' && val !== null && ! Array.isArray( val ) && typeof val.nl === 'string';
}

/**
 * Text attribute keys to look for on blocks.
 */
const TEXT_ATTRS = [ 'content', 'heading', 'tagline', 'subtext', 'title', 'line1', 'line2', 'label', 'buttonText',
	'interestHeading', 'interestText', 'formHeading', 'formTagline',
	'labelAddress', 'labelPhone', 'labelOpeningHours', 'labelName', 'labelSubject', 'labelMessage',
	'buttonText', 'openingHoursLine1', 'openingHoursLine2' ];

/**
 * Extract plain-text content from blocks for a given language.
 *
 * @param {Array}  blocks  Array of block objects from the editor.
 * @param {string} lang    Language code (e.g. 'nl', 'en', 'de').
 * @returns {string[]}     Array of plain-text strings (no HTML).
 */
export function extractContent( blocks, lang ) {
	const results = [];

	for ( const block of blocks ) {
		const attrs = block.attributes || {};

		// 1. Check contentTranslations (InnerBlocks pattern, e.g. rich-content-section)
		//    For non-Dutch, use stored chunks. For Dutch, fall through to innerBlocks.
		let hasTranslatedContent = false;
		if ( lang !== 'nl' && attrs.contentTranslations && attrs.contentTranslations[ lang ] ) {
			const chunks = attrs.contentTranslations[ lang ];
			if ( Array.isArray( chunks ) ) {
				for ( const chunk of chunks ) {
					const text = stripHtml( chunk );
					if ( text ) results.push( text );
				}
				hasTranslatedContent = true;
			}
		}

		// 2. Check known text attributes
		for ( const key of TEXT_ATTRS ) {
			const val = attrs[ key ];
			if ( val === undefined ) continue;

			if ( isTranslationObject( val ) ) {
				// Translation object — pick the requested language
				const text = stripHtml( val[ lang ] || '' );
				if ( text ) results.push( text );
			} else if ( typeof val === 'string' && val ) {
				// Plain string (core blocks)
				const text = stripHtml( val );
				if ( text ) results.push( text );
			} else if ( val && typeof val === 'object' && val.toString ) {
				// RichText object (WordPress stores some content as RichText values)
				const str = val.toString();
				if ( str && str !== '[object Object]' ) {
					const text = stripHtml( str );
					if ( text ) results.push( text );
				}
			}
		}


		// 3. Check array attributes with nested translation objects (e.g. page-cards pages[].label)
		for ( const [ key, val ] of Object.entries( attrs ) ) {
			if ( Array.isArray( val ) ) {
				for ( const item of val ) {
					if ( typeof item === 'object' && item !== null ) {
						for ( const [ subKey, subVal ] of Object.entries( item ) ) {
							if ( isTranslationObject( subVal ) ) {
								const text = stripHtml( subVal[ lang ] || '' );
								if ( text ) results.push( text );
							}
						}
					}
				}
			}
		}

		// 4. Recurse into innerBlocks (but skip if we already got translated content for this lang)
		if ( block.innerBlocks?.length ) {
			if ( ! hasTranslatedContent ) {
				results.push( ...extractContent( block.innerBlocks, lang ) );
			}
		}
	}

	return results;
}

// Expose globally for plugins
window.snelExtractContent = extractContent;
