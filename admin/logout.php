<?php
require_once __DIR__ . '/config/bootstrap.php';
session_destroy();
redirect('/admin/login.php');
