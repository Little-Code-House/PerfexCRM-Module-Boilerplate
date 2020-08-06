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

$CI = &get_instance();

hooks()->add_action('admin_init', 'client_retainer_init_menu_items');
hooks()->add_action('after_add_task', 'callback_after_add_task_client_retainer');
hooks()->add_action('after_update_task', 'callback_after_update_task_client_retainer');

/**
 * Load the module helper
 */
$CI->load->helper(CLIENT_RETAINER_MODULE_NAME . '/client_retainer');


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

  $moduleName = CLIENT_RETAINER_MODULE_NAME;

  $tablename = $db_prefix . $moduleName . '_clients';
  if (!$CI->db->table_exists($tablename)) {
    $CI->db->query("CREATE TABLE `$tablename` (
    `clients_id` INT(11) NOT NULL UNIQUE,
    `rate` INT(11) NOT NULL,
    `hours` INT(11) NOT NULL,
  CONSTRAINT `fk_{$tablename}_clients`
    FOREIGN KEY (clients_id) REFERENCES `{$db_prefix}clients` (userid)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
    PRIMARY KEY (clients_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=\"{$CI->db->char_set}\";");
  }

  $tablename = $db_prefix . $moduleName . '_tasks';
  if (!$CI->db->table_exists($tablename)) {
    $CI->db->query("CREATE TABLE `$tablename` (
    `tasks_id` INT(11) NOT NULL UNIQUE,
    `retainer_included` BOOLEAN NOT NULL,
    `billable` BOOLEAN NOT NULL,
  CONSTRAINT `fk_{$tablename}_clients`
    FOREIGN KEY (tasks_id) REFERENCES `{$db_prefix}tasks` (id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
    PRIMARY KEY (tasks_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=\"{$CI->db->char_set}\";");
  }

  $tablename = $db_prefix . $moduleName . '_retainer_invoices';
  if (!$CI->db->table_exists($tablename)) {
    $CI->db->query("CREATE TABLE `$tablename` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `invoices_id` INT(11),
    `clients_id` INT(11) NOT NULL,
    `year` INT(4) NOT NULL,
    `month` INT(2) NOT NULL,
    `retainer` BOOLEAN,
    `tasks` JSON,
  CONSTRAINT `fk_{$tablename}_invoices`
    FOREIGN KEY (invoices_id) REFERENCES `{$db_prefix}invoices` (id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_{$tablename}_clients`
    FOREIGN KEY (clients_id) REFERENCES `{$db_prefix}clients` (userid)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  CONSTRAINT retainer_invoices_pk PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=\"{$CI->db->char_set}\";");
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

function callback_after_add_task_client_retainer($id)
{
  $CI = &get_instance();
  $CI->load->model(CLIENT_RETAINER_MODULE_NAME . '/Client_retainer_task_model');
  $CI->Client_retainer_task_model->replace($id);
}

function callback_after_update_task_client_retainer($id)
{
  $CI = &get_instance();
  $CI->load->model(CLIENT_RETAINER_MODULE_NAME . '/Client_retainer_task_model');
  $CI->Client_retainer_task_model->replace($id);
}
