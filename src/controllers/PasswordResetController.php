<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\Csrf;

class PasswordResetController extends BaseController
{
    public function passwordForgotPage(): void
    {
        $this->render('password_forgot');
    }

    public function passwordResetPage(): void
    {
        $this->render('password_reset');
    }

    public function forgot(): void
    {
        Csrf::verify();
        $this->json([
            'error' => 'password_reset_disabled',
            'message' => 'Password reset is temporarily unavailable.',
        ], 503);
    }


    public function reset(): void
    {
        Csrf::verify();
        $this->json([
            'error' => 'password_reset_disabled',
            'message' => 'Password reset is temporarily unavailable.',
        ], 503);
    }

}
