<?php
$title = "Reset password";
$pageScripts = ['auth'];
  $pageStyles = ['login'];
include __DIR__."/partials/header.php";
use App\Security\Csrf;
?>
<div class="auth-content">
      <div class="auth-content-div">
            <h1>Forgot Password</h1>

            <form id="forgot-form" method="post" action="/password/forgot" class="form">
                <?= Csrf::inputField() ?>

                <label>
                    Email
                    <input type="email" name="email" required>
                </label>

                <button type="submit" class="btn primary">Send reset link</button>

                <p id="forgot-message" class="form-message"></p>
            </form>

                </div>
        </div>
    <div class="backdrop" hidden></div>
    </body>
</html>
