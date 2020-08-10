<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row mbot5">
      <ul class="nav nav-pills nav-justified" role="tablist">
        <li class="<?= $tab == 'retainer' ? 'active' : '' ?>">
          <a data-group="profile" href="<?= admin_url('client_retainer/list/retainer') ?>">
            Retainers </a>
        </li>
        <li class="<?= $tab == 'invoices' ? 'active' : '' ?>">
          <a data-group="profile" href="<?= admin_url('client_retainer/list/invoices') ?>">
            Invoices </a>
        </li>
        <li class="<?= $tab == 'tasks' ? 'active' : '' ?>">
          <a data-group="profile" href="<?= admin_url('client_retainer/list/tasks') ?>">
            Tasks </a>
        </li>
      </ul>
    </div>
    <div class="row">
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
          <?php $this->load->view((isset($tab) ? CLIENT_RETAINER_MODULE_NAME . '/tabs/' . $tab : CLIENT_RETAINER_MODULE_NAME . '/tabs/retainer')); ?>
        </div>
      </div>
    </div>

    <?php init_tail(); ?>
    <script src="<?php echo module_dir_url('client_retainer', 'dist/js.js'); ?>"></script>
    <link href="<?php echo module_dir_url('client_retainer', 'dist/style.css'); ?>" rel="stylesheet">