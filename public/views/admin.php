<?php
  /** @var array|null $stats */

  $title = $title ?? "StoryLine — Admin";

  if (!isset($stats) || !is_array($stats)) {
      $stats = [
          'date'           => date('Y-m-d'),
          'quote'          => null,
          'topStory'       => null,
          'storiesForDate' => 0,
          'storiesTotal'   => 0,
          'usersTotal'     => 0,
          'storiesSeries'  => [],
          'usersSeries'    => [],
      ];
  }

  // helper function
  function esc(string $s): string {
      return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }

  $date            = esc($stats['date'] ?? date('Y-m-d'));
  $quote           = $stats['quote']    ?? null;
  $top             = $stats['topStory'] ?? null;
  $storiesForDate  = (int)($stats['storiesForDate'] ?? 0);
  $storiesTotal    = (int)($stats['storiesTotal']   ?? 0);
  $usersTotal      = (int)($stats['usersTotal']     ?? 0);

  $storiesSeriesJson = esc(json_encode($stats['storiesSeries'] ?? []));
  $usersSeriesJson   = esc(json_encode($stats['usersSeries']   ?? []));

  include __DIR__ . "/partials/header.php";
?>

<h1>Admin panel</h1>

<section class="admin-filters">
  <form method="get" class="admin-filter-form">
    <label>
      Analytics for date:
      <input type="date" name="date" value="<?= $date ?>">
    </label>
    <button type="submit">Show</button>
  </form>
</section>

<section class="admin-cards">
  <article class="card">
    <h2>Quote for this date (<?= $date ?>)</h2>
    <?php if ($quote): ?>
      <p>"<?= esc($quote->sentence ?? '') ?>"</p>
      <p>
        <?php if (!empty($quote->author)): ?>
          — <?= esc($quote->author) ?>
        <?php endif; ?>
        <?php if (!empty($quote->book)): ?>
          <em>(<?= esc($quote->book) ?>)</em>
        <?php endif; ?>
      </p>
    <?php else: ?>
      <p>No quote for this date.</p>
    <?php endif; ?>
  </article>

  <article class="card">
    <h2>Stories for this date</h2>
    <p class="big-number"><?= $storiesForDate ?></p>
  </article>

  <article class="card">
    <h2>Stories total</h2>
    <p class="big-number"><?= $storiesTotal ?></p>
  </article>

  <article class="card">
    <h2>Users total</h2>
    <p class="big-number"><?= $usersTotal ?></p>
  </article>
</section>

<section class="admin-top-story">
  <h2>Most popular story for this date</h2>
  <?php if ($top): ?>
    <?php
      $storyTitle     = $top['title']           ?? $top->title           ?? '(Untitled)';
      $storyPublicId  = $top['story_public_id'] ?? $top->story_public_id ?? null;
      $userName       = $top['username']        ?? $top->username        ?? 'Anonymous';
      $userPublicId   = $top['user_public_id']  ?? $top->user_public_id  ?? null;
      $flowersCount   = (int)($top['flowers']   ?? $top->flowers         ?? 0);
    ?>
    <h3>
      <?php if ($storyPublicId): ?>
        <a href="/story/<?= esc($storyPublicId) ?>">
          <?= esc($storyTitle) ?>
        </a>
      <?php else: ?>
        <?= esc($storyTitle) ?>
      <?php endif; ?>
    </h3>
    <p>
      by
      <?php if ($userPublicId): ?>
        <a href="/user/<?= esc($userPublicId) ?>">
          <?= esc($userName) ?>
        </a>
      <?php else: ?>
        <?= esc($userName) ?>
      <?php endif; ?>
      · <?= $flowersCount ?> 🌸
    </p>
  <?php else: ?>
    <p>No stories for this date.</p>
  <?php endif; ?>
</section>


<section
  class="admin-charts"
  id="admin-charts"
  data-stories-series='<?= $storiesSeriesJson ?>'
  data-users-series='<?= $usersSeriesJson ?>'
>
  <div class="chart-card">
    <h2>Stories over time</h2>
    <div class="chart-wrapper">
      <canvas id="storiesChart"></canvas>
    </div>
  </div>

  <div class="chart-card">
    <h2>Users over time</h2>
    <div class="chart-wrapper">
      <canvas id="usersChart"></canvas>
    </div>
  </div>
</section>

</main>
</body>
</html>