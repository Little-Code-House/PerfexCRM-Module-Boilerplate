<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">


  <div class="content">
    <div class="panel_s">
      <div class="panel-body">
        <div class="col-md-6">
          <?php
          if (isset($client_id)) {
            echo form_open(admin_url('client_retainer/edit'));
          ?>
            <h1>Editing <?= $client_name ?></h1>
            <?php echo render_input('client_id', null, $client_id, 'hidden'); ?>
          <?php
          } else {
            echo form_open(admin_url('client_retainer/add'));
            echo render_select('client_id', $clients, array('userid', 'company'), 'Client', null, array(), array(), '');
          }
          ?>
          <?php echo render_input('rate', 'Retainer Fee', $rate ?? null, 'text', array(), array(), ''); ?>
          <?php echo render_input('hours', 'Included Hours', $hours ?? 8, 'text', array(), array(), ''); ?>
          <button class="btn btn-info only-save">
            Save Retained Client
          </button>
          <a class="btn" href="<?= admin_url("client_retainer/process") ?>">
            Process EoM
          </a>
          <?php echo form_close(); ?>
        </div>
        <div class="col-md-6">
          <table class="table table-retained-clients">
            <thead>
              <tr>
                <th>
                  Company
                </th>
                <th>
                  Rate</th>
                <th>
                  Hours
                </th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($retained as $row) { ?>
                <tr>
                  <td>
                    <?= $row->company ?>
                  </td>
                  <td>
                    <?= $row->rate ?>
                  </td>
                  <td>
                    <?= $row->hours ?>
                  </td>
                  <td>
                    <a class="btn" href="<?= admin_url("client_retainer?client_id=$row->clients_id&client_name=$row->company&rate=$row->rate&hours=$row->hours") ?>">
                      <i class="fa fa-edit"></i>
                    </a>
                    <a class="btn" href="<?= admin_url("client_retainer/delete?client_id=$row->clients_id") ?>">
                      <i class="fa fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if (isset($processed)) { ?>
      <div class="panel_s">
        <div class="panel-body">
          <h1>Processed Tasks</h1>
          <table class="table table-processed-clients">
            <thead>
              <tr>
                <th>
                  Company
                </th>
                <th>
                  No. of Tasks</th>
                <th>
                  Retainer
                </th>
                <th>
                  Invoice No.
                </th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($processed as $row) { ?>
                <tr>
                  <td>
                    <?= $row['client'] ?>
                  </td>
                  <td>
                    <?= $row['tasks'] ?>
                  </td>
                  <td>
                    <?= $row['retainer'] ?>
                  </td>
                  <td>
                    <a href="<?= admin_url("invoices/invoice/{$row['invoice_id']}") ?>">
                      <?= $row['invoice_number'] ?>
                    </a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php } ?>

    <div class="panel_s">
      <div class="panel-body">
        <h1>Monthly Invoice Summary</h1>
        <!-- <pre>
          <?= print_r($invoices, true) ?>
        </pre> -->
        <table class="table table-retainer-invoices">
          <thead>
            <tr>
              <th>

              </th>
              <?php foreach ($fymonths as $i => $m) { ?>
                <th>
                  <?= $months[$m] ?>
                </th>
              <?php } ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($invoices as $client => $invoiceList) { ?>
              <tr>
                <th>
                  <?= $client ?>
                </th>
                <?php foreach ($fymonths as $i => $m) { ?>
                  <td>
                    <?php if ($i > array_search((new DateTime())->format('m'), get_fymonths())) {
                      echo 'Future Date';
                    } else {
                      $invoiceFound = false; ?>
                      <?php foreach ($invoiceList as $invoice) { ?>
                        <?php if ($m == $invoice->month) {
                          $invoiceFound = true; ?>
                          Retainer <?= $invoice->retainer == 1 ? 'Yes' : 'No' ?> <br>
                          Tasks <?= count(json_decode($invoice->tasks)) ?> <br>
                          <?php if ($invoice->invoices_id) { ?>
                            <a href="<?= admin_url("invoices/list_invoices/{$invoice->invoices_id}") ?>">
                              View Invoice
                            </a>
                          <?php } ?>
                        <?php } ?>
                      <?php } ?>
                      <?php if (!$invoiceFound) {
                        $fyyear = get_fyyear($m); ?>
                        <a class="btn btn-info" href="<?= admin_url("client_retainer/process?client=$invoice->clients_id&month=$m&year=$fyyear") ?>">
                          Process
                        </a>
                      <?php } ?>
                    <?php } ?>
                  </td>
                <?php } ?>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <div class="panel_s">
        <div class="panel-body">

        </div>
      </div>

      <div class="panel_s">
        <div class="panel-body">
          <h1>Complete Billable Tasks</h1>
          <?php foreach ($tasks as $client_id => $taskList) { ?>
            <table class="table table-billable-tasks">
              <thead>
                <tr>
                  <th style="width:25%">
                    <?= get_company_name($client_id) ?>
                  </th>
                  <th style="width:25%">
                    Task Name
                  </th>
                  <th style="width:12%">
                    Billable
                  </th>
                  <th style="width:12%">
                    Rate
                  </th>
                  <th style="width:25%">
                    Retainer
                  </th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($taskList as $row) { ?>
                  <tr>

                    <td>

                    </td>

                    <td>
                      <a href="<?= admin_url("tasks/view/{$row['task_id']}") ?>">
                        <?= $row['name'] ?>
                      </a>
                    </td>
                    <td>
                      <?= $row['billable'] ? 'Yes' : 'No' ?>
                    </td>
                    <td>
                      <?= $row['hourly_rate'] ?>
                    </td>
                    <td>
                      <?= get_custom_field_value($row['task_id'], 'tasks_included_in_retainer', 'tasks') ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          <?php } ?>
        </div>
      </div>
      <?php init_tail(); ?>
      <script>
        $(function() {
          initDataTableInline('.table-retained-clients');
        });
      </script>