        <h1>Monthly Invoice Summary</h1>
        <table class="table table-retainer-invoices" style="table-layout: fixed;">
          <thead>
            <tr>
              <th style="width:250px">

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