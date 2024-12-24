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
