<?php
$navLinks = [
    'dashboard'   => ['icon'=>'bi-speedometer2',    'label'=>'Dashboard'],
    'pl_report'   => ['icon'=>'bi-bar-chart-line',  'label'=>'P&L Report'],
    'sales'       => ['icon'=>'bi-cash-stack',       'label'=>'Sales'],
    'expenses'    => ['icon'=>'bi-receipt',          'label'=>'Expenses'],
    'loans'       => ['icon'=>'bi-bank',             'label'=>'Loans'],
    'feed_cost'   => ['icon'=>'bi-basket3',          'label'=>'Feed Cost'],
    'salary'      => ['icon'=>'bi-people',           'label'=>'Salary & Labour'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kurinji Poultry Farm — <?= htmlspecialchars($navLinks[$page]['label'] ?? ucfirst($page)) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
:root { --primary: #1a6e3c; --accent: #f5a623; }
body { background: #f4f6f9; font-size: .9rem; }
.sidebar { width: 230px; min-height: 100vh; background: var(--primary); position: fixed; top:0; left:0; z-index:100; }
.sidebar .brand { padding: 1.2rem 1rem; font-size: 1.1rem; font-weight:700; color:#fff; border-bottom:1px solid rgba(255,255,255,.15); }
.sidebar .nav-link { color: rgba(255,255,255,.8); padding: .55rem 1.1rem; border-radius: 6px; margin: 2px 8px; display:flex; align-items:center; gap:8px; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.18); color:#fff; }
.main-content { margin-left: 230px; padding: 24px; }
.card { border:none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
.kpi-card { border-left: 4px solid var(--accent); }
.kpi-val { font-size: 1.6rem; font-weight:700; }
.kpi-label { font-size: .78rem; color: #888; text-transform:uppercase; letter-spacing:.5px; }
.table thead th { background: #f0f4f8; font-size: .78rem; text-transform:uppercase; letter-spacing:.3px; }
.badge-type { font-size:.72rem; }
.section-title { font-weight:700; font-size:1.1rem; color:var(--primary); margin-bottom:1rem; }
.year-filter form { display:inline-flex; align-items:center; gap:8px; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="brand"><i class="bi bi-egg-fried me-2"></i>Kurinji Poultry</div>
  <nav class="mt-2">
    <?php foreach($navLinks as $pg => $info): ?>
    <a href="index.php?page=<?= $pg ?>&year=<?= $year ?>" class="nav-link <?= $page===$pg ? 'active':'' ?>">
      <i class="bi <?= $info['icon'] ?>"></i> <?= $info['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div style="position: absolute; bottom: 0; width: 100%; padding: 1rem; border-top: 1px solid rgba(255,255,255,.15);">
    <div style="color: rgba(255,255,255,.7); font-size: .8rem; margin-bottom: .5rem;">Logged in as:</div>
    <div style="color: #fff; font-weight: 600; margin-bottom: 1rem;"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
    <a href="index.php?page=logout" class="btn btn-sm btn-outline-light w-100">
      <i class="bi bi-box-arrow-right me-1"></i>Logout
    </a>
  </div>
</div>

<!-- Main -->
<div class="main-content">
<!-- Topbar -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <span class="text-muted small">Kurinji Poultry Farm</span>
    <h4 class="mb-0 fw-bold"><?= $navLinks[$page]['label'] ?? ucfirst($page) ?></h4>
  </div>
  <div class="year-filter">
    <form method="get" action="index.php">
      <input type="hidden" name="page" value="<?= $page ?>">
      <label class="fw-semibold">Year:</label>
      <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php
        $years = q("SELECT DISTINCT YEAR(sale_date) y FROM sales_egg UNION SELECT DISTINCT YEAR(purchase_date) FROM exp_chick UNION SELECT DISTINCT YEAR(txn_date) FROM loan_transaction ORDER BY y DESC");
        foreach($years as $yr): ?>
        <option value="<?= $yr['y'] ?>" <?= $yr['y']==$year ? 'selected':'' ?>><?= $yr['y'] ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>