          <h1>Tasks</h1>

          <table class="table table-billable-tasks" style="table-layout: fixed;">
            <thead>
              <tr>
                <th style="width:30%">
                  Unattached Tasks
                </th>
                <th style="width:20%">
                  Created By
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
              <?php foreach ($unattached_tasks as $row) { ?>
                <?php echo form_open(admin_url("client_retainer/tasks/{$row['task_id']}")); ?>
                <tr id="<?= $row['task_id'] ?>" class="<?= $row['status'] == 5 && $row['billable'] ? 'invoiced' : '' ?>">

                  <td>
                    <button class="btn btn-info only-save">
                      Save
                    </button>
                    <?php if ($row['checked']) { ?>
                      <a class="btn btn-info only-save" style="background-color: green" href='<?= admin_url("client_retainer/uncheck/{$row['task_id']}") ?>'>
                        Checked
                      </a>
                    <?php } else { ?>
                      <a class="btn btn-info only-save" style="background-color: purple" href='<?= admin_url("client_retainer/check/{$row['task_id']}") ?>'>
                        Unchecked
                      </a>
                    <?php } ?>


                    <?php echo render_input("task[id]", null, $row['task_id'], 'hidden'); ?>
                  </td>

                  <td>
                    <?= $row['created_by'] ?>
                  </td>

                  <td>
                    <a target="_blank" href="<?= admin_url("tasks/view/{$row['task_id']}") ?>">
                      <?= $row['name'] ?>
                    </a>
                  </td>
                  <td>
                    <div class="form-group">
                      <select name="task[status]" data-width="100%">
                        <?php
                        foreach ($statusOptions as $option) { ?>
                          <option value="<?= $option['value'] ?>" <?= $option['value'] == $row['status'] ? 'selected' : '' ?>><?= $option['label'] ?></option>
                        <?php
                        }
                        ?>
                      </select>
                    </div>
                  </td>
                  <td>
                    <div class="form-group">
                      <div class="radio radio-primary radio-inline">
                        <input type="radio" name="task[custom_fields][tasks][<?= get_custom_field_id_from_slug('tasks_is_this_a_billable_retainer_task') ?>]" value="Yes" <?= $row['billable'] ? 'checked' : '' ?>>
                        <label for="tasks[<?= $row['task_id'] ?>][billable]">
                          Yes </label>
                      </div>
                      <div class="radio radio-primary radio-inline">
                        <input type="radio" name="task[custom_fields][tasks][<?= get_custom_field_id_from_slug('tasks_is_this_a_billable_retainer_task') ?>]" value="No" <?= $row['billable'] ? '' : 'checked' ?>>
                        <label for="tasks[<?= $row['task_id'] ?>][billable]">
                          No </label>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?= render_input("task[hourly_rate]", null, $row['hourly_rate'], 'text', array(), array(), ''); ?>
                  </td>
                  <td>
                    <?= $this->Tasks_model->get_billable_task_data($row['task_id'])->total_hours ?>
                  </td>
                  <td>
                    $<?= $this->Tasks_model->get_billable_task_data($row['task_id'])->total_hours * $row['hourly_rate'] ?>
                  </td>
                  <td>
                    <div class="form-group">
                      <select name="task[custom_fields][tasks][<?= get_custom_field_id_from_slug('tasks_included_in_retainer') ?>]" data-width="100%">
                        <?php
                        foreach ($retainerOptions as $option) { ?>
                          <option value="<?= $option['value'] ?>" <?= $option['value'] == get_custom_field_value($row['task_id'], 'tasks_included_in_retainer', 'tasks') ? 'selected' : '' ?>><?= $option['label'] ?></option>
                        <?php
                        }
                        ?>
                      </select>
                    </div>
                  </td>
                </tr>
                <?php echo form_close(); ?>
              <?php } ?>
            </tbody>
          </table>

          <?php foreach ($tasks as $client_id => $taskList) { ?>
            <table class="table table-billable-tasks" style="table-layout: fixed;">
              <thead>
                <tr>
                  <th style="width:30%">
                    <?= get_company_name($client_id) ?>
                  </th>
                  <th style="width:20%">
                    Created By
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
                  <?php echo form_open(admin_url("client_retainer/tasks/{$row['task_id']}")); ?>
                  <tr id="<?= $row['task_id'] ?>" class="<?= $row['status'] == 5 && $row['billable'] ? 'invoiced' : '' ?>">

                    <td>
                      <button class="btn btn-info only-save">
                        Save
                      </button>
                      <?php if ($row['checked']) { ?>
                        <a class="btn btn-info only-save" style="background-color: green" href='<?= admin_url("client_retainer/uncheck/{$row['task_id']}") ?>'>
                          Checked
                        </a>
                      <?php } else { ?>
                        <a class="btn btn-info only-save" style="background-color: purple" href='<?= admin_url("client_retainer/check/{$row['task_id']}") ?>'>
                          Unchecked
                        </a>
                      <?php } ?>
                      <?php echo render_input("tasks[{$row['task_id']}][id]", null, $row['task_id'], 'hidden'); ?>
                    </td>

                    <td>
                      <?= $row['created_by'] ?>
                    </td>

                    <td>
                      <a target="_blank" href="<?= admin_url("tasks/view/{$row['task_id']}") ?>">
                        <?= $row['name'] ?>
                      </a>
                    </td>
                    <td>
                      <div class="form-group">
                        <select name="task[status]" data-width="100%">
                          <?php
                          foreach ($statusOptions as $option) { ?>
                            <option value="<?= $option['value'] ?>" <?= $option['value'] == $row['status'] ? 'selected' : '' ?>><?= $option['label'] ?></option>
                          <?php
                          }
                          ?>
                        </select>
                      </div>
                    </td>
                    <td>
                      <div class="form-group">
                        <div class="radio radio-primary radio-inline">
                          <input type="radio" id="tasks[<?= $row['task_id'] ?>][billable]_yes" name="tasks[<?= $row['task_id'] ?>][custom_fields][tasks][<?= get_custom_field_id_from_slug('tasks_is_this_a_billable_retainer_task') ?>]" value="Yes" <?= $row['billable'] ? 'checked' : '' ?>>
                          <label for="tasks[<?= $row['task_id'] ?>][billable]">
                            Yes </label>
                        </div>
                        <div class="radio radio-primary radio-inline">
                          <input type="radio" id="tasks[<?= $row['task_id'] ?>][billable]_no" name="tasks[<?= $row['task_id'] ?>][custom_fields][tasks][<?= get_custom_field_id_from_slug('tasks_is_this_a_billable_retainer_task') ?>]" value="No" <?= $row['billable'] ? '' : 'checked' ?>>
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
                      <div class="form-group">
                        <select name="task[custom_fields][tasks][<?= get_custom_field_id_from_slug('tasks_included_in_retainer') ?>]" data-width="100%">
                          <?php
                          foreach ($retainerOptions as $option) { ?>
                            <option value="<?= $option['value'] ?>" <?= $option['value'] == get_custom_field_value($row['task_id'], 'tasks_included_in_retainer', 'tasks') ? 'selected' : '' ?>><?= $option['label'] ?></option>
                          <?php
                          }
                          ?>
                        </select>
                      </div>
                    </td>
                  </tr>
                  <?php echo form_close(); ?>
                <?php } ?>
              </tbody>
            </table>
          <?php } ?>