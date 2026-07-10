<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Safarisap &mdash; Explore. Experience. Belong.</title>
<style>
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#1f3a2e; color:#e8dcc3; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; text-align:center; }
  h1 { font-weight:600; margin:0 0 6px; }
  p { opacity:0.75; margin:0 0 20px; }
  a { color:#e8dcc3; }
</style>
</head>
<body>
<div>
  <h1>Safarisap</h1>
  <p>Explore. Experience. Belong. &mdash; public site is under construction.</p>
  <a href="<?= h(url('/admin/login.php')) ?>">Admin sign in</a>
</div>
</body>
</html>
