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
		const { layout } = attributes;

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
							{ label: 'List (masonry)', value: 'list' },
							{ label: 'Wall (full page)', value: 'wall' },
						],
						onChange: (value) => setAttributes({ layout: value }),
					}),
					createElement(RangeControl, {
						label: 'Reviews to show (0 = all featured)',
						value: attributes.count,
						min: 0,
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
						label: 'Show featured reviews first',
						checked: attributes.featuredFirst,
						onChange: (value) => setAttributes({ featuredFirst: value }),
					}),
					'carousel' === layout &&
						createElement(ToggleControl, {
							label: 'Auto rotate reviews',
							checked: attributes.autoplay,
							onChange: (value) => setAttributes({ autoplay: value }),
						}),
					'carousel' === layout &&
						createElement(RangeControl, {
							label: 'Rotation speed (ms)',
							value: attributes.interval,
							min: 2500,
							max: 20000,
							step: 500,
							onChange: (value) => setAttributes({ interval: value }),
						}),
					'carousel' === layout &&
						createElement(RangeControl, {
							label: 'Visible reviews at once',
							value: attributes.carouselVisible,
							min: 1,
							max: 6,
							onChange: (value) => setAttributes({ carouselVisible: value }),
						}),
					'grid' === layout &&
						createElement(RangeControl, {
							label: 'Grid rows',
							value: attributes.gridRows,
							min: 1,
							max: 6,
							onChange: (value) => setAttributes({ gridRows: value }),
						}),
					'grid' === layout &&
						createElement(RangeControl, {
							label: 'Grid columns',
							value: attributes.gridColumns,
							min: 1,
							max: 6,
							onChange: (value) => setAttributes({ gridColumns: value }),
						}),
					'wall' === layout &&
						createElement(SelectControl, {
							label: 'Wall style',
							value: attributes.wallStyle,
							options: [
								{ label: 'Standard', value: 'standard' },
								{ label: 'Noticeboard', value: 'noticeboard' },
							],
							onChange: (value) => setAttributes({ wallStyle: value }),
						}),
					'list' === layout &&
						createElement(SelectControl, {
							label: 'Orientation',
							value: attributes.orientation,
							options: [
								{ label: 'Vertical', value: 'vertical' },
								{ label: 'Horizontal', value: 'horizontal' },
							],
							onChange: (value) => setAttributes({ orientation: value }),
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
