<?php
defined('BASEPATH') or exit('No direct script access allowed');
defined('CLIENT_RETAINER_MODULE_NAME') or define('CLIENT_RETAINER_MODULE_NAME', 'client_retainer');


class Client_retainer extends AdminController
{
  protected $tableName;
  protected $joinTable;
  protected $retainerInvoiceTable;

  const TABLE = 'clients';


  public function __construct()
  {
    $this->tableName = db_prefix() . CLIENT_RETAINER_MODULE_NAME . '_' . self::TABLE;
    $this->joinTable = db_prefix() . 'clients';
    $this->retainerInvoiceTable =
      db_prefix() . CLIENT_RETAINER_MODULE_NAME . '_retainer_invoices';
    parent::__construct();
  }

  function getViewData()
  {
    $CI = &get_instance();
    $CI->load->model('Clients_model');

    $CI->db->select('clients_id, company, rate, hours');
    $CI->db->from($this->tableName);
    $CI->db->join($this->joinTable, "{$this->joinTable}.userid = {$this->tableName}.clients_id");
    $retained = $CI->db->get();

    $financialYear = (date('m') > 6) ? date('Y') + 1 : date('Y');

    $query = $this->db->query(<<<SQL
        SELECT `company`, `year`, `month`, `tasks`, `retainer`, `invoices_id`, `userid` AS `clients_id`
FROM (
SELECT *
FROM `tblclient_retainer_retainer_invoices`
WHERE (`month` < 6 AND `year` = $financialYear) OR (`month` > 6 AND `year` = $financialYear - 1)) AS `tblclient_retainer_retainer_invoices`
RIGHT JOIN `tblclients` ON `tblclients`.`userid` = `tblclient_retainer_retainer_invoices`.`clients_id`
WHERE `tblclients`.`active` = 1
ORDER BY `company`;
SQL);

    $retainerInvoices =
      $query->result();

    $invoices = [];

    foreach ($retainerInvoices as $i) {
      $invoices[$i->company][] = $i;
    }

    $this->load->model('Tasks_model');
    $this->load->model('Clients_model');

    $clients = $this->Clients_model->get(null, ['tblclients.active' => 1]);
    $this->db->where(['billed' => 0, 'rel_type' => 'customer', 'rel_id !=' => 11]);
    $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

    $tasksList = array_map(function ($v) {
      return [
        'client_id' => $v['rel_id'],
        'task_id' => $v['id'],
        'name' => $v['name'],
        'hourly_rate' => $v['hourly_rate'],
        'billable' => $v['billable']
      ];
    }, $tasks);

    $tasksTree = [];

    foreach ($tasksList as $t) {
      $tasksTree[$t['client_id']][] = $t;
    }


    $data = [
      'client_name' => $this->input->get('client_name') ?? null,
      'client_id' => $this->input->get('client_id') ?? null,
      'rate' => $this->input->get('rate') ?? null,
      'hours' => $this->input->get('hours') ?? null,
      'clients' => $this->Clients_model->get(),
      'retained' => $retained->result(),
      'tasks' => $tasksTree,
      'invoices' => $invoices,
      'processed' => $this->session->flashdata('processed'),
      'months' => get_monthnames(),
      'fymonths' => get_fymonths(),
    ];

    return $data;
  }

