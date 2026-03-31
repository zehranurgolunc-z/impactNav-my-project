<?php
require_once __DIR__ . '/functions.php';
session_init($config['app']['session_name']);
session_destroy();
redirect('/login.php');
