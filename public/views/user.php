<?php
$userId = $GLOBALS['route_params']['user_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StoryLine</title>
</head>
<body>
    <h1>Profil użytkownika #<?= htmlspecialchars((string)$userId) ?></h1>
</body>
</html>