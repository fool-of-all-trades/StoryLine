<?php
/** @var \App\Models\Story $story */
  $title = "StoryLine — " . ($story->title ?? '(Untitled)');
  $pageScripts = ['pages/story'];
  $pageStyles = ['story'];
  include __DIR__ . "/partials/header.php";

  function esc(string $s): string {
      return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }

  $defaultAvatar = '/uploads/avatars/default-avatar.jpg';
  $canShowAuthor = !$story->isAnonymous && !empty($story->user_public_id);
  $avatarPath = $defaultAvatar;

  if ($canShowAuthor && !empty($story->avatar_path) && str_starts_with($story->avatar_path, '/uploads/avatars/')) {
      $avatarPath = $story->avatar_path;
  }

  $avatarAlt = $canShowAuthor
      ? 'Avatar of ' . ($story->username ?? 'user')
      : 'Avatar of anonymous author';
?>

        <article class="story-full" data-story-uuid="<?= htmlspecialchars($story->story_public_id ?? '') ?>">
            
          <div class="author-badge">
            <div class="author-avatar">
              <img
                src="<?= esc($avatarPath) ?>"
                alt="<?= esc($avatarAlt) ?>"
                class="avatar"
              >
            </div>  

            <div class="author-name">
              <p>
                <?php if (!$canShowAuthor): ?>
                  Anonymous
                <?php else: ?>
                  <a href="/user/<?= htmlspecialchars($story->user_public_id) ?>">
                    <?= htmlspecialchars($story->username ?? 'user') ?>
                  </a>
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
