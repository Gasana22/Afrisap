<?php
require_once __DIR__ . '/../includes/bootstrap.php';

auth_logout();
redirect('worker/login.php');
