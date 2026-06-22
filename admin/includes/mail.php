<?php
function _admin_base_url(): string {
    if (isset($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
    return 'https://shimane-ib.roboco-op.org';
}

function _admin_mail(string $to, string $subject, string $body): bool {
    $headers  = "From: Robo Co-op <no-reply@roboco-op.org>\r\n";
    $headers .= "Reply-To: info@roboco-op.org\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    return mail($to, $subject, $body, $headers);
}

function send_admin_invite(string $to_email, string $to_name, string $token): bool {
    $link = _admin_base_url() . '/admin/accept-invite?token=' . urlencode($token);
    $body = "Hi {$to_name},

You have been invited to access the Shimane IB recruitment admin panel.

Click the link below to set your password and activate your account:

  {$link}

This link expires in 7 days. If you did not expect this invitation, ignore this email.

--
Robo Co-op
Shimane IB Recruitment Admin";
    return _admin_mail($to_email, 'You have been invited to Shimane IB Admin', $body);
}

function send_password_reset(string $to_email, string $to_name, string $token): bool {
    $link = _admin_base_url() . '/admin/reset-password?token=' . urlencode($token);
    $body = "Hi {$to_name},

We received a request to reset the password for your Shimane IB admin account.

Click the link below to set a new password:

  {$link}

This link expires in 1 hour. If you did not request this, ignore this email.

--
Robo Co-op
Shimane IB Recruitment Admin";
    return _admin_mail($to_email, 'Reset your Shimane IB Admin password', $body);
}
