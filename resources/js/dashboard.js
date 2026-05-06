document.addEventListener('DOMContentLoaded', function() {
    function getChartOptions(isDark) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: isDark ? '#ffffff' : '#000000'
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: isDark ? '#ffffff' : '#000000'
                    },
                    grid: {
                        color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    }
                },
                y: {
                    ticks: {
                        color: isDark ? '#ffffff' : '#000000'
                    },
                    grid: {
                        color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        };
    }

    const data = window.chartData;

    function updateCharts() {
        const isDark = document.body.classList.contains('dark-mode');
        const options = getChartOptions(isDark);

        if (data.hasVisits) {
            const visitsChart = Chart.getChart('visitsByDayChart');
            if (visitsChart) {
                visitsChart.options = { ...visitsChart.options, ...options };
                visitsChart.update();
            }
        }

        if (data.hasAppointments) {
            const appointmentsChart = Chart.getChart('appointmentsStatusChart');
            if (appointmentsChart) {
                appointmentsChart.options = { ...appointmentsChart.options, ...options };
                appointmentsChart.update();
            }
        }

        if (data.hasPatients) {
            const patientsChart = Chart.getChart('patientsByDepartmentChart');
            if (patientsChart) {
                patientsChart.options = { ...patientsChart.options, ...options };
                patientsChart.update();
            }
        }

        if (data.hasMonthly) {
            const monthlyChart = Chart.getChart('monthlyAppointmentsChart');
            if (monthlyChart) {
                monthlyChart.options = { ...monthlyChart.options, ...options };
                monthlyChart.update();
            }
        }
    }

    if (data.hasVisits) {
        new Chart(document.getElementById('visitsByDayChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: data.visitsLabels,
                datasets: [{
                    label: 'الزيارات',
                    data: data.visitsData,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: getChartOptions(document.body.classList.contains('dark-mode'))
        });
    }

    if (data.hasAppointments) {
        new Chart(document.getElementById('appointmentsStatusChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: data.appointmentsLabels,
                datasets: [{
                    data: data.appointmentsData,
                    backgroundColor: [
                        '#ffc107',
                        '#28a745',
                        '#dc3545'
                    ]
                }]
            },
            options: getChartOptions(document.body.classList.contains('dark-mode'))
        });
    }

    if (data.hasPatients) {
        new Chart(document.getElementById('patientsByDepartmentChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.patientsLabels,
                datasets: [{
                    label: 'عدد المرضى',
                    data: data.patientsData,
                    backgroundColor: 'rgba(40, 167, 69, 0.5)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: getChartOptions(document.body.classList.contains('dark-mode'))
        });
    }

    if (data.hasMonthly) {
        new Chart(document.getElementById('monthlyAppointmentsChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: data.monthlyLabels,
                datasets: [{
                    label: 'المواعيد',
                    data: data.monthlyData,
                    borderColor: '#6610f2',
                    backgroundColor: 'rgba(102, 16, 242, 0.2)',
                    fill: true,
                    tension: 0.2
                }]
            },
            options: getChartOptions(document.body.classList.contains('dark-mode'))
        });
    }

    // تحديث المخططات عند تغيير الوضع
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            setTimeout(updateCharts, 100); // تأخير بسيط للتأكد من تطبيق الوضع
        });
    }

    // مراقبة تغييرات الوضع الداكن
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                updateCharts();
            }
        });
    });
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
});