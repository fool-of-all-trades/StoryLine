<?php
  $title = "StoryLine — Stories Of The Day";
  $pageScripts = ['pages/stories-list'];
  $date = $_GET['date'] ?? 'today';
  $currentSort = $_GET['sort'] ?? 'new';
  include __DIR__."/partials/header.php";
?>

<header class="list-header">
  <h1>Stories — <?= htmlspecialchars($date) ?></h1>

  <section class="day-summary">
    <p class="stories-count">
      Stories added this day:
      <strong data-stories-count>0</strong>
    </p>

    <figure class="day-quote" data-quote-wrapper hidden>
      <blockquote data-quote-of-day></blockquote>
      <figcaption data-quote-of-day-meta></figcaption>
    </figure>
  </section>

  <div class="filter">
    <div class="filter-sort">
      Sort:
      <a href="?sort=new&date=<?= urlencode($date) ?>">Newest</a> ·
      <a href="?sort=top&date=<?= urlencode($date) ?>">Top</a>
    </div>

    <form method="GET" class="filter-date">
      <label>
        Date:
        <input
          type="date"
          name="date"
          value="<?= $date === 'today' ? date('Y-m-d') : htmlspecialchars($date) ?>"
          max="<?= date('Y-m-d') ?>"
        >
      </label>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">
      <button type="submit" class="btn">Show</button>
      <a class="btn-link" href="/stories?date=today&sort=<?= urlencode($currentSort) ?>">Today</a>
    </form>
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
