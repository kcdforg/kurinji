<?php $tab = $_GET['tab'] ?? 'chick'; ?>

<ul class="nav nav-tabs mb-3 flex-wrap">
  <?php
  $tabs = ['chick'=>'Chick','ingredients'=>'Feed Ingredients','feeds'=>'Ready Feeds',
           'medicine'=>'Medicine','rent'=>'Rent','asset'=>'Assets','misc'=>'Misc','accruals'=>'Accruals'];
  foreach($tabs as $k=>$lbl):
  ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$k?'active':'' ?>" href="?page=expenses&year=<?= $year ?>&tab=<?= $k ?>">
      <?= $lbl ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<?php if($tab === 'chick'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_chick WHERE YEAR(purchase_date)=?",[$year]);
$rows = q("SELECT purchase_date,item,seller,qty_birds,rate,amount,chick_count FROM exp_chick WHERE YEAR(purchase_date)=? ORDER BY purchase_date DESC",[$year]);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Chick Purchase — <?= $year ?></div>
  <span class="badge bg-danger fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Item</th><th>Seller</th><th class="text-end">Qty Birds</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-end">Chick Count</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['purchase_date'] ?></td><td><?= htmlspecialchars($r['item']??'') ?></td>
          <td><?= htmlspecialchars($r['seller']??'') ?></td>
          <td class="text-end"><?= num($r['qty_birds'],0) ?></td><td class="text-end"><?= num($r['rate'],4) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td><td class="text-end"><?= num($r['chick_count'],0) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'ingredients'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feed_ingredient WHERE YEAR(purchase_date)=?",[$year]);
$cats  = q("SELECT category, SUM(qty_kg) qty, SUM(amount) amt, AVG(rate) avg_rate FROM exp_feed_ingredient WHERE YEAR(purchase_date)=? GROUP BY category ORDER BY amt DESC",[$year]);
$rows  = q("SELECT purchase_date,category,item,seller,qty_kg,rate,amount FROM exp_feed_ingredient WHERE YEAR(purchase_date)=? ORDER BY purchase_date DESC",[$year]);
?>
<div class="row g-3 mb-3">
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="section-title">By Category — <?= $year ?></div>
      <table class="table table-sm">
        <thead><tr><th>Category</th><th class="text-end">Qty(Kg)</th><th class="text-end">Avg Rate</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($cats as $r): ?>
          <tr><td><?= $r['category'] ?></td><td class="text-end"><?= num($r['qty'],0) ?></td>
              <td class="text-end">₹<?= num($r['avg_rate'],2) ?>/Kg</td>
              <td class="text-end"><?= money($r['amt']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td colspan="3">Total</td><td class="text-end"><?= money($total) ?></td></tr></tfoot>
      </table>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="section-title">All Transactions</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover datatable">
          <thead><tr><th>Date</th><th>Category</th><th>Seller</th><th class="text-end">Qty(Kg)</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
          <?php foreach($rows as $r): ?>
            <tr><td><?= $r['purchase_date'] ?></td>
                <td><span class="badge bg-secondary badge-type"><?= $r['category'] ?></span></td>
                <td><?= htmlspecialchars($r['seller']??'') ?></td>
                <td class="text-end"><?= num($r['qty_kg'],0) ?></td><td class="text-end">₹<?= num($r['rate'],2) ?></td>
                <td class="text-end"><?= money($r['amount']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php elseif($tab === 'feeds'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_feeds WHERE YEAR(purchase_date)=?",[$year]);
$rows  = q("SELECT purchase_date,item,seller,qty_kg,rate,amount FROM exp_feeds WHERE YEAR(purchase_date)=? ORDER BY purchase_date DESC",[$year]);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Ready-Made Feed Purchase — <?= $year ?></div>
  <span class="badge bg-danger fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Item</th><th>Seller</th><th class="text-end">Qty(Kg)</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['purchase_date'] ?></td><td><?= htmlspecialchars($r['item']??'') ?></td>
          <td><?= htmlspecialchars($r['seller']??'') ?></td>
          <td class="text-end"><?= num($r['qty_kg'],0) ?></td><td class="text-end">₹<?= num($r['rate'],2) ?></td>
          <td class="text-end"><?= money($r['amount']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'medicine'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_medicine WHERE YEAR(purchase_date)=?",[$year]);
$rows  = q("SELECT purchase_date,item,seller,qty,rate,amount FROM exp_medicine WHERE YEAR(purchase_date)=? ORDER BY purchase_date DESC",[$year]);
$bysup = q("SELECT seller, SUM(amount) amt FROM exp_medicine WHERE YEAR(purchase_date)=? GROUP BY seller ORDER BY amt DESC LIMIT 10",[$year]);
?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between"><div class="section-title">Medicine & Vaccines — <?= $year ?></div>
      <span class="badge bg-danger fs-6"><?= money($total) ?></span></div>
      <table class="table table-sm table-hover datatable">
        <thead><tr><th>Date</th><th>Item</th><th>Seller</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr><td><?= $r['purchase_date'] ?></td><td><?= htmlspecialchars($r['item']??'') ?></td>
              <td><?= htmlspecialchars($r['seller']??'') ?></td>
              <td class="text-end"><?= money($r['amount']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <div class="section-title">Top Suppliers</div>
      <table class="table table-sm">
        <thead><tr><th>Supplier</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($bysup as $r): ?>
          <tr><td><?= htmlspecialchars($r['seller']??'') ?></td><td class="text-end"><?= money($r['amt']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($tab === 'rent'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_rent WHERE YEAR(payment_date)=?",[$year]);
$rows  = q("SELECT payment_date,place,rate,amount FROM exp_rent WHERE YEAR(payment_date)=? ORDER BY payment_date DESC",[$year]);
$byplace = q("SELECT place, SUM(amount) amt FROM exp_rent GROUP BY place ORDER BY amt DESC",[$year]);
?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between"><div class="section-title">Rent Payments — <?= $year ?></div>
      <span class="badge bg-danger fs-6"><?= money($total) ?></span></div>
      <table class="table table-sm table-hover datatable">
        <thead><tr><th>Date</th><th>Place</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr><td><?= $r['payment_date'] ?></td><td><?= htmlspecialchars($r['place']??'') ?></td>
              <td class="text-end">₹<?= num($r['rate'],2) ?></td><td class="text-end"><?= money($r['amount']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <div class="section-title">By Location (All Time)</div>
      <table class="table table-sm">
        <thead><tr><th>Location</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($byplace as $r): ?>
          <tr><td><?= htmlspecialchars($r['place']??'') ?></td><td class="text-end"><?= money($r['amt']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($tab === 'asset'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_asset",[$year]);
$rows  = q("SELECT purchase_date,item,seller,qty,rate,amount FROM exp_asset ORDER BY purchase_date DESC",[]);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Asset Purchases (All Time)</div>
  <span class="badge bg-secondary fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Item</th><th>Seller</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['purchase_date'] ?></td><td><?= htmlspecialchars($r['item']??'') ?></td>
          <td><?= htmlspecialchars($r['seller']??'') ?></td>
          <td class="text-end"><?= num($r['qty']) ?></td><td class="text-end">₹<?= num($r['rate'],2) ?></td>
          <td class="text-end fw-semibold"><?= money($r['amount']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'misc'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_misc WHERE YEAR(expense_date)=?",[$year]);
$rows  = q("SELECT expense_date,item,description,qty,rate,amount FROM exp_misc WHERE YEAR(expense_date)=? ORDER BY expense_date DESC",[$year]);
$byitem= q("SELECT item, SUM(amount) amt FROM exp_misc WHERE YEAR(expense_date)=? GROUP BY item ORDER BY amt DESC LIMIT 15",[$year]);
?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between"><div class="section-title">Miscellaneous Expenses — <?= $year ?></div>
      <span class="badge bg-danger fs-6"><?= money($total) ?></span></div>
      <table class="table table-sm table-hover datatable">
        <thead><tr><th>Date</th><th>Item</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr><td><?= $r['expense_date'] ?></td><td><?= htmlspecialchars($r['item']??'') ?></td>
              <td class="small text-muted"><?= htmlspecialchars($r['description']??'') ?></td>
              <td class="text-end"><?= money($r['amount']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <div class="section-title">Top Categories</div>
      <table class="table table-sm">
        <thead><tr><th>Item</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach($byitem as $r): ?>
          <tr><td><?= htmlspecialchars($r['item']??'') ?></td><td class="text-end"><?= money($r['amt']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($tab === 'accruals'): ?>
<?php
$total = (float)qval("SELECT COALESCE(SUM(amount),0) FROM exp_accruals",[]);
$rows  = q("SELECT accrual_date,item,seller,qty,rate,amount FROM exp_accruals ORDER BY accrual_date DESC",[]);
?>
<div class="card p-3">
  <div class="d-flex justify-content-between"><div class="section-title">Accruals / Liabilities</div>
  <span class="badge bg-warning text-dark fs-6"><?= money($total) ?></span></div>
  <table class="table table-sm table-hover datatable">
    <thead><tr><th>Date</th><th>Item</th><th>Seller / Party</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr><td><?= $r['accrual_date']??'—' ?></td><td><?= htmlspecialchars($r['item']??'') ?></td>
          <td><?= htmlspecialchars($r['seller']??'') ?></td>
          <td class="text-end fw-semibold text-warning"><?= money($r['amount']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
