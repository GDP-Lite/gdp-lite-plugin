(function () {
	'use strict';

	function serializeForm(form) {
		var data = {};
		new FormData(form).forEach(function (value, key) {
			data[key] = value;
		});
		return data;
	}

	function request(container, page) {
		var form = container.querySelector('[data-gdp-filter-form]');
		var results = container.querySelector('[data-gdp-filter-results]');
		var status = container.querySelector('[data-gdp-filter-status]');
		if (!form || !results || !window.gdpLiteFilters) return;

		var query = serializeForm(form);
		query.paged = page || 1;
		form.querySelector('[name="paged"]').value = query.paged;
		container.classList.add('is-loading');
		status.textContent = window.gdpLiteFilters.loading;

		var body = new URLSearchParams();
		body.append('action', 'gdp_filter_games');
		body.append('nonce', window.gdpLiteFilters.nonce);
		Object.keys(query).forEach(function (key) {
			body.append('query[' + key + ']', query[key]);
		});

		fetch(window.gdpLiteFilters.ajaxUrl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
			credentials: 'same-origin',
			body: body.toString()
		})
			.then(function (response) { return response.json(); })
			.then(function (payload) {
				if (!payload.success) throw new Error('Request failed');
				results.innerHTML = payload.data.html;
				status.textContent = payload.data.foundPosts + ' games';
				results.focus({preventScroll: true});
			})
			.catch(function () { status.textContent = window.gdpLiteFilters.error; })
			.finally(function () { container.classList.remove('is-loading'); });
	}

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('[data-gdp-filter-form]');
		if (!form) return;
		event.preventDefault();
		request(form.closest('[data-gdp-filter-container]'), 1);
	});

	document.addEventListener('change', function (event) {
		var form = event.target.closest('[data-gdp-filter-form]');
		if (!form || event.target.matches('input[type="search"]')) return;
		request(form.closest('[data-gdp-filter-container]'), 1);
	});

	document.addEventListener('reset', function (event) {
		var form = event.target.closest('[data-gdp-filter-form]');
		if (!form) return;
		window.setTimeout(function () { request(form.closest('[data-gdp-filter-container]'), 1); }, 0);
	});

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-gdp-page]');
		if (!button) return;
		request(button.closest('[data-gdp-filter-container]'), parseInt(button.getAttribute('data-gdp-page'), 10) || 1);
	});
}());
