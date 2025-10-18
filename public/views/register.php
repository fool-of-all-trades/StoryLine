<?php $title = "StoryLine — Rejestracja"; include __DIR__."/partials/header.php"; ?>

<h1>Rejestracja</h1>
<form method="post" action="/register">
  <label>Nazwa użytkownika <input name="username" required maxlength="30"></label>
  <label>Hasło <input type="password" name="password" required></label>
  <button type="submit" class="btn primary">Utwórz konto</button>
</form>
<p>Masz konto? <a href="/login">Zaloguj się</a></p>

    </main>
  </body>
</html>
