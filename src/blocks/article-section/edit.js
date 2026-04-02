/**
 * Article Section Block — Editor Component
 *
 * Structured section: tagline, heading, image, and body text.
 * Each field is a multilingual object translated individually.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, MediaUpload, MediaUploadCheck, InspectorControls } from '@wordpress/block-editor';
import { Button, PanelBody, ToggleControl } from '@wordpress/components';
import { select } from '@wordpress/data';
import TranslatableWrapper from '../components/TranslatableWrapper';
import BgColorControl, { BG_EDITOR_STYLES } from '../components/BgColorControl';
import { getLang, setLang, translateTexts } from '../components/lang-helpers';

export default function Edit({ attributes, setAttributes, clientId }) {
	const { backgroundColor, tagline, heading, imageUrl, imageId, content, verticalPadding } = attributes;

	const paddingClass = verticalPadding ? 'py-16 md:py-24' : 'py-0';

	const blockProps = useBlockProps({
		className: `relative ${paddingClass} px-4 md:px-16 lg:px-24`,
		style: { backgroundColor: BG_EDITOR_STYLES[backgroundColor] || BG_EDITOR_STYLES['white'] },
	});

	const handleTranslate = async (targetLang) => {
		const texts = [];
		const map = [];

		const t = getLang(tagline, 'nl');
		if (t) { texts.push(t); map.push({ type: 'tagline' }); }

		const h = getLang(heading, 'nl');
		if (h) { texts.push(h); map.push({ type: 'heading' }); }

		const c = getLang(content, 'nl');
		if (c) {
			const matches = c.match(/<p>(.*?)<\/p>/gs) || [];
			matches.forEach((match) => {
				const inner = match.replace(/^<p>/, '').replace(/<\/p>$/, '').trim();
				if (inner) {
					texts.push(inner);
					map.push({ type: 'paragraph' });
				}
			});
		}

		if (texts.length === 0) return;

		const tr = await translateTexts(texts, targetLang);

		const freshAttrs = select('core/block-editor').getBlockAttributes(clientId);

		let newTagline = freshAttrs.tagline;
		let newHeading = freshAttrs.heading;
		const translatedParagraphs = [];

		tr.forEach((translated, i) => {
			const m = map[i];
			if (m.type === 'tagline') newTagline = setLang(newTagline, targetLang, translated);
			else if (m.type === 'heading') newHeading = setLang(newHeading, targetLang, translated);
			else if (m.type === 'paragraph') translatedParagraphs.push(translated);
		});

		let newContent = freshAttrs.content;
		if (translatedParagraphs.length > 0) {
			newContent = setLang(newContent, targetLang, translatedParagraphs.map((p) => `<p>${p}</p>`).join(''));
		}

		setAttributes({ tagline: newTagline, heading: newHeading, content: newContent });
	};

	return (
		<>
			<InspectorControls>
				<BgColorControl
					value={backgroundColor}
					onChange={(v) => setAttributes({ backgroundColor: v })}
				/>
				<PanelBody title={__('Layout', 'snel')} initialOpen={false}>
					<ToggleControl
						label={__('Vertical Padding', 'snel')}
						checked={verticalPadding}
						onChange={(v) => setAttributes({ verticalPadding: v })}
					/>
				</PanelBody>
			</InspectorControls>
			<TranslatableWrapper
				blockProps={blockProps}
				label="Article Section"
				onTranslate={handleTranslate}
				fullWidth
			>
				{({ currentLang }) => (
					<div className="max-w-3xl mx-auto">
						{/* Tagline + Heading */}
						<div className="mb-12">
							<RichText
								tagName="p"
								value={getLang(tagline, currentLang)}
								onChange={(v) => setAttributes({ tagline: setLang(tagline, currentLang, v) })}
								placeholder={__('Tagline...', 'snel')}
								className="snel-editable text-sm font-medium tracking-wide text-text-muted uppercase mb-4"
								allowedFormats={[]}
							/>
							<RichText
								tagName="h2"
								value={getLang(heading, currentLang)}
								onChange={(v) => setAttributes({ heading: setLang(heading, currentLang, v) })}
								placeholder={__('Heading...', 'snel')}
								className="snel-editable text-3xl md:text-4xl font-bold text-text-primary mb-6"
								allowedFormats={['core/bold', 'core/italic']}
							/>
						</div>

						{/* Image */}
						<div className="mb-12">
							<MediaUploadCheck>
								<MediaUpload
									onSelect={(media) => setAttributes({ imageId: media.id, imageUrl: media.url })}
									allowedTypes={['image']}
									value={imageId}
									render={({ open }) => (
										imageUrl ? (
											<div className="relative group cursor-pointer" onClick={open}>
												<img
													src={imageUrl}
													alt=""
													className="w-full h-auto object-cover rounded-sm"
												/>
												<div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
													<span className="opacity-0 group-hover:opacity-100 text-white text-sm tracking-widest transition-opacity">
														{__('REPLACE IMAGE', 'snel')}
													</span>
												</div>
											</div>
										) : (
											<div
												onClick={open}
												className="w-full h-64 bg-gray-100 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-200 transition-colors rounded-sm"
											>
												<svg className="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" strokeWidth={1} viewBox="0 0 24 24">
													<path strokeLinecap="round" strokeLinejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
												</svg>
												<span className="text-sm text-gray-500">{__('Click to add image', 'snel')}</span>
											</div>
										)
									)}
								/>
							</MediaUploadCheck>
							{imageUrl && (
								<Button
									isDestructive
									variant="secondary"
									onClick={() => setAttributes({ imageId: 0, imageUrl: '' })}
									style={{ marginTop: '8px' }}
								>
									{__('Remove Image', 'snel')}
								</Button>
							)}
						</div>

						{/* Body Content */}
						<RichText
							tagName="div"
							value={getLang(content, currentLang)}
							onChange={(v) => setAttributes({ content: setLang(content, currentLang, v) })}
							placeholder={__('Write your content here...', 'snel')}
							className="snel-editable prose prose-lg max-w-none"
							multiline="p"
							allowedFormats={['core/bold', 'core/italic', 'core/link']}
						/>
					</div>
				)}
			</TranslatableWrapper>
		</>
	);
}
