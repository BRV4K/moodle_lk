<<<<<<< HEAD
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
defined('MOODLE_INTERNAL') || die();

class block_my_dashboard extends block_base {
    public function init() {
        $this->title = 'Информация о курсах';
    }

=======
<?php
// Убедитесь, что скрипт выполняется в контексте Moodle
defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/blocklib.php');

class block_my_dashboard extends block_base {

    /**
     * Инициализация блока
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_my_dashboard');
    }

    /**
     * Получение содержимого блока
     * @return stdClass
     */
>>>>>>> c836d222092c327bd8ff26c3f9499ba0bb2e1597
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $USER, $DB;

<<<<<<< HEAD
        // Получаем курсы пользователя
        $courses = enrol_get_users_courses($USER->id, true, 'id, fullname');

        $this->content = new stdClass();
        $this->content->text = '';

        // Подключаем необходимые стили и скрипты
        $this->page->requires->css('/blocks/my_dashboard/styles.css');
        $this->page->requires->js('/blocks/my_dashboard/script.js');

        foreach ($courses as $course) {
            // Получение прогресса курса
            $progress = $DB->get_record('course_completions', ['course' => $course->id, 'userid' => $USER->id]);
            $completion = $progress ? $progress->percentagecompleted : 0;

            // Получение ближайшего дедлайна
            $deadlines = $DB->get_records('assign', ['course' => $course->id], 'duedate ASC', 'name, duedate');
            $closest_deadline = null;
            if (!empty($deadlines)) {
                $closest_deadline = reset($deadlines);
                $days_until_deadline = ceil(($closest_deadline->duedate - time()) / (60 * 60 * 24));
            } else {
                $days_until_deadline = 'нет дедлайнов';
            }

            // Получение данных по тестам
            $grades = $DB->get_records_sql(
                "SELECT q.name AS quiz, g.grade, q.grade AS maxgrade
                 FROM {quiz_grades} g
                 JOIN {quiz} q ON g.quiz = q.id
                 WHERE q.course = ? AND g.userid = ?",
                [$course->id, $USER->id]
            );
            $grades_data = [];
            foreach ($grades as $grade) {
                $percentage = round(($grade->grade / $grade->maxgrade) * 100, 2);
                $grades_data[] = ['name' => $grade->quiz, 'percentage' => $percentage];
            }
            $grades_data_json = json_encode($grades_data);

            // Формируем блок для курса
            $this->content->text .= '<div class="block_my_dashboard course-block" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">';
            $this->content->text .= '<h3>' . format_string($course->fullname) . '</h3>';

            // Строка ближайшего дедлайна
            $this->content->text .= '<p>Ближайший дедлайн через: ' . $days_until_deadline . ' дней</p>';

            // Круговая диаграмма (общий прогресс по курсу)
            $this->content->text .= '<div style="text-align: center; margin-bottom: 20px;">
                <canvas class="progress-chart" data-completion="' . $completion . '" style="max-width: 200px;"></canvas>
            </div>';

            // График тестов (процент выполнения)
            $this->content->text .= '<div style="text-align: center; margin-bottom: 20px;">
                <canvas class="grades-chart" data-grades=\'' . htmlspecialchars($grades_data_json, ENT_QUOTES, 'UTF-8') . '\' style="max-width: 300px;"></canvas>
            </div>';

            $this->content->text .= '</div>';
        }

        if (empty($courses)) {
            $this->content->text = '<p>Курсы не найдены.</p>';
=======
        $this->content = new stdClass();
        $this->content->text = '';

        // Получение курсов пользователя
        $courses = $this->get_user_courses($USER->id);

        if (!empty($courses)) {
            foreach ($courses as $course) {
                $this->content->text .= $this->render_course_section($course);
            }
        } else {
            $this->content->text .= html_writer::tag('p', get_string('nocourses', 'block_my_dashboard'));
>>>>>>> c836d222092c327bd8ff26c3f9499ba0bb2e1597
        }

        return $this->content;
    }

<<<<<<< HEAD
    public function applicable_formats() {
        return array('site' => true, 'my' => true);
=======
    /**
     * Получение курсов пользователя
     * @param int $userid
     * @return array
     */
    private function get_user_courses($userid) {
        global $DB;

        $sql = "SELECT c.id, c.fullname
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE ue.userid = ?";

        return $DB->get_records_sql($sql, [$userid]);
    }

    /**
     * Рендеринг секции курса
     * @param stdClass $course
     * @return string
     */
    private function render_course_section($course) {
        global $USER;

        $content = html_writer::tag('h2', $course->fullname);
        $content .= html_writer::start_div('course-dashboard');

        // Получение прогресса курса
        $progress = $this->get_progress($course->id);
        $content .= html_writer::tag('p', get_string('progress', 'block_my_dashboard') . ': ' . $progress . '%');

        // Генерация графиков
        $quizData = $this->get_quiz_sumgrades_chart_data($USER->id);
        $statusData = $this->get_quiz_status_chart_data($USER->id);
        $avgTimeData = $this->get_avg_time_chart_data($USER->id);
        $content .= $this->generate_charts($quizData, $statusData, $avgTimeData);

        $content .= html_writer::end_div();

        return $content;
    }

    /**
     * Получение прогресса курса
     * @param int $courseid
     * @return float
     */
    private function get_progress($courseid) {
        global $DB;

        $sql = "SELECT COUNT(*) AS total,
                       SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END) AS completed
                FROM {course_modules} cm
                JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                WHERE cm.course = ? AND cm.visible = 1";

        $progress = $DB->get_record_sql($sql, [$courseid]);
        return ($progress && $progress->total > 0) ? round(($progress->completed / $progress->total) * 100, 2) : 0;
    }

