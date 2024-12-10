<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    // Право на добавление экземпляра блока
    'block/my_dashboard:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'student' => CAP_PREVENT,
            'guest' => CAP_PREVENT
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);