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
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $USER, $DB;

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
        }

        return $this->content;
    }

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
    }
}
