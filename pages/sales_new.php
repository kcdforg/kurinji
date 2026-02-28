<?php $tab = $_GET['tab'] ?? 'egg'; ?>

<ul class="nav nav-tabs mb-3">
  <?php
  $tabs = ['egg'=>'Egg Sales','feed'=>'Feed Sales','culling'=>'Culling','manure'=>'Manure','raw_material'=>'Raw Material','investment'=>'Investment'];
  foreach($tabs as $k=>$lbl):
  ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$k?'active':'' ?>" href="?page=sales&year=<?= $year ?>&tab=<?= $k ?>">
      <?= $lbl ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<!-- Add Record Button for Current Tab -->
<div class="mb-3">
  <button class="btn btn-success btn-sm" onclick="openCrudAdd('sales_<?= $tab ?>')">
    <i class="bi bi-plus-lg"></i> Add Record
  </button>
</div>

<?php if($tab === 'egg'): ?>
<?php
$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$totalEgg = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_egg WHERE 1 $yearFilter", $yearParam);
$rows = q("SELECT id,sale_date,particulars,qty,rate,amount FROM sales_egg WHERE 1 $yearFilter ORDER BY sale_date DESC", $yearParam);
?>
<div class="card p-3 mb-3">
  <div class="d-flex justify-content-between"><div class="section-title">Egg Sales <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
  <span class="badge bg-success fs-6"><?= money($totalEgg) ?></span></div>
  <div class="table-responsive">
    <table class="table table-sm table-hover datatable">
      <thead><tr><th>Date</th><th>Particulars</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-center">Actions</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr><td><?= $r['sale_date'] ?></td><td><?= htmlspecialchars($r['particulars']??'') ?></td>
            <td class="text-end"><?= num($r['qty']) ?></td><td class="text-end"><?= num($r['rate'],4) ?></td>
            <td class="text-end"><?= money($r['amount']) ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('sales_egg',<?= $r['id'] ?>)">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('sales_egg',<?= $r['id'] ?>)">
                <i class="bi bi-trash"></i>
              </button>
            </td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Monthly buyer summary -->
<div class="card p-3">
  <div class="section-title">Monthly Summary by Buyer</div>
  <div class="table-responsive">
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Month</th><th>Buyer</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php
    $ms = q("SELECT DATE_FORMAT(sale_date,'%Y-%m') m, particulars, SUM(amount) amt FROM sales_egg WHERE 1 $yearFilter GROUP BY m,particulars ORDER BY m,amt DESC", $yearParam);
    foreach($ms as $r):
    ?><tr><td><?= $r['m'] ?></td><td><?= htmlspecialchars($r['particulars']??'') ?></td><td class="text-end"><?= money($r['amt']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif($tab === 'feed'): ?>
<?php
$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_feed WHERE 1 $yearFilter", $yearParam);
$rows = q("SELECT id,sale_date,particulars,qty,rate,amount FROM sales_feed WHERE 1 $yearFilter ORDER BY sale_date DESC", $yearParam);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Feed Sales <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
  <span class="badge bg-success fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Customer</th><th class="text-end">Qty(Kg)</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-center">Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['sale_date'] ?></td><td><?= htmlspecialchars($r['particulars']??'') ?></td>
          <td class="text-end"><?= num($r['qty'],0) ?></td><td class="text-end"><?= num($r['rate'],2) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('sales_feed',<?= $r['id'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('sales_feed',<?= $r['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'culling'): ?>
<?php
$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_culling WHERE 1 $yearFilter", $yearParam);
$rows = q("SELECT id,sale_date,particulars,qty_birds,rate,amount,manure_kg_estimate FROM sales_culling WHERE 1 $yearFilter ORDER BY sale_date DESC", $yearParam);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Culling Sales <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
  <span class="badge bg-success fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Particulars</th><th class="text-end">Birds</th><th class="text-end">Rate/Bird</th><th class="text-end">Amount</th><th class="text-end">Manure Est.(Kg)</th><th class="text-center">Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['sale_date'] ?></td><td><?= htmlspecialchars($r['particulars']??'') ?></td>
          <td class="text-end"><?= num($r['qty_birds'],0) ?></td><td class="text-end"><?= num($r['rate'],2) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td>
          <td class="text-end"><?= num($r['manure_kg_estimate'],0) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('sales_culling',<?= $r['id'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('sales_culling',<?= $r['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'manure'): ?>
<?php
$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_manure WHERE 1 $yearFilter", $yearParam);
$rows = q("SELECT id,sale_date,particulars,qty,rate,amount FROM sales_manure WHERE 1 $yearFilter ORDER BY sale_date DESC", $yearParam);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Manure Sales <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
  <span class="badge bg-success fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Particulars</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-center">Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['sale_date'] ?></td><td><?= htmlspecialchars($r['particulars']??'') ?></td>
          <td class="text-end"><?= num($r['qty']) ?></td><td class="text-end"><?= num($r['rate'],2) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('sales_manure',<?= $r['id'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('sales_manure',<?= $r['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'raw_material'): ?>
<?php
$yearFilter = $year == 0 ? '' : 'AND YEAR(sale_date)=?';
$yearParam = $year == 0 ? [] : [$year];
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_raw_material WHERE 1 $yearFilter", $yearParam);
$rows = q("SELECT id,sale_date,particulars,qty,rate,amount FROM sales_raw_material WHERE 1 $yearFilter ORDER BY sale_date DESC", $yearParam);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Raw Material Sales <?= $year == 0 ? '(All Years)' : "— " . $year ?></div>
  <span class="badge bg-success fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Particulars</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-center">Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['sale_date'] ?></td><td><?= htmlspecialchars($r['particulars']??'') ?></td>
          <td class="text-end"><?= num($r['qty']) ?></td><td class="text-end"><?= num($r['rate'],2) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('sales_raw_material',<?= $r['id'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('sales_raw_material',<?= $r['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'investment'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM sales_investment",[]);
$rows = q("SELECT id,inv_date,particulars,amount FROM sales_investment ORDER BY inv_date DESC",[]);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Investments Received</div>
  <span class="badge bg-primary fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Investor / Description</th><th class="text-end">Amount</th><th class="text-center">Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['inv_date'] ?></td><td><?= htmlspecialchars($r['particulars']??'') ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('sales_investment',<?= $r['id'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('sales_investment',<?= $r['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
