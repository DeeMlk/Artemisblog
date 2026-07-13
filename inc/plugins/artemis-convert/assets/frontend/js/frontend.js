(function ($) {
	'use strict';

	var recorded = {};

	function postAnalytics(endpoint, ctaId, useBeacon) {
		if (typeof artemisConvert === 'undefined' || !artemisConvert.analytics) {
			return;
		}
		ctaId = parseInt(ctaId, 10);
		if (!ctaId) {
			return;
		}
		var payload = JSON.stringify({ cta_id: ctaId });
		if (useBeacon && window.navigator && navigator.sendBeacon && window.Blob) {
			navigator.sendBeacon(artemisConvert.restUrl + endpoint, new Blob([payload], { type: 'application/json' }));
			return;
		}
		$.ajax({
			url: artemisConvert.restUrl + endpoint,
			method: 'POST',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', artemisConvert.nonce);
				xhr.setRequestHeader('Content-Type', 'application/json');
			},
			data: payload,
			contentType: 'application/json'
		});
	}

	function recordView(ctaId) {
		ctaId = parseInt(ctaId, 10);
		if (!ctaId || recorded[ctaId]) {
			return;
		}
		recorded[ctaId] = true;
		postAnalytics('view', ctaId, false);
	}

	function recordClick(ctaId) {
		postAnalytics('click', ctaId, true);
	}

	$('.artemis-convert-cta-track[data-cta-id], .artemis-convert-cta-link[data-cta-id]').each(function () {
		var id = $(this).attr('data-cta-id');
		if (id) {
			recordView(id);
		}
	});

	// Clique: usar data-redirect-url para que o contador só rode no clique real (evita prefetch contar como clique).
	$(document).on('click', '.artemis-convert-cta-link[data-redirect-url]', function (e) {
		var redirectUrl = $(this).attr('data-redirect-url');
		if (redirectUrl) {
			e.preventDefault();
			window.location = redirectUrl;
		}
	});

	$(document).on('click', '.artemis-convert-cta-shortcode-content[data-cta-id] a, .artemis-convert-cta-shortcode-content[data-cta-id] button, .artemis-convert-cta-shortcode-content[data-cta-id] input[type="submit"]', function () {
		var id = $(this).closest('.artemis-convert-cta-shortcode-content').attr('data-cta-id');
		if (id) {
			recordClick(id);
		}
	});
})(jQuery);
