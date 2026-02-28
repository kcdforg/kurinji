<?php
// AJAX CRUD Handler - Unified for all entities
require 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$entity = $_POST['entity'] ?? $_GET['entity'] ?? '';

function ok($msg='Saved', $data=[]){ echo json_encode(['success'=>true,'message'=>$msg]+$data); exit; }
function err($msg){ echo json_encode(['success'=>false,'message'=>$msg]); exit; }
function p($k){ return trim($_POST[$k] ?? ''); }
function pn($k){ $v=trim($_POST[$k]??''); return $v===''?null:(float)$v; }
function pd($k){ $v=trim($_POST[$k]??''); return $v===''?null:$v; }

try {

// ── GET single record for edit modal ────────────────────────────────────────
if($action==='get'){
    $id=(int)($_GET['id']??0);
    $tables=[
        'sales_egg'=>'sales_egg','sales_feed'=>'sales_feed','sales_culling'=>'sales_culling',
        'sales_manure'=>'sales_manure','sales_raw_material'=>'sales_raw_material',
        'sales_investment'=>'sales_investment',
        'exp_chick'=>'exp_chick','exp_feed_ingredient'=>'exp_feed_ingredient',
        'exp_feeds'=>'exp_feeds','exp_medicine'=>'exp_medicine','exp_salary'=>'exp_salary',
        'exp_labour'=>'exp_labour','exp_rent'=>'exp_rent','exp_asset'=>'exp_asset',
        'exp_misc'=>'exp_misc','exp_accruals'=>'exp_accruals',
        'loan_lender'=>'loan_lender','loan_transaction'=>'loan_transaction',
        'production_daily'=>'production_daily',
    ];
    if(!isset($tables[$entity])) err('Unknown entity');
    $st = db()->prepare("SELECT * FROM {$tables[$entity]} WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch();
    if(!$row) err('Record not found');
    echo json_encode(['success'=>true,'data'=>$row]); exit;
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if($action==='delete'){
    $id=(int)($_POST['id']??0);
    $allowed=['sales_egg','sales_feed','sales_culling','sales_manure','sales_raw_material',
              'sales_investment','exp_chick','exp_feed_ingredient','exp_feeds','exp_medicine',
              'exp_salary','exp_labour','exp_rent','exp_asset','exp_misc','exp_accruals',
              'loan_lender','loan_transaction','production_daily'];
    if(!in_array($entity,$allowed)) err('Unknown entity');
    $st = db()->prepare("DELETE FROM `$entity` WHERE id=?");
    $st->execute([$id]);
    ok('Record deleted');
}

// ── ADD / EDIT ────────────────────────────────────────────────────────────────
$id = (int)(p('id'));
$isEdit = $id > 0;

switch($entity){

case 'sales_egg':
case 'sales_feed':
case 'sales_manure':
case 'sales_raw_material':
    $data=['sale_date'=>pd('sale_date'),'particulars'=>p('particulars'),
           'qty'=>pn('qty'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['sale_date']||!$data['amount']) err('Date and Amount are required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE `$entity` SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO `$entity` ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'sales_culling':
    $data=['sale_date'=>pd('sale_date'),'particulars'=>p('particulars'),
           'qty_birds'=>pn('qty_birds'),'rate'=>pn('rate'),'amount'=>pn('amount'),
           'manure_kg_estimate'=>pn('manure_kg_estimate')];
    if(!$data['sale_date']||!$data['amount']) err('Date and Amount are required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE sales_culling SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $st=db()->prepare("INSERT INTO sales_culling (sale_date,particulars,qty_birds,rate,amount,manure_kg_estimate) VALUES (?,?,?,?,?,?)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'sales_investment':
    $data=['inv_date'=>pd('inv_date'),'particulars'=>p('particulars'),'amount'=>pn('amount')];
    if(!$data['inv_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit) { $st=db()->prepare("UPDATE sales_investment SET inv_date=?,particulars=?,amount=? WHERE id=$id");
                  $st->execute(array_values($data)); }
    else        { $st=db()->prepare("INSERT INTO sales_investment (inv_date,particulars,amount) VALUES (?,?,?)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_chick':
    $data=['purchase_date'=>pd('purchase_date'),'item'=>p('item'),'seller'=>p('seller'),
           'qty_birds'=>pn('qty_birds'),'rate'=>pn('rate'),'amount'=>pn('amount'),'chick_count'=>pn('chick_count')];
    if(!$data['purchase_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_chick SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_chick ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_feeds':
    $data=['purchase_date'=>pd('purchase_date'),'item'=>p('item'),'seller'=>p('seller'),
           'qty_kg'=>pn('qty_kg'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['purchase_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_feeds SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_feeds ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_medicine':
    $data=['purchase_date'=>pd('purchase_date'),'item'=>p('item'),'seller'=>p('seller'),
           'qty'=>pn('qty'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['purchase_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_medicine SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_medicine ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_salary':
    $data=['payment_date'=>pd('payment_date'),'employee_name'=>p('employee_name'),
           'qty'=>pn('qty'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['payment_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_salary SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_salary ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_rent':
    $data=['payment_date'=>pd('payment_date'),'place'=>p('place'),
           'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['payment_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_rent SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_rent ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_misc':
    $data=['expense_date'=>pd('expense_date'),'item'=>p('item'),'description'=>p('description'),
           'qty'=>pn('qty'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['expense_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_misc SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_misc ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_feed_ingredient':
    $data=['purchase_date'=>pd('purchase_date'),'category'=>p('category'),'item'=>p('item'),
           'seller'=>p('seller'),'qty_kg'=>pn('qty_kg'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['purchase_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_feed_ingredient SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_feed_ingredient ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'loan_transaction':
    $data=['lender_id'=>pn('lender_id'),'txn_date'=>pd('txn_date'),
           'loan_availed'=>pn('loan_availed'),'balance'=>pn('balance'),
           'interest_pct'=>pn('interest_pct'),'interest_amount'=>pn('interest_amount'),
           'amount_paid'=>pn('amount_paid')];
    if(!$data['txn_date']||!$data['lender_id']) err('Date and Lender are required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE loan_transaction SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO loan_transaction ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_labour':
    $data=['payment_date'=>pd('payment_date'),'labour_type'=>p('labour_type'),
           'qty'=>pn('qty'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['payment_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_labour SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_labour ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_asset':
    $data=['purchase_date'=>pd('purchase_date'),'item'=>p('item'),'seller'=>p('seller'),
           'qty'=>pn('qty'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['purchase_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_asset SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_asset ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'exp_accruals':
    $data=['accrual_date'=>pd('accrual_date'),'item'=>p('item'),'seller'=>p('seller'),
           'qty'=>pn('qty'),'rate'=>pn('rate'),'amount'=>pn('amount')];
    if(!$data['accrual_date']||!$data['amount']) err('Date and Amount required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE exp_accruals SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO exp_accruals ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

case 'production_daily':
    $data=['record_date'=>pd('record_date'),'location'=>p('location'),'shed'=>p('shed'),
           'week_code'=>p('week_code'),'alive'=>pn('alive'),'mortality'=>pn('mortality'),
           'total_mortality'=>pn('total_mortality'),'eggs_produced'=>pn('eggs_produced'),
           'production_pct'=>pn('production_pct'),'egg_sales'=>pn('egg_sales'),
           'local_sales'=>pn('local_sales'),'damages'=>pn('damages'),'egg_stock'=>pn('egg_stock')];
    if(!$data['record_date']) err('Date is required');
    if($isEdit){ $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
                 $st=db()->prepare("UPDATE production_daily SET $sets WHERE id=$id");
                 $st->execute(array_values($data)); }
    else        { $cols=implode(',',array_keys($data));$ph=implode(',',array_fill(0,count($data),'?'));
                  $st=db()->prepare("INSERT INTO production_daily ($cols) VALUES ($ph)");
                  $st->execute(array_values($data)); }
    ok($isEdit?'Updated':'Added');

default:
    err('Unknown entity type');
}

} catch (Exception $e) {
    err('Error: ' . $e->getMessage());
}
?>