  public function index()
  {
    $data = $this->getViewData();

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
    $data = $this->input->get();
    log_message('debug', 'START RETAINER PROCESSING');
    $this->load->model('Tasks_model');
    $this->load->model('Invoices_model');
    $this->db->where(db_prefix() . 'clients.active', 1);
    if (null != $this->input->get('client')) {
      $this->db->where(db_prefix() . 'clients.userid', $this->input->get('client'));
    }
    $clients = $this->db->get(db_prefix() . 'clients')->result_array();
    $nowDate = new DateTime('now');
    $tax = get_tax_by_id(1);
    $processed = [];
    foreach ($clients as $client) {
      $invoiceTasks = [];
      $retainerTaskHours = 0;
      $retainerTaskDiscount = 0;
      $retainerTaskAmount = 0;
      $this->db->select('rate, hours');
      $this->db->where(self::TABLE . '_id', $client['userid']);
      $retainer = $this->db->get($this->tableName)->row();
      if (null !== $this->input->get('month')) {
        $taskYear =  $this->input->get('year') . '-' . ($this->input->get('month') + 1) . '-' . '01';
        $tasks = $this->get_billable_tasks_before($client['userid'], $taskYear);
      } else {
        $tasks = $this->get_billable_tasks_before($client['userid']);
      }
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
        $makeDescription = function ($id) use ($retainerTaskDiscount, $retainer) {
          if ($retainer) {
            $retainerIncluded = 'This is included in the clients retainer' == get_custom_field_value($id, 'tasks_included_in_retainer', 'tasks');
            if ($retainerIncluded) {
              if ($retainerTaskDiscount < 1) {
                return '';
              } else {
                return '';
              }
            } else {
              return 'Not covered by retainer.  ';
            }
          } else {
            return '';
          }
        };
        $taskRetained =
          function ($id) {
            return 'This is included in the clients retainer' == get_custom_field_value($id, 'tasks_included_in_retainer', 'tasks');
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
          if (null != $this->input->get('month')) {
            $monthName = get_monthnames()[$this->input->get('month')];
          } else {
            $monthName = $nowDate->format('F');
          }
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
          $itemLine['qty'] = $retainerTaskDiscount < 1 || !$taskRetained($task->id) ? $task->total_hours : 1;
          $itemLine['unit'] = null;
          $itemLine['rate'] = $retainerTaskDiscount < 1 || !$taskRetained($task->id) ? $task->hourly_rate : 0;
          $itemLine['taxname'][] = $tax->name . '|' . $tax->taxrate;
          $invoiceData['newitems'][] = $itemLine;
          $invoiceData['billed_tasks'][$index + 1][] = $task->id;
        }
        if ($retainerTaskDiscount > 0 && $retainerTaskDiscount < 1) {
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

        $invoiceId = $this->Invoices_model->add($invoiceData);
        $this->db->select('*');
        $this->db->where('module_name', 'xero_sync');
        $this->db->where('active', '1');
        $xeroActive = $this->db->get(db_prefix() . 'modules')->row();
        if ($xeroActive) {
          sleep(5);
        }

        $processed[] = [
          'client' => $client['company'],
          'tasks' => count($tasks),
          'retainer' => is_null($retainer) ? 'No' : 'Yes',
          'invoice_number' => format_invoice_number($invoiceId),
          'invoice_id' => $invoiceId
        ];
      }
      $this->db->insert($this->retainerInvoiceTable, [
        'invoices_id' => $invoiceId ?? null,
        'clients_id' => $client['userid'],
        'year' => $this->input->get('year') ?? $nowDate->format('Y'),
        'month' => $this->input->get('month') ?? $nowDate->format('m'),
        'retainer' => !is_null($retainer) ?? false,
        'tasks' => json_encode(array_map(fn ($t) => $t->id, $invoiceTasks) ?? [])
      ]);
    }
    $this->session->set_flashdata('processed', $processed);
    redirect(admin_url(CLIENT_RETAINER_MODULE_NAME));
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

  function get_billable_tasks_before($customer_id = false, $date = '')
  {
    $has_permission_view = has_permission('tasks', '', 'view');
    $noPermissionsQuery  = get_tasks_where_string(false);

    $this->db->where('billable', 1);
    $this->db->where('billed', 0);
    $this->db->where('rel_type != "project"');

    if ($date != '') {
      $this->db->where('startdate <', $date);
    }

    if ($customer_id != false) {
      $this->db->where(
        '
                (
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE clientid=' . $this->db->escape_str($customer_id) . ') AND rel_type="invoice")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'estimates WHERE clientid=' . $this->db->escape_str($customer_id) . ') AND rel_type="estimate")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'contracts WHERE client=' . $this->db->escape_str($customer_id) . ') AND rel_type="contract")
                OR
                ( rel_id IN (SELECT ticketid FROM ' . db_prefix() . 'tickets WHERE userid=' . $this->db->escape_str($customer_id) . ') AND rel_type="ticket")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'expenses WHERE clientid=' . $this->db->escape_str($customer_id) . ') AND rel_type="expense")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'proposals WHERE rel_id=' . $this->db->escape_str($customer_id) . ' AND rel_type="customer") AND rel_type="proposal")
                OR
                (rel_id IN (SELECT userid FROM ' . db_prefix() . 'clients WHERE userid=' . $this->db->escape_str($customer_id) . ') AND rel_type="customer")
                )'
      );
    }

    if (!$has_permission_view) {
      $this->db->where($noPermissionsQuery);
    }

    $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

    $i = 0;
    foreach ($tasks as $task) {
      $task_rel_data         = get_relation_data($task['rel_type'], $task['rel_id']);
      $task_rel_value        = get_relation_values($task_rel_data, $task['rel_type']);
      $tasks[$i]['rel_name'] = $task_rel_value['name'];
      if (total_rows(db_prefix() . 'taskstimers', [
        'task_id' => $task['id'],
        'end_time' => null,
      ]) > 0) {
        $tasks[$i]['started_timers'] = true;
      } else {
        $tasks[$i]['started_timers'] = false;
      }
      $i++;
    }

    return $tasks;
  }
}
