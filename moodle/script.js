<<<<<<< HEAD
document.addEventListener("DOMContentLoaded", function () {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js не загружен.');
        return;
    }

    const charts = document.querySelectorAll('canvas');

    charts.forEach(chart => {
        const ctx = chart.getContext('2d');

        // Круговая диаграмма прогресса курса
        if (chart.classList.contains('progress-chart')) {
            const completion = chart.dataset.completion || 0;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Завершено', 'Осталось'],
                    datasets: [{
                        data: [completion, 100 - completion],
                        backgroundColor: ['#4caf50', '#ccc']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // График успеваемости по тестам
        if (chart.classList.contains('grades-chart')) {
            const grades = JSON.parse(chart.dataset.grades || '[]');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: grades.map(g => g.name),
                    datasets: [{
                        label: 'Процент выполнения',
                        data: grades.map(g => g.percentage),
                        backgroundColor: '#42a5f5'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    });
});



function toggleCourseInfo(button) {
    const courseInfo = button.parentElement.querySelector('.course-info');
    const arrow = button.querySelector('.arrow');
    if (courseInfo.style.display === "none") {
        courseInfo.style.display = "block";
        arrow.style.transform = "rotate(0deg)";
    } else {
        courseInfo.style.display = "none";
        arrow.style.transform = "rotate(180deg)";
    }
}
=======
document.addEventListener("DOMContentLoaded", function() {
    // Инициализация графика
    const ctx = document.getElementById("myChart").getContext("2d");
    const myChart = new Chart(ctx, {
        type: 'bar', // Тип графика
        data: {
            labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
            datasets: [{
                label: 'Прогресс',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(54, 162, 235, 0.2)', // Прозрачный синий
                borderColor: 'rgba(54, 162, 235, 1)', // Синий
                borderWidth: 1
            }]
        },
        options: {
            responsive: true, // Адаптивность
            maintainAspectRatio: false // Пропорциональность
        }
    });
});
>>>>>>> c836d222092c327bd8ff26c3f9499ba0bb2e1597
