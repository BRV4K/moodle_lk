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

// Пример кода для включения ссылки в меню
$settings->add(new admin_setting_heading('local_user_dashboard', '', 'User Dashboard'));
$settings->add(new admin_setting_configtext('local_user_dashboard/url', get_string('pluginname', 'local_user_dashboard'), '', 'local/user_dashboard/page.php'));

echo $OUTPUT->footer();