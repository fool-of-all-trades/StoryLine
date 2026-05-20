<?php 
  $title = "StoryLine — Login"; 
  $pageScripts = ['auth'];
  $pageStyles = ['login'];
  include __DIR__."/partials/header.php"; 
  use App\Security\Csrf;
?>

      <div class="auth-content">
        <div class="auth-content-div">
          <h1>WELCOME BACK</h1>
          <form id="login-form" method="post" action="/login">
            <?= Csrf::inputField() ?>
            <input type="hidden" name="redirect" value="/dashboard">
            <label>Username or email <input type="text" name="identifier" required maxlength="255"></label>
            </br>
            <label>Password
              <input id="passwordInput" type="password" name="password" required></input>
            </label>
            </br>
            <label>
              <input type="checkbox" name="remember" value="1">
              Remember me on this device
            </label>
            <p class="form-help">Only use this on your own device.</p>
            <!-- <input id="togglePasswordVisibilityCheckbox" type="checkbox">Show -->

            <button type="submit" class="btn primary">Login</button>
            <p id="login-message" class="form-message"></p>
          </form>
          <p>No account yet? <a href="/register">Register</a></p>
          <p>Forgot your password? <a href="/password/forgot">Reset</a></p>
        </div>
      </div>
    </div>
    <div class="backdrop" hidden></div>
  </body>
</html>
