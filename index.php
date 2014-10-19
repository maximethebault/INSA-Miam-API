<?php

set_include_path('.');
chdir('.');
set_time_limit(30);

header("Expires: Mon, 01 Jan 1970 05:00:00 GM");

require 'vendor/autoload.php';
require_once 'config.php';

$cfg = ActiveRecord\Config::instance();
$cfg->set_model_directory(__DIR__ . '/src/Model');
$cfg->set_connections(array('production' => 'mysql://' . $config['db_username'] . ':' . $config['db_password'] . '@' . $config['db_host'] . '/' . $config['db_database'] . ';charset=utf8'));
$cfg->set_default_connection('production');

$controller = new Controller();
$controller->run();