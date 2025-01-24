<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
/**
 * Файл block_my_dashboard.php
 * Обновлённое разделение информации по курсам и времени
 */

defined('MOODLE_INTERNAL') || die();

class block_my_dashboard extends block_base {

    public function init() {
        $this->title = 'Личный кабинет';
    }

    public function get_content() {
        global $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $userid = $USER->id;
        $userfullname = fullname($USER);

        try {
            // Основной блок контента
            $this->content->text = '<div class="dashboard-wrapper">';

            // Карточка пользователя
            $this->content->text .= '<div class="user-card">
                <div></div>
                <div>
                    <h2>' . $userfullname . '</h2>
                    <p>Email: ' . $USER->email . '</p>
                </div>
            </div>';

            // Получение курсов пользователя
            try {
                $courses = $DB->get_records_sql(
                    "SELECT c.id AS course_id, c.fullname AS course_name
                     FROM {course} c
                     JOIN {enrol} e ON e.courseid = c.id
                     JOIN {user_enrolments} ue ON ue.enrolid = e.id
                     WHERE ue.userid = ?",
                    [$userid]
                );
            } catch (Exception $e) {
                $this->content->text .= '<p>Не удалось загрузить курсы: ' . $e->getMessage() . '</p>';
                return $this->content;
            }

            foreach ($courses as $course) {
                $this->content->text .= '<div class="course-block">';
                $this->content->text .= '<h3>' . $course->course_name . '</h3>';

                //Блок со всеми графиками
                $this->content->text .= '<div class="course-info">';
                // Блок с дедлайнами и прогрессом
                $this->content->text .= '<div class="course-details">';

               try {
                   // Дедлайны по курсу
                   $deadlines = $DB->get_records_sql(
                       "SELECT name, timestart
                        FROM {event}
                        WHERE timestart > UNIX_TIMESTAMP() AND courseid = ?
                        ORDER BY timestart ASC",
                       [$course->course_id]
                   );

                   $this->content->text .= '<div class="card">
                       <h4>Дедлайны</h4>';

                   if ($deadlines) {
                       $count = 0;
                       $this->content->text .= '<div class="deadlines-container">';

                       foreach ($deadlines as $deadline) {
                           $this->content->text .= '<p class="deadline-item' . ($count >= 3 ? ' hidden-deadline' : '') . '">' .
                               $deadline->name . ' - ' . userdate($deadline->timestart) . '</p>';
                           $count++;
                       }

                       $this->content->text .= '</div>';

                       if ($count > 3) {
                           $this->content->text .= '<button class="view-all-deadlines">Показать все дедлайны</button>';
                       }
                   } else {
                       $this->content->text .= '<p>Нет ближайших дедлайнов.</p>';
                   }

                   $this->content->text .= '</div>';
               } catch (Exception $e) {
                   $this->content->text .= '<p>Ошибка загрузки дедлайнов: ' . $e->getMessage() . '</p>';
               }


                try {
                    // Прогресс по курсу
                    $course_grades = $DB->get_record_sql(
                        "SELECT SUM(q.sumgrades) AS max_grades,
                                SUM(COALESCE(qa.sumgrades, 0)) AS user_grades
                         FROM {quiz} q
                         JOIN {course_modules} cm ON cm.instance = q.id
                         JOIN {modules} m ON m.id = cm.module
                         LEFT JOIN (
                             SELECT qa.quiz, MAX(qa.id) AS last_attempt_id
                             FROM {quiz_attempts} qa
                             WHERE qa.userid = ?
                             GROUP BY qa.quiz
                         ) last_attempts ON q.id = last_attempts.quiz
                         LEFT JOIN {quiz_attempts} qa ON qa.id = last_attempts.last_attempt_id
                         WHERE q.course = ?
                           AND q.id IN (
                               SELECT MAX(q2.id)
                               FROM {quiz} q2
                               WHERE q2.course = ?
                               GROUP BY q2.name
                           )
                           AND cm.visible = 1
                           AND m.name = 'quiz';",
                        [$userid, $course->course_id, $course->course_id]
                    );


                    $max_grades = $course_grades->max_grades ?? 0;
                    $user_grades = $course_grades->user_grades ?? 0;

                    $completion = ($max_grades > 0) ? round(($user_grades / $max_grades) * 100, 2) : 0;

                    $this->content->text .= '<div class="card">
                        <h4>Прогресс</h4>
                        <canvas class="progress-chart" data-completion="' . $completion . '"></canvas>
                    </div>';
                } catch (Exception $e) {
                    $this->content->text .= '<p>Ошибка загрузки прогресса: ' . $e->getMessage() . '</p>';
                }

                $this->content->text .= '</div>'; // Закрытие course-details

                try {
                    // Тесты по курсу
                    $tests = $DB->get_records_sql(
                        "SELECT q.name AS test_name,
                                q.sumgrades AS max_grades,
                                COALESCE(qa.sumgrades, 0) AS user_grades
                         FROM {quiz} q
                         JOIN {course_modules} cm ON cm.instance = q.id
                         JOIN {modules} m ON m.id = cm.module
                         LEFT JOIN (
                             SELECT qa.quiz, MAX(qa.id) AS last_attempt_id
                             FROM {quiz_attempts} qa
                             WHERE qa.userid = ?
                             GROUP BY qa.quiz
                         ) last_attempts ON q.id = last_attempts.quiz
                         LEFT JOIN {quiz_attempts} qa ON qa.id = last_attempts.last_attempt_id
                         WHERE q.course = ?
                           AND q.id IN (
                               SELECT MAX(q2.id)
                               FROM {quiz} q2
                               WHERE q2.course = ?
                               GROUP BY q2.name
                           )
                           AND cm.visible = 1
                           AND m.name = 'quiz';",
                        [$userid, $course->course_id, $course->course_id]
                    );


                    $testdata = [];
                    foreach ($tests as $test) {
                        $percentage = ($test->max_grades > 0) ? round(($test->user_grades / $test->max_grades) * 100, 2) : 0;
                        $testdata[] = [
                            'name' => $test->test_name,
                            'percentage' => $percentage
                        ];
                    }

                    $this->content->text .= '<div class="card">
                        <h4>Тесты</h4>
                        <canvas class="grades-chart" data-grades="' . htmlspecialchars(json_encode($testdata), ENT_QUOTES, 'UTF-8') . '"></canvas>
                    </div>';
                } catch (Exception $e) {
                    $this->content->text .= '<p>Ошибка загрузки тестов: ' . $e->getMessage() . '</p>';
                }

                $this->content->text .= '</div>'; // Закрытие course-info
                $this->content->text .= '</div>'; // Закрытие course-block
            }

            try {
                // Время на платформе
                $time_data = $DB->get_records_sql(
                    "SELECT
                        FROM_UNIXTIME(timecreated, '%Y-%m-%d') AS activity_date,
                        COUNT(*) AS activity_count
                     FROM {logstore_standard_log}
                     WHERE userid = ?
                     GROUP BY FROM_UNIXTIME(timecreated, '%Y-%m-%d')
                     ORDER BY activity_date;",
                    [$userid]
                );

                $activity_data = [];
                foreach ($time_data as $row) {
                    $activity_data[] = [
                        'date' => $row->activity_date,
                        'count' => $row->activity_count,
                        'minutes' => $row->activity_count
                    ];
                }

                $this->content->text .= '<div class="card">
                    <h3>Активность по дням</h3>
                    <canvas class="activity-chart" data-activity="' . htmlspecialchars(json_encode($activity_data), ENT_QUOTES, 'UTF-8') . '"></canvas>
                </div>';

                $active_days = count($activity_data);

                $this->content->text .= '<div class="time-details" style="display: flex; gap: 20px;">';

                $this->content->text .= '<div class="card" style="flex: 1;">
                    <h3>Активные дни</h3>
                    <p>' . $active_days . '</p>
                </div>';

                $this->content->text .= '<div class="card" style="flex: 1;">
                    <h3>Всего часов за месяц</h3>
                    <p>' . round(array_sum(array_column($activity_data, 'minutes')) / 60, 2) . ' часов</p>
                </div>';

                $this->content->text .= '</div>'; // Закрытие time-details
            } catch (Exception $e) {
                $this->content->text .= '<p>Ошибка загрузки данных времени: ' . $e->getMessage() . '</p>';
            }

            $this->content->text .= '</div>'; // Закрытие dashboard-wrapper
        } catch (Exception $e) {
            $this->content->text = '<p>Произошла ошибка: ' . $e->getMessage() . '</p>';
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