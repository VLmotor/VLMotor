<?php
const REFERENCE_KEY = 'reference';
const BRAND_KEY = 'brand';
const MODEL_KEY = 'model';
const FAMILY_KEY = 'family';
const LINES_PER_FILE = 5000;
const ADMIN_DIR_NAME = 'admin367nyjlfd';

function createConstants()
{
    define('PS_ROOT_DIR', realpath('./../'));
//    define('PS_ROOT_DIR', realpath('C:\Desarrollo\workspace\VLMotor\ps_installation'));
    define('_PS_ADMIN_DIR_', PS_ROOT_DIR . '/' . ADMIN_DIR_NAME . '/');
    define('ADMIN_IMPORT_DIR', _PS_ADMIN_DIR_ . '/import/');
    define('SCRIPT_IMPORT_DIR', PS_ROOT_DIR . '/import/');
    define('CSV_FILE', SCRIPT_IMPORT_DIR . 'combinations.csv');
}

createConstants();

require_once(PS_ROOT_DIR . '/config/config.inc.php');
require_once(PS_ROOT_DIR . '/controllers/admin/AdminImportController.php');

include_once('./utils.php');
