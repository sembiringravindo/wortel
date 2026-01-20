// Chart.js Configuration and Custom Charts

class WarehouseCharts {
    constructor() {
        this.charts = {};
        this.init();
    }
    
    init() {
        this.registerCustomElements();
        this.initDefaultCharts();
    }
    
    registerCustomElements() {
        // Custom chart elements can be registered here
        Chart.register({
            id: 'warehouseBackground',
            beforeDraw: (chart) => {
                if (chart.config.options.plugins.background) {
                    const ctx = chart.ctx;
                    const chartArea = chart.chartArea;
                    
                    ctx.save();
                    ctx.fillStyle = chart.config.options.plugins.background.color;
                    ctx.fillRect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
                    ctx.restore();
                }
            }
        });
    }
    
    initDefaultCharts() {
        // Initialize all charts on page
        document.querySelectorAll('canvas[data-chart]').forEach(canvas => {
            const chartType = canvas.getAttribute('data-chart');
            const chartData = JSON.parse(canvas.getAttribute('data-chart-data') || '{}');
            
            switch(chartType) {
                case 'monthly':
                    this.createMonthlyChart(canvas, chartData);
                    break;
                case 'comparison':
                    this.createComparisonChart(canvas, chartData);
                    break;
                case 'quality':
                    this.createQualityChart(canvas, chartData);
                    break;
                case 'stock':
                    this.createStockChart(canvas, chartData);
                    break;
            }
        });
    }
    
    createMonthlyChart(canvas, data) {
        const ctx = canvas.getContext('2d');
        this.charts[canvas.id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Stok Masuk',
                    data: data.incoming || [],
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#4caf50',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }, {
                    label: 'Stok Keluar',
                    data: data.outgoing || [],
                    borderColor: '#ff9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#ff9800',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#4caf50',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                label += context.parsed.y.toLocaleString('id-ID') + ' Kg';
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID') + ' Kg';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Jumlah (Kg)',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    createComparisonChart(canvas, data) {
        const ctx = canvas.getContext('2d');
        this.charts[canvas.id] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels || ['Stok Masuk', 'Stok Keluar'],
                datasets: [{
                    label: 'Jumlah (Kg)',
                    data: data.values || [0, 0],
                    backgroundColor: [
                        'rgba(76, 175, 80, 0.8)',
                        'rgba(255, 152, 0, 0.8)'
                    ],
                    borderColor: [
                        '#4caf50',
                        '#ff9800'
                    ],
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toLocaleString('id-ID')} Kg`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID') + ' Kg';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    createQualityChart(canvas, data) {
        const ctx = canvas.getContext('2d');
        this.charts[canvas.id] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels || [],
                datasets: [{
                    data: data.values || [],
                    backgroundColor: [
                        'rgba(76, 175, 80, 0.8)',
                        'rgba(255, 152, 0, 0.8)',
                        'rgba(194, 24, 91, 0.8)'
                    ],
                    borderColor: [
                        '#4caf50',
                        '#ff9800',
                        '#c2185b'
                    ],
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value.toLocaleString('id-ID')} Kg (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    createStockChart(canvas, data) {
        const ctx = canvas.getContext('2d');
        this.charts[canvas.id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Stok Tersedia',
                    data: data.stock || [],
                    borderColor: '#2196f3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID') + ' Kg';
                            }
                        }
                    }
                }
            }
        });
    }
    
    updateChart(chartId, newData) {
        if (this.charts[chartId]) {
            this.charts[chartId].data = newData;
            this.charts[chartId].update();
        }
    }
    
    destroyChart(chartId) {
        if (this.charts[chartId]) {
            this.charts[chartId].destroy();
            delete this.charts[chartId];
        }
    }
    
    exportChart(chartId, filename = 'chart') {
        if (this.charts[chartId]) {
            const link = document.createElement('a');
            link.download = `${filename}-${new Date().toISOString().split('T')[0]}.png`;
            link.href = this.charts[chartId].toBase64Image();
            link.click();
        }
    }
    
    getChartData(chartId) {
        return this.charts[chartId] ? this.charts[chartId].data : null;
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.warehouseCharts = new WarehouseCharts();
    
    // Add chart export functionality
    document.querySelectorAll('.export-chart').forEach(button => {
        button.addEventListener('click', function() {
            const chartId = this.getAttribute('data-chart-id');
            const filename = this.getAttribute('data-filename') || 'chart';
            if (chartId && window.warehouseCharts) {
                window.warehouseCharts.exportChart(chartId, filename);
            }
        });
    });
    
    // Add chart refresh functionality
    document.querySelectorAll('.refresh-chart').forEach(button => {
        button.addEventListener('click', async function() {
            const chartId = this.getAttribute('data-chart-id');
            const endpoint = this.getAttribute('data-endpoint');
            
            if (chartId && endpoint && window.warehouseCharts) {
                this.disabled = true;
                this.innerHTML = '🔄 Memuat...';
                
                try {
                    const response = await fetch(endpoint);
                    const data = await response.json();
                    window.warehouseCharts.updateChart(chartId, data);
                    warehouseSystem.showNotification('Chart diperbarui', 'success');
                } catch (error) {
                    warehouseSystem.showNotification('Gagal memperbarui chart', 'error');
                } finally {
                    this.disabled = false;
                    this.innerHTML = '🔄';
                }
            }
        });
    });
});