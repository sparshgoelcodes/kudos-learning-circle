<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'kudos' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         '-FK5|bFKmZ`_[Js~,9iK]L.AXaKtPN|tx)W V`KUe9>zfu}/t{k9C<2R0vpJY>sz' );
define( 'SECURE_AUTH_KEY',  'I2!F3&#bb#}L(5>OcmR-sx:$^W0C;i*D/[,yl^[Od30,:C*9OCEb&w0Wz vd}aj4' );
define( 'LOGGED_IN_KEY',    '(4`<(O&WL/D^[OIWpifEDWQ*#$~8]IvdTqMLE$]C]1[ORmcGB:*M:@sQ^9$4?b|,' );
define( 'NONCE_KEY',        '2APs?_<6ma{4h7jo2B.{%:^,zOv:00pC nk(IU%d(H dVh_O6C=C+IU|}qyYkHI(' );
define( 'AUTH_SALT',        '[FVW@<NSHJ)jeNEHzVB)mcx}RgF%jg4aysn[j,8my.1RPRmQX YM;^+pEEY% [9o' );
define( 'SECURE_AUTH_SALT', '5rKA2B.yOoO,%k[xWVpf3{7o4/;f0iR+w%3A)>d|=|R*y}/?m<%8suCMSsqkHp;A' );
define( 'LOGGED_IN_SALT',   'Gg(VZije;H0oC1=BqG171P%8ElKSlOtOpsIH8!5zAm$>@ROGZh}H_Z8J%pt=%_e^' );
define( 'NONCE_SALT',       '/%CPy=7>!KMopVIt=lmX?(f{aj+f>aXrS{ A!8kf92y&dO5Np92O-OJ<K(XG2_2{' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
