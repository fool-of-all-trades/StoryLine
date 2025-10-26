<?php 
  $title = "StoryLine — Login"; 
  include __DIR__."/partials/header.php"; 
  use App\Security\Csrf;
?>

      <h1>Login</h1>
      <form method="post" action="/login">
        <?= Csrf::inputField() ?>
        <input type="hidden" name="redirect" value="/dashboard">
        <label>Username <input name="username" required maxlength="30"></label>
        <label>Password <input type="password" name="password" required></label>
        <button type="submit" class="btn primary">Welcome</button>
      </form>
      <p>No account yet? <a href="/register">Register</a></p>

    </main>
  </body>
</html>
