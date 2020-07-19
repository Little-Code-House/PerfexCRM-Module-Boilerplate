<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Client Retainer
Description: Administer client retainer details and processes outstanding work.
Version: 2.3.0
Requires at least: 2.3.*
*/

defined('CLIENT_RETAINER_MODULE_NAME') or define('CLIENT_RETAINER_MODULE_NAME', 'client_retainer');

hooks()->add_action('admin_init', 'client_retainer_init_menu_items');


/**
 * Register module activation hook
 * @param  string $module   module system name
 * @param  mixed  $function function for the hook
 * @return mixed
 */

register_activation_hook(CLIENT_RETAINER_MODULE_NAME, 'client_retainer_activation');

/**
 * Register module deactivation hook
 * @param  string $module   module system name
 * @param  mixed  $function function for the hook
 * @return mixed
 */

// register_deactivation_hook($module, $function);

/**
 * Register module uninstall hook
 * @param  string $module   module system name
 * @param  mixed  $function function for the hook
 * @return mixed
 */

// register_uninstall_hook($module, $function);

function client_retainer_activation()
{
  $CI = &get_instance();
  $db_prefix = db_prefix();

  $perfexTables = [
    'clients' => 'userid',
  ];

  foreach ($perfexTables as $table => $id) {
    $moduleName = CLIENT_RETAINER_MODULE_NAME;
    $tablename = $db_prefix . $moduleName . '_' . $table;
    if (!$CI->db->table_exists($tablename)) {
      $CI->db->query("CREATE TABLE `$tablename` (
    `{$table}_id` INT(11) NOT NULL UNIQUE,
    `rate` INT(11) NOT NULL,
    `hours` INT(11) NOT NULL,
  CONSTRAINT `fk_{$tablename}_{$table}`
    FOREIGN KEY ({$table}_id) REFERENCES `{$db_prefix}{$table}` ($id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
    PRIMARY KEY ({$table}_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=\"{$CI->db->char_set}\";");

    }
  }
}

function client_retainer_init_menu_items()
{
  $CI = &get_instance();
  $CI->app_menu->add_sidebar_menu_item('client_retainer', [
    'name'     => 'Retained Clients',
    'href'     => admin_url('client_retainer'),
    'position' => 30,
    'icon'     => 'fa fa-list',
  ]);
}



