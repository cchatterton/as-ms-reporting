(function () {
    'use strict';

    document.querySelectorAll('[data-asms-background]').forEach(function (element) {
        const colour = element.getAttribute('data-asms-background');

        if (/^rgba\((?:0|255),\s*(?:0|128),\s*0,\s*(?:0(?:\.\d+)?|1(?:\.0+)?)\)$/.test(colour)) {
            element.style.background = colour;
        }
    });

    document.querySelectorAll('[data-asms-heat-alpha]').forEach(function (element) {
        const alpha = Math.max(0, Math.min(1, Number(element.getAttribute('data-asms-heat-alpha')) || 0));
        element.style.background = 'rgba(0, 128, 0, ' + alpha + ')';
    });

    const canvas = document.getElementById('msChart');

    if (!canvas || typeof window.Chart === 'undefined' || typeof window.ASMSReportData === 'undefined') {
        return;
    }

    const report = window.ASMSReportData;

    new window.Chart(canvas, {
        type: 'bar',
        data: {
            labels: report.labels,
            datasets: [
                {
                    data: report.base,
                    backgroundColor: '#777',
                    borderWidth: 0,
                    barPercentage: 1,
                    categoryPercentage: 0.92,
                    stack: 'stack1'
                },
                {
                    data: report.over,
                    backgroundColor: '#e46b6b',
                    borderWidth: 0,
                    barPercentage: 1,
                    categoryPercentage: 0.92,
                    stack: 'stack1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            layout: {
                padding: { left: 0, right: 0 }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { display: false }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: '#e0e0e0' },
                    ticks: { display: false },
                    border: { display: false }
                }
            }
        }
    });
}());
