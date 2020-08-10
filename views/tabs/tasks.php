          <?php echo form_open(admin_url('client_retainer/tasks')); ?>
          <h1>Tasks</h1>
          <button class="btn btn-info only-save">
            Save Tasks
          </button>
          <?php foreach ($tasks as $client_id => $taskList) { ?>
            <table class="table table-billable-tasks" style="table-layout: fixed;">
              <thead>
                <tr>
                  <th style="width:50%">
                    <?= get_company_name($client_id) ?>
                  </th>
                  <th style="width:50%">
                    Task Name
                  </th>
                  <th style="width:200px">
                    Status
                  </th>
                  <th style="width:125px">
                    Billable
                  </th>
                  <th style="width:90px">
                    Rate
                  </th>
                  <th style="width:90px">
                    Hours
                  </th>
                  <th style="width:90px">
                    Total Cost
                  </th>
                  <th style="width:310px">
                    Retainer
                  </th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($taskList as $row) { ?>
                  <tr id="<?= $row['task_id'] ?>">

                    <td>
                      <button class="btn btn-info only-save" formaction='<?= admin_url("client_retainer/tasks/{$row['task_id']}") ?>'>
                        Save
                      </button>
                      <?php echo render_input("tasks[{$row['task_id']}][id]", null, $row['task_id'], 'hidden'); ?>
                    </td>

                    <td>
                      <a target="_blank" href="<?= admin_url("tasks/view/{$row['task_id']}") ?>">
                        <?= $row['name'] ?>
                      </a>
                    </td>
                    <td>
                      <?= render_select("tasks[{$row['task_id']}][status]", $statusOptions, array('value', 'label'), null, $row['status']['id'], array(), array(), '', '', false) ?>
                    </td>
                    <td>
                      <div class="form-group">
                        <div class="radio radio-primary radio-inline">
                          <input type="radio" id="tasks[<?= $row['task_id'] ?>][billable]" name="tasks[<?= $row['task_id'] ?>][billable]" value="1" <?= $row['billable'] ? 'checked' : '' ?>>
                          <label for="tasks[<?= $row['task_id'] ?>][billable]">
                            Yes </label>
                        </div>
                        <div class="radio radio-primary radio-inline">
                          <input type="radio" id="tasks[<?= $row['task_id'] ?>][billable]" name="tasks[<?= $row['task_id'] ?>][billable]" value="0" <?= $row['billable'] ? '' : 'checked' ?>>
                          <label for="tasks[<?= $row['task_id'] ?>][billable]">
                            No </label>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?= render_input("tasks[{$row['task_id']}][hourly_rate]", null, $row['hourly_rate'], 'text', array(), array(), ''); ?>
                    </td>
                    <td>
                      <?= $this->Tasks_model->get_billable_task_data($row['task_id'])->total_hours ?>
                    </td>
                    <td>
                      $<?= $this->Tasks_model->get_billable_task_data($row['task_id'])->total_hours * $row['hourly_rate'] ?>
                    </td>
                    <td>
                      <?php
                      $field_id = get_custom_field_id_from_slug('tasks_included_in_retainer');
                      echo render_select("tasks[{$row['task_id']}][custom_fields][tasks][$field_id]", $retainerOptions, array('value', 'label'), null, get_custom_field_value($row['task_id'], 'tasks_included_in_retainer', 'tasks'), array(), array(), '', '', false);
                      ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          <?php } ?>
          <?php echo form_close(); ?>