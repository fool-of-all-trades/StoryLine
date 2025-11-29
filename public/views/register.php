<?php 
  $title = "StoryLine — Register"; 
  $pageScripts = ['auth'];
  include __DIR__."/partials/header.php"; 
  use App\Security\Csrf;
?>

      <h1>Register</h1>
      <form id="register-form" method="post" action="/register">
        <?= Csrf::inputField() ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <label>
          Username
          <input name="username" required maxlength="30" value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label>
          Email
          <input type="email" name="email" required maxlength="255" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label>
          Password
          <input type="password" name="password" required>
        </label>
        
        <label>
          Repeat password
          <input type="password" name="password_confirm" required>
        </label>
        
        <button type="submit" class="btn primary">Welcome</button>

        <p id="register-message" class="form-message"></p>
      </form>
      <p>You already have an account? <a href="/login">Login</a></p>

    </main>
  </body>
</html>
