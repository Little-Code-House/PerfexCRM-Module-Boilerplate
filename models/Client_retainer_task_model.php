<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Client_retainer_task_model extends App_Model
{

  protected $tableName;

  const TABLE = 'tasks';

  public function __construct()
  {
    $this->tableName = db_prefix() . CLIENT_RETAINER_MODULE_NAME . '_' . self::TABLE;
    $this->load->model('tasks_model');

    parent::__construct();
  }

  public function replace($id)
  {
    log_message('DEBUG', 'Updating retainer table');
    $task = $this->tasks_model->get($id);
    $retainerIncluded = 'This is included in the clients retainer' == get_custom_field_value($id, 'tasks_included_in_retainer', 'tasks');
    $billable = 'Yes' == get_custom_field_value($id, 'tasks_is_this_a_billable_retainer_task', 'tasks');

    $this->db->replace($this->tableName, [
      'tasks_id' => $id,
      'retainer_included' => $retainerIncluded,
      'billable' => $billable,
      'checked' => 0
    ]);

    $data = ['billable' => $billable];

    if (!isset($task->hourly_rate) || $task->hourly_rate == 0) {
      $data['hourly_rate'] = 100;
    }

    $this->db->where('id', $id);
    $this->db->update(db_prefix() . 'tasks', $data);
  }
}
