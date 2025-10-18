<?php
  $id = $GLOBALS['route_params']['story_id'] ?? null;
  $title = "StoryLine — Story #".htmlspecialchars((string)$id);
  include __DIR__."/partials/header.php";
?>

<article class="story-full">
  <header>
    <h1>Story #<?= htmlspecialchars((string)$id) ?></h1>
    <div class="meta">
      <p>Autor: Anonim</p>
      <p>Cytat: "fjalsdjflsjkdlfkjslakdjflksjdf"</p>
      <p>2025-03-12</p>
      <p>12 🌸</p>
    </div>
  </header>
  <section class="content">
    <p>Tu pełna treść…</p>
  </section>
  <footer class="actions">
    <button class="like" data-like data-story="<?= htmlspecialchars((string)$id) ?>">🌸 Flower</button>
  </footer>
</article>


  </main>
</body>
</html>
