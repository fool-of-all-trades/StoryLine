<?php
/** @var \App\Models\User $user */
$title = "StoryLine — User Panel";
include __DIR__."/partials/header.php";
?>
      <main data-user-public-id="<?= htmlspecialchars($user->public_id, ENT_QUOTES, 'UTF-8') ?>">
        <section class="parent">
          <section class="section1">
            <div class="section1-left"></div>
            <div class="section1-right">
              <div class="gold-label">
                <p><?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?></p>
              </div>
              <div class="gold-label">
                <p>join: <?= htmlspecialchars($user->createdAt->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?></p>
              </div>
              <div class="gold-label">
                <p>edit</p>
              </div>
            </div>
          </section>
          <section class="section2">
            <p>"Favorite book statement - All this happened, more or less."</p>
          </section>
          <section class="section3">
            <h3 id="user-word-stats">
              You've written X words all together! That's a Y.
              <br />
              I'm proud of you.
            </h3>
          </section>
          <section class="section4"></section>
          <section class="section5">
            <div class="section5-top">
              <div class="number-of-stories">
                <p id="user-stories-count">0</p>
              </div>
              <input id="user-stories-search" type="text" placeholder="Search your stories..." />
            </div>
            <div class="section5-middle" id="user-stories-list">
              <!-- Insert user book cards using JS -->
            </div>
            <div class="section5-bottom">
              <div class="left-arrow">
                <div class="triangle triangle-left"></div>
              </div>
              <div class="right-arrow">
                <div class="triangle triangle-right"></div>
              </div>
            </div>
          </section>
        </section>
      </main>
    </main>
  </body>
</html>