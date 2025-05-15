import { useSelect } from '@wordpress/data';

export default function AttachmentImage({ imageId, size = 'full' }) {

	const { image } = useSelect((select) => ({
		image: select('core').getMedia(imageId),
	}));

	const imageAttributes = () =>{
		let attributes = {
			src: image.source_url,
			alt: image.alt_text,
			className: `equipes-image attachment-${size} size-${size}`,
			width: image.media_details.width,
			height: image.media_details.height,
		};
		if (image.media_details && image.media_details.sizes && image.media_details.sizes[size]) {
			attributes.src = image.media_details.sizes[size].source_url;
			attributes.width = image.media_details.sizes[size].width;
			attributes.height = image.media_details.sizes[size].height;
		}

		return attributes;
	};

	return (
		<>
			{image && (
				<img {...imageAttributes()} />
			)}
		</>
	)
}