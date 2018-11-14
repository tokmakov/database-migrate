<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_USER', 'root');
define('DB_PASS', '');
define('TABLE_CURRENT_STATE', 'current_state_database');

require 'lib/Database.php';
require 'lib/Migration.php';

// поддерживаются четыре опции: help, state, backup и restore
$help  = 'Usage: php ' . $argv[0] . ' [-h|-s|-b|-r]' . PHP_EOL;
$help .= 'Options:' . PHP_EOL;
$help .= '    -h --help       Show this message' . PHP_EOL;
$help .= '    -s --state      Show current state' . PHP_EOL;
$help .= '    -b --backup     Create database backup' . PHP_EOL;
$help .= '    -r --restore    Restore database from backup';

if ($argc > 2) { // допускается только одна опция за раз
    die($help);
}
if ($argc == 2) {
    $params = array(
        'h::' => 'help::',
        's::' => 'state::',
        'b::' => 'backup::',
        'r::' => 'restore::'
    );
    $options = getopt(implode('', array_keys($params)), $params);
    // опция help (справка по использованию)
    if (isset($options['help']) || isset($options['h'])) {
        die($help);
    }
    // опция state (текущее состояние базы данных)
    if (isset($options['state']) || isset($options['s'])) {
        $migration = new Migration(
            DB_HOST,
            DB_NAME,
            DB_USER,
            DB_PASS,
            TABLE_CURRENT_STATE
        );
        $migration->state();
        die();
    }
    // опция backup (создание резервной копии)
    if (isset($options['backup']) || isset($options['b'])) {
        $migration = new Migration(
            DB_HOST,
            DB_NAME,
            DB_USER,
            DB_PASS,
            TABLE_CURRENT_STATE
        );
        $migration->backup();
        die();
    }
    // опция restore (восстановление из резервной копии)
    if (isset($options['restore']) || isset($options['r'])) {
        $migration = new Migration(
            DB_HOST,
            DB_NAME,
            DB_USER,
            DB_PASS,
            TABLE_CURRENT_STATE
        );
        $migration->restore();
        die();
    }
}

// собственно, сама миграция
$migration = new Migration(
    DB_HOST,
    DB_NAME,
    DB_USER,
    DB_PASS,
    TABLE_CURRENT_STATE
);
$migration->migrate();