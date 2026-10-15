<?php

$registrationEmails = array_map(
    'trim',
    explode(',', (string) env('ADMIN_NOTIFICATION_EMAILS', env('ADMIN_NOTIFICATION_EMAIL', '')))
);

return [
    'registration_emails' => array_values(array_unique(array_filter(
        $registrationEmails,
        fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
    ))),
];
