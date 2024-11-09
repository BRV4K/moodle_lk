<?php
class user_deadlines extends block_base {
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
        $courses = enrol_get_users_courses($USER->id, true);

        if ($courses) {
            foreach ($courses as $course) {
                // Получаем информацию о прогрессе и дедлайнах
                $progress = $this->get_progress($course->id);
                $deadlines = $this->get_deadlines($course->id);

                $this->content->text .= html_writer::tag('h3', $course->fullname);
                $this->content->text .= html_writer::tag('p', "Прогресс: $progress%");
                $this->content->text .= html_writer::tag('p', "Дедлайны: $deadlines");
            }
        } else {
            $this->content->text .= html_writer::tag('p', get_string('nocourses', 'block_my_dashboard'));
        }

        $this->content->text .= html_writer::link(new moodle_url('/blocks/my_dashboard/page.php'), get_string('viewdetails', 'block_my_dashboard'));

        // Подключаем CSS и JS
        $this->page->requires->css('/blocks/my_dashboard/styles.css');
        $this->page->requires->js('/blocks/my_dashboard/script.js');

        return $this->content;
    }

    private function get_progress($courseid) {
        // Вставьте логику получения прогресса пользователя в данном курсе.
        return rand(0, 100); // Замените это на реальную логику
    }

    private function get_deadlines($courseid) {
        // Вставьте логику получения дедлайнов для заданий в курсе.
        return "Ближайший дедлайн: " . date('d-m-Y', strtotime('+5 days')); // Замените на реальную логику
    }
}