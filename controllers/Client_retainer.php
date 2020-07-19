<?php
defined('BASEPATH') or exit('No direct script access allowed');
defined('CLIENT_RETAINER_MODULE_NAME') or define('CLIENT_RETAINER_MODULE_NAME', 'client_retainer');


class Client_retainer extends AdminController
{
  protected $tableName;
  protected $joinTable;

  const TABLE = 'clients';


  public function __construct()
  {
    $this->tableName = db_prefix() . CLIENT_RETAINER_MODULE_NAME . '_' . self::TABLE;
    $this->joinTable = db_prefix() . 'clients';
    parent::__construct();
  }

  public function index()
  {
    $CI = &get_instance();
    $CI->load->model('Clients_model');

    $CI->db->select('clients_id, company, rate, hours');
    $CI->db->from($this->tableName);
    $CI->db->join($this->joinTable, "{$this->joinTable}.userid = {$this->tableName}.clients_id");
    $retained = $this->db->get();

    $data = [
      'client_name' => $this->input->get('client_name') ?? null,
      'client_id' => $this->input->get('client_id') ?? null,
      'rate' => $this->input->get('rate') ?? null,
      'hours' => $this->input->get('hours') ?? null,
      'clients' => $this->Clients_model->get(),
      'retained' => $retained->result()
    ];

    $this->load->view(CLIENT_RETAINER_MODULE_NAME . '/list', $data);
  }

  public function add()
  {
    $data = $this->input->post();

    $this->db->insert($this->tableName, [
      self::TABLE . '_id' => $data['client_id'],
      'rate' => $data['rate'],
      'hours' => $data['hours']
    ]);

    redirect(admin_url(CLIENT_RETAINER_MODULE_NAME));
  }

  public function edit()
  {
    $data = $this->input->post();

    $this->db->where(self::TABLE . '_id', $data['client_id']);
    $this->db->update($this->tableName, [
      'rate' => $data['rate'],
      'hours' => $data['hours']
    ]);

    redirect(admin_url(CLIENT_RETAINER_MODULE_NAME));
  }

  public function delete()
  {
    $data = $this->input->get();

    $this->db->where(self::TABLE . '_id', $data['client_id']);
    $this->db->delete($this->tableName);

    redirect(admin_url(CLIENT_RETAINER_MODULE_NAME));
  }
}
