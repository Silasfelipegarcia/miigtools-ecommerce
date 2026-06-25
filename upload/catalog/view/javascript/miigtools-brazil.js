(function ($) {
	'use strict';

	function digits(value) {
		return String(value || '').replace(/\D/g, '');
	}

	function maskCep(value) {
		var d = digits(value).slice(0, 8);

		if (d.length <= 5) {
			return d;
		}

		return d.slice(0, 5) + '-' + d.slice(5);
	}

	function maskCpf(value) {
		var d = digits(value).slice(0, 11);

		return d
			.replace(/(\d{3})(\d)/, '$1.$2')
			.replace(/(\d{3})(\d)/, '$1.$2')
			.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
	}

	function maskCnpj(value) {
		var d = digits(value).slice(0, 14);

		return d
			.replace(/(\d{2})(\d)/, '$1.$2')
			.replace(/(\d{3})(\d)/, '$1.$2')
			.replace(/(\d{3})(\d)/, '$1/$2')
			.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
	}

	function fieldId(prefix, name) {
		return prefix ? '#input-' + prefix + '-' + name : '#input-' + name;
	}

	function mergeStreetNumber($form, prefix) {
		var $street = $(fieldId(prefix, 'address-1'));
		var $number = $(fieldId(prefix, 'address-number'));

		if (!$street.length || !$number.length) {
			return;
		}

		var street = $.trim($street.val());
		var number = $.trim($number.val());

		if (street && number && !/,/.test(street)) {
			$street.val(street + ', ' + number);
		}
	}

	function loadZones(prefix, countryId, language, uf, callback) {
		$.ajax({
			url: 'index.php?route=localisation/country&country_id=' + countryId + '&language=' + language,
			dataType: 'json',
			success: function (json) {
				var html = '<option value="">' + ($('[data-oc-brazil-select]').data('ocBrazilSelect') || '--- Selecione ---') + '</option>';
				var zoneId = 0;

				if (json.zone && json.zone.length) {
					for (var i = 0; i < json.zone.length; i++) {
						var zone = json.zone[i];
						var selected = uf && zone.code === uf;

						if (selected) {
							zoneId = zone.zone_id;
						}

						html += '<option value="' + zone.zone_id + '" data-code="' + zone.code + '"' + (selected ? ' selected' : '') + '>' + zone.name + '</option>';
					}
				}

				$(fieldId(prefix, 'zone')).html(html);

				if (typeof callback === 'function') {
					callback(zoneId);
				}
			}
		});
	}

	function lookupCep($wrap) {
		var prefix = $wrap.data('prefix') || '';
		var countryId = $wrap.data('countryId') || 30;
		var language = $wrap.data('language') || 'pt-br';
		var $postcode = $(fieldId(prefix, 'postcode'));
		var cep = digits($postcode.val());

		if (cep.length !== 8) {
			return;
		}

		$postcode.val(maskCep(cep));

		$.ajax({
			url: 'https://viacep.com.br/ws/' + cep + '/json/',
			dataType: 'json',
			success: function (data) {
				if (data.erro) {
					alert('CEP não encontrado. Verifique e tente novamente.');
					return;
				}

				$(fieldId(prefix, 'address-1')).val(data.logradouro || '');
				$(fieldId(prefix, 'address-2')).val(data.bairro || '');
				$(fieldId(prefix, 'city')).val(data.localidade || '');

				var $country = $(fieldId(prefix, 'country'));

				if ($country.length) {
					$country.val(countryId);
				}

				loadZones(prefix, countryId, language, data.uf || '');
			},
			error: function () {
				alert('Não foi possível consultar o CEP. Tente novamente.');
			}
		});
	}

	function initDocumentMasks() {
		$(document).on('input', '[data-oc-brazil-document-number]', function () {
			var type = $(this).closest('form').find('[data-oc-brazil-document-type]').val() || 'CPF';

			$(this).val(type === 'CNPJ' ? maskCnpj(this.value) : maskCpf(this.value));
		});

		$(document).on('change', '[data-oc-brazil-document-type]', function () {
			var $number = $(this).closest('form').find('[data-oc-brazil-document-number]');

			if ($number.length) {
				$number.trigger('input');
			}
		});
	}

	function initCepMasks() {
		$(document).on('input', '[data-oc-brazil-postcode]', function () {
			$(this).val(maskCep(this.value));
		});

		$(document).on('blur', '[data-oc-brazil-postcode]', function () {
			var $wrap = $(this).closest('[data-oc-brazil-address]');

			if ($wrap.length) {
				lookupCep($wrap);
			}
		});
	}

	function initRecipientToggle() {
		$(document).on('change', '#input-recipient-other', function () {
			$('#recipient-fields').toggleClass('d-none', !this.checked);
		});
	}

	function initFormMerge() {
		$(document).on('submit', 'form', function () {
			var $form = $(this);

			$form.find('[data-oc-brazil-address]').each(function () {
				mergeStreetNumber($form, $(this).data('prefix') || '');
			});
		});
	}

	function initAddressBlocks() {
		$('[data-oc-brazil-address]').each(function () {
			var $wrap = $(this);
			var prefix = $wrap.data('prefix') || '';
			var countryId = $wrap.data('countryId') || 30;

			$(fieldId(prefix, 'country')).val(countryId);
		});
	}

	$(function () {
		initDocumentMasks();
		initCepMasks();
		initRecipientToggle();
		initFormMerge();
		initAddressBlocks();
	});
})(window.jQuery);
