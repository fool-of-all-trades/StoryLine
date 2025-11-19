<?php
/** @var \App\Models\User $user */
$title = "StoryLine — User Panel";
include __DIR__."/partials/header.php";

$sessionUser = current_user();
$isOwnProfile = $sessionUser && ($sessionUser['public_id'] === $user->public_id);
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
            <?php if ($isOwnProfile): ?>
              <!-- change username -->
              <form id="username-form" method="post" action="/api/me/username" class="username-form">
                <?= \App\Security\Csrf::inputField() ?>

                <label>
                  Username*
                  <input type="text" name="username" maxlength="40" required></input>
                </label>

                <button type="submit" class="btn secondary">Change username</button>
                <p id="username-message" class="form-message"></p>
              </form>

              <!-- change password -->
              <form id="password-form" method="post" action="/api/me/password" class="password-form">
                <?= \App\Security\Csrf::inputField() ?>

                <label>
                  Password*
                  <input id="passwordInput" type="password" name="password" minlength="8" required></input>
                </label>

                <input id="togglePasswordVisibilityCheckbox" type="checkbox">Show

                <button type="submit" class="btn secondary">Change password</button>
                <p id="password-message" class="form-message"></p>
              </form>
            <?php endif; ?>
          </section>
          
          <section class="section2">
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

            <?php if ($isOwnProfile): ?>
              <form id="favorite-quote-form" method="post" action="/api/me/favorite-quote" class="favorite-quote-form">
                <?= \App\Security\Csrf::inputField() ?>

                <label>
                  Sentence*
                  <textarea name="favorite_quote_sentence" rows="3" maxlength="500"><?= htmlspecialchars($user->favorite_quote_sentence ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>

                <label>
                  Author
                  <input type="text" name="favorite_quote_author" maxlength="200"
                        value="<?= htmlspecialchars($user->favorite_quote_author ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </label>

                <label>
                  Book
                  <input type="text" name="favorite_quote_book" maxlength="200"
                        value="<?= htmlspecialchars($user->favorite_quote_book ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </label>

                <button type="submit" class="btn secondary">Save favorite quote</button>
                <p id="favorite-quote-message" class="form-message"></p>
              </form>
            <?php endif; ?>
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