    /**
     * Получение данных по оценкам квизов
     * @param int $userid
     * @return array
     */
    private function get_quiz_sumgrades_chart_data($userid) {
        global $DB;

        $sql = "SELECT q.name, SUM(gg.finalgrade) AS total
                FROM {quiz} q
                JOIN {grade_items} gi ON gi.iteminstance = q.id
                JOIN {grade_grades} gg ON gg.itemid = gi.id
                WHERE gi.itemmodule = 'quiz' AND gg.userid = ?
                GROUP BY q.id";

        return $DB->get_records_sql($sql, [$userid]);
    }

    /**
     * Получение данных по статусу квизов
     * @param int $userid
     * @return array
     */
    private function get_quiz_status_chart_data($userid) {
        global $DB;

        $sql = "SELECT q.name, COUNT(*) AS attempts
                FROM {quiz} q
                JOIN {quiz_attempts} qa ON qa.quiz = q.id
                WHERE qa.userid = ?
                GROUP BY q.id";

        return $DB->get_records_sql($sql, [$userid]);
    }

    /**
     * Получение среднего времени выполнения квизов
     * @param int $userid
     * @return array
     */
    private function get_avg_time_chart_data($userid) {
        global $DB;

        $sql = "SELECT q.name, AVG(qa.timefinish - qa.timecreate) AS avg_time
                FROM {quiz} q
                JOIN {quiz_attempts} qa ON qa.quiz = q.id
                WHERE qa.userid = ?
                GROUP BY q.id";

        return $DB->get_records_sql($sql, [$userid]);
    }

    /**
     * Генерация HTML для графиков
     * @param array $quizData
     * @param array $statusData
     * @param array $avgTimeData
     * @return string
     */
    private function generate_charts($quizData, $statusData, $avgTimeData) {
        // Подготовка данных для графиков
        $quizNames = array_column($quizData, 'name');
        $quizScores = array_column($quizData, 'total');
        $quizAttempts = array_column($statusData, 'attempts');
        $avgTimes = array_column($avgTimeData, 'avg_time');

        $chartsHTML = html_writer::start_div('charts-container');
        $chartsHTML .= html_writer::tag('canvas', '', ['id' => 'quizScoresChart']);
        $chartsHTML .= html_writer::tag('canvas', '', ['id' => 'quizAttemptsChart']);
        $chartsHTML .= html_writer::tag('canvas', '', ['id' => 'avgTimesChart']);
        $chartsHTML .= html_writer::end_div();

        $chartsHTML .= html_writer::script(
            "// JavaScript для графиков (Chart.js)
            var ctx1 = document.getElementById('quizScoresChart').getContext('2d');
            new Chart(ctx1, { type: 'bar', data: { labels: " . json_encode($quizNames) . ", datasets: [{ data: " . json_encode($quizScores) . " }] } });
            var ctx2 = document.getElementById('quizAttemptsChart').getContext('2d');
            new Chart(ctx2, { type: 'pie', data: { labels: " . json_encode($quizNames) . ", datasets: [{ data: " . json_encode($quizAttempts) . " }] } });
            var ctx3 = document.getElementById('avgTimesChart').getContext('2d');
            new Chart(ctx3, { type: 'line', data: { labels: " . json_encode($quizNames) . ", datasets: [{ data: " . json_encode($avgTimes) . " }] } });"
        );

        return $chartsHTML;
>>>>>>> c836d222092c327bd8ff26c3f9499ba0bb2e1597
    }
}
