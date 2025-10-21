<?php $title = "StoryLine — Register"; include __DIR__."/partials/header.php"; ?>

<h1>Register</h1>
<form method="post" action="/register">
  <label>Username <input name="username" required maxlength="30"></label>
  <label>Password <input type="password" name="password" required></label>
  <button type="submit" class="btn primary">Welcome</button>
</form>
<p>You already have an account? <a href="/login">Login</a></p>

    </main>
  </body>
</html>
