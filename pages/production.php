<?php
$tab     = $_GET['tab'] ?? 'daily';
$yw      = $year == 0;
$yc      = $yw ? '' : " AND YEAR(record_date) = $year";
$floc    = $_GET['floc']  ?? '';
$fshed   = $_GET['fshed'] ?? '';

$lc  = $floc  ? " AND location = " . db()->quote($floc)  : '';
$sc  = $fshed ? " AND shed = "     . db()->quote($fshed) : '';

// Distinct filter options
$locations  = q("SELECT DISTINCT location FROM production_daily ORDER BY location");
$sheds_all  = q("SELECT DISTINCT location, shed FROM production_daily ORDER BY location, shed");

$tabs = [
    'daily'    => ['label' => 'Daily Register',    'icon' => 'bi-table'],
    'monthly'  => ['label' => 'Monthly Summary',   'icon' => 'bi-calendar3'],
    'analytics'=> ['label' => 'Analytics',         'icon' => 'bi-graph-up'],
];
?>

<!-- Tab Nav -->
<ul class="nav nav-tabs mb-3">
<?php foreach ($tabs as $k => $t): ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$k?'active':'' ?>"
       href="?page=production&year=<?= $year ?>&tab=<?= $k ?>&floc=<?= urlencode($floc) ?>&fshed=<?= urlencode($fshed) ?>">
      <i class="bi <?= $t['icon'] ?> me-1"></i><?= $t['label'] ?>
    </a>
  </li>
<?php endforeach; ?>
</ul>

