<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
require_once(__DIR__ . '/../../config.php');


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
        $courses = $DB->get_records('enrol', array('id' => $USER->id));

        if ($courses) {
            foreach ($courses as $course) {

                $courseinfo = $DB->get_record('course', array('id' => $course->courseid));
                if ($courseinfo) {
                   
                    $progress = $this->get_progress($courseinfo->id);

                   
                    $deadlines = $this->get_deadlines($courseinfo->id);

                    $quizData = $this->get_quiz_sumgrades_chart_data($USER->id);
                    
                    $statusData = $this->get_quiz_status_chart_data($USER->id);

                    $avgTimeData = $this->get_quiz_avg_time_chart_data($USER->id);

                    $progressData = $this->get_course_progress_data($USER->id);
                    

                    $this->content->text .= html_writer::tag('h3', $courseinfo->fullname);
                    $this->content->text .= html_writer::tag('p', "Дедлайны: $deadlines");
                    $this->content->text .= html_writer::tag('h3', 'Информация о пользователе');
                    $this->content->text .= html_writer::tag('p', $this->get_user_info($USER->id));
                    $this->content->text .= html_writer::tag('h3', 'Информация о тестах пользователя');
                    $this->content->text .= html_writer::tag('p', $this->get_user_quiz_info($USER->id));
                    $this->content->text .= '<canvas id="quizSumgradesChart" width="400" height="200"></canvas>';
                    
                    $this->content->text .= '<canvas id="quizStatusChart" width="200" height="100"></canvas>';

                    $this->content->text .= '<canvas id="quizAvgTimeChart" width="400" height="200"></canvas>';

                    $this->content->text .= '<canvas id="courseProgressChart" width="400" height="200"></canvas>';

                    $this->content->text .= "
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('courseProgressChart').getContext('2d');
    var courseProgressChart = new Chart(ctx, {
        type: 'bar', // Баровый график
        data: {
            labels: " . json_encode($progressData['labels']) . ", // Курсы
            datasets: [{
                label: 'Процент выполнения курса',
                data: " . json_encode($progressData['progress']) . ", // Процент выполнения
                backgroundColor: 'rgba(54, 162, 235, 0.5)', // Цвет заливки
                borderColor: 'rgba(54, 162, 235, 1)', // Цвет рамки
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Процент выполнения' // Подпись для оси Y
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Курсы' // Подпись для оси X
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Отключаем легенду
                }
            }
        }
    });
});
</script>
";

                    $this->content->text .= "
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('quizAvgTimeChart').getContext('2d');
    var quizAvgTimeChart = new Chart(ctx, {
        type: 'line', // Линейный график
        data: {
            labels: " . json_encode($avgTimeData['labels']) . ",
            datasets: [{
                label: 'Среднее время (минуты)',
                data: " . json_encode($avgTimeData['avg_time']) . ",
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Время (минуты)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Тесты'
                    }
                }
            }
        }
    });
});
</script>
";

                    $this->content->text .= "
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('quizStatusChart').getContext('2d');
    var quizStatusChart = new Chart(ctx, {
        type: 'pie', // Круговая диаграмма
        data: {
            labels: " . json_encode($statusData['labels']) . ",
            datasets: [{
                label: 'Распределение статусов',
                data: " . json_encode($statusData['status_counts']) . ",
                backgroundColor: [
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 99, 132, 0.2)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
});
</script>
";



                    $this->content->text .= "
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('quizSumgradesChart').getContext('2d');
    var quizSumgradesChart = new Chart(ctx, {
        type: 'bar', // Столбчатый график
        data: {
            labels: " . json_encode($quizData['labels']) . ", // Названия тестов
            datasets: [{
                label: 'Суммарные баллы за тесты',
                data: " . json_encode($quizData['sumgrades']) . ", // Суммарные баллы
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Баллы'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Тесты'
                    }
                }
            }
        }
    });
});
</script>
";

                }
            }
        } else {
            $this->content->text .= html_writer::tag('p', get_string('nocourses', 'block_my_dashboard'));
        }

        $this->content->text .= html_writer::link(new moodle_url('/blocks/my_dashboard/page.php'), get_string('viewdetails', 'block_my_dashboard'));

        
        $this->page->requires->css('/blocks/my_dashboard/styles.css');
        $this->page->requires->js('/blocks/my_dashboard/script.js');

        return $this->content;
    }

    private function get_progress($courseid) {
        global $DB, $USER;

        
        $sql = "SELECT COUNT(id) as completed
                FROM {course_modules_completion}
                WHERE coursemoduleid > 0 AND userid > 0 AND completionstate = 1";

        $completion = $DB->get_record_sql($sql, array($courseid, $USER->id));

        return $completion ? $completion->completed : 0;
    }

    private function get_deadlines($courseid) {
        global $DB;

        
        $sql = "SELECT duedate
                FROM {assign}
                WHERE course > 0 AND duedate > 1
                ORDER BY duedate ASC LIMIT 1";

        $deadline = $DB->get_record_sql($sql, array($courseid, time()));

        if ($deadline) {
            return "Ближайший дедлайн: " . date('d-m-Y', $deadline->duedate);
        } else {
            return "Нет ближайших дедлайнов.";
        }
    }

    private function get_user_info($userid) {
        global $DB;
    
        
        $sql = "SELECT DISTINCT u.firstname, u.lastname, u.email, u.username, c.fullname AS course_name, r.name AS role, cmc.completionstate
                FROM {user} u
                LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                LEFT JOIN {role} r ON r.id = ra.roleid
                LEFT JOIN {course_modules_completion} cmc ON cmc.userid = u.id AND cmc.coursemoduleid = c.id
                WHERE u.id = :userid";
    
        
        $user_info = $DB->get_record_sql($sql, array('userid' => $userid));
    
        
        if ($user_info) {
            return "Имя: " . $user_info->lastname . "<br>" .
                   "Email: " . $user_info->email . "<br>" .
                   "Логин: " . $user_info->username . "<br>" .
                   "Курс: " . $user_info->course_name . "<br>" .
                   "Статус выполнения: " . ($user_info->completionstate == 1 ? 'Завершено' : 'Не завершено');
        } else {
            return "Информация о пользователе не найдена.";
        }
    }

    private function get_user_quiz_info($userid) {
        global $DB;
        
        
        $sql = "SELECT 
                    q.name AS quiz_name,  
                    q.course AS course_id,  
                    qa.attempt,  
                    qa.timestart,  
                    qa.timefinish,  
                    qa.sumgrades AS score,  
                    qa.state,  
                    qg.grade AS final_grade,  
                    u.firstname,  
                    u.lastname,  
                    u.email,  
                    u.username  
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id = qa.quiz  
                JOIN {user} u ON u.id = qa.userid  
                LEFT JOIN {quiz_grades} qg ON qg.quiz = qa.quiz AND qg.userid = qa.userid  
                WHERE qa.userid = :userid  
                ORDER BY qa.timestart DESC";
        
        
        $quiz_info = $DB->get_records_sql($sql, array('userid' => $userid));
        
        
        if ($quiz_info) {
            $output = "";
            foreach ($quiz_info as $quiz) {
                $output .= "Тест: " . $quiz->quiz_name . "<br>" .
                           "Попытка: " . $quiz->attempt . "<br>" .
                           "Время начала: " . date('d-m-Y H:i:s', $quiz->timestart) . "<br>" .
                           "Время завершения: " . date('d-m-Y H:i:s', $quiz->timefinish) . "<br>" .
                           "Баллы: " . $quiz->score . "<br>" .
                           "Статус: " . $quiz->state . "<br>" . "<br><br>";
            }
            return $output;
        } else {
            return "Информация о тестах пользователя не найдена.";
        }
    }

    private function get_quiz_sumgrades_chart_data($userid) {
        global $DB;
    
        
        $sql = "SELECT 
                    q.name AS quiz_name,
                    SUM(qa.sumgrades) AS total_sumgrades
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id = qa.quiz
                WHERE qa.userid = :userid
                GROUP BY q.id, q.name";
    
        $data = $DB->get_records_sql($sql, ['userid' => $userid]);
    
        $chartData = [
            'labels' => [],
            'sumgrades' => []
        ];
    
        foreach ($data as $record) {
            $chartData['labels'][] = $record->quiz_name; 
            $chartData['sumgrades'][] = $record->total_sumgrades;
        }
    
        return $chartData;
    }

    
    private function get_quiz_status_chart_data($userid) {
        global $DB;
    
        
        $sql = "SELECT 
                    qa.state,
                    COUNT(qa.id) AS count
                FROM {quiz_attempts} qa
                WHERE qa.userid = :userid
                GROUP BY qa.state";
    
        $data = $DB->get_records_sql($sql, ['userid' => $userid]);
    
        $chartData = [
            'labels' => ['Завершено', 'Незавершено'],
            'status_counts' => [0, 0] // Индексы: 0 = завершено, 1 = незавершено
        ];
    
        foreach ($data as $record) {
            if ($record->state === 'finished') {
                $chartData['status_counts'][0] = $record->count;
            } else {
                $chartData['status_counts'][1] = $record->count;
            }
        }
    
        return $chartData;
    }
    private function get_quiz_avg_time_chart_data($userid) {
        global $DB;
    
        
        $sql = "SELECT 
                    q.name AS quiz_name,
                    AVG(qa.timefinish - qa.timestart) AS avg_time
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id = qa.quiz
                WHERE qa.userid = :userid AND qa.timefinish > qa.timestart
                GROUP BY q.id, q.name";
    
        $data = $DB->get_records_sql($sql, ['userid' => $userid]);
    
        $chartData = [
            'labels' => [],
            'avg_time' => []
        ];
    
        foreach ($data as $record) {
            $chartData['labels'][] = $record->quiz_name;
            $chartData['avg_time'][] = round($record->avg_time / 60, 2); 
        }
    
        return $chartData;
    }

    private function get_course_progress_data($userid) {
        global $DB;
    
        
        $sql = "SELECT 
                    c.fullname AS course_name, 
                    COUNT(cmc.id) AS total_modules,
                    SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END) AS completed_modules
                FROM {course_modules_completion} cmc
                JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                JOIN {course} c ON c.id = cm.course
                WHERE cmc.userid = :userid
                GROUP BY c.id";
    
        $data = $DB->get_records_sql($sql, ['userid' => $userid]);
    
        
        $chartData = [
            'labels' => [],
            'progress' => []
        ];
    
        foreach ($data as $record) {
            $course_progress = ($record->total_modules > 0) ? round(($record->completed_modules / $record->total_modules) * 100, 2) : 0;
            $chartData['labels'][] = $record->course_name; 
            $chartData['progress'][] = $course_progress;
        }
    
        return $chartData;
    }

}