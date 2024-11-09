<?php
require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();
require_login();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_dashboard/page.php'));
$PAGE->set_title(get_string('userdashboard', 'local_user_dashboard'));
$PAGE->set_heading(get_string('userdashboard', 'local_user_dashboard'));

// Вставляем CSS
$PAGE->requires->css('/local/user_dashboard/styles.css');

// Получаем информацию о пользователе
$user = $USER;

// Начинаем вывод страницы
echo $OUTPUT->header();
echo html_writer::tag('h1', get_string('welcome', 'local_user_dashboard', $user->firstname));

// Здесь можно добавить дополнительный контент личного кабинета
echo html_writer::tag('p', get_string('email', 'local_user_dashboard', $user->email));

// Пример кода для включения ссылки в меню
$settings->add(new admin_setting_heading('local_user_dashboard', '', 'User Dashboard'));
$settings->add(new admin_setting_configtext('local_user_dashboard/url', get_string('pluginname', 'local_user_dashboard'), '', 'local/user_dashboard/page.php'));

require_once('../../config.php'); // Путь к конфигурационному файлу Moodle
require_once($CFG->dirroot . '/blocks/user_deadlines'); // Подключение вашего блока

// Определение контекста
$context = context_system::instance(); // Используйте нужный контекст

// Создание экземпляра блока
$blockinstance = new user_deadlines(); // Создание экземпляра вашего блока

// Добавление блока
$output = $PAGE->get_renderer('core');
echo $output->block($blockinstance);

echo $OUTPUT->footer();