<!-- Filters bar -->
<div class="card p-2 mb-3">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted small fw-semibold">Filter:</span>
    <select class="form-select form-select-sm w-auto" id="flocSel">
      <option value="">All Locations</option>
      <?php foreach ($locations as $l): ?>
      <option value="<?= htmlspecialchars($l['location']) ?>" <?= $floc===$l['location']?'selected':'' ?>>
        <?= htmlspecialchars($l['location']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <select class="form-select form-select-sm w-auto" id="fshedSel">
      <option value="">All Sheds</option>
      <?php foreach ($sheds_all as $s): ?>
      <option value="<?= htmlspecialchars($s['shed']) ?>"
              data-loc="<?= htmlspecialchars($s['location']) ?>"
              <?= $fshed===$s['shed']?'selected':'' ?>>
        <?= htmlspecialchars($s['location']) ?> — <?= htmlspecialchars($s['shed']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-primary" onclick="applyFilter()">Apply</button>
    <a class="btn btn-sm btn-outline-secondary" href="?page=production&year=<?= $year ?>&tab=<?= $tab ?>">Reset</a>
  </div>
</div>

<?php

// ═══════════════════════════════════════════════════════════════
// TAB 1: DAILY REGISTER
// ═══════════════════════════════════════════════════════════════
if ($tab === 'daily'):

$total_eggs = (int)qval("SELECT COALESCE(SUM(eggs_produced),0) FROM production_daily WHERE 1=1$yc$lc$sc");
$total_mort = (int)qval("SELECT COALESCE(SUM(mortality),0)     FROM production_daily WHERE 1=1$yc$lc$sc");
$total_sold = (int)qval("SELECT COALESCE(SUM(egg_sales+COALESCE(local_sales,0)),0) FROM production_daily WHERE 1=1$yc$lc$sc");
$avg_pct    = (float)qval("SELECT COALESCE(AVG(production_pct),0) FROM production_daily WHERE production_pct > 0$yc$lc$sc");

$rows = q("SELECT id, record_date, location, shed, week_code,
                  alive, mortality, total_mortality,
                  eggs_produced, production_pct,
                  egg_sales, local_sales, damages,
                  (COALESCE(egg_sales,0)+COALESCE(local_sales,0)) AS tot_sold,
                  egg_stock
           FROM production_daily
           WHERE 1=1$yc$lc$sc
           ORDER BY record_date DESC, location, shed
           LIMIT 5000");
?>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['Total Eggs Produced', number_format($total_eggs), 'success',  'bi-egg'],
    ['Total Eggs Sold',     number_format($total_sold), 'primary',  'bi-cart-check'],
    ['Total Mortality',     number_format($total_mort), 'danger',   'bi-heartbreak'],
    ['Avg Production %',    number_format($avg_pct*100,1).'%', 'info','bi-percent'],
  ];
  foreach ($kpis as [$lbl,$val,$col,$ico]): ?>
  <div class="col-6 col-md-3">
    <div class="card kpi-card p-3">
      <div class="kpi-label"><i class="bi <?= $ico ?> me-1"></i><?= $lbl ?></div>
      <div class="kpi-val text-<?= $col ?>"><?= $val ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="section-title mb-0">Daily Production Records — <?= $yw?'All Time':$year ?> <?= $floc?"| $floc":'' ?> <?= $fshed?"| $fshed":'' ?></div>
    <button class="btn btn-sm btn-outline-success" onclick="openCrudAdd('production_daily')">
      <i class="bi bi-plus-circle me-1"></i>Add Record
    </button>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover dt" style="font-size:.8rem">
      <thead>
        <tr>
          <th>Date</th>
          <th>Location</th>
          <th>Shed</th>
          <th>Week</th>
          <th class="text-end">Alive</th>
          <th class="text-end">Mortality</th>
          <th class="text-end">Cum.Mort</th>
          <th class="text-end">Eggs Prod.</th>
          <th class="text-end">Prod %</th>
          <th class="text-end">Mkt Sales</th>
          <th class="text-end">Local</th>
          <th class="text-end">Damages</th>
          <th class="text-end">Tot.Sold</th>
          <th class="text-end">Stock</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $pct = $r['production_pct'] ? round($r['production_pct']*100, 1) : null;
        $pct_class = $pct >= 80 ? 'text-success' : ($pct >= 60 ? 'text-warning' : 'text-danger');
      ?>
        <tr>
          <td><?= $r['record_date'] ?></td>
          <td><span class="badge bg-<?= $r['location']==='M.Puthur'?'primary':'success' ?> badge-type"><?= $r['location'] ?></span></td>
          <td><?= $r['shed'] ?></td>
          <td class="text-muted"><?= $r['week_code'] ?? '—' ?></td>
          <td class="text-end"><?= $r['alive'] !== null ? indian_number($r['alive'],0) : '—' ?></td>
          <td class="text-end <?= $r['mortality']>5?'text-danger':'' ?>"><?= $r['mortality'] !== null ? indian_number($r['mortality'],0) : '—' ?></td>
          <td class="text-end text-muted"><?= $r['total_mortality'] !== null ? indian_number($r['total_mortality'],0) : '—' ?></td>
          <td class="text-end fw-semibold"><?= $r['eggs_produced'] !== null ? indian_number($r['eggs_produced'],0) : '—' ?></td>
          <td class="text-end <?= $pct_class ?>"><?= $pct !== null ? $pct.'%' : '—' ?></td>
          <td class="text-end"><?= $r['egg_sales'] !== null ? indian_number($r['egg_sales'],0) : '—' ?></td>
          <td class="text-end"><?= $r['local_sales'] !== null ? indian_number($r['local_sales'],0) : '—' ?></td>
          <td class="text-end text-danger"><?= $r['damages'] !== null ? indian_number($r['damages'],0) : '—' ?></td>
          <td class="text-end"><?= $r['tot_sold'] ? indian_number($r['tot_sold'],0) : '—' ?></td>
          <td class="text-end text-info"><?= $r['egg_stock'] !== null ? indian_number($r['egg_stock'],0) : '—' ?></td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="openCrudEdit('production_daily',<?= $r['id'] ?>)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="crudQuickDelete('production_daily',<?= $r['id'] ?>)">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php

// ═══════════════════════════════════════════════════════════════
// TAB 2: MONTHLY SUMMARY
// ═══════════════════════════════════════════════════════════════
elseif ($tab === 'monthly'):

$pfmt   = $yw ? "'%Y'" : "'%Y-%m'";
$monthly = q("
    SELECT DATE_FORMAT(record_date,$pfmt) period,
           location, shed,
           SUM(eggs_produced)               total_eggs,
           SUM(COALESCE(egg_sales,0)+COALESCE(local_sales,0)) total_sold,
           SUM(mortality)                   total_mort,
           AVG(CASE WHEN production_pct>0 THEN production_pct END) avg_pct,
           AVG(CASE WHEN alive>0 THEN alive END)                   avg_alive,
           MAX(eggs_produced)               max_eggs,
           MIN(CASE WHEN eggs_produced>0 THEN eggs_produced END)   min_eggs
    FROM production_daily
    WHERE 1=1$yc$lc$sc
    GROUP BY period, location, shed
    ORDER BY period DESC, location, shed
");

// Location-level totals by period
$loc_totals = q("
    SELECT DATE_FORMAT(record_date,$pfmt) period,
           location,
           SUM(eggs_produced)               total_eggs,
           SUM(COALESCE(egg_sales,0)+COALESCE(local_sales,0)) total_sold,
           SUM(mortality)                   total_mort,
           AVG(CASE WHEN production_pct>0 THEN production_pct END) avg_pct
    FROM production_daily
    WHERE 1=1$yc$lc$sc
    GROUP BY period, location
    ORDER BY period DESC, location
");

// Grand totals by period (both locations)
$grand_totals = q("
    SELECT DATE_FORMAT(record_date,$pfmt) period,
           SUM(eggs_produced) total_eggs,
           SUM(COALESCE(egg_sales,0)+COALESCE(local_sales,0)) total_sold,
           SUM(mortality) total_mort
    FROM production_daily
    WHERE 1=1$yc$lc$sc
    GROUP BY period
    ORDER BY period DESC
");
?>

<!-- Location comparison KPIs -->
<div class="row g-3 mb-4">
<?php
$loc_kpis = q("
    SELECT location,
           SUM(eggs_produced) eggs,
           SUM(mortality) mort,
           AVG(CASE WHEN production_pct>0 THEN production_pct END) avg_pct,
           MAX(record_date) last_date
    FROM production_daily
    WHERE 1=1$yc$lc$sc
    GROUP BY location ORDER BY location
");
foreach ($loc_kpis as $lk): ?>
<div class="col-md-6">
  <div class="card p-3 border-<?= $lk['location']==='M.Puthur'?'primary':'success' ?>" style="border-width:2px!important">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="fw-bold fs-6"><?= $lk['location'] ?></div>
        <div class="text-muted small">Last entry: <?= $lk['last_date'] ?></div>
      </div>
      <span class="badge bg-<?= $lk['location']==='M.Puthur'?'primary':'success' ?> fs-6">
        <?= $lk['location']==='M.Puthur'?'3 Sheds':'2 Sheds' ?>
      </span>
    </div>
    <div class="row mt-2 g-2">
      <div class="col-4 text-center">
        <div class="small text-muted">Total Eggs</div>
        <div class="fw-bold"><?= indian_number($lk['eggs'],0) ?></div>
      </div>
      <div class="col-4 text-center">
        <div class="small text-muted">Mortality</div>
        <div class="fw-bold text-danger"><?= indian_number($lk['mort'],0) ?></div>
      </div>
      <div class="col-4 text-center">
        <div class="small text-muted">Avg Prod %</div>
        <div class="fw-bold text-<?= $lk['avg_pct']*100>=75?'success':'warning' ?>"><?= number_format($lk['avg_pct']*100,1) ?>%</div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- Period summary table -->
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="section-title">Shed-wise <?= $yw?'Yearly':'Monthly' ?> Summary</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover dt" style="font-size:.8rem">
          <thead><tr>
            <th><?= $yw?'Year':'Month' ?></th><th>Location</th><th>Shed</th>
            <th class="text-end">Avg Alive</th>
            <th class="text-end">Total Eggs</th>
            <th class="text-end">Avg Prod%</th>
            <th class="text-end">Total Sold</th>
            <th class="text-end">Mortality</th>
          </tr></thead>
          <tbody>
          <?php foreach ($monthly as $r):
            $pct = $r['avg_pct'] ? round($r['avg_pct']*100,1) : null;
            $pct_class = $pct >= 80 ? 'text-success' : ($pct >= 60 ? 'text-warning' : ($pct ? 'text-danger' : ''));
          ?>
            <tr>
              <td class="fw-semibold"><?= $r['period'] ?></td>
              <td><span class="badge bg-<?= $r['location']==='M.Puthur'?'primary':'success' ?> badge-type"><?= $r['location'] ?></span></td>
              <td><?= $r['shed'] ?></td>
              <td class="text-end"><?= $r['avg_alive'] ? indian_number($r['avg_alive'],0) : '—' ?></td>
              <td class="text-end"><?= $r['total_eggs'] ? indian_number($r['total_eggs'],0) : '—' ?></td>
              <td class="text-end <?= $pct_class ?>"><?= $pct ? $pct.'%' : '—' ?></td>
              <td class="text-end"><?= $r['total_sold'] ? indian_number($r['total_sold'],0) : '—' ?></td>
              <td class="text-end text-danger"><?= $r['total_mort'] ? $r['total_mort'] : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-3">
      <div class="section-title">Combined <?= $yw?'Yearly':'Monthly' ?> Totals</div>
      <table class="table table-sm table-hover">
        <thead><tr>
          <th><?= $yw?'Year':'Month' ?></th>
          <th class="text-end">Total Eggs</th>
          <th class="text-end">Sold</th>
          <th class="text-end">Mortality</th>
        </tr></thead>
        <tbody>
        <?php foreach ($grand_totals as $r): ?>
          <tr>
            <td class="fw-semibold"><?= $r['period'] ?></td>
            <td class="text-end"><?= indian_number($r['total_eggs'],0) ?></td>
            <td class="text-end"><?= indian_number($r['total_sold'],0) ?></td>
            <td class="text-end text-danger"><?= $r['total_mort'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card p-3 mt-3">
      <div class="section-title">Top 5 Production Days</div>
      <table class="table table-sm table-hover">
        <thead><tr><th>Date</th><th>Location</th><th>Shed</th><th class="text-end">Eggs</th><th class="text-end">Prod%</th></tr></thead>
        <tbody>
        <?php
        $top5 = q("SELECT record_date,location,shed,eggs_produced,production_pct
                   FROM production_daily WHERE eggs_produced IS NOT NULL$yc$lc$sc
                   ORDER BY eggs_produced DESC LIMIT 5");
        foreach ($top5 as $r): ?>
          <tr>
            <td><?= $r['record_date'] ?></td>
            <td><span class="badge bg-<?= $r['location']==='M.Puthur'?'primary':'success' ?> badge-type"><?= $r['location'] ?></span></td>
            <td><?= $r['shed'] ?></td>
            <td class="text-end fw-bold text-success"><?= indian_number($r['eggs_produced'],0) ?></td>
            <td class="text-end"><?= $r['production_pct']?number_format($r['production_pct']*100,1).'%':'—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php

// ═══════════════════════════════════════════════════════════════
// TAB 3: ANALYTICS
// ═══════════════════════════════════════════════════════════════
elseif ($tab === 'analytics'):

// Monthly production per shed (for chart)
$pfmt = $yw ? "'%Y'" : "'%Y-%m'";
$chart_data = q("
    SELECT DATE_FORMAT(record_date,$pfmt) period,
           location, shed,
           ROUND(AVG(CASE WHEN production_pct>0 THEN production_pct*100 END),2) avg_pct,
           SUM(eggs_produced) total_eggs,
           SUM(mortality) total_mort,
           AVG(CASE WHEN alive>0 THEN alive END) avg_alive
    FROM production_daily
    WHERE 1=1$yc$lc$sc
    GROUP BY period, location, shed
    ORDER BY period, location, shed
");

// Build JS datasets per shed
$sheds_list = [
    'M.Puthur|S-1' => ['label'=>'M.Puthur S-1','color'=>'#1a6e3c'],
    'M.Puthur|S-2' => ['label'=>'M.Puthur S-2','color'=>'#28a745'],
    'M.Puthur|S-3' => ['label'=>'M.Puthur S-3','color'=>'#6cbf7f'],
    'T.Patti|S-1'  => ['label'=>'T.Patti S-1', 'color'=>'#f5a623'],
    'T.Patti|S-2'  => ['label'=>'T.Patti S-2', 'color'=>'#e07b00'],
];

$periods = array_unique(array_column($chart_data,'period'));
sort($periods);

$shed_pct   = [];  // production % per shed per period
$shed_eggs  = [];  // eggs per shed per period
$shed_mort  = [];  // mortality per shed per period
$all_mort   = [];  // combined mortality per period

foreach ($chart_data as $r) {
    $key = $r['location'].'|'.$r['shed'];
    $shed_pct[$key][$r['period']]  = (float)$r['avg_pct'];
    $shed_eggs[$key][$r['period']] = (int)$r['total_eggs'];
    $shed_mort[$key][$r['period']] = (int)$r['total_mort'];
    $all_mort[$r['period']] = ($all_mort[$r['period']] ?? 0) + (int)$r['total_mort'];
}

// Summary stats per shed
$shed_stats = q("
    SELECT location, shed,
           COUNT(*) total_days,
           ROUND(AVG(CASE WHEN production_pct>0 THEN production_pct*100 END),2) avg_pct,
           MAX(production_pct*100)  max_pct,
           MIN(CASE WHEN production_pct>0 THEN production_pct*100 END) min_pct,
           SUM(eggs_produced)       total_eggs,
           SUM(mortality)           total_mort,
           ROUND(SUM(mortality)/NULLIF(AVG(CASE WHEN alive>0 THEN alive END),0)*100,3) mort_rate,
           MAX(record_date)         last_date,
           AVG(CASE WHEN alive>0 THEN alive END) avg_alive
    FROM production_daily
    WHERE 1=1$yc$lc$sc
    GROUP BY location, shed
    ORDER BY location, shed
");
?>

<!-- Shed Performance Cards -->
<div class="row g-3 mb-4">
<?php foreach ($shed_stats as $s):
    $col = $s['location']==='M.Puthur' ? 'primary' : 'success';
    $pct_class = $s['avg_pct']>=80 ? 'success' : ($s['avg_pct']>=65 ? 'warning' : 'danger');
?>
<div class="col-md-4">
  <div class="card p-3 h-100">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-bold"><?= $s['location'] ?> — <?= $s['shed'] ?></div>
      <span class="badge bg-<?= $col ?>"><?= $s['total_days'] ?> days</span>
    </div>
    <div class="row g-2 text-center">
      <div class="col-4">
        <div class="small text-muted">Avg Prod%</div>
        <div class="fw-bold fs-6 text-<?= $pct_class ?>"><?= $s['avg_pct'] ? $s['avg_pct'].'%' : '—' ?></div>
      </div>
      <div class="col-4">
        <div class="small text-muted">Total Eggs</div>
        <div class="fw-bold"><?= indian_number($s['total_eggs'],0) ?></div>
      </div>
      <div class="col-4">
        <div class="small text-muted">Mort Rate</div>
        <div class="fw-bold text-danger"><?= $s['mort_rate'] ? $s['mort_rate'].'%' : '—' ?></div>
      </div>
      <div class="col-4">
        <div class="small text-muted">Avg Alive</div>
        <div class="fw-semibold"><?= $s['avg_alive'] ? indian_number($s['avg_alive'],0) : '—' ?></div>
      </div>
      <div class="col-4">
        <div class="small text-muted">Max Prod%</div>
        <div class="fw-semibold text-success"><?= $s['max_pct'] ? number_format($s['max_pct'],1).'%' : '—' ?></div>
      </div>
      <div class="col-4">
        <div class="small text-muted">Total Mort</div>
        <div class="fw-semibold text-danger"><?= indian_number($s['total_mort'],0) ?></div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card p-3">
      <div class="section-title">Production % Trend — Per Shed (<?= $yw?'All Time':$year ?>)</div>
      <canvas id="chartPct" height="70"></canvas>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="section-title">Monthly Egg Production — Per Shed</div>
      <canvas id="chartEggs" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-3">
      <div class="section-title">Monthly Mortality Trend</div>
      <canvas id="chartMort" height="120"></canvas>
    </div>
  </div>
</div>

<!-- Worst mortality weeks -->
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="section-title">Top 10 Highest Mortality Days</div>
      <table class="table table-sm table-hover">
        <thead><tr><th>Date</th><th>Location</th><th>Shed</th><th class="text-end">Mortality</th><th class="text-end">Alive</th></tr></thead>
        <tbody>
        <?php
        $high_mort = q("SELECT record_date,location,shed,mortality,alive
                        FROM production_daily WHERE mortality IS NOT NULL AND mortality > 0$yc$lc$sc
                        ORDER BY mortality DESC LIMIT 10");
        foreach ($high_mort as $r): ?>
          <tr>
            <td><?= $r['record_date'] ?></td>
            <td><span class="badge bg-<?= $r['location']==='M.Puthur'?'primary':'success' ?> badge-type"><?= $r['location'] ?></span></td>
            <td><?= $r['shed'] ?></td>
            <td class="text-end fw-bold text-danger"><?= $r['mortality'] ?></td>
            <td class="text-end"><?= $r['alive'] ? indian_number($r['alive'],0) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="section-title">Weekly Production % Summary</div>
      <table class="table table-sm table-hover dt">
        <thead><tr><th>Week</th><th>Location</th><th>Shed</th><th class="text-end">Avg Prod%</th><th class="text-end">Total Eggs</th></tr></thead>
        <tbody>
        <?php
        $weekly = q("
            SELECT YEAR(record_date) yr,
                   WEEK(record_date,1) wk,
                   location, shed,
                   ROUND(AVG(CASE WHEN production_pct>0 THEN production_pct*100 END),1) avg_pct,
                   SUM(eggs_produced) total_eggs
            FROM production_daily
            WHERE 1=1$yc$lc$sc
            GROUP BY yr, wk, location, shed
            ORDER BY yr DESC, wk DESC, location, shed
            LIMIT 100
        ");
        foreach ($weekly as $r):
            $pct_class = $r['avg_pct']>=80?'text-success':($r['avg_pct']>=65?'text-warning':'text-danger');
        ?>
          <tr>
            <td><?= $r['yr'] ?> W<?= str_pad($r['wk'],2,'0',STR_PAD_LEFT) ?></td>
            <td><span class="badge bg-<?= $r['location']==='M.Puthur'?'primary':'success' ?> badge-type"><?= $r['location'] ?></span></td>
            <td><?= $r['shed'] ?></td>
            <td class="text-end <?= $pct_class ?>"><?= $r['avg_pct'] ? $r['avg_pct'].'%' : '—' ?></td>
            <td class="text-end"><?= $r['total_eggs'] ? indian_number($r['total_eggs'],0) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const periods = <?= json_encode(array_values($periods)) ?>;

const shedColors = {
  'M.Puthur|S-1': '#1a6e3c',
  'M.Puthur|S-2': '#28a745',
  'M.Puthur|S-3': '#6cbf7f',
  'T.Patti|S-1':  '#f5a623',
  'T.Patti|S-2':  '#e07b00',
};
const shedLabels = {
  'M.Puthur|S-1': 'M.Puthur S-1',
  'M.Puthur|S-2': 'M.Puthur S-2',
  'M.Puthur|S-3': 'M.Puthur S-3',
  'T.Patti|S-1':  'T.Patti S-1',
  'T.Patti|S-2':  'T.Patti S-2',
};

const shedPct  = <?= json_encode($shed_pct)  ?>;
const shedEggs = <?= json_encode($shed_eggs) ?>;
const shedMort = <?= json_encode($shed_mort) ?>;
const allMort  = <?= json_encode($all_mort)  ?>;

function buildDatasets(dataMap, fill=false) {
  return Object.keys(shedLabels).filter(k => dataMap[k]).map(k => ({
    label: shedLabels[k],
    data: periods.map(p => dataMap[k][p] ?? null),
    borderColor: shedColors[k],
    backgroundColor: shedColors[k] + '22',
    fill: fill,
    tension: 0.3,
    pointRadius: periods.length > 60 ? 0 : 3,
    spanGaps: true,
  }));
}

// Production % line chart
new Chart(document.getElementById('chartPct'), {
  type: 'line',
  data: { labels: periods, datasets: buildDatasets(shedPct) },
  options: {
    plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false,
      callbacks: { label: c => c.dataset.label + ': ' + (c.raw??'').toFixed(1) + '%' } } },
    scales: { y: { ticks: { callback: v => v+'%' }, min: 0, max: 110 } }
  }
});

// Eggs stacked bar chart
new Chart(document.getElementById('chartEggs'), {
  type: 'bar',
  data: { labels: periods, datasets: Object.keys(shedLabels).filter(k=>shedEggs[k]).map(k=>({
    label: shedLabels[k],
    data: periods.map(p => shedEggs[k]?.[p] ?? 0),
    backgroundColor: shedColors[k]+'bb',
    borderRadius: 2,
  }))},
  options: {
    plugins: { legend: { position: 'top' }, tooltip: { mode: 'index' } },
    scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: v => indian_number_js(v) } } }
  }
});

// Mortality line chart
new Chart(document.getElementById('chartMort'), {
  type: 'line',
  data: { labels: periods, datasets: [{
    label: 'Total Mortality',
    data: periods.map(p => allMort[p] ?? 0),
    borderColor: '#dc3545',
    backgroundColor: 'rgba(220,53,69,.15)',
    fill: true,
    tension: 0.3,
    pointRadius: periods.length > 60 ? 0 : 3,
  }]},
  options: {
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => 'Mortality: ' + c.raw } } },
    scales: { y: { ticks: { callback: v => v } } }
  }
});

function indian_number_js(v) {
  if (!v && v!==0) return '—';
  let s = Math.floor(Math.abs(v)).toString();
  if (s.length > 3) {
    let l=s.slice(-3), r=s.slice(0,-3);
    r = r.split('').reverse().join('').match(/.{1,2}/g).join(',').split('').reverse().join('');
    s = r + ',' + l;
  }
  return (v<0?'-':'') + s;
}
</script>

<?php endif; ?>

<script>
function applyFilter() {
  const loc  = document.getElementById('flocSel').value;
  const shed = document.getElementById('fshedSel').value;
  window.location = `?page=production&year=<?= $year ?>&tab=<?= $tab ?>&floc=${encodeURIComponent(loc)}&fshed=${encodeURIComponent(shed)}`;
}
$(function(){
  $('table.dt').DataTable({pageLength:25, order:[], responsive:true,
    language:{search:'Filter:',lengthMenu:'Show _MENU_ rows'}
  });
});
</script>
