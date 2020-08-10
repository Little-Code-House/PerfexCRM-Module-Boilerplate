<h1>Retainers</h1>
<div class="col-md-4">
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
<div class="col-md-8">
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