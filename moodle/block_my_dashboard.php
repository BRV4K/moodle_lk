<?php
defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/blocklib.php');

class block_my_dashboard extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_my_dashboard');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $USER, $DB;

        $this->content = new stdClass();
        $this->content->text = '';

        // Получаем текущие курсы пользователя
        $courses = $DB->get_records_sql("
            SELECT c.id, c.fullname
            FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE ue.userid = ?", array($USER->id));

        if ($courses) {
            foreach ($courses as $course) {
                $this->content->text .= html_writer::tag('h2', $course->fullname);
                $this->content->text .= html_writer::start_div('course-dashboard');

                // Получение и отображение прогресса курса
                $progress = $this->get_progress($course->id);
                $this->content->text .= html_writer::tag('p', 'Прогресс: ' . $progress . '%');

                // Получаем ключевые данные
                $deadlines = $this->get_deadlines($course->id);
                $quizData = $this->get_quiz_sumgrades_chart_data($USER->id);
                $statusData = $this->get_quiz_status_chart_data($USER->id);
                $avgTimeData = $this->get_avg_time_chart_data($USER->id);

                // Добавляем визуализацию
                $this->content->text .= $this->generate_charts($quizData, $statusData, $avgTimeData);

                $this->content->text .= html_writer::end_div(); // Закрываем курс-dashboard
            }
        } else {
            $this->content->text .= html_writer::tag('p', 'Нет доступных курсов.');
        }

        return $this->content;
    }

    private function get_progress($courseid) {
        global $DB, $USER;

        // Получение данных о прогрессе курса
        $sql = "
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN cm.completionstate = 1 THEN 1 ELSE 0 END) AS completed
            FROM {course_modules} cm
            JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
            WHERE cm.course = ? AND cm.visible = 1";

        $progress = $DB->get_record_sql($sql, array($courseid));
        return $progress->total > 0 ? round(($progress->completed / $progress->total) * 100, 2) : 0;
    }

    private function get_deadlines($courseid) {
        global $DB;

        // Получение сроков выполнения заданий
        return $DB->get_records_sql("
            SELECT a.name, a.duedate
            FROM {assign} a
            WHERE a.course = ?
            AND a.duedate > ?", array($courseid, time()));
    }

    private function get_quiz_sumgrades_chart_data($userid) {
        global $DB;

        // Получение данных по сумме оценок квизов
        return $DB->get_records_sql("
            SELECT q.name, SUM(gg.finalgrade) AS total
            FROM {quiz} q
            JOIN {grade_items} gi ON gi.iteminstance = q.id
            JOIN {grade_grades} gg ON gg.itemid = gi.id
            WHERE gi.itemmodule = 'quiz' AND gg.userid = ?
            GROUP BY q.id", array($userid));
    }

    private function get_quiz_status_chart_data($userid) {
        global $DB;

        // Получение данных о статусе квизов
        return $DB->get_records_sql("
            SELECT q.name, COUNT(*) AS attempts
            FROM {quiz} q
            JOIN {quiz_attempts} qa ON qa.quiz = q.id
            WHERE qa.userid = ?
            GROUP BY q.id", array($userid));
    }

    private function get_avg_time_chart_data($userid) {
        global $DB;

        // Получение данных по среднему времени выполнения квизов
        return $DB->get_records_sql("
            SELECT q.name, AVG(qa.timefinish - qa.timecreate) AS avg_time
            FROM {quiz} q
            JOIN {quiz_attempts} qa ON qa.quiz = q.id
            WHERE qa.userid = ?
            GROUP BY q.id", array($userid));
    }

    private function generate_charts($quizData, $statusData, $avgTimeData) {
        $quizNames = [];
        $quizScores = [];
        $quizAttempts = [];
        $avgTimes = [];

        foreach ($quizData as $data) {
            $quizNames[] = $data->name;
            $quizScores[] = $data->total;
        }

        foreach ($statusData as $data) {
            $quizAttempts[$data->name] = $data->attempts;
        }

        foreach ($avgTimeData as $data) {
            $avgTimes[$data->name] = $data->avg_time;
        }

        // Генерация HTML для графиков с использованием Chart.js
        $chartsHTML = html_writer::start_div('charts-container');
        $chartsHTML .= html_writer::tag('canvas', '', ['id' => 'quizScoresChart']);
        $chartsHTML .= html_writer::tag('canvas', '', ['id' => 'quizAttemptsChart']);
        $chartsHTML .= html_writer::tag('canvas', '', ['id' => 'avgTimesChart']);
        $chartsHTML .= html_writer::end_div();

        // Сценарий для инициализации графиков
        $chartsHTML .= html_writer::script("
            var ctx1 = document.getElementById('quizScoresChart').getContext('2d');
            var quizScoresChart = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: " . json_encode($quizNames) . ",
                    datasets: [{
                        label: 'Сумма оценок',
                        data: " . json_encode($quizScores) . ",
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });

            var ctx2 = document.getElementById('quizAttemptsChart').getContext('2d');
            var quizAttemptsChart = new Chart(ctx2, {
                type: 'pie',
                data: {
                    labels: " . json_encode($quizNames) . ",
                    datasets: [{
                        label: 'Количество попыток',
                        data: " . json_encode(array_values($quizAttempts)) . ",
                        backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)'],
                    }]
                },
                options: { responsive: true }
            });

            var ctx3 = document.getElementById('avgTimesChart').getContext('2d');
            var avgTimesChart = new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: " . json_encode($quizNames) . ",
                    datasets: [{
                        label: 'Среднее время (секундах)',
                        data: " . json_encode(array_values($avgTimes)) . ",
                        borderColor: 'rgba(255, 206, 86, 1)',
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        fill: true
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        ");

        return $chartsHTML;
    }
}