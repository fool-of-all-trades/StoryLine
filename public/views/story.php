<?php $id = $GLOBALS['route_params']['story_id'] ?? null; ?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8"><title>Story #<?= htmlspecialchars((string)$id) ?></title>
  <link rel="stylesheet" href="/styles/main.css">
</head>
<body>
  <main class="container">
    <h1>Story #<?= htmlspecialchars((string)$id) ?></h1>
  </main>
</body>
</html>
