<?php
// Feed ingredient analysis
$overview = q("
  SELECT category,
         SUM(qty_kg) total_kg,
         SUM(amount) total_cost,
         SUM(amount)/NULLIF(SUM(qty_kg),0) avg_rate,
         COUNT(*) txns
  FROM exp_feed_ingredient
  GROUP BY category ORDER BY total_cost DESC
");

$byYear = q("
  SELECT YEAR(purchase_date) yr, category,
         SUM(qty_kg) total_kg,
         SUM(amount) total_cost,
         SUM(amount)/NULLIF(SUM(qty_kg),0) avg_rate
  FROM exp_feed_ingredient
  GROUP BY yr, category ORDER BY yr, total_cost DESC
");

$byMonth = q("
  SELECT DATE_FORMAT(purchase_date,'%Y-%m') m,
         category,
         SUM(qty_kg) qty,
         SUM(amount)/NULLIF(SUM(qty_kg),0) avg_rate,
         SUM(amount) cost
  FROM exp_feed_ingredient
  WHERE 1 " . ($year == 0 ? '' : 'AND YEAR(purchase_date)=?') . "
  GROUP BY m, category ORDER BY m, cost DESC
", $year == 0 ? [] : [$year]);

// Monthly total feed cost
$monthTotal = q("
  SELECT m, SUM(cost) total FROM (
    SELECT DATE_FORMAT(purchase_date,'%Y-%m') m, SUM(amount) cost FROM exp_feed_ingredient WHERE 1 " . ($year == 0 ? '' : 'AND YEAR(purchase_date)=?') . " GROUP BY m
    UNION ALL
    SELECT DATE_FORMAT(purchase_date,'%Y-%m'), SUM(amount) FROM exp_feeds WHERE 1 " . ($year == 0 ? '' : 'AND YEAR(purchase_date)=?') . " GROUP BY DATE_FORMAT(purchase_date,'%Y-%m')
  ) x GROUP BY m ORDER BY m
", $year == 0 ? [] : array_fill(0, 2, $year));
?>

<!-- Overall feed ingredient summary -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card p-3">
      <div class="section-title">Feed Ingredient Overview (All Time)</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover datatable">
          <thead>
            <tr>
              <th>Category</th>
              <th class="text-end">Total Qty (Kg)</th>
              <th class="text-end">Total Cost</th>
              <th class="text-end">Avg Rate / Kg</th>
              <th class="text-end">Transactions</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $gtKg=0; $gtCost=0;
          foreach($overview as $r):
            $gtKg+=$r['total_kg']; $gtCost+=$r['total_cost'];
          ?>
            <tr>
              <td><span class="badge bg-secondary"><?= $r['category'] ?></span></td>
              <td class="text-end"><?= num($r['total_kg'],0) ?></td>
              <td class="text-end"><?= money($r['total_cost']) ?></td>
              <td class="text-end">₹<?= num($r['avg_rate'],2) ?>/Kg</td>
              <td class="text-end"><?= $r['txns'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="fw-bold table-light">
              <td>Grand Total</td>
              <td class="text-end"><?= num($gtKg,0) ?> Kg</td>
              <td class="text-end"><?= money($gtCost) ?></td>
              <td></td><td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Year-wise rate trend -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card p-3">
      <div class="section-title">Year-wise Avg Rate per Kg by Ingredient</div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered datatable">
          <thead>
            <tr>
              <th>Year</th><th>Category</th>
              <th class="text-end">Qty(Kg)</th>
              <th class="text-end">Total Cost</th>
              <th class="text-end">Avg Rate/Kg</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($byYear as $r): ?>
            <tr>
              <td><?= $r['yr'] ?></td>
              <td><?= $r['category'] ?></td>
              <td class="text-end"><?= num($r['total_kg'],0) ?></td>
              <td class="text-end"><?= money($r['total_cost']) ?></td>
              <td class="text-end fw-semibold">₹<?= num($r['avg_rate'],2) ?>/Kg</td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Monthly feed cost for selected year -->
<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="section-title">Monthly Feed Cost Detail <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead><tr><th>Month</th><th>Category</th><th class="text-end">Qty(Kg)</th><th class="text-end">Avg Rate</th><th class="text-end">Cost</th></tr></thead>
          <tbody>
          <?php foreach($byMonth as $r): ?>
            <tr>
              <td><?= $r['m'] ?></td>
              <td><span class="badge bg-secondary badge-type"><?= $r['category'] ?></span></td>
              <td class="text-end"><?= num($r['qty'],0) ?></td>
              <td class="text-end">₹<?= num($r['avg_rate'],2) ?></td>
              <td class="text-end"><?= money($r['cost']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="section-title">Monthly Total Feed Cost <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <canvas id="chartFeed" height="200"></canvas>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('chartFeed'), {
  type:'line',
  data:{
    labels: <?= json_encode(array_column($monthTotal,'m')) ?>,
    datasets:[{
      label:'Total Feed Cost',
      data: <?= json_encode(array_map(fn($r)=>(float)$r['total'], $monthTotal)) ?>,
      borderColor:'#1a6e3c', backgroundColor:'rgba(26,110,60,.1)', fill:true, tension:.3
    }]
  },
  options:{scales:{y:{ticks:{callback:v=>'₹'+v.toLocaleString('en-IN')}}}}
});
</script>
