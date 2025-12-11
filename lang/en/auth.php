<?php

return [
    'register_success' => 'Account created successfully. Please check your email for the verification code.',
    'email_verified'   => 'Email verified successfully.',
    'no_valid_code'    => 'There is no valid verification code or it has expired. Please request a new code.',
    'invalid_code'     => 'The verification code is incorrect.',

    'login_success'            => 'Logged in successfully.',
    'invalid_credentials'      => 'The provided credentials are incorrect.',
    'email_not_verified'       => 'Please verify your email address before logging in.',
    'verification_email_resent' => 'تم إرسال كود تفعيل جديد إلى بريدك الإلكتروني.',
    'email_already_verified'   => 'بريدك الإلكتروني مفعل بالفعل.',

    'password_reset_code_sent' => 'If your email exists in our system, a reset code has been sent.',
    'password_reset_success'   => 'Your password has been reset successfully.',
    'password_reset_invalid_code' => 'The reset code is invalid or has expired.',

    'account_locked' => 'Your account has been temporarily locked due to multiple failed login attempts. Please try again after :minutes minutes.',
    'account_locked_subject' => 'Your account has been temporarily locked',
    'account_locked_body' => 'Your account has been temporarily locked due to failed login attempts. You can try again after :until.',
];
