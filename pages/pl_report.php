<?php
// Yearly summary across all years
$yearlyData = q("
  SELECT yr,
    SUM(rev)  AS revenue,
    SUM(exp)  AS expense,
    SUM(rev)-SUM(exp) AS pl
  FROM (
    SELECT YEAR(sale_date) yr, amount rev, 0 exp FROM sales_egg
    UNION ALL SELECT YEAR(sale_date), amount, 0 FROM sales_feed
    UNION ALL SELECT YEAR(sale_date), amount, 0 FROM sales_culling
    UNION ALL SELECT YEAR(sale_date), amount, 0 FROM sales_manure
    UNION ALL SELECT YEAR(sale_date), amount, 0 FROM sales_raw_material
    UNION ALL SELECT YEAR(purchase_date), 0, amount FROM exp_chick
    UNION ALL SELECT YEAR(purchase_date), 0, amount FROM exp_feed_ingredient
    UNION ALL SELECT YEAR(purchase_date), 0, amount FROM exp_feeds
    UNION ALL SELECT YEAR(purchase_date), 0, amount FROM exp_medicine
    UNION ALL SELECT YEAR(payment_date),  0, amount FROM exp_salary
    UNION ALL SELECT YEAR(work_start),    0, amount FROM exp_labour WHERE work_start IS NOT NULL
    UNION ALL SELECT YEAR(payment_date),  0, amount FROM exp_rent
    UNION ALL SELECT YEAR(expense_date),  0, amount FROM exp_misc
    UNION ALL SELECT YEAR(purchase_date), 0, amount FROM exp_asset
  ) t GROUP BY yr ORDER BY yr
");

// Monthly breakdown for selected year
$monthly = q("
  SELECT m, SUM(rev) rev, SUM(exp) exp, SUM(rev)-SUM(exp) pl FROM (
    SELECT DATE_FORMAT(sale_date,'%b') m, MONTH(sale_date) mo, amount rev, 0 exp FROM sales_egg      WHERE YEAR(sale_date)=?
    UNION ALL SELECT DATE_FORMAT(sale_date,'%b'), MONTH(sale_date), amount, 0 FROM sales_feed     WHERE YEAR(sale_date)=?
    UNION ALL SELECT DATE_FORMAT(sale_date,'%b'), MONTH(sale_date), amount, 0 FROM sales_culling  WHERE YEAR(sale_date)=?
    UNION ALL SELECT DATE_FORMAT(sale_date,'%b'), MONTH(sale_date), amount, 0 FROM sales_manure   WHERE YEAR(sale_date)=?
    UNION ALL SELECT DATE_FORMAT(sale_date,'%b'), MONTH(sale_date), amount, 0 FROM sales_raw_material WHERE YEAR(sale_date)=?
    UNION ALL SELECT DATE_FORMAT(purchase_date,'%b'), MONTH(purchase_date), 0, amount FROM exp_chick          WHERE YEAR(purchase_date)=?
    UNION ALL SELECT DATE_FORMAT(purchase_date,'%b'), MONTH(purchase_date), 0, amount FROM exp_feed_ingredient WHERE YEAR(purchase_date)=?
    UNION ALL SELECT DATE_FORMAT(purchase_date,'%b'), MONTH(purchase_date), 0, amount FROM exp_feeds           WHERE YEAR(purchase_date)=?
    UNION ALL SELECT DATE_FORMAT(purchase_date,'%b'), MONTH(purchase_date), 0, amount FROM exp_medicine        WHERE YEAR(purchase_date)=?
    UNION ALL SELECT DATE_FORMAT(payment_date,'%b'),  MONTH(payment_date),  0, amount FROM exp_salary          WHERE YEAR(payment_date)=?
    UNION ALL SELECT DATE_FORMAT(payment_date,'%b'),  MONTH(payment_date),  0, amount FROM exp_rent            WHERE YEAR(payment_date)=?
    UNION ALL SELECT DATE_FORMAT(expense_date,'%b'),  MONTH(expense_date),  0, amount FROM exp_misc            WHERE YEAR(expense_date)=?
    UNION ALL SELECT DATE_FORMAT(work_start,'%b'),    MONTH(work_start),    0, amount FROM exp_labour          WHERE YEAR(work_start)=?
    UNION ALL SELECT DATE_FORMAT(purchase_date,'%b'), MONTH(purchase_date), 0, amount FROM exp_asset           WHERE YEAR(purchase_date)=?
  ) t GROUP BY m, mo ORDER BY mo",
  array_fill(0,14,$year));

// Interest paid by year
$intByYear = q("SELECT YEAR(txn_date) yr, SUM(interest_amount) i FROM loan_transaction GROUP BY yr ORDER BY yr");
$intMap = array_column($intByYear,'i','yr');
?>

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card p-3">
      <div class="section-title">Yearly P&L Summary (All Years)</div>
      <table class="table table-bordered table-hover table-sm align-middle datatable">
        <thead>
          <tr>
            <th>Year</th>
            <th class="text-end">Revenue</th>
            <th class="text-end">Expense</th>
            <th class="text-end">Gross P&L</th>
            <th class="text-end">Interest Paid</th>
            <th class="text-end">Net P&L</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $cumRev = $cumExp = 0;
        foreach($yearlyData as $row):
          $rev = (float)$row['revenue'];
          $exp = (float)$row['expense'];
          $pl  = $rev - $exp;
          $int = (float)($intMap[$row['yr']] ?? 0);
          $net = $pl - $int;
          $cumRev += $rev; $cumExp += $exp;
        ?>
          <tr>
            <td class="fw-semibold"><?= $row['yr'] ?></td>
            <td class="text-end text-success"><?= money($rev) ?></td>
            <td class="text-end text-danger"><?= money($exp) ?></td>
            <td class="text-end fw-semibold <?= $pl>=0?'text-success':'text-danger' ?>"><?= money($pl) ?></td>
            <td class="text-end text-warning"><?= money($int) ?></td>
            <td class="text-end fw-bold <?= $net>=0?'text-success':'text-danger' ?>"><?= money($net) ?></td>
            <td><span class="badge bg-<?= $net>=0?'success':'danger' ?>"><?= $net>=0?'Profit':'Loss' ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="table-dark fw-bold">
            <td>TOTAL</td>
            <td class="text-end"><?= money($cumRev) ?></td>
            <td class="text-end"><?= money($cumExp) ?></td>
            <td class="text-end"><?= money($cumRev-$cumExp) ?></td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card p-3">
      <div class="section-title">Monthly P&L — <?= $year ?></div>
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
          <thead>
            <tr>
              <th>Month</th>
              <th class="text-end">Revenue</th>
              <th class="text-end">Expense</th>
              <th class="text-end">Gross P&L</th>
              <th class="text-end">Gross Margin %</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($monthly as $row):
            $rev = (float)$row['rev'];
            $exp = (float)$row['exp'];
            $pl  = $rev - $exp;
            $mgn = $rev > 0 ? ($pl / $rev * 100) : 0;
          ?>
            <tr>
              <td class="fw-semibold"><?= $row['m'] ?></td>
              <td class="text-end"><?= money($rev) ?></td>
              <td class="text-end"><?= money($exp) ?></td>
              <td class="text-end fw-semibold <?= $pl>=0?'text-success':'text-danger' ?>"><?= money($pl) ?></td>
              <td class="text-end <?= $mgn>=0?'text-success':'text-danger' ?>"><?= num($mgn,1) ?>%</td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Revenue Category breakdown -->
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="section-title">Revenue by Category — <?= $year ?></div>
      <table class="table table-sm table-hover">
        <thead><tr><th>Category</th><th class="text-end">Amount</th><th class="text-end">%</th></tr></thead>
        <tbody>
        <?php
        $revCats = [
          'Egg Sales'      => (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_egg   WHERE YEAR(sale_date)=?",[$year]),
          'Feed Sales'     => (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_feed  WHERE YEAR(sale_date)=?",[$year]),
          'Culling'        => (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_culling WHERE YEAR(sale_date)=?",[$year]),
          'Manure'         => (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_manure  WHERE YEAR(sale_date)=?",[$year]),
          'Raw Material'   => (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_raw_material WHERE YEAR(sale_date)=?",[$year]),
        ];
        $rtot = array_sum($revCats);
        foreach($revCats as $cat=>$amt):
        ?>
          <tr>
            <td><?= $cat ?></td>
            <td class="text-end"><?= money($amt) ?></td>
            <td class="text-end"><?= $rtot>0 ? num($amt/$rtot*100,1).'%' : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end"><?= money($rtot) ?></td><td class="text-end">100%</td></tr></tfoot>
      </table>
    </div>
  </div>

  <!-- Expense Category breakdown -->
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="section-title">Expense by Category — <?= $year ?></div>
      <table class="table table-sm table-hover">
        <thead><tr><th>Category</th><th class="text-end">Amount</th><th class="text-end">%</th></tr></thead>
        <tbody>
        <?php
        $expCats = [
          'Chick Purchase'   => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_chick          WHERE YEAR(purchase_date)=?",[$year]),
          'Feed Ingredients' => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feed_ingredient WHERE YEAR(purchase_date)=?",[$year]),
          'Ready Feeds'      => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feeds           WHERE YEAR(purchase_date)=?",[$year]),
          'Medicine'         => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_medicine        WHERE YEAR(purchase_date)=?",[$year]),
          'Salary'           => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_salary          WHERE YEAR(payment_date)=?", [$year]),
          'Labour'           => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_labour          WHERE YEAR(work_start)=?",   [$year]),
          'Rent'             => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_rent            WHERE YEAR(payment_date)=?", [$year]),
          'Misc'             => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_misc            WHERE YEAR(expense_date)=?", [$year]),
          'Assets'           => (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_asset           WHERE YEAR(purchase_date)=?",[$year]),
        ];
        $etot = array_sum($expCats);
        foreach($expCats as $cat=>$amt):
        ?>
          <tr>
            <td><?= $cat ?></td>
            <td class="text-end text-danger"><?= money($amt) ?></td>
            <td class="text-end"><?= $etot>0 ? num($amt/$etot*100,1).'%' : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end text-danger"><?= money($etot) ?></td><td class="text-end">100%</td></tr></tfoot>
      </table>
    </div>
  </div>
</div>
