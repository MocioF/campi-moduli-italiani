<?php
// phpstan-bootstrap.php
$GLOBALS['wpdb']         = new stdClass();
$GLOBALS['wpdb']->prefix = 'wp_';

require_once __DIR__ . '/campi-moduli-italiani.php';
