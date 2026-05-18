<div
  class="modal-backdrop"
  data-modal-backdrop
  hidden
></div>

<div
  class="modal"
  id="profile-edit-modal"
  role="dialog"
  aria-modal="true"
  aria-labelledby="profile-edit-title"
  hidden
>
  <div class="modal-card">
    <div class="modal-header">
      <h2 id="profile-edit-title">Edit profile</h2>
      <button type="button" class="modal-close" data-close-profile-edit aria-label="Close">✕</button>
    </div>

    <div class="modal-body">
      <!-- Avatar -->
      <form id="avatar-form"
            method="post"
            action="/api/me/avatar"
            enctype="multipart/form-data"
            class="avatar-form">
        <?= \App\Security\Csrf::inputField() ?>

        <label>
          Avatar
          <input type="file" name="avatar" accept="image/*">
        </label>

        <button type="submit" class="btn secondary">Change avatar</button>
        <p id="avatar-message" class="form-message"></p>
      </form>

      <!-- Username -->
      <form id="username-form" method="post" action="/api/me/username" class="username-form">
        <?= \App\Security\Csrf::inputField() ?>

        <label>
          Username*
          <input type="text" name="username" maxlength="40" required>
        </label>

        <button type="submit" class="btn secondary">Change username</button>
        <p id="username-message" class="form-message"></p>
      </form>

      <!-- Password -->
      <form id="password-form" method="post" action="/api/me/password" class="password-form">
        <?= \App\Security\Csrf::inputField() ?>

        <label>
          Password*
          <input id="passwordInput" type="password" name="password" minlength="8" required>
        </label>

        <label class="inline">
          <input id="togglePasswordVisibilityCheckbox" type="checkbox">
          Show
        </label>

        <button type="submit" class="btn secondary">Change password</button>
        <p id="password-message" class="form-message"></p>
      </form>

      <!-- Favorite quote -->
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
    </div>
  </div>
</div>
