<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Client_retainer_task_model extends App_Model
{

  protected $tableName;

  const TABLE = 'tasks';

  public function __construct()
  {
    $this->tableName = db_prefix() . CLIENT_RETAINER_MODULE_NAME . '_' . self::TABLE;

    parent::__construct();
  }

  public function replace($id)
  {
    log_message('DEBUG', 'Updating retainer table');
    $retainerIncluded = 'This is included in the clients retainer' == get_custom_field_value($id, 'tasks_included_in_retainer', 'tasks');

    $this->db->replace($this->tableName, [
      'tasks_id' => $id,
      'retainer_included' => $retainerIncluded
    ]);
  }
}
