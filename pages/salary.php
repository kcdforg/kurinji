<?php
$tab = $_GET['tab'] ?? 'salary';
?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $tab==='salary'?'active':'' ?>" href="?page=salary&year=<?= $year ?>&tab=salary">Salary</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='labour'?'active':'' ?>" href="?page=salary&year=<?= $year ?>&tab=labour">Labour</a></li>
</ul>

<?php if($tab === 'salary'): ?>
<?php
$yearFilter = $year == 0 ? '' : 'AND YEAR(payment_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_salary WHERE 1 $yearFilter", $yearParam);
$byEmp = q("
  SELECT employee_name,
         COUNT(*) months,
         SUM(amount) total,
         AVG(amount) avg_monthly,
         MIN(payment_date) first_pay,
         MAX(payment_date) last_pay
  FROM exp_salary WHERE 1 $yearFilter
  GROUP BY employee_name ORDER BY total DESC", $yearParam);

$monthly = q("
  SELECT DATE_FORMAT(payment_date,'%Y-%m') m, employee_name, SUM(amount) amt
  FROM exp_salary WHERE 1 $yearFilter
  GROUP BY m, employee_name ORDER BY m, amt DESC", $yearParam);

// All employees transactions
$allTxn = q("SELECT id, payment_date,employee_name,qty,rate,amount FROM exp_salary WHERE 1 $yearFilter ORDER BY payment_date DESC", $yearParam);
?>
<div class="row g-3 mb-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="d-flex justify-content-between">
        <div class="section-title">By Employee <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
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
      <div class="section-title">Monthly Salary Chart <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
      <canvas id="chartSal" height="140"></canvas>
    </div>
  </div>
</div>

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="section-title mb-0">All Salary Transactions <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
    <button class="btn btn-sm btn-outline-success" onclick="openCrudAdd('exp_salary')">
      <i class="bi bi-plus-circle me-1"></i>Add Record
    </button>
  </div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Employee</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-center">Actions</th></tr></thead>
    <tbody>
    <?php foreach($allTxn as $r): ?>
      <tr><td><?= $r['payment_date'] ?></td><td><?= htmlspecialchars($r['employee_name']??'') ?></td>
          <td class="text-end"><?= num($r['qty']) ?></td><td class="text-end"><?= num($r['rate'],2) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('exp_salary',<?= $r['id'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('exp_salary',<?= $r['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
// Build chart data - monthly totals
$salMonthly = q("SELECT DATE_FORMAT(payment_date,'%Y-%m') m, SUM(amount) amt FROM exp_salary WHERE 1 $yearFilter GROUP BY m ORDER BY m", $yearParam);
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

<!-- Action Buttons -->
<div class="mt-4">
  <?php if($tab === 'salary'): ?>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#salaryModal" onclick="clearSalaryForm()">
      <i class="bi bi-plus-lg"></i> Add Salary Record
    </button>
  <?php endif; ?>
</div>

<!-- Salary Modal -->
<div class="modal fade" id="salaryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add/Edit Salary Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="salaryForm">
          <input type="hidden" id="salaryId">
          <div class="mb-3">
            <label class="form-label">Payment Date</label>
            <input type="date" class="form-control" id="salaryDate" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Employee Name</label>
            <input type="text" class="form-control" id="salaryEmployee" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantity (Days)</label>
            <input type="number" step="0.01" class="form-control" id="salaryQty" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Rate</label>
            <input type="number" step="0.01" class="form-control" id="salaryRate" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" class="form-control" id="salaryAmount" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="saveSalary()">Save</button>
      </div>
    </div>
  </div>
</div>

<script>
function clearSalaryForm() {
  document.getElementById('salaryForm').reset();
  document.getElementById('salaryId').value = '';
}

function editSalary(row) {
  document.getElementById('salaryId').value = row.id;
  document.getElementById('salaryDate').value = row.payment_date;
  document.getElementById('salaryEmployee').value = row.employee_name;
  document.getElementById('salaryQty').value = row.qty;
  document.getElementById('salaryRate').value = row.rate;
  document.getElementById('salaryAmount').value = row.amount;
  new bootstrap.Modal(document.getElementById('salaryModal')).show();
}

function deleteSalary(id) {
  if (!confirm('Are you sure you want to delete this record?')) return;
  
  const formData = new FormData();
  formData.append('action', 'delete_exp_salary');
  formData.append('id', id);

  fetch('crud_handler.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    });
}

function saveSalary() {
  const id = document.getElementById('salaryId').value;
  const formData = new FormData();
  formData.append('action', id ? 'edit_exp_salary' : 'add_exp_salary');
  if (id) formData.append('id', id);
  formData.append('date', document.getElementById('salaryDate').value);
  formData.append('employee_name', document.getElementById('salaryEmployee').value);
  formData.append('qty', document.getElementById('salaryQty').value);
  formData.append('rate', document.getElementById('salaryRate').value);
  formData.append('amount', document.getElementById('salaryAmount').value);

  fetch('crud_handler.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    });
}
</script>

