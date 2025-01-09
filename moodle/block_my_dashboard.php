<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
/**
 * Файл block_my_dashboard.php
 * Отображение личного кабинета в Moodle
 */

defined('MOODLE_INTERNAL') || die();

class block_my_dashboard extends block_base {

    public function init() {
        $this->title = 'Информация о курсах';
    }

    public function get_content() {
        global $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $userid = $USER->id;

        try {
            // Получение данных о курсах
            $courses = $DB->get_records_sql(
                "SELECT c.id AS course_id, c.fullname AS course_name
                 FROM {course} c
                 JOIN {enrol} e ON e.courseid = c.id
                 JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = ?",
                [$userid]
            );

            $this->content->text = '<div class="block_my_dashboard">';
            foreach ($courses as $course) {
                // Начало блока курса
                $this->content->text .= '<div class="course-container" style="margin-bottom: 20px;">
                    <h3 class="course-header" style="cursor: pointer;">' . $course->course_name . '</h3>
                    <div class="course-content" style="padding: 15px; border: 1px solid #ddd; border-radius: 8px;">';

                // Общая оценка курса (на основе тестов)
                $course_grades = $DB->get_record_sql(
                    "SELECT SUM(COALESCE(qa.sumgrades, 0)) AS user_grades, SUM(q.sumgrades) AS max_grades
                     FROM {quiz} q
                     LEFT JOIN {quiz_attempts} qa ON q.id = qa.quiz AND qa.userid = ?
                     WHERE q.course = ? AND q.id IN (
                         SELECT MAX(id) FROM {quiz} GROUP BY name
                     )",
                    [$userid, $course->course_id]
                );

                $completion = ($course_grades->max_grades > 0) ? round(($course_grades->user_grades / $course_grades->max_grades) * 100, 2) : 0;
                $this->content->text .= '<div style="text-align: center; margin-bottom: 20px;">
                    <canvas class="progress-chart" data-completion="' . $completion . '" style="max-width: 200px;"></canvas>
                </div>';

                // Прогресс тестов для курса
                $tests = $DB->get_records_sql(
                    "SELECT q.name AS test_name, q.sumgrades AS max_grades,
                            COALESCE(MAX(qa.sumgrades), 0) AS user_grades
                     FROM {quiz} q
                     LEFT JOIN {quiz_attempts} qa ON q.id = qa.quiz AND qa.userid = ?
                     WHERE q.course = ? AND q.id IN (
                         SELECT MAX(id) FROM {quiz} GROUP BY name
                     )
                     GROUP BY q.id, q.name, q.sumgrades",
                    [$userid, $course->course_id]
                );

                $testdata = [];
                foreach ($tests as $test) {
                    $percentage = ($test->max_grades > 0) ? round(($test->user_grades / $test->max_grades) * 100, 2) : 0;
                    $testdata[] = [
                        'name' => $test->test_name,
                        'percentage' => $percentage
                    ];
                }
                $this->content->text .= '<div style="text-align: center; margin-bottom: 20px;">
                    <canvas class="grades-chart" data-grades=\'' . htmlspecialchars(json_encode($testdata), ENT_QUOTES, 'UTF-8') . '\' style="max-width: 400px; height: 200px;"></canvas>
                </div>';

                // Получение дедлайнов для курса
                $deadlines = $DB->get_records_sql(
                    "SELECT id, name, timestart, eventtype
                     FROM {event}
                     WHERE timestart > UNIX_TIMESTAMP() AND courseid = ?
                     ORDER BY timestart ASC",
                    [$course->course_id]
                );

                if ($deadlines) {
                    $nearest_deadline = reset($deadlines);
                    $this->content->text .= '<p>Ближайший дедлайн: ' . $nearest_deadline->name . ' (' . userdate($nearest_deadline->timestart) . ')</p>';

                    // Кнопка для раскрытия всех дедлайнов
                    $this->content->text .= '<button onclick="toggleDeadlines(this)" style="cursor: pointer;">Показать все дедлайны</button>';
                    $this->content->text .= '<ul class="deadlines-list" style="display: none;">';
                    foreach ($deadlines as $deadline) {
                        $type_label = ($deadline->eventtype === 'open') ? 'Открытие' : 'Закрытие';
                        $this->content->text .= '<li>' . $type_label . ': ' . $deadline->name . ' (' . userdate($deadline->timestart) . ')</li>';
                    }
                    $this->content->text .= '</ul>';
                } else {
                    $this->content->text .= '<p>Нет ближайших дедлайнов.</p>';
                }

                $this->content->text .= '</div>'; // Закрытие course-content
                $this->content->text .= '</div>'; // Закрытие course-container
            }
            $this->content->text .= '</div>'; // Закрытие block_my_dashboard
        } catch (Exception $e) {
            $this->content->text = '<p>Произошла ошибка. Обратитесь к администратору.</p>';
        }

        // Подключение скриптов и стилей
        $this->page->requires->js('/blocks/my_dashboard/script.js');
        $this->page->requires->css('/blocks/my_dashboard/styles.css');

        return $this->content;
    }

    public function applicable_formats() {
        return ['my' => true];
    }
}
