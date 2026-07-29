(function ($) {
	'use strict';
	function activateTab(tab) {
		$('.gdp-meta-nav button').removeClass('is-active').attr('aria-selected', 'false');
		$('.gdp-meta-nav button[data-tab="' + tab + '"]').addClass('is-active').attr('aria-selected', 'true');
		$('.gdp-meta-panel').removeClass('is-active');
		$('.gdp-meta-panel[data-panel="' + tab + '"]').addClass('is-active');
	}
	function fieldValue(name) {
		var $field = $('[name="gdp_fields[' + name + ']"], [name="gdp_fields[' + name + '][]"]');
		if ($field.is(':checkbox')) return $field.is(':checked') ? 1 : 0;
		return $field.val();
	}
	function refreshConditions() {
		$('.gdp-field-wrap[data-show-if]').each(function () {
			var raw = $(this).attr('data-show-if'), rules = {};
			try { rules = raw ? JSON.parse(raw) : {}; } catch (e) { rules = {}; }
			var visible = true;
			Object.keys(rules).forEach(function (key) { if (String(fieldValue(key)) !== String(rules[key])) visible = false; });
			$(this).toggleClass('gdp-condition-hidden', !visible);
		});
	}
	$(document).on('click', '.gdp-meta-nav button', function () { activateTab($(this).data('tab')); });
	$(document).on('change input', '[name^="gdp_fields["]', refreshConditions);
	$(document).on('click', '.gdp-select-image', function (e) {
		e.preventDefault(); var $box = $(this).closest('.gdp-image-field');
		var frame = wp.media({ title: 'Select image', button: { text: 'Use image' }, multiple: false });
		frame.on('select', function () { var item = frame.state().get('selection').first().toJSON(); $box.find('input[type="hidden"]').val(item.id); $box.find('.gdp-image-preview').html('<img src="' + (item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url) + '" alt="">'); $box.find('.gdp-remove-image').prop('hidden', false); });
		frame.open();
	});
	$(document).on('click', '.gdp-remove-image', function (e) { e.preventDefault(); var $box = $(this).closest('.gdp-image-field'); $box.find('input[type="hidden"]').val(''); $box.find('.gdp-image-preview').empty(); $(this).prop('hidden', true); });
	$(refreshConditions);
})(jQuery);
