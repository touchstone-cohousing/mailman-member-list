<?php
class MailmanMemberListSettingsPage
{
  /**
   * Holds the values to be used in the fields callbacks
   */
  private $options;

  /**
   * Start up
   */
  public function __construct()
  {
    add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
    add_action( 'admin_init', array( $this, 'page_init' ) );
  }

  /**
   * Add options page
   */
  public function add_plugin_page()
  {
    // This page will be under "Settings"
    add_options_page(
      'Mailman Member List',
      'Mailman Members',
      'manage_options',
      'mailman-member-list',
      array( $this, 'create_admin_page' )
    );
  }

  /**
   * Options page callback
   */
  public function create_admin_page()
  {
    // Set class property
    $this->options = get_option( 'mml_options' );
    ?>
    <div class="wrap">
      <?php screen_icon(); ?>
      <h2>Mailman Member List Settings</h2>
      <form method="post" action="options.php">
      <?php
        // This prints out all hidden setting fields
        settings_fields( 'mml_option_group' );
        do_settings_sections( 'mml-setting-admin' );
        submit_button();
      ?>
      </form>
    </div>
    <?php
  }

  /**
   * Register and add settings
   */
  public function page_init()
  {
    register_setting(
      'mml_option_group', // Option group
      'mml_options', // Option name
      array( $this, 'sanitize' ) // Sanitize
    );

    add_settings_section(
      'mml_main_settings_section', // ID
      'General', // Title
      array( $this, 'print_section_info' ), // Callback
      'mml-setting-admin' // Page
    );

    add_settings_field(
      'mailman_path', // ID
      'Mailman Path<br/>(e.g. /usr/local/mailman)', // Title
      array( $this, 'mailman_path_callback' ), // Callback
      'mml-setting-admin', // Page
      'mml_main_settings_section' // Section
    );

    add_settings_field(
      'ignore_lists', // ID
      'Lists to Ignore<br/>(e.g. mailman,admins)', // Title
      array( $this, 'ignore_lists_callback' ), // Callback
      'mml-setting-admin', // Page
      'mml_main_settings_section' // Section
    );
  }

  /**
   * Sanitize each setting field as needed
   *
   * @param array $input Contains all settings fields as array keys
   */
  public function sanitize( $input )
  {
    return $input;
  }

  /**
   * Print the Section text
   */
  public function print_section_info()
  {
  }

  /**
   * Get the settings option array and print one of its values
   */
  public function mailman_path_callback()
  {
    printf(
      '<input type="text" id="mailman_path" name="mml_options[mailman_path]" value="%s" style="width: 500px"/>',
      isset( $this->options['mailman_path'] ) ? esc_attr( $this->options['mailman_path']) : ''
    );
  }

  public function ignore_lists_callback()
  {
    printf(
      '<input type="text" id="ignore_lists" name="mml_options[ignore_lists]" value="%s" style="width: 400px"/>',
      isset( $this->options['ignore_lists'] ) ? esc_attr( $this->options['ignore_lists']) : ''
    );
  }
}
?>