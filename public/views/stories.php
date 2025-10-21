<?php
  $title = "StoryLine — Stories Of The Day";
  $date = $GLOBALS['route_params']['date'] ?? ($_GET['date'] ?? 'today');
  include __DIR__."/partials/header.php";
?>

<header class="list-header">
  <h1>Stories — <?= htmlspecialchars($date) ?></h1>
  <div class="filter">
    Sort:
    <a href="?sort=new">Newests</a> ·
    <a href="?sort=top">Top</a> ·
    <a href="?sort=date">By date</a>
  </div>
</header>

<ul class="stories" id="stories-list">
  <!-- Stories will be loaded here by JavaScript -->
</ul>

<div class="pagination">
  <button class="btn" data-loadmore>Load more</button>
</div>

  </main>
</body>
</html>
