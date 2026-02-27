<?php
// ── KPI queries ──────────────────────────────────────────
$egg_rev   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_egg      WHERE YEAR(sale_date)=?", [$year]);
$feed_rev  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_feed     WHERE YEAR(sale_date)=?", [$year]);
$cull_rev  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_culling  WHERE YEAR(sale_date)=?", [$year]);
$man_rev   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_manure   WHERE YEAR(sale_date)=?", [$year]);
$rm_rev    = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_raw_material WHERE YEAR(sale_date)=?", [$year]);
$total_rev = $egg_rev + $feed_rev + $cull_rev + $man_rev + $rm_rev;

$chick_exp = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_chick          WHERE YEAR(purchase_date)=?", [$year]);
$ingr_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feed_ingredient WHERE YEAR(purchase_date)=?", [$year]);
$feed_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feeds           WHERE YEAR(purchase_date)=?", [$year]);
$med_exp   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_medicine        WHERE YEAR(purchase_date)=?", [$year]);
$sal_exp   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_salary          WHERE YEAR(payment_date)=?",  [$year]);
$lab_exp   = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_labour          WHERE YEAR(work_start)=?",    [$year]);
$rent_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_rent            WHERE YEAR(payment_date)=?",  [$year]);
$misc_exp  = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_misc            WHERE YEAR(expense_date)=?",  [$year]);
$asset_exp = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_asset           WHERE YEAR(purchase_date)=?", [$year]);
$total_exp = $chick_exp + $ingr_exp + $feed_exp + $med_exp + $sal_exp + $lab_exp + $rent_exp + $misc_exp + $asset_exp;

$net_pl    = $total_rev - $total_exp;

$loan_bal  = (float)qval("
    SELECT COALESCE(SUM(t.balance),0) FROM loan_transaction t
    INNER JOIN (SELECT lender_id, MAX(txn_date) md FROM loan_transaction GROUP BY lender_id) lx
    ON t.lender_id=lx.lender_id AND t.txn_date=lx.md", []);

$int_paid  = (float)qval("SELECT COALESCE(SUM(interest_amount),0) FROM loan_transaction WHERE YEAR(txn_date)=?", [$year]);

// Monthly trend for charts
$monthly = q("
    SELECT m, SUM(rev) rev, SUM(exp) exp FROM (
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_egg      WHERE YEAR(sale_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_feed     WHERE YEAR(sale_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_culling  WHERE YEAR(sale_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(sale_date,'%Y-%m') m, SUM(amount) rev, 0 exp FROM sales_manure   WHERE YEAR(sale_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(purchase_date,'%Y-%m') m, 0 rev, SUM(amount) exp FROM exp_chick          WHERE YEAR(purchase_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(purchase_date,'%Y-%m') m, 0 rev, SUM(amount) exp FROM exp_feed_ingredient WHERE YEAR(purchase_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(purchase_date,'%Y-%m') m, 0 rev, SUM(amount) exp FROM exp_feeds           WHERE YEAR(purchase_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(payment_date,'%Y-%m') m,  0 rev, SUM(amount) exp FROM exp_salary          WHERE YEAR(payment_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(payment_date,'%Y-%m') m,  0 rev, SUM(amount) exp FROM exp_rent            WHERE YEAR(payment_date)=? GROUP BY m
      UNION ALL
      SELECT DATE_FORMAT(expense_date,'%Y-%m') m,  0 rev, SUM(amount) exp FROM exp_misc            WHERE YEAR(expense_date)=? GROUP BY m
    ) x GROUP BY m ORDER BY m",
    array_fill(0,10,$year));

$mLabels = json_encode(array_column($monthly,'m'));
$mRev    = json_encode(array_map(fn($r)=>(float)$r['rev'], $monthly));
$mExp    = json_encode(array_map(fn($r)=>(float)$r['exp'], $monthly));

// Revenue donut
$revParts = ['Egg'=>$egg_rev,'Feed Sales'=>$feed_rev,'Culling'=>$cull_rev,'Manure'=>$man_rev,'Raw Material'=>$rm_rev];
$expParts = ['Chick'=>$chick_exp,'Feed Ingr.'=>$ingr_exp,'Ready Feed'=>$feed_exp,'Medicine'=>$med_exp,
             'Salary'=>$sal_exp,'Labour'=>$lab_exp,'Rent'=>$rent_exp,'Misc'=>$misc_exp,'Assets'=>$asset_exp];
?>

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
      <div class="section-title">Monthly Revenue vs Expense — <?= $year ?></div>
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
      <div class="section-title">Expense Breakdown — <?= $year ?></div>
      <canvas id="chartExp" height="130"></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="section-title">Top Egg Buyers — <?= $year ?></div>
      <table class="table table-sm table-hover">
        <thead><tr><th>Buyer</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        <?php
        $buyers = q("SELECT particulars, SUM(amount) amt FROM sales_egg WHERE YEAR(sale_date)=? GROUP BY particulars ORDER BY amt DESC LIMIT 10", [$year]);
        foreach($buyers as $b): ?>
          <tr><td><?= htmlspecialchars($b['particulars'] ?? '—') ?></td><td class="text-end"><?= money($b['amt']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const mLabels = <?= $mLabels ?>;
const mRev    = <?= $mRev ?>;
const mExp    = <?= $mExp ?>;
new Chart(document.getElementById('chartMonthly'), {
  type: 'bar',
  data: {
    labels: mLabels,
    datasets: [
      { label:'Revenue', data:mRev, backgroundColor:'rgba(26,110,60,.7)', borderRadius:4 },
      { label:'Expense', data:mExp, backgroundColor:'rgba(220,53,69,.55)', borderRadius:4 },
    ]
  },
  options: { plugins:{legend:{position:'top'}}, scales:{ y:{ ticks:{ callback: v=>'₹'+v.toLocaleString('en-IN') } } } }
});

new Chart(document.getElementById('chartRev'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_keys($revParts)) ?>,
    datasets: [{ data: <?= json_encode(array_values($revParts)) ?>,
      backgroundColor:['#1a6e3c','#28a745','#17a2b8','#6c757d','#f5a623'] }]
  },
  options:{ plugins:{ legend:{position:'bottom'} } }
});

new Chart(document.getElementById('chartExp'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_keys($expParts)) ?>,
    datasets: [{ label:'Expense', data: <?= json_encode(array_values($expParts)) ?>,
      backgroundColor:'rgba(220,53,69,.65)', borderRadius:4 }]
  },
  options: { indexAxis:'y', plugins:{legend:{display:false}},
    scales:{ x:{ ticks:{ callback:v=>'₹'+v.toLocaleString('en-IN') } } } }
});
</script>
