document.addEventListener("DOMContentLoaded", function () {
    // Логика для кнопки "Показать все дедлайны"
    const viewAllButton = document.querySelector('.view-all-deadlines');
    const hiddenDeadlines = document.querySelectorAll('.hidden-deadline');

    if (viewAllButton && hiddenDeadlines) {
        viewAllButton.addEventListener('click', function () {
            hiddenDeadlines.forEach(item => item.classList.remove('hidden-deadline'));
            viewAllButton.style.display = 'none'; // Скрываем кнопку после раскрытия
        });
    }

    // Инициализация круговых диаграмм
    const progressCharts = document.querySelectorAll(".progress-chart");
    progressCharts.forEach((chart) => {
        const completion = parseFloat(chart.getAttribute("data-completion")) || 0;
        new Chart(chart, {
            type: "doughnut",
            data: {
                datasets: [
                    {
                        data: [completion, 100 - completion],
                        backgroundColor: ["#3371CE", "#E9F2FF"],
                    },
                ],
            },
            options: {
                plugins: {
                    legend: { display: false },
                },
            },
        });
    });

    // Инициализация столбчатых диаграмм
    const gradesCharts = document.querySelectorAll(".grades-chart");
    gradesCharts.forEach((chart) => {
        const gradesData = JSON.parse(chart.getAttribute("data-grades") || "[]");
        new Chart(chart, {
            type: "bar",
            data: {
                labels: gradesData.map((g) => g.name),
                datasets: [
                    {
                        label: "Процент выполнения",
                        data: gradesData.map((g) => g.percentage),
                        backgroundColor: "#3371CE",
                    },
                ],
            },
            options: {
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true, max: 100 },
                },
            },
        });
    });

    // Инициализация графиков активности по дням
    const activityCharts = document.querySelectorAll(".activity-chart");
    activityCharts.forEach((chart) => {
        const activityData = JSON.parse(chart.getAttribute("data-activity") || "[]");

        // Проверка данных для обработки
        const labels = activityData.map((entry) => entry.date);
        const dataPoints = activityData.map((entry) => entry.minutes);

        new Chart(chart, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Активность (минуты)",
                        data: dataPoints,
                        borderColor: "#3371CE",
                        backgroundColor: "#E9F2FF",
                        fill: true,
                    },
                ],
            },
            options: {
                plugins: {
                    legend: { display: true },
                },
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    });
});