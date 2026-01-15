<?php
declare(strict_types=1);

final class Validator
{
    /** @return array<string,string> */
    public static function validateRegistration(array $input): array
    {
        $errors = [];

        $role = (string)($input['role'] ?? '');
        if (!in_array($role, ['student', 'parent'], true)) {
            $errors['role'] = 'Please choose Student or Parent.';
        }

        $username = trim((string)($input['username'] ?? ''));
        if ($username === '' || strlen($username) < 3 || strlen($username) > 24) {
            $errors['username'] = 'Username must be 3–24 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores.';
        }

        $email = trim((string)($input['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        $password = (string)($input['password'] ?? '');
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($role === 'student') {
            $ageGroup = (string)($input['age_group'] ?? '');
            if (!in_array($ageGroup, ['3-5', '6-8', '9-12'], true)) {
                $errors['age_group'] = 'Please choose an age group.';
            }
        }

        return $errors;
    }

    /** @return array<string,string> */
    public static function validateLogin(array $input): array
    {
        $errors = [];

        $usernameOrEmail = trim((string)($input['username_or_email'] ?? ''));
        if ($usernameOrEmail === '') {
            $errors['username_or_email'] = 'Enter your username or email.';
        }

        $password = (string)($input['password'] ?? '');
        if ($password === '') {
            $errors['password'] = 'Enter your password.';
        }

        return $errors;
    }
}
