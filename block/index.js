(function (blocks, element, components, blockEditor, serverSideRender) {
	'use strict';

	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var PanelBody = components.PanelBody;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType('business-review-importer/reviews', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Review settings', initialOpen: true },
						el(TextControl, {
							label: 'Title',
							value: attributes.title,
							onChange: function (value) {
								setAttributes({ title: value });
							},
						}),
						el(SelectControl, {
							label: 'Layout',
							value: attributes.layout,
							options: [
								{ label: 'Carousel', value: 'carousel' },
								{ label: 'Grid', value: 'grid' },
								{ label: 'List', value: 'list' },
								{ label: 'Wall (full page)', value: 'wall' },
							],
							onChange: function (value) {
								setAttributes({ layout: value });
							},
						}),
						el(RangeControl, {
							label: 'Reviews to show (0 = all featured)',
							value: attributes.count,
							min: 0,
							max: 48,
							onChange: function (value) {
								setAttributes({ count: value });
							},
						}),
						el(RangeControl, {
							label: 'Minimum rating',
							value: attributes.minRating,
							min: 1,
							max: 5,
							step: 0.5,
							onChange: function (value) {
								setAttributes({ minRating: value });
							},
						}),
						el(ToggleControl, {
							label: 'Show featured reviews first',
							checked: attributes.featuredFirst,
							onChange: function (value) {
								setAttributes({ featuredFirst: value });
							},
						}),
						attributes.layout === 'carousel' && el(ToggleControl, {
							label: 'Auto rotate reviews',
							checked: attributes.autoplay,
							onChange: function (value) {
								setAttributes({ autoplay: value });
							},
						}),
						attributes.layout === 'carousel' && el(RangeControl, {
							label: 'Rotation speed (ms)',
							value: attributes.interval,
							min: 2500,
							max: 20000,
							step: 500,
							onChange: function (value) {
								setAttributes({ interval: value });
							},
						}),
						attributes.layout === 'carousel' && el(RangeControl, {
							label: 'Visible reviews at once',
							value: attributes.carouselVisible,
							min: 1,
							max: 6,
							onChange: function (value) {
								setAttributes({ carouselVisible: value });
							},
						}),
						attributes.layout === 'grid' && el(RangeControl, {
							label: 'Grid rows',
							value: attributes.gridRows,
							min: 1,
							max: 6,
							onChange: function (value) {
								setAttributes({ gridRows: value });
							},
						}),
						attributes.layout === 'grid' && el(RangeControl, {
							label: 'Grid columns',
							value: attributes.gridColumns,
							min: 1,
							max: 6,
							onChange: function (value) {
								setAttributes({ gridColumns: value });
							},
						}),
						attributes.layout === 'wall' && el(SelectControl, {
							label: 'Wall style',
							value: attributes.wallStyle,
							options: [
								{ label: 'Standard', value: 'standard' },
								{ label: 'Noticeboard', value: 'noticeboard' },
							],
							onChange: function (value) {
								setAttributes({ wallStyle: value });
							},
						}),
						attributes.layout === 'list' && el(SelectControl, {
							label: 'Orientation',
							value: attributes.orientation,
							options: [
								{ label: 'Vertical', value: 'vertical' },
								{ label: 'Horizontal', value: 'horizontal' },
							],
							onChange: function (value) {
								setAttributes({ orientation: value });
							},
						}),
						el(ToggleControl, {
							label: 'Cover full page width',
							checked: attributes.fullPage,
							onChange: function (value) {
								setAttributes({ fullPage: value });
							},
						})
					)
				),
				el(ServerSideRender, {
					block: 'business-review-importer/reviews',
					attributes: attributes,
				})
			);
		},
		save: function () {
			return null;
		},
	});
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender);
