<?php
/** @var \App\Models\User $user */
$title = "StoryLine — User Panel";
$pageScripts = ['pages/profile', 'edit-modal'];
$pageStyles = ['user'];
include __DIR__."/partials/header.php";

$sessionUser = current_user();
$isOwnProfile = $sessionUser && ($sessionUser['public_id'] === $user->public_id);
?>
      <div class="profile-content" data-user-public-id="<?= htmlspecialchars($user->public_id, ENT_QUOTES, 'UTF-8') ?>">
        <div class="user-avatar">
          <?php
            $avatar = $user->avatar_path ?? '/uploads/avatars/default-avatar.jpg';
          ?>
          <img
            src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>"
            alt="Avatar of <?= htmlspecialchars($user->username ?? 'user', ENT_QUOTES, 'UTF-8') ?>"
            class="avatar"
          >
        </div>  

        <div class="user-info">
          <div class="gold-label">
            <p><?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="gold-label">
            <p>join: <?= htmlspecialchars($user->createdAt->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>

        <div class="user-edit-button">
          <?php if ($isOwnProfile): ?>
            <button
              type="button"
              class="btn secondary"
              data-open-profile-edit
              aria-haspopup="dialog"
              aria-controls="profile-edit-modal"
            >
            <div id="edit-button-icon"></div>
            </button>
          <?php endif; ?>
        </div>
      
        <div class="user-favorite-quote">
          <h3>Favorite quote</h3>

          <?php if ($user->favorite_quote_sentence): ?>
            <blockquote class="favorite-quote">
              "<?= htmlspecialchars($user->favorite_quote_sentence, ENT_QUOTES, 'UTF-8') ?>"
              <?php if ($user->favorite_quote_author): ?>
                <br><small>— <?= htmlspecialchars($user->favorite_quote_author, ENT_QUOTES, 'UTF-8') ?></small>
              <?php endif; ?>
              <?php if ($user->favorite_quote_book): ?>
                <br><small><em><?= htmlspecialchars($user->favorite_quote_book, ENT_QUOTES, 'UTF-8') ?></em></small>
              <?php endif; ?>
            </blockquote>
          <?php else: ?>
            <p>This user hasn't set a favorite quote yet.</p>
          <?php endif; ?>
          </div>

        <section class="user-stories-section">
          <div class="section5-top">
            <div class="number-of-stories">
              <p id="user-stories-count">0</p>
            </div>
            <input id="user-stories-search" type="text" placeholder="Search your stories..." />
          </div>
          <div class="section5-middle" id="user-stories-list"></div>
          <div class="section5-bottom">
            <div class="left-arrow"><div class="triangle triangle-left"></div></div>
            <div class="right-arrow"><div class="triangle triangle-right"></div></div>
          </div>
        </section>

        <div class="user-word-stats">
          <h3 id="user-word-stats">
            You've written 0 words all together!
          </h3>
        </div>

        <?php if ($isOwnProfile): ?>
          <?php include __DIR__ . "/partials/profile-edit-modal.php"; ?>
        <?php endif; ?>
      </div>

    </div>

    <div class="backdrop" hidden></div>
  </body>
</html>