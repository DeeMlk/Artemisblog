(function ($) {
	'use strict';

	var frame;
	var frameMobile;
	var $imageInput = $('#artemis_cta_image');
	var $preview = $('.artemis-cta-image-preview');
	var $removeBtn = $('.artemis-cta-remove-image');
	var $imageMobileInput = $('#artemis_cta_image_mobile');
	var $previewMobile = $('.artemis-cta-image-mobile-preview');
	var $removeBtnMobile = $('.artemis-cta-remove-image-mobile');
	var $typeInputs = $('.artemis-cta-type-option input[type="radio"]');
	var $imageFields = $('.artemis-cta-image-fields');
	var $shortcodeFields = $('.artemis-cta-shortcode-fields');

	function updateCtaTypeFields() {
		var type = $typeInputs.filter(':checked').val() || 'image';
		$('.artemis-cta-type-option').removeClass('is-selected');
		$typeInputs.filter(':checked').closest('.artemis-cta-type-option').addClass('is-selected');
		$imageFields.toggle(type === 'image');
		$shortcodeFields.toggle(type === 'shortcode');
	}

	$typeInputs.on('change', updateCtaTypeFields);
	updateCtaTypeFields();

	$('.artemis-cta-upload-image').on('click', function (e) {
		e.preventDefault();
		if (frame) {
			frame.open();
			return;
		}
		frame = wp.media({
			title: 'Selecionar imagem do CTA',
			library: { type: 'image' },
			multiple: false,
			button: { text: 'Usar esta imagem' }
		});
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$imageInput.val(attachment.id);
			$preview.html($('<img>').attr('src', attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url).css({ maxWidth: '300px', height: 'auto' }));
			$removeBtn.show();
		});
		frame.open();
	});

	$removeBtn.on('click', function (e) {
		e.preventDefault();
		$imageInput.val('');
		$preview.empty();
		$removeBtn.hide();
	});

	$('.artemis-cta-upload-image-mobile').on('click', function (e) {
		e.preventDefault();
		if (frameMobile) {
			frameMobile.open();
			return;
		}
		frameMobile = wp.media({
			title: 'Selecionar imagem do CTA (mobile)',
			library: { type: 'image' },
			multiple: false,
			button: { text: 'Usar esta imagem' }
		});
		frameMobile.on('select', function () {
			var attachment = frameMobile.state().get('selection').first().toJSON();
			$imageMobileInput.val(attachment.id);
			$previewMobile.html($('<img>').attr('src', attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url).css({ maxWidth: '300px', height: 'auto' }));
			$removeBtnMobile.show();
		});
		frameMobile.open();
	});

	$removeBtnMobile.on('click', function (e) {
		e.preventDefault();
		$imageMobileInput.val('');
		$previewMobile.empty();
		$removeBtnMobile.hide();
	});

	// UTM accordion
	$('.artemis-cta-utm-toggle-btn').on('click', function () {
		var $wrap = $(this).closest('.artemis-cta-utm-toggle');
		var $content = $wrap.find('.artemis-cta-utm-toggle-content');
		var isOpen = $wrap.hasClass('is-open');
		$wrap.toggleClass('is-open', !isOpen);
		$content.toggle(!isOpen);
		$(this).attr('aria-expanded', !isOpen);
	});

	// Posição: multi-seleção — cada checkbox alterna seu próprio card
	$('.artemis-cta-position-option input[type="checkbox"]').on('change', function () {
		$(this).closest('.artemis-cta-position-option').toggleClass('is-selected', this.checked);
	});
})(jQuery);
