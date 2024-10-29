<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u629237220_Shcl5' );

/** Database username */
define( 'DB_USER', 'u629237220_ieI26' );

/** Database password */
define( 'DB_PASSWORD', 'tjhzuL44Sj' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'Ay2<jTct<_Lw,S~*.YE^IJfv>2@?C?u{>Z{w1~.Kf9*%R~jXzI[qX#/TL~nj.;$i' );
define( 'SECURE_AUTH_KEY',   'K+B2r_bi@eQ8c9k>tdRePa}50s3m!T/I1T{[l}ux^4;Frp#)?tNE}7DR]e$bG;p?' );
define( 'LOGGED_IN_KEY',     'FCVKCof*[6QU}I(9|f $+F![qZ$qMo6A]CAq:{@`+DG0_ P-LAyAI,?5fYgaw2tu' );
define( 'NONCE_KEY',         'f+?hQJ$|6.{2gaI)[qL%B;X8+P>)Z~lcszJ&0rrSWQ!H(4=j8G#nh&f&VQ%._u6I' );
define( 'AUTH_SALT',         'J6*_H*]f!86E0)-B SU50i$T95JqA|A3/chzv%(>o6PQT;yOcH@pgooy0XE &)<k' );
define( 'SECURE_AUTH_SALT',  'lLaP5/,evDwtk17&aCxafw=lDEYb$mNP;iQ&R6z9 U[%q8I}Xhnu26V}-$]st1u`' );
define( 'LOGGED_IN_SALT',    '7G-Wa-I)hNr|Vt@5<8+ATJdf9|7SXcO+I m$LdGO?#fLb`1x(+?@.@)!_u7p~&/j' );
define( 'NONCE_SALT',        'QTRZ4!|H8<kZve(2gK#|gk2q1ruR&v5jA/EB>H&XK<ZbPjM}XLx8$}dSt-7/z~6p' );
define( 'WP_CACHE_KEY_SALT', 'aAhp3ANo,L0D4QXsL],DeBG D 3so;o?4O$x[~a)n>vg#,xGtJ!3R4g|FL((!|v2' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', 'e905e336178a2ee97cc3fbadb00a1eca' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
