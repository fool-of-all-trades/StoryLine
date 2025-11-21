<?php 
  $title = "StoryLine — Login"; 
  include __DIR__."/partials/header.php"; 
  use App\Security\Csrf;
?>

      <h1>Login</h1>
      <form method="post" action="/login">
        <?= Csrf::inputField() ?>
        <input type="hidden" name="redirect" value="/dashboard">
        <label>Username or email <input name="identifier" required maxlength="255"></label>
        <label>Password
          <input id="passwordInput" type="password" name="password" required></input>
        </label>
        <input id="togglePasswordVisibilityCheckbox" type="checkbox">Show

        <button type="submit" class="btn primary">Welcome</button>
      </form>
      <p>No account yet? <a href="/register">Register</a></p>
      <p>Forgot your password? <a href="/password/forgot">Reset</a></p>

    </main>
  </body>
</html>
