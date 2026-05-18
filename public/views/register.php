<?php 
  $title = "StoryLine — Register"; 
  $pageScripts = ['auth'];
  $pageStyles = ['login'];
  include __DIR__."/partials/header.php"; 
  use App\Security\Csrf;
?>

      <div class="auth-content">
      <div class="auth-content-div">
      <h1>WELCOME</h1>
      <form id="register-form" method="post" action="/register">
        <?= Csrf::inputField() ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <label>
          Username
          <input type="text" name="username" required maxlength="30" value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
        </br>

        <label>
          Email
          <input type="email" name="email" required maxlength="255" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
        </br>

        <label>
          Password
          <input type="password" name="password" required>
        </label>
        </br>
        
        <label>
          Repeat password
          <input type="password" name="password_confirm" required>
        </label>
        </br>
        
        <button type="submit" class="btn primary">Register</button>

        <p id="register-message" class="form-message"></p>
      </form>
      <p>You already have an account? <a href="/login">Login</a></p>

        </div>
        </div>
    <div class="backdrop" hidden></div>
  </body>
</html>
