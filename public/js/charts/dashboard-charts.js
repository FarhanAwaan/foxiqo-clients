/**
 * DashboardCharts — shared Chart.js helpers for admin & customer dashboards.
 * Requires: jQuery, Chart.js 4.x
 */
window.DashboardCharts = (function ($) {
    'use strict';

    var COLORS = {
        primary:       '#206bc4',
        primaryFill:   'rgba(32,107,196,0.10)',
        success:       '#2fb344',
        successFill:   'rgba(47,179,68,0.10)',
        danger:        '#d63939',
        dangerFill:    'rgba(214,57,57,0.10)',
        warning:       '#f76707',
        warningFill:   'rgba(247,103,7,0.10)',
        neutral:       '#94a3b8',
        positive:      '#2fb344',
        negative:      '#d63939',
        gridLine:      'rgba(0,0,0,0.06)',
        textMuted:     '#64748b',
    };

    var _instances = {};

    function _destroy(id) {
        if (_instances[id]) {
            _instances[id].destroy();
            delete _instances[id];
        }
    }

    function _showError($canvas, message) {
        $canvas.closest('.chart-container').html(
            '<div class="d-flex align-items-center justify-content-center h-100 text-muted small">' +
            '<i class="ti ti-alert-triangle me-1"></i>' + (message || 'Failed to load chart') +
            '</div>'
        );
    }

    /**
     * Call Volume — area/line chart (daily within selected range)
     * Expected JSON: { labels: [...], values: [...] }
     * @param {string}  url
     * @param {string}  canvasId
     * @param {object}  [params]  — e.g. { range: 'last7' } or { range: 'custom', from: '...', to: '...' }
     */
    function loadCallVolumeChart(url, canvasId, params) {
        var dfd = $.Deferred();
        var $canvas = $('#' + canvasId);

        $.getJSON(url, params || {})
            .done(function (data) {
                _destroy(canvasId);
                _instances[canvasId] = new Chart($canvas[0], {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Calls',
                            data: data.values,
                            fill: true,
                            backgroundColor: COLORS.primaryFill,
                            borderColor: COLORS.primary,
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                            pointBackgroundColor: COLORS.primary,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    title: function (items) { return items[0].label; },
                                    label: function (item) {
                                        return ' ' + item.parsed.y + ' call' + (item.parsed.y !== 1 ? 's' : '');
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: COLORS.textMuted,
                                    maxTicksLimit: 10,
                                    maxRotation: 0,
                                    font: { size: 11 },
                                },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: COLORS.gridLine },
                                ticks: {
                                    color: COLORS.textMuted,
                                    precision: 0,
                                    font: { size: 11 },
                                },
                            },
                        },
                    },
                });
                dfd.resolve();
            })
            .fail(function () {
                _showError($canvas, 'Could not load call volume data');
                dfd.resolve();
            });

        return dfd.promise();
    }

    /**
     * Sentiment breakdown — doughnut chart
     * Expected JSON: { positive: N, neutral: N, negative: N }
     */
    function loadSentimentChart(url, canvasId, params) {
        var dfd = $.Deferred();
        var $canvas = $('#' + canvasId);

        $.getJSON(url, params || {})
            .done(function (data) {
                var total = (data.positive || 0) + (data.neutral || 0) + (data.negative || 0);

                _destroy(canvasId);
                _instances[canvasId] = new Chart($canvas[0], {
                    type: 'doughnut',
                    data: {
                        labels: ['Positive', 'Neutral', 'Negative'],
                        datasets: [{
                            data: [data.positive || 0, data.neutral || 0, data.negative || 0],
                            backgroundColor: [COLORS.positive, COLORS.neutral, COLORS.negative],
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: COLORS.textMuted,
                                    padding: 12,
                                    font: { size: 12 },
                                    usePointStyle: true,
                                    pointStyleWidth: 8,
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (item) {
                                        var val = item.parsed;
                                        var pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                        return ' ' + item.label + ': ' + val + ' (' + pct + '%)';
                                    },
                                },
                            },
                        },
                    },
                    plugins: [{
                        id: 'centerText',
                        afterDraw: function (chart) {
                            var ctx = chart.ctx;
                            var cx  = chart.chartArea.left + (chart.chartArea.right  - chart.chartArea.left) / 2;
                            var cy  = chart.chartArea.top  + (chart.chartArea.bottom - chart.chartArea.top)  / 2;
                            ctx.save();
                            ctx.textAlign    = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.font         = 'bold 22px system-ui, sans-serif';
                            ctx.fillStyle    = '#1a1a2e';
                            ctx.fillText(total, cx, cy - 8);
                            ctx.font      = '12px system-ui, sans-serif';
                            ctx.fillStyle = COLORS.textMuted;
                            ctx.fillText('total', cx, cy + 12);
                            ctx.restore();
                        },
                    }],
                });
                dfd.resolve();
            })
            .fail(function () {
                _showError($canvas, 'Could not load sentiment data');
                dfd.resolve();
            });

        return dfd.promise();
    }

    /**
     * Revenue vs Cost — grouped bar + line combo (admin only, 6 months, fixed range)
     * Expected JSON: { labels: [...], revenue: [...], cost: [...] }
     */
    function loadRevenueChart(url, canvasId) {
        var dfd = $.Deferred();
        var $canvas = $('#' + canvasId);

        $.getJSON(url)
            .done(function (data) {
                _destroy(canvasId);
                _instances[canvasId] = new Chart($canvas[0], {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Revenue',
                                data: data.revenue,
                                backgroundColor: COLORS.primaryFill,
                                borderColor: COLORS.primary,
                                borderWidth: 1.5,
                                borderRadius: 4,
                                order: 2,
                            },
                            {
                                type: 'line',
                                label: 'AI Cost',
                                data: data.cost,
                                borderColor: COLORS.danger,
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                tension: 0.3,
                                pointRadius: 3,
                                pointBackgroundColor: COLORS.danger,
                                order: 1,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    color: COLORS.textMuted,
                                    padding: 16,
                                    font: { size: 12 },
                                    usePointStyle: true,
                                    pointStyleWidth: 8,
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (item) {
                                        return ' ' + item.dataset.label + ': $' + Number(item.parsed.y).toFixed(2);
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: COLORS.textMuted, font: { size: 11 } },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: COLORS.gridLine },
                                ticks: {
                                    color: COLORS.textMuted,
                                    font: { size: 11 },
                                    callback: function (v) { return '$' + v; },
                                },
                            },
                        },
                    },
                });
                dfd.resolve();
            })
            .fail(function () {
                _showError($canvas, 'Could not load revenue data');
                dfd.resolve();
            });

        return dfd.promise();
    }

    /**
     * Date range picker — wires up Today/Yesterday/Last 7/Last 30/Custom buttons.
     * @param {object} opts
     * @param {string} opts.containerSelector  — wrapping element selector, e.g. '#chartRangeContainer'
     * @param {function} opts.onRefresh        — called with params object when range changes
     */
    function initDateRangePicker(opts) {
        var $container = $(opts.containerSelector);
        var $btns      = $container.find('.chart-range-btn');
        var $customDiv = $container.find('.chart-custom-range');
        var $fromInput = $container.find('.chart-from-date');
        var $toInput   = $container.find('.chart-to-date');
        var $applyBtn  = $container.find('.chart-apply-range');

        // Cap date inputs at today
        var today = new Date().toISOString().slice(0, 10);
        var dfltFrom = new Date(Date.now() - 6 * 86400000).toISOString().slice(0, 10);
        $fromInput.attr('max', today).val(dfltFrom);
        $toInput.attr('max', today).val(today);

        $btns.on('click', function () {
            var range = $(this).data('range');

            $btns.removeClass('btn-primary').addClass('btn-outline-secondary');
            $(this).removeClass('btn-outline-secondary').addClass('btn-primary');

            if (range === 'custom') {
                $customDiv.removeClass('d-none');
                return; // wait for Apply
            }

            $customDiv.addClass('d-none');
            opts.onRefresh({ range: range });
        });

        $applyBtn.on('click', function () {
            var from = $fromInput.val();
            var to   = $toInput.val();
            if (!from || !to) return;
            if (from > to) {
                $fromInput.addClass('is-invalid');
                return;
            }
            $fromInput.removeClass('is-invalid');
            opts.onRefresh({ range: 'custom', from: from, to: to });
        });

        $toInput.on('keydown', function (e) {
            if (e.key === 'Enter') $applyBtn.trigger('click');
        });
    }

    return {
        loadCallVolumeChart:  loadCallVolumeChart,
        loadSentimentChart:   loadSentimentChart,
        loadRevenueChart:     loadRevenueChart,
        initDateRangePicker:  initDateRangePicker,
    };

}(jQuery));
