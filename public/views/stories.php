<?php
  $title = "StoryLine — Historie dnia";
  $date = $GLOBALS['route_params']['date'] ?? ($_GET['date'] ?? 'today');
  include __DIR__."/partials/header.php";
?>

<header class="list-header">
  <h1>Historie — <?= htmlspecialchars($date) ?></h1>
  <div class="filter">
    Sortuj:
    <a href="?sort=new">Najnowsze</a> ·
    <a href="?sort=top">Top</a> ·
    <a href="?sort=date">Po dacie</a>
  </div>
</header>

<ul class="story-list">
  <li class="story-card">
    <a href="/story/1" class="title">Tytuł</a>
    <div class="meta">Autor: Anonim · 327 słów · 12 🌸</div>
    <p class="preview">Pierwsze 1–2 zdania…</p>
    <button class="like" data-like data-story="1">🌸 Flower</button>
  </li>
  <li class="story-card">
    <a href="/story/2" class="title">Tytuł</a>
    <div class="meta">Autor: <a href="/user/1">alice</a> · 489 słów · 5 🌸</div>
    <p class="preview">Krótki zajawkowy fragment…</p>
    <button class="like" data-like data-story="2">🌸 Flower</button>
  </li>
</ul>

<div class="pagination">
  <button class="btn" data-loadmore>Load more</button>
</div>

  </main>
</body>
</html>
