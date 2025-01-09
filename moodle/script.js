document.addEventListener("DOMContentLoaded", function () {
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
                        backgroundColor: ["#2a72d4", "#e0e0e0"],
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
                        backgroundColor: "#2a72d4",
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

    // Функция для сворачивания/разворачивания курса
    window.toggleCourseBlock = function (header) {
        const container = header.nextElementSibling;
        const arrow = header.querySelector(".arrow");
        const isHidden = getComputedStyle(container).display === "none";
        container.style.display = isHidden ? "block" : "none";
        arrow.textContent = isHidden ? "▲" : "▼";
    };

    // Функция для сворачивания/разворачивания дедлайнов
    window.toggleDeadlines = function (button) {
        const deadlinesList = button.nextElementSibling;
        const arrow = button.querySelector(".arrow");
        const isHidden = getComputedStyle(deadlinesList).display === "none";
        deadlinesList.style.display = isHidden ? "block" : "none";
        button.innerHTML = isHidden ? "Скрыть все дедлайны <span class='arrow'>▲</span>" : "Показать все дедлайны <span class='arrow'>▼</span>";
    };
});
