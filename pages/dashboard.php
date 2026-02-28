<?php
// ── KPI queries ──────────────────────────────────────────
$dashboardCharts = true;  // Flag for footer to initialize charts
$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$egg_rev   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_egg WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$feed_rev  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_feed WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$cull_rev  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_culling WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$man_rev   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_manure WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$rm_rev    = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_raw_material WHERE 1 $yearFilter", $yearParam);
$total_rev = $egg_rev + $feed_rev + $cull_rev + $man_rev + $rm_rev;

$yearFilter = $year == 0 ? '' : 'AND YEAR(purchase_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$chick_exp = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_chick WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(purchase_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$ingr_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feed_ingredient WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(purchase_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$feed_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feeds WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(purchase_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$med_exp   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_medicine WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(payment_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$sal_exp   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_salary WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(work_start)=?';
$yearParam = $year == 0 ? [] : [$year];
$lab_exp   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_labour WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(payment_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$rent_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_rent WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(expense_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$misc_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_misc WHERE 1 $yearFilter", $yearParam);

$yearFilter = $year == 0 ? '' : 'AND YEAR(purchase_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$asset_exp = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_asset WHERE 1 $yearFilter", $yearParam);
$total_exp = $chick_exp + $ingr_exp + $feed_exp + $med_exp + $sal_exp + $lab_exp + $rent_exp + $misc_exp + $asset_exp;

$net_pl    = $total_rev - $total_exp;

$loan_bal  = (float)qval("
    SELECT COALESCE(SUM(t.balance),0) FROM loan_transaction t
    INNER JOIN (SELECT lender_id, MAX(txn_date) md FROM loan_transaction GROUP BY lender_id) lx
    ON t.lender_id=lx.lender_id AND t.txn_date=lx.md", []);

$yearFilter = $year == 0 ? '' : 'AND YEAR(txn_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$int_paid  = (float)qval("SELECT COALESCE(SUM(interest_amount),0) FROM loan_transaction WHERE 1 $yearFilter", $yearParam);

// Monthly trend for charts
$yearFilters = [];
if ($year == 0) {
  $yearFilters = array_fill(0, 10, '');
} else {
  $yearFilters = [
    ' AND YEAR(sale_date)=? ',
    ' AND YEAR(sale_date)=? ',
    ' AND YEAR(sale_date)=? ',
    ' AND YEAR(sale_date)=? ',
    ' AND YEAR(purchase_date)=? ',
    ' AND YEAR(purchase_date)=? ',
    ' AND YEAR(purchase_date)=? ',
    ' AND YEAR(payment_date)=? ',
    ' AND YEAR(payment_date)=? ',
    ' AND YEAR(expense_date)=? ',
  ];
}

$whereConditions = [
  " WHERE 1 $yearFilters[0] ",  // sales_egg
  " WHERE 1 $yearFilters[1] ",  // sales_feed
  " WHERE 1 $yearFilters[2] ",  // sales_culling
  " WHERE 1 $yearFilters[3] ",  // sales_manure
  " WHERE 1 $yearFilters[4] ",  // exp_chick
  " WHERE 1 $yearFilters[5] ",  // exp_feed_ingredient
  " WHERE 1 $yearFilters[6] ",  // exp_feeds
  " WHERE 1 $yearFilters[7] ",  // exp_salary
  " WHERE 1 $yearFilters[8] ",  // exp_rent
  " WHERE 1 $yearFilters[9] ",  // exp_misc
];

$monthlyParams = $year == 0 ? [] : array_fill(0, 10, $year);

$monthly = q("
    SELECT m, SUM(rev) rev, SUM(exp) exp FROM (
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_egg $whereConditions[0] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_feed $whereConditions[1] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_culling $whereConditions[2] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_manure $whereConditions[3] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(purchase_date,'%Y-%m') m, 0 rev, SUM(amount) exp FROM exp_chick $whereConditions[4] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(purchase_date,'%Y-%m') m, 0 rev, SUM(amount) exp FROM exp_feed_ingredient $whereConditions[5] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(purchase_date,'%Y-%m') m, 0 rev, SUM(amount) exp FROM exp_feeds $whereConditions[6] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(payment_date,'%Y-%m') m,  0 rev, SUM(amount) exp FROM exp_salary $whereConditions[7] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(payment_date,'%Y-%m') m,  0 rev, SUM(amount) exp FROM exp_rent $whereConditions[8] GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(expense_date,'%Y-%m') m,  0 rev, SUM(amount) exp FROM exp_misc $whereConditions[9] GROUP BY m
    ) x GROUP BY m ORDER BY m",
    $monthlyParams);

$mLabels = json_encode(array_column($monthly,'m'));
$mRev    = json_encode(array_map(fn($r)=>(float)$r['rev'], $monthly));
$mExp    = json_encode(array_map(fn($r)=>(float)$r['exp'], $monthly));

// Revenue donut
$revParts = ['Egg'=>$egg_rev,'Feed Sales'=>$feed_rev,'Culling'=>$cull_rev,'Manure'=>$man_rev,'Raw Material'=>$rm_rev];
$expParts = ['Chick'=>$chick_exp,'Feed Ingr.'=>$ingr_exp,'Ready Feed'=>$feed_exp,'Medicine'=>$med_exp,
             'Salary'=>$sal_exp,'Labour'=>$lab_exp,'Rent'=>$rent_exp,'Misc'=>$misc_exp,'Assets'=>$asset_exp];
?>

<script>
window.dashboardCharts = true;
window.mLabels = <?= $mLabels ?>;
window.mRev = <?= $mRev ?>;
window.mExp = <?= $mExp ?>;
window.revPartLabels = <?= json_encode(array_keys($revParts)) ?>;
window.revPartData = <?= json_encode(array_values($revParts)) ?>;
window.expPartLabels = <?= json_encode(array_keys($expParts)) ?>;
window.expPartData = <?= json_encode(array_values($expParts)) ?>;
</script>

<!-- KPI Row -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['Total Revenue',   $total_rev, 'success', 'bi-graph-up-arrow'],
    ['Total Expense',   $total_exp, 'danger',  'bi-graph-down-arrow'],
    ['Net P&L',         $net_pl,    $net_pl>=0?'success':'danger', 'bi-calculator'],
    ['Loan Outstanding',$loan_bal,  'warning', 'bi-bank'],
    ['Interest Paid',   $int_paid,  'info',    'bi-percent'],
  ];
  foreach($kpis as [$lbl,$val,$col,$ico]): ?>
  <div class="col-6 col-lg">
    <div class="card kpi-card h-100 p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="kpi-label"><?= $lbl ?></div>
          <div class="kpi-val text-<?= $col ?>"><?= money($val) ?></div>
        </div>
        <i class="bi <?= $ico ?> fs-2 text-<?= $col ?> opacity-25"></i>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="card p-3 h-100">
      <div class="section-title">Monthly Revenue vs Expense <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <canvas id="chartMonthly" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="row g-3 h-100">
      <div class="col-12">
        <div class="card p-3 h-100">
          <div class="section-title">Revenue Breakdown</div>
          <canvas id="chartRev" height="90"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Expense Breakdown -->
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="section-title">Expense Breakdown <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <canvas id="chartExp" height="130"></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="section-title">Top Egg Buyers <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <table class="table table-sm table-hover">
        <thead><tr><th>Buyer</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        <?php
        $yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
        $yearParam = $year == 0 ? [] : [$year];
        $buyers = q("SELECT particulars, SUM(amount) amt FROM sales_egg WHERE 1 $yearFilter GROUP BY particulars ORDER BY amt DESC LIMIT 10", $yearParam);
        foreach($buyers as $b): ?>
          <tr><td><?= htmlspecialchars($b['particulars'] ?? '—') ?></td><td class="text-end"><?= money($b['amt']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


