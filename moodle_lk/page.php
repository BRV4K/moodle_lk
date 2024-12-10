<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();
require_login();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/block/my_dashboard/page.php'));
$PAGE->set_title(get_string('my_dashboard', 'block_my_dashboard'));
$PAGE->set_heading(get_string('my_dashboard', 'block_my_dashboard'));

$context = context_system::instance();
require_login();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/my_dashboard/page.php'));
$PAGE->set_title('Информация о пользователях');
$PAGE->set_heading('Информация о пользователях');

global $DB;
$sql = "SELECT id, firstname, lastname, email, username FROM {user} ORDER BY lastname ASC";
$users = $DB->get_records_sql($sql);

echo $OUTPUT->header();
echo html_writer::tag('h1', 'Список пользователей');

if ($users) {
    $table = new html_table();
    $table->head = ['ID', 'Имя', 'Фамилия', 'Email', 'Логин'];

    foreach ($users as $user) {
        $table->data[] = [
            $user->id,
            $user->firstname,
            $user->lastname,
            $user->email,
            $user->username,
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::tag('p', 'Пользователи не найдены.');
}

$PAGE->requires->css('/block/my_dashboard/styles.css');


$user = $DB->get_record('user', array('id' => $USER->id));

echo $OUTPUT->header();
echo html_writer::tag('h1', get_string('welcome', 'block_my_dashboard', $user->firstname));

// Пример кода для включения ссылки в меню
//$settings->add(new admin_setting_heading('block_my_dashboard', '', 'User Dashboard'));
//$settings->add(new admin_setting_configtext('block_my_dashboard/url', get_string('pluginname', 'block_my_dashboard'), '', 'block/my_dashboard/page.php'));

echo $OUTPUT->footer();