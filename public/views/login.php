<?php $title = "StoryLine — Logowanie"; include __DIR__."/partials/header.php"; ?>

<h1>Zaloguj się</h1>
<form method="post" action="/login">
  <label>Login <input name="username" required></label>
  <label>Hasło <input type="password" name="password" required></label>
  <button type="submit" class="btn primary">Wejdź</button>
</form>
<p>Nie masz konta? <a href="/register">Zarejestruj się</a></p>

    </main>
  </body>
</html>
