<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
defined('MOODLE_INTERNAL') || die();

class block_my_dashboard extends block_base {
    public function init() {
        $this->title = 'Информация о курсах';
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $USER, $DB;

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
        }

        return $this->content;
    }

    public function applicable_formats() {
        return array('site' => true, 'my' => true);
    }
}
