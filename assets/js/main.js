(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {

		// Só age dentro do blog (o canvas usa <body class="artemis-blog">).
		var root = document.querySelector('.artemis-blog');
		if (!root) return;

		// -----------------------------------------------------------------
		// Header: sombra ao rolar
		// -----------------------------------------------------------------
		var header = document.querySelector('.site-header');
		if (header) {
			var lastScrolled = false;
			var onScroll = function () {
				var scrolled = window.scrollY > 8;
				if (scrolled !== lastScrolled) {
					header.classList.toggle('is-scrolled', scrolled);
					lastScrolled = scrolled;
				}
			};
			window.addEventListener('scroll', onScroll, { passive: true });
			onScroll();
		}

		// -----------------------------------------------------------------
		// Menu mobile
		// -----------------------------------------------------------------
		var toggle = document.querySelector('.menu-toggle');
		var nav    = document.querySelector('.site-nav');
		if (toggle && nav) {
			toggle.addEventListener('click', function () {
				var expanded = toggle.getAttribute('aria-expanded') === 'true';
				toggle.setAttribute('aria-expanded', String(!expanded));
				nav.classList.toggle('is-open', !expanded);
			});
		}

		// -----------------------------------------------------------------
		// Busca inline no header
		// -----------------------------------------------------------------
		var searchForm   = document.querySelector('.header-search-form');
		var searchToggle = document.querySelector('.search-toggle');
		var searchInput  = document.querySelector('.header-search-input');
		var searchClose  = document.querySelector('.search-close');

		function openSearch() {
			if (!searchForm) return;
			searchForm.classList.add('is-open');
			if (searchToggle) searchToggle.setAttribute('aria-expanded', 'true');
			if (searchInput) setTimeout(function () { searchInput.focus(); }, 50);
		}
		function closeSearch() {
			if (!searchForm) return;
			searchForm.classList.remove('is-open');
			if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
			if (searchInput) searchInput.value = '';
		}
		if (searchToggle) searchToggle.addEventListener('click', openSearch);
		if (searchClose) searchClose.addEventListener('click', closeSearch);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && searchForm && searchForm.classList.contains('is-open')) closeSearch();
		});

		// Fecha o menu ao clicar fora
		document.addEventListener('click', function (e) {
			if (nav && nav.classList.contains('is-open') && !e.target.closest('.site-nav') && !e.target.closest('.menu-toggle')) {
				nav.classList.remove('is-open');
				if (toggle) toggle.setAttribute('aria-expanded', 'false');
			}
		});

		// -----------------------------------------------------------------
		// Voltar ao topo
		// -----------------------------------------------------------------
		var backTop = document.querySelector('.footer-back-top');
		if (backTop) {
			backTop.addEventListener('click', function (e) {
				e.preventDefault();
				window.scrollTo({ top: 0, behavior: 'smooth' });
			});
		}

		// -----------------------------------------------------------------
		// AJAX: filtros de categoria + paginação ([data-posts-target])
		// -----------------------------------------------------------------
		function getTarget() { return document.querySelector('[data-posts-target]'); }

		function ajaxLoad(url, pushHistory) {
			var target = getTarget();
			if (!target) { window.location = url; return; }
			target.classList.add('is-loading');
			var section = target.closest('section');
			if (section) {
				var top = section.getBoundingClientRect().top + window.scrollY - 80;
				window.scrollTo({ top: top, behavior: 'smooth' });
			}
			fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
				.then(function (r) { return r.text(); })
				.then(function (html) {
					var doc = new DOMParser().parseFromString(html, 'text/html');
					var fresh = doc.querySelector('[data-posts-target]');
					if (fresh) target.innerHTML = fresh.innerHTML;
					if (pushHistory) history.pushState({ artemis: true }, '', url);
					target.classList.remove('is-loading');
				})
				.catch(function () {
					target.classList.remove('is-loading');
					window.location = url;
				});
		}

		document.addEventListener('click', function (e) {
			var target = getTarget();
			if (!target) return;
			var chip = e.target.closest('.artemis-filters .filter-chip');
			if (chip && chip.getAttribute('href')) {
				e.preventDefault();
				ajaxLoad(chip.href, true);
				return;
			}
			var pageLink = e.target.closest('.artemis-pagination a');
			if (pageLink && pageLink.getAttribute('href')) {
				e.preventDefault();
				ajaxLoad(pageLink.href, true);
			}
		});

		window.addEventListener('popstate', function (e) {
			if (e.state && e.state.artemis) ajaxLoad(window.location.href, false);
		});

		// -----------------------------------------------------------------
		// Slider de relacionados (single)
		// -----------------------------------------------------------------
		var slider = document.querySelector('[data-related-slider]');
		if (slider) {
			var track = slider.querySelector('.related-track');
			var prev  = document.querySelector('.related-prev');
			var next  = document.querySelector('.related-next');
			var card  = track.querySelector('article');
			var step  = card ? (card.getBoundingClientRect().width + 28) : 320;
			function updateArrows() {
				if (!prev || !next) return;
				var hasOverflow = track.scrollWidth > track.clientWidth + 4;
				prev.disabled = !hasOverflow || track.scrollLeft <= 4;
				next.disabled = !hasOverflow || track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
			}
			if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step, behavior: 'smooth' }); });
			if (next) next.addEventListener('click', function () { track.scrollBy({ left: step, behavior: 'smooth' }); });
			track.addEventListener('scroll', updateArrows, { passive: true });
			window.addEventListener('resize', function () {
				card = track.querySelector('article');
				step = card ? (card.getBoundingClientRect().width + 28) : 320;
				updateArrows();
			});
			updateArrows();
		}
	});
})();
