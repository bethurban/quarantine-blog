<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_quarantine_blog' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'I_%JFj:b3!BUBhPM#sb&6,BWtNZONh$l,Cu2i16TXB}6UTuEiu7K]MN*T)M=*n3v' );
define( 'SECURE_AUTH_KEY',  'Q5oEpVSRk2qY,i2LR.Hos..d@?*vJM]kOH3;}MPLY,P;D0(hqd!|Yds60[7M6S%9' );
define( 'LOGGED_IN_KEY',    'bO``.QWD@!zZOQi_]zf^HT47#`x6*xr 5~2UFS-9qTeHoTLXHiq(}dUR$ZoOF;PR' );
define( 'NONCE_KEY',        'MH~hHPo bHT-rEr]i.w*6!NV};MdAr&pi-(Jx81HCtjruH4-X1Nt@<Hz/a}xX$QW' );
define( 'AUTH_SALT',        'FM)d{&TD$]Bvwl!Xn&Jotjo=4z(u>=}(X3p^XI&rChqS%[r{BIX#Xq]smYSA3YNa' );
define( 'SECURE_AUTH_SALT', '=o8nPQ#-MG0#CWX@w5$_|8&wy!ov]b^PngWd|nB5,vdL~#SGS%W,8f[@>F!YRw&#' );
define( 'LOGGED_IN_SALT',   '73Gt.+oKK,=Mbb`-1@#+afve[4yu5 d/%!m/.~YZll:Vf2B;ZHR/^}LU$^-bgj_!' );
define( 'NONCE_SALT',       'xo^u/:OdJsP_k4P%`8;3Ww:q]{RVP<w(E|N2r4u|%xi.HBrsx30Ey2aC$[{>nGF*' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
