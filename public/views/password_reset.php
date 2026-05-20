<?php
    $title = "Set new password";
    $pageScripts = ['auth'];
    $pageStyles = ['login'];
    include __DIR__."/partials/header.php";
    use App\Security\Csrf;

    $selector = htmlspecialchars($selector ?? ($_GET['selector'] ?? ''), ENT_QUOTES, 'UTF-8');
    $token = htmlspecialchars($token ?? ($_GET['token'] ?? ''), ENT_QUOTES, 'UTF-8');
    $resetError = $resetError ?? null;
?>

<div class="auth-content">
      <div class="auth-content-div">
            <h1>Reset your password</h1>

            <?php if ($resetError): ?>
                <p id="reset-message" class="form-message error">This reset link is invalid or has expired.</p>
            <?php else: ?>
            <form id="reset-form" method="post" action="/password/reset" class="form">
                <?= Csrf::inputField() ?>

                <input type="hidden" name="selector" value="<?= $selector ?>">
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
            <?php endif; ?>

                </div>
        </div>
    <div class="backdrop" hidden></div>
    </body>
</html>
