<?php
  $id = $GLOBALS['route_params']['user_id'] ?? null;
  $title = "StoryLine — User Panel".htmlspecialchars((string)$id);
  include __DIR__."/partials/header.php";
?>

<h1>User #<?= htmlspecialchars((string)$id) ?></h1>
<ul class="story-list">
  <li class="story-card"><a class="title" href="/story/1">story 1</a></li>
  <li class="story-card"><a class="title" href="/story/7">story 2</a></li>
</ul>

    </main>
</body>
</html>