document.addEventListener("DOMContentLoaded", function () {
    //Функция скрытия/открытия информации о курсе
    document.querySelectorAll('.toggle-course').forEach(button => {
        button.addEventListener('click', function () {
            const courseDetails = this.closest('.course-block').querySelector('.course-details');
            const isVisible = courseDetails.style.display === 'block';

            courseDetails.style.display = isVisible ? 'none' : 'block';
            this.textContent = isVisible ? '▼' : '▲';
        });
    });
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
                        backgroundColor: ["#4caf50", "#e0e0e0"],
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
                        backgroundColor: "#4caf50",
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
                        borderColor: "#4caf50",
                        backgroundColor: "rgba(76, 175, 80, 0.2)",
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
