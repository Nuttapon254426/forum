function initializeCharts() {
    // Area Chart
    const areaChart = document.getElementById("myAreaChart");
    if (areaChart) {
        fetch('/forum/api/get-stats.php')
            .then(response => response.json())
            .then(data => {
                new Chart(areaChart, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: "จำนวนผู้เข้าชม",
                            lineTension: 0.3,
                            backgroundColor: "rgba(78, 115, 223, 0.05)",
                            borderColor: "rgba(78, 115, 223, 1)",
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(78, 115, 223, 1)",
                            pointBorderColor: "rgba(78, 115, 223, 1)",
                            pointHoverRadius: 3,
                            pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                            pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            data: data.views
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                        scales: {
                            xAxes: [{ gridLines: { display: false, drawBorder: false } }],
                            yAxes: [{ ticks: { beginAtZero: true } }]
                        },
                        legend: { display: false },
                        tooltips: { backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", titleMarginBottom: 10, titleFontColor: '#6e707e', titleFontSize: 14, borderColor: '#dddfeb', borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: false, intersect: false, mode: 'index', caretPadding: 10 }
                    }
                });
            });
    }

    // Pie Chart
    const pieChart = document.getElementById("myPieChart");
    if (pieChart) {
        fetch('/forum/api/get-category-stats.php')
            .then(response => response.json())
            .then(data => {
                new Chart(pieChart, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.counts,
                            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617'],
                            hoverBorderColor: "rgba(234, 236, 244, 1)"
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        tooltips: { backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", borderColor: '#dddfeb', borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: false, caretPadding: 10 },
                        legend: { display: false },
                        cutoutPercentage: 80
                    }
                });
            });
    }
}

// Call initialization when DOM is ready
document.addEventListener('DOMContentLoaded', initializeCharts);
