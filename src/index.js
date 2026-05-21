import { registerBlockType } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	TextControl,
	ToggleControl,
	RangeControl,
	SelectControl,
	PanelBody,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('truspilot-review/reviews', {
	edit: (props) => {
		const { attributes, setAttributes } = props;

		return createElement(
			'div',
			{},
			createElement(
				InspectorControls,
				{},
				createElement(
					PanelBody,
					{ title: 'Review settings', initialOpen: true },
					createElement(TextControl, {
						label: 'Title',
						value: attributes.title,
						onChange: (value) => setAttributes({ title: value }),
					}),
					createElement(SelectControl, {
						label: 'Layout',
						value: attributes.layout,
						options: [
							{ label: 'Carousel', value: 'carousel' },
							{ label: 'Grid', value: 'grid' },
							{ label: 'Full review wall', value: 'wall' },
						],
						onChange: (value) => setAttributes({ layout: value }),
					}),
					createElement(RangeControl, {
						label: 'Reviews to show',
						value: attributes.count,
						min: 1,
						max: 48,
						onChange: (value) => setAttributes({ count: value }),
					}),
					createElement(RangeControl, {
						label: 'Minimum rating',
						value: attributes.minRating,
						min: 1,
						max: 5,
						step: 0.5,
						onChange: (value) => setAttributes({ minRating: value }),
					}),
					createElement(ToggleControl, {
						label: 'Auto rotate reviews',
						checked: attributes.autoplay,
						onChange: (value) => setAttributes({ autoplay: value }),
					}),
					createElement(RangeControl, {
						label: 'Rotation speed',
						value: attributes.interval,
						min: 2500,
						max: 20000,
						step: 500,
						onChange: (value) => setAttributes({ interval: value }),
					}),
					createElement(ToggleControl, {
						label: 'Show featured reviews first',
						checked: attributes.featuredFirst,
						onChange: (value) => setAttributes({ featuredFirst: value }),
					}),
					createElement(ToggleControl, {
						label: 'Cover full page width',
						checked: attributes.fullPage,
						onChange: (value) => setAttributes({ fullPage: value }),
					})
				)
			),
			createElement(ServerSideRender, {
				block: 'truspilot-review/reviews',
				attributes: attributes,
			})
		);
	},
	save: () => null,
});
