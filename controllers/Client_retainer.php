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

    $this->load->model('Tasks_model');
    $tasks = $this->Tasks_model->get_billable_tasks();


    $data = [
      'client_name' => $this->input->get('client_name') ?? null,
      'client_id' => $this->input->get('client_id') ?? null,
      'rate' => $this->input->get('rate') ?? null,
      'hours' => $this->input->get('hours') ?? null,
      'clients' => $this->Clients_model->get(),
      'retained' => $retained->result(),
      'tasks' => $tasks
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

  public function process()
  {
    log_message('debug', 'START RETAINER PROCESSING');
    $this->load->model('Clients_model');
    $this->load->model('Tasks_model');
    $this->load->model('Invoices_model');
    $clients = $this->Clients_model->get();
    $nowDate = new DateTime('now');
    $tax = get_tax_by_id(1);
    foreach ($clients as $client) {
      $invoiceTasks = [];
      $retainerTaskHours = 0;
      $retainerTaskDiscount = 0;
      $retainerTaskAmount = 0;
      $this->db->select('rate, hours');
      $this->db->where(self::TABLE . '_id', $client['userid']);
      $retainer = $this->db->get($this->tableName)->row();
      $tasks = $this->Tasks_model->get_billable_tasks($client['userid']);
      // Process billable tasks
      foreach ($tasks as $task) {
        if ($task['datefinished']) {
          $taskData = $this->Tasks_model->get_billable_task_data($task['id']);
          $invoiceTasks[] = $taskData;
          $data = $this->Tasks_model->get_billable_task_data($task['id']);
          $retainerIncluded = 'This is included in the clients retainer' == get_custom_field_value($task['id'], 'tasks_included_in_retainer', 'tasks');
          if ($retainerIncluded) {
            $retainerTaskHours += $taskData->total_hours;
            $retainerTaskAmount += $taskData->total_hours * $taskData->hourly_rate;
          }
        }
      }

      //Process Retainer      
      if ($retainer && $retainerTaskHours > 0) {
        $retainerTaskDiscount = ($retainer->hours / $retainerTaskHours);
        $retainerTaskDiscount = ($retainerTaskDiscount > 1) ? 1 : $retainerTaskDiscount;
      }

      //Make Invoice
      if ($retainer || count($invoiceTasks) > 0) {
        $makeDescription = function ($id) use ($retainerTaskDiscount) {
          $retainerIncluded = 'This is included in the clients retainer' == get_custom_field_value($id, 'tasks_included_in_retainer', 'tasks');
          if ($retainerIncluded) {
            return round(($retainerTaskDiscount * 100)) . '% covered by retainer.';
          } else {
            return 'Not covered by retainer.  ';
          }
        };
        $invoiceData = [];
        $invoiceData['clientid'] = $client['userid'];
        $invoiceData['number'] = $this->get_next_invoice_number();
        $invoiceData['billing_street'] = $client['billing_street'];
        $invoiceData['billing_city'] = $client['billing_city'];
        $invoiceData['billing_state'] = $client['billing_state'];
        $invoiceData['billing_zip'] = $client['billing_zip'];
        $invoiceData['billing_country'] = $client['billing_country'];
        $invoiceData['show_shipping_on_invoice'] = 'off';
        $invoiceData['date'] = $nowDate->format('d/m/Y');
        $invoiceData['duedate'] = (clone $nowDate)->modify('first day of next month')->add(new DateInterval('P6D'))->format('d/m/Y');
        $invoiceData['allowed_payment_modes'] = [];
        $invoiceData['allowed_payment_modes'][] = '1';
        $invoiceData['allowed_payment_modes'][] = 'stripe';
        $invoiceData['currency'] = get_base_currency()->id;
        $invoiceData['show_quantity_as'] = '3';
        $invoiceData['taxname'] = $tax->name . '|' . $tax->taxrate;
        $invoiceData['newitems'] = [];
        if ($retainer) {
          $monthName = (clone $nowDate)->modify('first day of next month')->format('F');
          $itemLine = [];
          $itemLine['order'] = '1';
          $itemLine['description'] = $monthName . ' Retainer';
          $itemLine['long_description'] = '';
          $itemLine['qty'] = '1';
          $itemLine['unit'] = null;
          $itemLine['rate'] = $retainer->rate;
          $itemLine['taxname'][] = $tax->name . '|' . $tax->taxrate;
          $invoiceData['newitems'][] = $itemLine;
        }
        foreach ($invoiceTasks as $index => $task) {
          $itemLine = [];
          $itemLine['order'] = $index + 2;
          $itemLine['description'] = $task->name;
          $itemLine['long_description'] = $makeDescription($task->id);
          $itemLine['qty'] = $task->total_hours;
          $itemLine['unit'] = null;
          $itemLine['rate'] = $task->hourly_rate;
          $itemLine['taxname'][] = $tax->name . '|' . $tax->taxrate;
          $invoiceData['newitems'][] = $itemLine;
          $invoiceData['billed_tasks'][$index + 1][] = $task->id;
        }
        if ($retainerTaskDiscount > 0) {
          $itemLine = [];
          $itemLine['order'] = '99';
          $itemLine['description'] = 'Work Included In Retainer';
          $itemLine['long_description'] = '';
          $itemLine['qty'] = '1';
          $itemLine['unit'] = null;
          $itemLine['rate'] = 0 - ($retainerTaskAmount * $retainerTaskDiscount);
          $itemLine['taxname'][] =
            $tax->name . '|' . $tax->taxrate;
          $invoiceData['newitems'][] = $itemLine;
        }
        $invoiceData['save_as_draft'] = true;
        $invoiceData['subtotal'] = array_reduce($invoiceData['newitems'], function ($a, $v) {
          $a += $v['qty'] * $v['rate'];
          return $a;
        });


        $taxesTotal = array_reduce($invoiceData['newitems'], function ($a, $v) use ($tax) {
          if ($v['taxname']) {
            $a += ($v['qty'] * $v['rate']) * ($tax->taxrate / 100);
          }
          return $a;
        });
        $invoiceData['total'] = $invoiceData['subtotal'] + $taxesTotal;

        // echo '<pre>' . print_r($invoiceData, true) . '</pre>';

        $this->Invoices_model->add($invoiceData);
      }
    }
  }

  function get_next_invoice_number()
  {
    $next_invoice_number = get_option('next_invoice_number');
    $format = get_option('invoice_number_format');

    if (isset($invoice)) {
      $format = $invoice->number_format;
    }

    $prefix = get_option('invoice_prefix');

    if ($format == 1) {
      $__number = $next_invoice_number;
      if (isset($invoice)) {
        $__number = $invoice->number;
        $prefix = '<span id="prefix">' . $invoice->prefix . '</span>';
      }
    } else if ($format == 2) {
      if (isset($invoice)) {
        $__number = $invoice->number;
        $prefix = $invoice->prefix;
        $prefix = '<span id="prefix">' . $prefix . '</span><span id="prefix_year">' . date('Y', strtotime($invoice->date)) . '</span>/';
      } else {
        $__number = $next_invoice_number;
        $prefix = $prefix . '<span id="prefix_year">' . date('Y') . '</span>/';
      }
    } else if ($format == 3) {
      if (isset($invoice)) {
        $yy = date('y', strtotime($invoice->date));
        $__number = $invoice->number;
        $prefix = '<span id="prefix">' . $invoice->prefix . '</span>';
      } else {
        $yy = date('y');
        $__number = $next_invoice_number;
      }
    } else if ($format == 4) {
      if (isset($invoice)) {
        $yyyy = date('Y', strtotime($invoice->date));
        $mm = date('m', strtotime($invoice->date));
        $__number = $invoice->number;
        $prefix = '<span id="prefix">' . $invoice->prefix . '</span>';
      } else {
        $yyyy = date('Y');
        $mm = date('m');
        $__number = $next_invoice_number;
      }
    }

    return str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
  }
}
