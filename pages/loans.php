<?php
$tab = $_GET['tab'] ?? 'overview';

// Latest balance per lender
$lenders = q("
  SELECT l.id, l.lender_name, l.lender_type, l.is_closed,
         t.balance     AS current_balance,
         t.interest_pct,
         SUM_T.total_availed,
         SUM_T.total_interest,
         SUM_T.total_paid
  FROM loan_lender l
  LEFT JOIN (
    SELECT lender_id,
           SUM(COALESCE(loan_availed,0)) total_availed,
           SUM(COALESCE(interest_amount,0)) total_interest,
           SUM(COALESCE(amount_paid,0)) total_paid
    FROM loan_transaction GROUP BY lender_id
  ) SUM_T ON l.id = SUM_T.lender_id
  LEFT JOIN loan_transaction t
    ON t.lender_id = l.id
    AND t.txn_date = (SELECT MAX(txn_date) FROM loan_transaction WHERE lender_id=l.id)
  ORDER BY l.is_closed, l.lender_type, current_balance DESC
");

$total_outstanding = array_sum(array_column($lenders, 'current_balance'));
$total_availed_all = array_sum(array_column($lenders, 'total_availed'));
$total_int_all     = array_sum(array_column($lenders, 'total_interest'));
?>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $tab==='overview'?'active':'' ?>" href="?page=loans&year=<?= $year ?>&tab=overview">Overview</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='transactions'?'active':'' ?>" href="?page=loans&year=<?= $year ?>&tab=transactions">Transactions</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='monthly'?'active':'' ?>" href="?page=loans&year=<?= $year ?>&tab=monthly">Monthly Summary</a></li>
</ul>

<?php if($tab === 'overview'): ?>

<!-- Summary KPIs -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card kpi-card p-3">
      <div class="kpi-label">Total Outstanding</div>
      <div class="kpi-val text-danger"><?= money($total_outstanding) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card kpi-card p-3">
      <div class="kpi-label">Total Availed (All Time)</div>
      <div class="kpi-val text-primary"><?= money($total_availed_all) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card kpi-card p-3">
      <div class="kpi-label">Total Interest Paid (All Time)</div>
      <div class="kpi-val text-warning"><?= money($total_int_all) ?></div>
    </div>
  </div>
</div>

<div class="card p-3">
  <div class="section-title">All Lenders — Current Status</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover datatable align-middle">
      <thead>
        <tr>
          <th>Lender</th><th>Type</th><th>Status</th>
          <th class="text-end">Total Availed</th>
          <th class="text-end">Total Interest</th>
          <th class="text-end">Total Paid</th>
          <th class="text-end">Current Balance</th>
          <th class="text-end">Interest %</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($lenders as $l):
        $typeColors = ['individual'=>'primary','finance_company'=>'info','chit'=>'warning',
                       'overdraft'=>'danger','emi'=>'secondary','partner'=>'success'];
        $col = $typeColors[$l['lender_type']] ?? 'secondary';
      ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($l['lender_name']) ?></td>
          <td><span class="badge bg-<?= $col ?> badge-type"><?= $l['lender_type'] ?></span></td>
          <td><?= $l['is_closed'] ? '<span class="badge bg-success">Closed</span>' : '<span class="badge bg-danger">Active</span>' ?></td>
          <td class="text-end"><?= money($l['total_availed']) ?></td>
          <td class="text-end"><?= money($l['total_interest']) ?></td>
          <td class="text-end"><?= money($l['total_paid']) ?></td>
          <td class="text-end fw-bold <?= ($l['current_balance']??0) > 0 ? 'text-danger' : 'text-success' ?>">
            <?= money($l['current_balance']) ?>
          </td>
          <td class="text-end"><?= $l['interest_pct'] ? num($l['interest_pct'],2).'%' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="table-dark fw-bold">
          <td colspan="3">TOTAL</td>
          <td class="text-end"><?= money($total_availed_all) ?></td>
          <td class="text-end"><?= money($total_int_all) ?></td>
          <td class="text-end"><?= money(array_sum(array_column($lenders,'total_paid'))) ?></td>
          <td class="text-end text-danger"><?= money($total_outstanding) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php elseif($tab === 'transactions'): ?>
<?php
$selectedLender = (int)($_GET['lender_id'] ?? 0);
?>
<div class="row mb-3">
  <div class="col-md-4">
    <select class="form-select" onchange="location.href='?page=loans&year=<?= $year ?>&tab=transactions&lender_id='+this.value">
      <option value="0">— All Lenders —</option>
      <?php foreach($lenders as $l): ?>
      <option value="<?= $l['id'] ?>" <?= $l['id']==$selectedLender ? 'selected':'' ?>><?= htmlspecialchars($l['lender_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<?php
$where = $selectedLender ? ("WHERE t.lender_id=$selectedLender " . ($year == 0 ? '' : "AND YEAR(t.txn_date)=$year")) : ($year == 0 ? "WHERE 1" : "WHERE YEAR(t.txn_date)=$year");
$txns  = q("SELECT t.txn_date, l.lender_name, l.lender_type, t.loan_availed, t.balance, t.interest_pct, t.interest_amount, t.amount_paid
            FROM loan_transaction t JOIN loan_lender l ON l.id=t.lender_id $where ORDER BY t.txn_date DESC");
?>
<div class="card p-3">
  <div class="section-title">Loan Transactions <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
  <div class="table-responsive">
    <table class="table table-sm table-hover datatable">
      <thead>
        <tr><th>Date</th><th>Lender</th><th>Type</th>
            <th class="text-end">Availed</th><th class="text-end">Balance</th>
            <th class="text-end">Int %</th><th class="text-end">Interest</th><th class="text-end">Paid</th></tr>
      </thead>
      <tbody>
      <?php foreach($txns as $t): ?>
        <tr>
          <td><?= $t['txn_date'] ?></td>
          <td><?= htmlspecialchars($t['lender_name']) ?></td>
          <td><span class="badge bg-secondary badge-type"><?= $t['lender_type'] ?></span></td>
          <td class="text-end <?= ($t['loan_availed']??0)>0?'text-primary':'' ?>"><?= money($t['loan_availed']) ?></td>
          <td class="text-end"><?= money($t['balance']) ?></td>
          <td class="text-end"><?= $t['interest_pct'] ? num($t['interest_pct'],2).'%' : '—' ?></td>
          <td class="text-end text-warning"><?= money($t['interest_amount']) ?></td>
          <td class="text-end text-success"><?= money($t['amount_paid']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif($tab === 'monthly'): ?>
<?php
$yearFilter = $year == 0 ? '' : 'AND YEAR(txn_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$monthly = q("
  SELECT DATE_FORMAT(txn_date,'%Y-%m') m,
         SUM(loan_availed)    availed,
         SUM(interest_amount) interest,
         SUM(amount_paid)     paid
  FROM loan_transaction WHERE 1 $yearFilter
  GROUP BY m ORDER BY m", $yearParam);
?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="section-title">Monthly Loan Summary <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <table class="table table-sm table-hover">
        <thead><tr><th>Month</th><th class="text-end">New Loans</th><th class="text-end">Interest</th><th class="text-end">Repayments</th><th class="text-end">Net</th></tr></thead>
        <tbody>
        <?php foreach($monthly as $r):
          $net = (float)$r['availed'] - (float)$r['paid'];
        ?>
          <tr>
            <td><?= $r['m'] ?></td>
            <td class="text-end text-primary"><?= money($r['availed']) ?></td>
            <td class="text-end text-warning"><?= money($r['interest']) ?></td>
            <td class="text-end text-success"><?= money($r['paid']) ?></td>
            <td class="text-end <?= $net>0?'text-danger':'text-success' ?>"><?= money($net) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="section-title">Interest by Lender Type <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <table class="table table-sm">
        <thead><tr><th>Type</th><th class="text-end">Interest Paid</th></tr></thead>
        <tbody>
        <?php
        $bytype = q("SELECT l.lender_type, SUM(t.interest_amount) int FROM loan_transaction t JOIN loan_lender l ON l.id=t.lender_id WHERE 1 $yearFilter GROUP BY l.lender_type ORDER BY int DESC", $yearParam);
        foreach($bytype as $r): ?>
          <tr><td><?= $r['lender_type'] ?></td><td class="text-end text-warning"><?= money($r['int']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
