(function (blocks, element, components, blockEditor, i18n, ServerSideRender) {
	'use strict';
	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;
	var RangeControl = components.RangeControl;
	var Placeholder = components.Placeholder;
	var __ = i18n.__;

	var configs = {
		'game-grid': { title: __('Game Grid', 'gdp-lite'), icon: 'screenoptions' },
		'game-filters': { title: __('Game Filters', 'gdp-lite'), icon: 'filter' },
		'featured-games': { title: __('Featured Games', 'gdp-lite'), icon: 'star-filled' },
		'provider-games': { title: __('Provider Games', 'gdp-lite'), icon: 'admin-site-alt3' }
	};

	function settingsPanel(props, slug) {
		var a = props.attributes;
		var controls = [];
		if (slug === 'provider-games' || slug === 'game-grid' || slug === 'game-filters') {
			controls.push(el(TextControl, { key: 'provider', label: __('Provider slug', 'gdp-lite'), value: a.provider || '', onChange: function(v){ props.setAttributes({provider:v}); } }));
		}
		controls.push(el(TextControl, { key: 'type', label: __('Game Type slug', 'gdp-lite'), value: a.type || '', onChange: function(v){ props.setAttributes({type:v}); } }));
		controls.push(el(TextControl, { key: 'collection', label: __('Collection slug', 'gdp-lite'), value: a.collection || '', onChange: function(v){ props.setAttributes({collection:v}); } }));
		controls.push(el(TextControl, { key: 'category', label: __('Category slug', 'gdp-lite'), value: a.category || '', onChange: function(v){ props.setAttributes({category:v}); } }));
		controls.push(el(SelectControl, { key: 'volatility', label: __('Volatility', 'gdp-lite'), value: a.volatility || '', options: [
			{label:__('All', 'gdp-lite'),value:''},{label:__('Low','gdp-lite'),value:'low'},{label:__('Medium','gdp-lite'),value:'medium'},{label:__('High','gdp-lite'),value:'high'}
		], onChange:function(v){props.setAttributes({volatility:v});} }));
		controls.push(el(RangeControl, { key:'limit', label:__('Number of games','gdp-lite'), value:a.limit || 8, min:1, max:48, onChange:function(v){props.setAttributes({limit:v});} }));
		controls.push(el(RangeControl, { key:'columns', label:__('Columns','gdp-lite'), value:a.columns || 4, min:1, max:6, onChange:function(v){props.setAttributes({columns:v});} }));
		controls.push(el(SelectControl, { key:'orderby', label:__('Order by','gdp-lite'), value:a.orderby || 'date', options:[
			{label:__('Newest','gdp-lite'),value:'date'},{label:__('Modified','gdp-lite'),value:'modified'},{label:__('Name','gdp-lite'),value:'title'},{label:__('RTP','gdp-lite'),value:'rtp'},{label:__('Max Win','gdp-lite'),value:'max_win'},{label:__('Popular','gdp-lite'),value:'popular'},{label:__('Random','gdp-lite'),value:'rand'}
		], onChange:function(v){props.setAttributes({orderby:v});} }));
		controls.push(el(SelectControl, { key:'order', label:__('Direction','gdp-lite'), value:a.order || 'DESC', options:[{label:__('Descending','gdp-lite'),value:'DESC'},{label:__('Ascending','gdp-lite'),value:'ASC'}], onChange:function(v){props.setAttributes({order:v});} }));
		return el(InspectorControls, {}, el(PanelBody, {title:__('Game display settings','gdp-lite'), initialOpen:true}, controls));
	}

	Object.keys(configs).forEach(function(slug){
		registerBlockType('gdp-lite/' + slug, {
			edit: function(props){
				var needsProvider = slug === 'provider-games' && !props.attributes.provider;
				return el('div', {className:'gdp-block-editor-wrap'},
					settingsPanel(props, slug),
					needsProvider ? el(Placeholder, {icon:configs[slug].icon, label:configs[slug].title, instructions:__('Enter a provider slug in the block settings.','gdp-lite')}) : el(ServerSideRender, {block:'gdp-lite/' + slug, attributes:props.attributes})
				);
			},
			save: function(){ return null; }
		});
	});
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n, window.wp.serverSideRender);
