<?php
$tab = $_GET['tab'] ?? 'salary';
?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $tab==='salary'?'active':'' ?>" href="?page=salary&year=<?= $year ?>&tab=salary">Salary</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='labour'?'active':'' ?>" href="?page=salary&year=<?= $year ?>&tab=labour">Labour</a></li>
</ul>

<?php if($tab === 'salary'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_salary WHERE YEAR(payment_date)=?",[$year]);
$byEmp = q("
  SELECT employee_name,
         COUNT(*) months,
         SUM(amount) total,
         AVG(amount) avg_monthly,
         MIN(payment_date) first_pay,
         MAX(payment_date) last_pay
  FROM exp_salary WHERE YEAR(payment_date)=?
  GROUP BY employee_name ORDER BY total DESC", [$year]);

$monthly = q("
  SELECT DATE_FORMAT(payment_date,'%Y-%m') m, employee_name, SUM(amount) amt
  FROM exp_salary WHERE YEAR(payment_date)=?
  GROUP BY m, employee_name ORDER BY m, amt DESC", [$year]);

// All employees transactions
$allTxn = q("SELECT payment_date,employee_name,qty,rate,amount FROM exp_salary WHERE YEAR(payment_date)=? ORDER BY payment_date DESC",[$year]);
?>
<div class="row g-3 mb-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="d-flex justify-content-between">
        <div class="section-title">By Employee — <?= $year ?></div>
        <span class="badge bg-danger fs-6"><?= money($total) ?></span>
      </div>
      <table class="table table-sm table-hover">
        <thead>
          <tr><th>Employee</th><th class="text-end">Months</th><th class="text-end">Avg/Month</th><th class="text-end">Total</th></tr>
        </thead>
        <tbody>
        <?php foreach($byEmp as $r): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($r['employee_name']??'—') ?></td>
            <td class="text-end"><?= $r['months'] ?></td>
            <td class="text-end"><?= money($r['avg_monthly']) ?></td>
            <td class="text-end"><?= money($r['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td colspan="3">Total</td><td class="text-end"><?= money($total) ?></td></tr></tfoot>
      </table>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="section-title">Monthly Salary Chart — <?= $year ?></div>
      <canvas id="chartSal" height="140"></canvas>
    </div>
  </div>
</div>

<div class="card p-3">
  <div class="section-title">All Salary Transactions — <?= $year ?></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Employee</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach($allTxn as $r): ?>
      <tr><td><?= $r['payment_date'] ?></td><td><?= htmlspecialchars($r['employee_name']??'') ?></td>
          <td class="text-end"><?= num($r['qty']) ?></td><td class="text-end"><?= num($r['rate'],2) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
// Build chart data - monthly totals
$salMonthly = q("SELECT DATE_FORMAT(payment_date,'%Y-%m') m, SUM(amount) amt FROM exp_salary WHERE YEAR(payment_date)=? GROUP BY m ORDER BY m",[$year]);
?>
<script>
new Chart(document.getElementById('chartSal'), {
  type:'bar',
  data:{
    labels: <?= json_encode(array_column($salMonthly,'m')) ?>,
    datasets:[{
      label:'Salary',
      data: <?= json_encode(array_map(fn($r)=>(float)$r['amt'],$salMonthly)) ?>,
      backgroundColor:'rgba(220,53,69,.65)', borderRadius:4
    }]
  },
  options:{scales:{y:{ticks:{callback:v=>'₹'+v.toLocaleString('en-IN')}}}}
});
</script>

<?php elseif($tab === 'labour'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_labour",[]);
$rows  = q("SELECT work_start,work_end,worker_name,days,wages_per_day,amount FROM exp_labour ORDER BY work_start DESC",[]);
$byWorker = q("SELECT worker_name, SUM(days) total_days, SUM(amount) total FROM exp_labour GROUP BY worker_name ORDER BY total DESC",[]);
?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="d-flex justify-content-between">
        <div class="section-title">By Worker (All Time)</div>
        <span class="badge bg-secondary fs-6"><?= money($total) ?></span>
      </div>
      <table class="table table-sm">
        <thead><tr><th>Worker</th><th class="text-end">Days</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($byWorker as $r): ?>
          <tr><td><?= htmlspecialchars($r['worker_name']??'') ?></td>
              <td class="text-end"><?= num($r['total_days'],0) ?></td>
              <td class="text-end"><?= money($r['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td colspan="2">Total</td><td class="text-end"><?= money($total) ?></td></tr></tfoot>
      </table>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="section-title">Labour Contracts</div>
      <table class="table table-sm table-hover datatable">
        <thead><tr><th>Start</th><th>End</th><th>Worker</th><th class="text-end">Days</th><th class="text-end">Wage/Day</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr><td><?= $r['work_start']??'—' ?></td><td><?= $r['work_end']??'—' ?></td>
              <td><?= htmlspecialchars($r['worker_name']??'') ?></td>
              <td class="text-end"><?= num($r['days'],0) ?></td>
              <td class="text-end">₹<?= num($r['wages_per_day'],2) ?></td>
              <td class="text-end"><?= money($r['amount']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
