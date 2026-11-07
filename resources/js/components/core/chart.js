let chartModulePromise = null;
let chartInitializationPromise = null;
let jenisChart = null;
let genderChart = null;
let reminderChart = null;
let statusChart = null;
let masaKerjaChart = null;
let usiaChart = null;
let riwayatChart = null;
let pangkatPnsChart = null;
let pangkatPppkChart = null;
let pendidikanChart = null;

function loadChart() {
    if (!chartModulePromise) {
        chartModulePromise = import('chart.js/auto')
            .then(module => module.default)
            .catch(error => {
                chartModulePromise = null;
                throw error;
            });
    }
    return chartModulePromise;
}

/**
 * Horizontal Bar Chart
 */
export async function createHorizontalBarChart(
    canvasId,
    dataArray = [],
    color = '#2563EB'
) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return null;
    }
    const Chart = await loadChart();
    Chart.getChart(canvas)?.destroy();
    const labels = dataArray.map(
        item => item.label
    );
    const values = dataArray.map(
        item => Number(item.value) || 0
    );
    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Pegawai',
                data: values,
                backgroundColor: color,
                borderRadius: 8,
                barThickness: 18
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return `${context.raw} Pegawai`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}
/**
 * Doughnut Chart
 */
export async function createDoughnutChart(
    canvasId,
    legendId,
    dataObject = {},
    colors = [],
    unitLabel = 'Pegawai'
) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return null;
    }
    const Chart = await loadChart();
    Chart.getChart(canvas)?.destroy();
    let labels = [];
    let values = [];
    if (Array.isArray(dataObject)) {
        labels = dataObject.map(
            item => item.label
        );
        values = dataObject.map(
            item => Number(item.value) || 0
        );
    } else if (
        dataObject &&
        typeof dataObject === 'object'
    ) {
        labels = Object.keys(dataObject);
        values = Object.values(dataObject).map(
            value => Number(value) || 0
        );
    }
    const total = values.reduce(
        (sum, value) => sum + value,
        0
    );
    const chart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const value =
                                Number(context.raw) || 0;
                            const percent =
                                total === 0
                                    ? 0
                                    : (
                                        (value / total) *
                                        100
                                    ).toFixed(1);
                            return `${context.label}: ${value} ${unitLabel} (${percent}%)`;
                        }
                    }
                }
            }
        },
        plugins: [{
            id: 'centerText',
            beforeDraw(chart) {
                const {
                    width,
                    height,
                    ctx
                } = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font =
                    'bold 28px sans-serif';
                ctx.fillStyle = '#0F172A';
                ctx.fillText(
                    total,
                    width / 2,
                    height / 2 - 12
                );
                ctx.font =
                    '13px sans-serif';
                ctx.fillStyle = '#64748B';
                ctx.fillText(
                    unitLabel,
                    width / 2,
                    height / 2 + 16
                );
                ctx.restore();
            }
        }]
    });
    const legend =
        document.getElementById(legendId);
    if (legend) {
        legend.innerHTML = '';
        labels.forEach((label, index) => {
            const value =
                Number(values[index]) || 0;
            const percent =
                total === 0
                    ? 0
                    : (
                        (value / total) *
                        100
                    ).toFixed(1);
            const color =
                colors[index] ?? '#94A3B8';
            legend.innerHTML += `
                <div class="flex justify-between items-center py-1">
                    <div class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 rounded-full"
                            style="background:${color}">
                        </span>
                        <span class="text-sm text-slate-700">
                            ${label}
                        </span>
                    </div>
                    <span class="font-semibold text-slate-800">
                        ${value}
                        <span class="text-slate-400 text-xs">
                            (${percent}%)
                        </span>
                    </span>
                </div>
            `;
        });
    }
    return chart;
}

/**
 * Init seluruh chart dashboard
 */
