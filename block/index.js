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

	blocks.registerBlockType('truspilot-review/reviews', {
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
								{ label: 'Full review wall', value: 'wall' },
							],
							onChange: function (value) {
								setAttributes({ layout: value });
							},
						}),
						el(RangeControl, {
							label: 'Reviews to show',
							value: attributes.count,
							min: 1,
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
							label: 'Auto rotate reviews',
							checked: attributes.autoplay,
							onChange: function (value) {
								setAttributes({ autoplay: value });
							},
						}),
						el(RangeControl, {
							label: 'Rotation speed',
							value: attributes.interval,
							min: 2500,
							max: 20000,
							step: 500,
							onChange: function (value) {
								setAttributes({ interval: value });
							},
						}),
						el(ToggleControl, {
							label: 'Show featured reviews first',
							checked: attributes.featuredFirst,
							onChange: function (value) {
								setAttributes({ featuredFirst: value });
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
					block: 'truspilot-review/reviews',
					attributes: attributes,
				})
			);
		},
		save: function () {
			return null;
		},
	});
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender);
