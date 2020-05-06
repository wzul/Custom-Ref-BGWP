<?php
/**
 * Plugin Name: Custom Ref BGWP
 * Plugin URI:  http://wanzul.net
 * Description: Add custom reference for GiveWP.
 * Version:     1.0.1
 * Author:      Wan Zulkarnain
 * Author URI:  https://www.wanzul.net
 * Text Domain: custom-ref-bgwp
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!defined('CUSTOM_REF_BGWP_MIN_GIVE_VER')) {
  define('CUSTOM_REF_BGWP_MIN_GIVE_VER', '1.8.3');
}
if (!defined('CUSTOM_REF_BGWP_MIN_PHP_VER')) {
  define('CUSTOM_REF_BGWP_MIN_PHP_VER', '7.0.0');
}
if (!defined('CUSTOM_REF_BGWP_PLUGIN_FILE')) {
  define('CUSTOM_REF_BGWP_PLUGIN_FILE', __FILE__);
}
if (!defined('CUSTOM_REF_BGWP_PLUGIN_DIR')) {
  define('CUSTOM_REF_BGWP_PLUGIN_DIR', dirname(CUSTOM_REF_BGWP_PLUGIN_FILE));
}
if (!defined('CUSTOM_REF_BGWP_PLUGIN_URL')) {
  define('CUSTOM_REF_BGWP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('CUSTOM_REF_BGWP_BASENAME')) {
  define('CUSTOM_REF_BGWP_BASENAME', plugin_basename(__FILE__));
}

if (!class_exists('CustomRefBGWP')):

  class CustomRefBGWP {

    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return CustomRefBGWP The *Singleton* instance.
     */
    public static function get_instance() {
      if (null === self::$instance) {
        self::$instance = new self();
      }

      return self::$instance;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone() {

    }

    /**
     * CustomRefBGWP constructor.
     *
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct() {
      add_action('admin_init', array($this, 'check_environment'));
      add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Init the plugin after plugins_loaded so environment variables are set.
     */
    public function init() {

      // Don't hook anything else in the plugin if we're in an incompatible environment.
      if (self::get_environment_warning()) {
        return;
      }

      $this->includes();
    }

    /**
     * The primary sanity check, automatically disable the plugin on activation if it doesn't
     * meet minimum requirements.
     *
     * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
     */
    public static function activation_check() {
      $environment_warning = self::get_environment_warning(true);
      if ($environment_warning) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die($environment_warning);
      }
    }

    /**
     * Check the server environment.
     *
     * The backup sanity check, in case the plugin is activated in a weird way,
     * or the environment changes after activation.
     */
    public function check_environment() {

      $environment_warning = self::get_environment_warning();
      if ($environment_warning && is_plugin_active(plugin_basename(__FILE__))) {
        deactivate_plugins(plugin_basename(__FILE__));
        $this->add_admin_notice('bad_environment', 'error', $environment_warning);
        if (isset($_GET['activate'])) {
          unset($_GET['activate']);
        }
      }

      // Check for if give plugin activate or not.
      $is_give_active = defined('GIVE_PLUGIN_BASENAME') ? is_plugin_active(GIVE_PLUGIN_BASENAME) : false;
      // Check to see if Give is activated, if it isn't deactivate and show a banner.
      if (is_admin() && current_user_can('activate_plugins') && !$is_give_active) {

        $this->add_admin_notice('prompt_give_activate', 'error', sprintf(__('<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> plugin installed and activated for Custom Reference for Billplz for GiveWP to activate.', 'custom-ref-bgwp'), 'https://givewp.com'));

        // Don't let this plugin activate
        deactivate_plugins(plugin_basename(__FILE__));

        if (isset($_GET['activate'])) {
          unset($_GET['activate']);
        }

        return false;
      }

      // Check min Give version.
      if (defined('CUSTOM_REF_BGWP_MIN_GIVE_VER') && version_compare(GIVE_VERSION, CUSTOM_REF_BGWP_MIN_GIVE_VER, '<')) {

        $this->add_admin_notice('prompt_give_version_update', 'error', sprintf(__('<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> core version %s+ for the Custom Reference for Billplz for GiveWP add-on to activate.', 'custom-ref-bgwp'), 'https://givewp.com', CUSTOM_REF_BGWP_MIN_GIVE_VER));

        // Don't let this plugin activate.
        deactivate_plugins(plugin_basename(__FILE__));

        if (isset($_GET['activate'])) {
          unset($_GET['activate']);
        }

        return false;
      }
    }

    /**
     * Environment warnings.
     *
     * Checks the environment for compatibility problems.
     * Returns a string with the first incompatibility found or false if the environment has no problems.
     *
     * @param bool $during_activation
     *
     * @return bool|mixed|string
     */
    public static function get_environment_warning($during_activation = false) {

      if (version_compare(phpversion(), CUSTOM_REF_BGWP_MIN_PHP_VER, '<')) {
        if ($during_activation) {
          $message = __('The plugin could not be activated. The minimum PHP version required for this plugin is %1$s. You are running %2$s. Please contact your web host to upgrade your server\'s PHP version.', 'custom-ref-bgwp');
        } else {
          $message = __('The plugin has been deactivated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'custom-ref-bgwp');
        }

        return sprintf($message, CUSTOM_REF_BGWP_MIN_PHP_VER, phpversion());
      }

      if (!function_exists('curl_init')) {

        if ($during_activation) {
          return __('The plugin could not be activated. cURL is not installed. Please contact your web host to install cURL.', 'custom-ref-bgwp');
        }

        return __('The plugin has been deactivated. cURL is not installed. Please contact your web host to install cURL.', 'custom-ref-bgwp');
      }

      return false;
    }

    private function includes() {

      // Checks if Give is installed.
      if (!class_exists('Give')) {
        return false;
      }

      if (is_admin()) {
        // Nothing
      }

      include CUSTOM_REF_BGWP_PLUGIN_DIR . '/includes/logic.php';
    }

  }

  $GLOBALS['custom_ref_bgwp'] = CustomRefBGWP::get_instance();
  register_activation_hook(__FILE__, array('CustomRefBGWP', 'activation_check'));

endif; // End if class_exists check.