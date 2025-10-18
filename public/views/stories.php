<?php
$date = $GLOBALS['route_params']['date'] ?? ($_GET['date'] ?? null);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8"><title>Stories — <?= htmlspecialchars((string)$date) ?></title>
  <link rel="stylesheet" href="/styles/main.css">
</head>
<body>
  <main class="container">
    <h1>Stories for <?= htmlspecialchars((string)$date) ?></h1>
    <!-- lista historii z danego dnia -->
  </main>
</body>
</html>