export function initChart() {
    if (chartInitializationPromise) {
        return chartInitializationPromise;
    }
    const chartTargets = [
        [window.chartJenisPegawai, 'chartJenisPegawai'],
        [window.ChartJenisKelamin, 'ChartJenisKelamin'],
        [window.chartReminder, 'pegawaiChart'],
        [window.chartStatus, 'chartStatus'],
        [window.chartMasaKerja, 'chartMasaKerja'],
        [window.chartUsia, 'chartUsia'],
        [window.chartRiwayat, 'chartRiwayat'],
        [window.chartPangkat, 'chartPangkat'],
        [window.chartGolonganPppk, 'chartPangkatPppk'],
        [window.chartPendidikan, 'chartPendidikan']
    ];
    if (!chartTargets.some(
        ([data, canvasId]) =>
            data && document.getElementById(canvasId)
    )) {
        return Promise.resolve();
    }

    chartInitializationPromise = (async () => {
        try {
            await loadChart();
        } catch (error) {
            chartInitializationPromise = null;
            console.error(
                'Gagal memuat Chart.js:',
                error
            );
            return;
        }

        const initialize = async (
            instance,
            data,
            canvasId,
            create
        ) => {
            if (
                instance ||
                !data ||
                !document.getElementById(canvasId)
            ) {
                return instance;
            }
            try {
                return await create();
            } catch (error) {
                console.error(
                    `Gagal membuat grafik ${canvasId}:`,
                    error
                );
                return null;
            }
        };

        jenisChart = await initialize(
            jenisChart,
            window.chartJenisPegawai,
            'chartJenisPegawai',
            () => createDoughnutChart(
                'chartJenisPegawai',
                'legendJenisPegawai',
                window.chartJenisPegawai,
                [
                    '#2563EB',
                    '#10B981',
                    '#F59E0B'
                ],
                'Pegawai'
            )
        );
        genderChart = await initialize(
            genderChart,
            window.ChartJenisKelamin,
            'ChartJenisKelamin',
            () => createDoughnutChart(
                'ChartJenisKelamin',
                'legendGender',
                window.ChartJenisKelamin,
                [
                    '#3B82F6',
                    '#EC4899'
                ],
                'Pegawai'
            )
        );
        reminderChart = await initialize(
            reminderChart,
            window.chartReminder,
            'pegawaiChart',
            () => createDoughnutChart(
                'pegawaiChart',
                'legendReminder',
                {
                    'Naik Pangkat':
                        window.chartReminder.total_pangkat,
                    'KGB':
                        window.chartReminder.total_kgb,
                    'Pensiun':
                        window.chartReminder.total_pensiun
                },
                [
                    '#2563EB',
                    '#8B5CF6',
                    '#F97316'
                ],
                'Event'
            )
        );
        statusChart = await initialize(
            statusChart,
            window.chartStatus,
            'chartStatus',
            () => createDoughnutChart(
                'chartStatus',
                'legendStatus',
                window.chartStatus,
                [
                    '#10B981',
                    '#EF4444',
                    '#F59E0B'
                ],
                'Pegawai'
            )
        );
        masaKerjaChart = await initialize(
            masaKerjaChart,
            window.chartMasaKerja,
            'chartMasaKerja',
            () => createDoughnutChart(
                'chartMasaKerja',
                'legendMasaKerja',
                window.chartMasaKerja,
                [
                    '#2563EB',
                    '#10B981',
                    '#F59E0B',
                    '#8B5CF6'
                ],
                'Pegawai'
            )
        );
        usiaChart = await initialize(
            usiaChart,
            window.chartUsia,
            'chartUsia',
            () => createDoughnutChart(
                'chartUsia',
                'legendUsia',
                window.chartUsia,
                [
                    '#0EA5E9',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444'
                ],
                'Pegawai'
            )
        );
        riwayatChart = await initialize(
            riwayatChart,
            window.chartRiwayat,
            'chartRiwayat',
            () => createDoughnutChart(
                'chartRiwayat',
                'legendRiwayat',
                {
                    'KGB':
                        window.chartRiwayat.total_kgb,
                    'Naik Pangkat':
                        window.chartRiwayat.total_pangkat,
                    'Pensiun':
                        window.chartRiwayat.total_pensiun
                },
                [
                    '#8B5CF6',
                    '#2563EB',
                    '#F97316'
                ],
                'Pegawai'
            )
        );
        pangkatPnsChart = await initialize(
            pangkatPnsChart,
            window.chartPangkat,
            'chartPangkat',
            () => createHorizontalBarChart(
                'chartPangkat',
                window.chartPangkat,
                '#2563EB'
            )
        );
        pangkatPppkChart = await initialize(
            pangkatPppkChart,
            window.chartGolonganPppk,
            'chartPangkatPppk',
            () => createHorizontalBarChart(
                'chartPangkatPppk',
                window.chartGolonganPppk,
                '#10B981'
            )
        );
        pendidikanChart = await initialize(
            pendidikanChart,
            window.chartPendidikan,
            'chartPendidikan',
            () => createHorizontalBarChart(
                'chartPendidikan',
                window.chartPendidikan,
                '#0EA5E9'
            )
        );
    })();
    return chartInitializationPromise;
}
/**
 * Resize chart
 */
export function resizeChart() {
    [
        jenisChart,
        genderChart,
        reminderChart,
        pangkatPnsChart,
        pendidikanChart,
        statusChart,
        usiaChart,
        masaKerjaChart,
        riwayatChart,
        pangkatPppkChart
    ].forEach(chart => {
        if (!chart) {
            return;
        }
        chart.resize();
        chart.update('none');
    });
}
