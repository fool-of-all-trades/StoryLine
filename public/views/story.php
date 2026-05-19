<?php
/** @var \App\Models\Story $story */
  $title = "StoryLine — " . ($story->title ?? '(Untitled)');
  $pageScripts = ['pages/story'];
  $pageStyles = ['story'];
  include __DIR__ . "/partials/header.php";

  function esc(string $s): string {
      return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }
?>

        <article class="story-full" data-story-uuid="<?= htmlspecialchars($story->story_public_id ?? '') ?>">
            
          <div class="author-badge">
            <div class="author-avatar">
              <img
                src="/uploads/avatars/default-avatar.jpg"
                alt="Avatar of someone"
                class="avatar"
              >
            </div>  

            <!-- render author username or guest name or anonymous -->
            <div class="author-name">
              <p>
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
            </div>
          </div>

          <div class="content story-content">
            <div>
              <h1><?= htmlspecialchars($story->title ?? '(Untitled)') ?></h1>
              <p><?= nl2br(htmlspecialchars($story->content)) ?></p>
            </div>
            <p id="story-date"><?= htmlspecialchars($story->createdAt->format('Y-m-d')) ?></p>
          </div>

          <button
            class="story-flower-count"
            type="button"
            data-like
            data-story="<?= esc($story->story_public_id) ?>"
            aria-pressed="false"
          >
            <div class="flower-emoji" aria-hidden="true"></div>
            <span class="flower-count"><span data-count><?= (int)($story->flower_count ?? 0) ?></span></span>
          </button>
        </article>
      </div>

      <div class="backdrop" hidden></div>
  </body>
</html>
