<?php
/**
 * Root entry point - redirects to the public marketing site.
 * (When mod_rewrite is active, .htaccess routes "/" here directly to
 * public/index.php instead; this file is the fallback for servers without
 * rewrite support, e.g. `php -S localhost:8080`.)
 */

require_once __DIR__ . '/includes/bootstrap.php';

redirect('public/index.php');
