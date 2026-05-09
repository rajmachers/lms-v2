<?php
// Moodle configuration file — LMS V2 (PostgreSQL + pgvector)
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'lmsv2-db';
$CFG->dbname    = 'moodlev2';
$CFG->dbuser    = 'moodlev2user';
$CFG->dbpass    = 'moodlev2pass_change_me';
$CFG->prefix    = 'mdl2_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport'    => '5432',
    'dbsocket'  => '',
);

$CFG->wwwroot   = 'http://159.65.149.161:10183/lmsv2';
$CFG->dataroot  = '/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02777;

require_once(__DIR__ . '/lib/setup.php');
