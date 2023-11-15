<?php

/**
 * Plugin Name: WP Password bcrypt
 * Plugin URI:  https://github.com/roots/wp-password-bcrypt
 * Description: Replaces wp_hash_password and wp_check_password with password_hash and password_verify.
 * Author:      Roots
 * Author URI:  https://roots.io
 * Version:     1.0.0
 * Licence:     MIT
 */

/**
 * Determine if the plaintext password matches the encrypted password hash.
 *
 * If the password hash is not encrypted using the PASSWORD_DEFAULT (bcrypt)
 * algorithm, the password will be rehashed and updated once verified.
 *
 * @link https://www.php.net/manual/en/function.password-verify.php
 * @link https://www.php.net/manual/en/function.password-needs-rehash.php
 *
 * @param  string     $password The password in plaintext.
 * @param  string     $hash     The hashed password to check against.
 * @param  string|int $user_id  The optional user ID.
 * @return bool
 *
 * @SuppressWarnings(PHPMD.CamelCaseVariableName) $wp_hasher
 */
function wp_check_password($password, $hash, $user_id = '')
{
    if (! password_needs_rehash($hash, PASSWORD_DEFAULT, apply_filters('wp_hash_password_options', []))) {
        return apply_filters(
            'check_password',
            password_verify($password, $hash),
            $password,
            $hash,
            $user_id
        );
    }

    global $wp_hasher;

    if (empty($wp_hasher)) {
        require_once ABSPATH . WPINC . '/class-phpass.php';
        $wp_hasher = new PasswordHash(8, true);
    }

    if (! empty($user_id) && $wp_hasher->CheckPassword($password, $hash)) {
        $hash = wp_set_password($password, $user_id);
    }

    return apply_filters(
        'check_password',
        password_verify($password, $hash),
        $password,
        $hash,
        $user_id
    );
}

/**
 * Hash the provided password using the PASSWORD_DEFAULT (bcrypt)
 * algorithm.
 *
 * @link https://www.php.net/manual/en/function.password-hash.php
 *
 * @param  string $password The password in plain text.
 * @return string
 */
function wp_hash_password($password)
{
    return password_hash(
        $password,
        PASSWORD_DEFAULT,
        apply_filters('wp_hash_password_options', [])
    );
}

/**
 * Hash and update the user's password.
 *
 * @param  string $password The new user password in plaintext.
 * @param  int    $user_id  The user ID.
 * @return string
 */
function wp_set_password($password, $user_id)
{
    $hash = wp_hash_password($password);
    global $wpdb;

    $wpdb->update($wpdb->users, [
        'user_pass' => $hash,
        'user_activation_key' => ''
    ], ['ID' => $user_id]);

    clean_user_cache($user_id);

    return $hash;
}
