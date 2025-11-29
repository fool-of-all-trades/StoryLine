<?php
/** @var \App\Models\Story $story */
  $title = "StoryLine — " . htmlspecialchars($story->title ?? '(Untitled)', ENT_QUOTES, 'UTF-8');
  $pageScripts = ['pages/story'];
  include __DIR__ . "/partials/header.php";
?>

<article class="story-full" data-story-uuid="<?= htmlspecialchars($story->story_public_id ?? '') ?>">
  <header>
    <h1><?= htmlspecialchars($story->title ?? '(Untitled)') ?></h1>
    <div class="meta">

      <!-- render author username or guest name or anonymous -->
      <p>
        Author:
        <?php if ($story->isAnonymous): ?>
          Anonymous
        <?php elseif ($story->user_public_id): ?>
          <a href="/user/<?= htmlspecialchars($story->user_public_id) ?>">
            <?= htmlspecialchars($story->username ?? 'user') ?>
          </a>
        <?php elseif (!empty($story->guestName)): ?>
          <?= htmlspecialchars($story->guestName) ?>
        <?php else: ?>
          Anonymous
        <?php endif; ?>
      </p>

      <?php if (!empty($story->prompt_sentence)): ?>
        <p>Quote: "<?= htmlspecialchars($story->prompt_sentence) ?>"</p>
      <?php endif; ?>
      <p><?= htmlspecialchars($story->createdAt->format('Y-m-d')) ?></p>
      <p><span data-count><?= (int)($story->flower_count ?? 0) ?></span> 🌸</p>
    </div>
  </header>

  <section class="content">
    <p><?= nl2br(htmlspecialchars($story->content)) ?></p>
  </section>

  <footer class="actions">
    <button class="like" data-like data-story="<?= (int)$story->id ?>">🌸 Flower</button>
  </footer>
</article>

</main>
</body>
</html>
