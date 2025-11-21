<?php
$title = "Set new password";
include __DIR__."/partials/header.php";
use App\Security\Csrf;

$token = htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8');
?>

            <h1>Reset your password</h1>

            <form id="reset-form" method="post" action="/password/reset" class="form">
                <?= Csrf::inputField() ?>

                <input type="hidden" name="token" value="<?= $token ?>">

                <label>
                    New password
                    <input type="password" name="password" minlength="8" required>
                </label>

                <label>
                    Confirm new password
                    <input type="password" name="password_confirm" minlength="8" required>
                </label>

                <button type="submit" class="btn primary">Set new password</button>

                <p id="reset-message" class="form-message"></p>
            </form>

        </main>
    </body>
</html>
