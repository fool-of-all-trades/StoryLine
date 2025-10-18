<?php
  $id = $GLOBALS['route_params']['user_id'] ?? null;
  $title = "StoryLine — Użytkownik #".htmlspecialchars((string)$id);
  include __DIR__."/partials/header.php";
?>

<h1>Użytkownik #<?= htmlspecialchars((string)$id) ?></h1>
<ul class="story-list">
  <li class="story-card"><a class="title" href="/story/1">historia 1</a></li>
  <li class="story-card"><a class="title" href="/story/7">historia 2</a></li>
</ul>

    </main>
</body>
</html>