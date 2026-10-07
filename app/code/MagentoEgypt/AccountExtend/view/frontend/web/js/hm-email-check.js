/**
 * Hub Market — live email domain check and "Did you mean …?" (CL036-TC97).
 *
 * When the customer leaves an email field on sign-up, account edit or checkout,
 * GET /rest/<store>/V1/hm/email-check asks whether the domain can receive mail
 * (DNS: MX, or A/AAAA) and whether a near-miss was meant
 * (magentoegypt.co → magentoegypt.com, gmial.com → gmail.com).
 *
 *  - A domain that cannot receive mail fails a validation rule on the field, so
 *    the form's own validation stops it — on sign-up that is BEFORE the mobile
 *    OTP popup, whose rule validates every other field first. The server-side
 *    guards (customer save, guest order) enforce the same answer regardless.
 *  - A suggestion is a hint under the field; clicking it fills it in. It never
 *    blocks: gmial.com, for one, does accept mail.
 *
 * Unknown (request failed, not answered yet) is treated as deliverable, like the
 * server: the check can only ever refuse what it has positively seen fail.
 * Login forms are skipped — there is nothing to fix about an existing account's
 * address there.
 */
define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    var FIELDS = 'input[type="email"]',
        SKIP = '#hm-login-form, .block-customer-login, #login-form, .form-login:not([data-role="email-with-possible-login"])',
        FORMAT = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;

    return function (config) {
        var results = {},
            pending = {};

        function norm(value) {
            return String(value || '').trim().toLowerCase();
        }

        function hintFor(el) {
            var id = (el.id || el.name || 'email') + '-hm-email-hint',
                $hint = $('#' + $.escapeSelector(id));

            if (!$hint.length) {
                $hint = $('<div role="status" aria-live="polite"></div>')
                    .attr('id', id)
                    .css({marginTop: '.4rem', fontSize: '1.3rem', color: 'var(--hm-foreground-muted)'});
                $(el).after($hint);
            }

            return $hint;
        }

        function render(el, result) {
            var $hint = hintFor(el),
                parts;

            $hint.empty();
            if (!result || !result.suggestion || norm(result.suggestion) === norm(el.value)) {
                return;
            }

            //  "Did you mean %1?" around a link that applies the suggestion.
            parts = String(config.msgSuggest).split('%1');
            $hint.append(document.createTextNode(parts[0] || ''));
            $('<a href="#"></a>')
                .text(result.suggestion)
                .attr('data-hm-email-suggest', result.suggestion)
                .attr('data-for', el.id || '')
                .css({fontWeight: 600, textDecoration: 'underline'})
                .appendTo($hint);
            $hint.append(document.createTextNode(parts[1] || ''));
        }

        function apply(el, result) {
            $(el).addClass('hm-email-deliverable');
            render(el, result);
            if ($(el.form).data('validator')) {
                $(el).valid();
            }
        }

        function check(el) {
            var value = norm(el.value);

            if (!FORMAT.test(value)) {
                render(el, null);

                return;
            }
            if (results[value]) {
                apply(el, results[value]);

                return;
            }
            if (pending[value]) {
                return;
            }
            pending[value] = true;

            $.ajax({url: config.url, data: {email: value}, dataType: 'json', global: false})
                .done(function (response) {
                    results[value] = {
                        domain: response.domain || value.split('@').pop(),
                        deliverable: response.deliverable !== false,
                        suggestion: response.suggestion || null
                    };
                    if (norm(el.value) === value) {
                        apply(el, results[value]);
                    }
                })
                .always(function () {
                    delete pending[value];
                });
        }

        $.validator.addMethod(
            'hm-email-deliverable',
            function (value) {
                var result = results[norm(value)];

                return !(result && result.deliverable === false);
            },
            function (params, element) {
                var result = results[norm(element.value)] || {};

                return String(config.msgUndeliverable).replace('%1', result.domain || '');
            }
        );
        $.validator.addClassRules('hm-email-deliverable', {'hm-email-deliverable': true});

        $(document).on('blur change', FIELDS, function () {
            if (!$(this).closest(SKIP).length) {
                check(this);
            }
        });

        $(document).on('click', '[data-hm-email-suggest]', function (event) {
            var el = document.getElementById($(this).attr('data-for'));

            event.preventDefault();
            if (el) {
                el.value = $(this).attr('data-hm-email-suggest');
                $(el).trigger('input').trigger('change');
                el.focus();
            }
        });
    };
});
