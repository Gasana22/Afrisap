<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!is_logged_in()) {
    redirect('/index.php');
}

// All admin-panel pages are reachable by both platform staff and tenant
// (farm owner/org) users today. If a page should be platform-only or
// gated by a specific permission, check it at the top of that page, e.g.:
//
//   if (!has_permission('crops.manage')) {
//       http_response_code(403);
//       exit('403 Forbidden');
//   }
