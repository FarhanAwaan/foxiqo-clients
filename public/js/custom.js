/**
 * Custom JavaScript for Foxiqo Clients Portal
 * Built with jQuery
 */

(function($) {
    'use strict';

    // ============================================
    // Global Configuration
    // ============================================

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // ============================================
    // Document Ready
    // ============================================

    $(document).ready(function() {
        initializeTooltips();
        initializeConfirmDialogs();
        initializeFormValidation();
        initializeTableSearch();
        initializeAutoSubmit();
    });

    // ============================================
    // Initialize Tooltips
    // ============================================

    function initializeTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // ============================================
    // Confirm Dialogs
    // ============================================

    function initializeConfirmDialogs() {
        $(document).on('click', '[data-confirm]', function(e) {
            var message = $(this).data('confirm') || 'Are you sure you want to proceed?';

            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Form submit confirmation
        $(document).on('submit', 'form[data-confirm]', function(e) {
            var message = $(this).data('confirm') || 'Are you sure you want to submit this form?';

            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ============================================
    // Form Validation Helpers
    // ============================================

    function initializeFormValidation() {
        // Add loading state to submit buttons
        $(document).on('submit', 'form:not([data-no-loading])', function() {
            var $btn = $(this).find('[type="submit"]');
            $btn.addClass('btn-loading').prop('disabled', true);
        });

        // Clear validation errors on input
        $(document).on('input change', '.is-invalid', function() {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        });
    }

    // ============================================
    // Table Search
    // ============================================

    function initializeTableSearch() {
        $(document).on('input', '[data-table-search]', function() {
            var searchText = $(this).val().toLowerCase();
            var tableId = $(this).data('table-search');
            var $table = $(tableId);

            $table.find('tbody tr').each(function() {
                var rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchText) > -1);
            });
        });
    }

    // ============================================
    // Auto Submit Forms
    // ============================================

    function initializeAutoSubmit() {
        // Auto-submit on select change
        $(document).on('change', 'select[data-auto-submit]', function() {
            $(this).closest('form').submit();
        });

        // Auto-submit on checkbox change
        $(document).on('change', 'input[type="checkbox"][data-auto-submit]', function() {
            $(this).closest('form').submit();
        });
    }

    // ============================================
    // Utility Functions
    // ============================================

    window.Foxiqo = {
        /**
         * Show a toast notification
         */
        toast: function(message, type) {
            type = type || 'info';
            // Implement toast notification if needed
            console.log('[' + type.toUpperCase() + '] ' + message);
        },

        /**
         * Format currency
         */
        formatCurrency: function(amount, currency) {
            currency = currency || 'USD';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        /**
         * Format number with commas
         */
        formatNumber: function(number) {
            return new Intl.NumberFormat('en-US').format(number);
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };

    /**
     * Global copy-to-clipboard function
     * Accepts an element ID, reads its value or textContent, and copies to clipboard.
     * Shows visual feedback on the nearest button.
     */
    window.copyToClipboard = function(elementId, e) {
        var element = document.getElementById(elementId);
        if (!element) return;

        var text = element.value !== undefined && element.value !== '' ? element.value : element.textContent.trim();
        var btn = e ? e.target.closest('button') : null;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                if (btn) {
                    var originalHtml = btn.innerHTML;
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>';
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-success');
                    setTimeout(function() {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-primary');
                    }, 2000);
                }
            }).catch(function() {
                fallbackCopy(element, text);
            });
        } else {
            fallbackCopy(element, text);
        }

        function fallbackCopy(el, val) {
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                el.select();
                document.execCommand('copy');
            } else {
                var ta = document.createElement('textarea');
                ta.value = val;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            if (btn) {
                var originalHtml = btn.innerHTML;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>';
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-success');
                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                }, 2000);
            }
        }
    };

    // ============================================
    // AJAX Form Handling
    // ============================================

    $(document).on('submit', 'form[data-ajax]', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('[type="submit"]');
        var url = $form.attr('action');
        var method = $form.attr('method') || 'POST';

        $btn.addClass('btn-loading').prop('disabled', true);

        $.ajax({
            url: url,
            method: method,
            data: $form.serialize(),
            success: function(response) {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if (response.message) {
                    Foxiqo.toast(response.message, 'success');
                }

                if (response.reload) {
                    window.location.reload();
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON?.errors || {};

                // Clear previous errors
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').remove();

                // Show new errors
                Object.keys(errors).forEach(function(field) {
                    var $input = $form.find('[name="' + field + '"]');
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + errors[field][0] + '</div>');
                });

                if (xhr.responseJSON?.message) {
                    Foxiqo.toast(xhr.responseJSON.message, 'error');
                }
            },
            complete: function() {
                $btn.removeClass('btn-loading').prop('disabled', false);
            }
        });
    });

})(jQuery);
