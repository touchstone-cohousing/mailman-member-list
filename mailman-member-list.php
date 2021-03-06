<?php
/**
 * Plugin Name: Mailman Member List
 * Plugin URI: http://github.com/hooverlunch/mailman-member-list
 * Description: Fetches mailman lists and their members using mailman shell commands.
 * Version: 0.1
 * Author: Tom Smyth
 * Author URI: http://sassafras.coop
 * License: GPL2
 */

/*  Copyright 2015 Tom Smyth (email: tom@sassafras.coop)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once(WP_PLUGIN_DIR . '/mailman-member-list/settings-page.php');

class MailmanMemberList {

  function __construct() {
    add_shortcode( 'mailman-members', array( $this, 'get_all_members' ) );
    if( is_admin() ) new MailmanMemberListSettingsPage();
    $this->options = get_option( 'mml_options' );
    $this->options['bin_path'] = $this->options['mailman_path'] . '/bin';
  }

  // Prints an HTML string consisting of list names, descriptions, and members for all lists on the system.
  function get_all_members() {
    $lists = $this->get_lists();
    $ignore = array_map('strtolower', explode(',', $this->options['ignore_lists']));
    $host = $this->get_host();

    $html = array();
    $html[] = '<div id="mailman-lists">';

    if ($host == NULL) {
      $html[] = '<em>ERROR: Could not find hostname in mailman config file.</em>';
    }

    foreach($lists as $list) {
      $name_lc = strtolower($list[0]);

      // Skip ignored lists (compare as lowercase).
      if (in_array($name_lc, $ignore)) continue;

      $html[] = <<<HTML
  <div class="mailman-list">
    <div class="list-name"><a href="mailto:{$name_lc}@{$host}">{$name_lc}@{$host}</a></div>
    <div class="list-description">{$list[1]}</div>
    <a href="#" class="show-link">Show Members</a>
    <a href="#" class="hide-link" style="display: none">Hide Members</a>
    <ul style="display: none">
HTML;

      $members = $this->get_members($list[0]);

      foreach($members as $member) {
        // Remove escaping that mailmain adds for some reason
        $name = preg_replace('/("|\\\\)/', '', $member[1]);

        $html[] = '<li>';

        if (count($member) == 1) {
          $html[] = <<<HTML
      <span class="member-name no-name"></span><a class="member-email" href="mailto:{$member[0]}">{$member[0]}</a>
HTML;
        } else {
          $html[] = <<<HTML
      <span class="member-name">{$name}</span>
      &lt;<a class="member-email" href="mailto:{$member[0]}">{$member[0]}</a>&gt;
HTML;
        }

        $html[] = <<<HTML

    </li>
HTML;
      }
      $html[] = '</ul></div>';
    }

    $html[] = '';
    $html[] = <<<HTML
  </div>

  <script type="text/javascript">
    function mml_toggle(e) {
      e.preventDefault();
      var show = jQuery(e.target).hasClass('show-link');
      var parent = jQuery(e.target).closest('.mailman-list');
      parent.find('ul, .hide-link')[show ? 'show' : 'hide']();
      parent.find('.show-link')[show ? 'hide' : 'show']();
    }
    jQuery(document).ready(function(){
      jQuery('.mailman-list a').on('click', mml_toggle);
      jQuery('.mailman-list-loading').hide();
    });
  </script>
HTML;

    return implode("\n", $html);
  }

  // Given a list name, looks up list description and returns array of form ['list-name', 'List Description']
  function add_list_description($name) {
    exec($this->options['bin_path'] . "/config_list -o - $name | grep \"^description =\"", $out);

    return array($name, substr($out[0], 15, -1));
  }

  // Returns an array of form [['list1-name', 'List 1 description'], ['list2-name', 'List 2 description']]
  private function get_lists() {
    exec($this->options['bin_path'] . '/list_lists -b', $lists);

    return array_map(array($this, 'add_list_description'), $lists);
  }

  // Returns an array of form ['email1@example.com', 'Jane Doe'], or ['email1@example.com'] if no name present.
  private function parse_member_name_email($str) {
    if (preg_match('/^(.+?)\s+<(.+)>$/', $str, $matches) === 1)
      return array($matches[2], $matches[1]);
    else
      return array($str);
  }

  // Returns an array of form [['email1@example.com', 'Jane Doe'], ['email2@example.com', 'John Doe'], ['email2@example.com']]
  // Note that second element of sub array, person name, may not be present.
  private function get_members($list_name) {
    exec($this->options['bin_path'] . "/list_members -f $list_name", $members);
    return array_map(array($this, 'parse_member_name_email'), $members);
  }

  // Gets DEFAULT_EMAIL_HOST setting from mm_cfg.py or Defaults.py, or NULL if not found.
  private function get_host() {
    $files = array('mm_cfg.py', 'Defaults.py');
    $dir = $this->options['mailman_path'] . '/Mailman';
    $regexp = '/DEFAULT_EMAIL_HOST\s*=\s*[\'"](.+)[\'"]/';
    foreach ($files as $file) {
      $path = "$dir/$file";
      if (file_exists($path) && preg_match($regexp, file_get_contents($path), $m)) {
        return $m[1];
      }
    }
    return NULL;
  }
}

$mml = new MailmanMemberList();
?>
