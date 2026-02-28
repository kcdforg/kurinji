</div><!-- /main-content -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
$(function(){
  $('table.datatable').DataTable({
    pageLength: 25,
    order: [],
    responsive: true,
    language: { search: "Search:", lengthMenu: "Show _MENU_ rows" }
  });
  
  // Initialize dashboard charts if on dashboard page
  if(typeof window.dashboardCharts !== 'undefined' && window.dashboardCharts) {
    if(document.getElementById('chartMonthly')) {
      new Chart(document.getElementById('chartMonthly'), {
        type: 'bar',
        data: {
          labels: window.mLabels || [],
          datasets: [
            { label:'Revenue', data: window.mRev || [], backgroundColor:'rgba(26,110,60,.7)', borderRadius:4 },
            { label:'Expense', data: window.mExp || [], backgroundColor:'rgba(220,53,69,.55)', borderRadius:4 },
          ]
        },
        options: { plugins:{legend:{position:'top'}}, scales:{ y:{ ticks:{ callback: v=>'₹'+v.toLocaleString('en-IN') } } } }
      });
    }
    
    if(document.getElementById('chartRev')) {
      new Chart(document.getElementById('chartRev'), {
        type: 'doughnut',
        data: {
          labels: window.revPartLabels || [],
          datasets: [{ data: window.revPartData || [],
            backgroundColor:['#1a6e3c','#28a745','#17a2b8','#6c757d','#f5a623'] }]
        },
        options:{ plugins:{ legend:{position:'bottom'} } }
      });
    }
    
    if(document.getElementById('chartExp')) {
      new Chart(document.getElementById('chartExp'), {
        type: 'bar',
        data: {
          labels: window.expPartLabels || [],
          datasets: [{ label:'Expense', data: window.expPartData || [],
            backgroundColor:'rgba(220,53,69,.65)', borderRadius:4 }]
        },
        options: { indexAxis:'y', plugins:{legend:{display:false}},
          scales:{ x:{ ticks:{ callback:v=>'₹'+v.toLocaleString('en-IN') } } } }
      });
    }
  }
});
</script>

<!-- Shared CRUD Modal -->
<div class="modal fade" id="crudModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="crudModalTitle">Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="crudModalBody"><!-- injected --></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="crudSaveBtn" onclick="crudSave()">
          <i class="bi bi-check-lg me-1"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete confirm modal -->
<div class="modal fade" id="delModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white"><h6 class="modal-title">Confirm Delete</h6>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">Are you sure you want to delete this record?</div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger btn-sm" id="delConfirmBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
let _crudEntity='', _crudId=0, _delEntity='', _delId=0;
const crudModal = new bootstrap.Modal('#crudModal');
const delModal  = new bootstrap.Modal('#delModal');

function openCrudAdd(entity) {
  _crudEntity = entity;
  _crudId = 0;
  document.getElementById('crudModalTitle').textContent = 'Add ' + entity.replace(/_/g, ' ');
  document.getElementById('crudModalBody').innerHTML = buildCrudForm(getCrudFields(entity));
  crudModal.show();
}

function openCrudEdit(entity, id) {
  _crudEntity = entity;
  _crudId = id;
  document.getElementById('crudModalTitle').textContent = 'Edit ' + entity.replace(/_/g, ' ');
  document.getElementById('crudModalBody').innerHTML = buildCrudForm(getCrudFields(entity));
  fetch(`crud_handler.php?action=get&entity=${entity}&id=${id}`)
    .then(r => r.json())
    .then(d => {
      if(d.success) {
        Object.entries(d.data).forEach(([k,v]) => {
          const el = document.getElementById('f_' + k);
          if(el) el.value = v ?? '';
        });
      } else {
        alert('Error: ' + d.message);
      }
    })
    .catch(e => alert('Error: ' + e.message));
  crudModal.show();
}

function buildCrudForm(fields) {
  let html = '<div class="row g-3">';
  fields.forEach(f => {
    if(f.type === 'hidden') return;
    let inp = '';
    if(f.type === 'select') {
      inp = `<select id="f_${f.name}" name="${f.name}" class="form-select form-select-sm">`;
      inp += '<option value="">— Select —</option>';
      if(f.options) {
        f.options.forEach(o => {
          inp += `<option value="${o.v}">${o.l}</option>`;
        });
      }
      inp += '</select>';
    } else if(f.type === 'textarea') {
      inp = `<textarea id="f_${f.name}" name="${f.name}" class="form-control form-control-sm" rows="2"></textarea>`;
    } else {
      inp = `<input type="${f.type||'text'}" id="f_${f.name}" name="${f.name}" class="form-control form-control-sm" placeholder="${f.label}">`;
    }
    const col = f.col || 'col-md-6';
    html += `<div class="${col}"><label class="form-label small fw-semibold">${f.label}</label>${inp}</div>`;
  });
  return html + '</div>';
}

function getCrudFields(entity) {
  const schemas = {
    'sales_egg': [
      {name:'sale_date',label:'Sale Date',type:'date'},
      {name:'particulars',label:'Particulars'},
      {name:'qty',label:'Qty',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'sales_feed': [
      {name:'sale_date',label:'Sale Date',type:'date'},
      {name:'particulars',label:'Particulars'},
      {name:'qty',label:'Qty',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'sales_culling': [
      {name:'sale_date',label:'Sale Date',type:'date'},
      {name:'particulars',label:'Particulars'},
      {name:'qty_birds',label:'Qty Birds',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
      {name:'manure_kg_estimate',label:'Manure Est (kg)',type:'number'},
    ],
    'sales_manure': [
      {name:'sale_date',label:'Sale Date',type:'date'},
      {name:'particulars',label:'Particulars'},
      {name:'qty',label:'Qty (kg)',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'sales_raw_material': [
      {name:'sale_date',label:'Sale Date',type:'date'},
      {name:'particulars',label:'Particulars'},
      {name:'qty',label:'Qty',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'sales_investment': [
      {name:'inv_date',label:'Investment Date',type:'date'},
      {name:'particulars',label:'Particulars'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_chick': [
      {name:'purchase_date',label:'Purchase Date',type:'date'},
      {name:'item',label:'Item'},
      {name:'seller',label:'Seller'},
      {name:'qty_birds',label:'Qty Birds',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
      {name:'chick_count',label:'Chick Count',type:'number'},
    ],
    'exp_feed_ingredient': [
      {name:'purchase_date',label:'Purchase Date',type:'date'},
      {name:'category',label:'Category'},
      {name:'item',label:'Item'},
      {name:'seller',label:'Seller'},
      {name:'qty_kg',label:'Qty (kg)',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_feeds': [
      {name:'purchase_date',label:'Purchase Date',type:'date'},
      {name:'item',label:'Item'},
      {name:'seller',label:'Seller'},
      {name:'qty_kg',label:'Qty (kg)',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_medicine': [
      {name:'purchase_date',label:'Purchase Date',type:'date'},
      {name:'item',label:'Item'},
      {name:'seller',label:'Seller'},
      {name:'qty',label:'Qty',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_salary': [
      {name:'payment_date',label:'Payment Date',type:'date'},
      {name:'employee_name',label:'Employee Name'},
      {name:'qty',label:'Days',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_labour': [
      {name:'payment_date',label:'Payment Date',type:'date'},
      {name:'labour_type',label:'Labour Type'},
      {name:'qty',label:'Days',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_rent': [
      {name:'payment_date',label:'Payment Date',type:'date'},
      {name:'place',label:'Place'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_misc': [
      {name:'expense_date',label:'Expense Date',type:'date'},
      {name:'item',label:'Item'},
      {name:'description',label:'Description',type:'textarea'},
      {name:'qty',label:'Qty',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_asset': [
      {name:'purchase_date',label:'Purchase Date',type:'date'},
      {name:'item',label:'Item'},
      {name:'seller',label:'Seller'},
      {name:'qty',label:'Qty',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'exp_accruals': [
      {name:'accrual_date',label:'Accrual Date',type:'date'},
      {name:'item',label:'Item'},
      {name:'seller',label:'Seller/Party'},
      {name:'qty',label:'Qty',type:'number'},
      {name:'rate',label:'Rate',type:'number'},
      {name:'amount',label:'Amount',type:'number'},
    ],
    'loan_transaction': [
      {name:'lender_id',label:'Lender',type:'select'},
      {name:'txn_date',label:'Transaction Date',type:'date'},
      {name:'loan_availed',label:'Loan Availed',type:'number'},
      {name:'balance',label:'Balance',type:'number'},
      {name:'interest_pct',label:'Interest %',type:'number'},
      {name:'interest_amount',label:'Interest Amount',type:'number'},
      {name:'amount_paid',label:'Amount Paid',type:'number'},
    ],
    'production_daily': [
      {name:'record_date',label:'Record Date',type:'date'},
      {name:'location',label:'Location'},
      {name:'shed',label:'Shed'},
      {name:'week_code',label:'Week Code'},
      {name:'alive',label:'Alive',type:'number'},
      {name:'mortality',label:'Mortality',type:'number'},
      {name:'total_mortality',label:'Total Mortality',type:'number'},
      {name:'eggs_produced',label:'Eggs Produced',type:'number'},
      {name:'production_pct',label:'Production %',type:'number'},
      {name:'egg_sales',label:'Egg Sales',type:'number'},
      {name:'local_sales',label:'Local Sales',type:'number'},
      {name:'damages',label:'Damages',type:'number'},
      {name:'egg_stock',label:'Egg Stock',type:'number'},
    ],
  };
  return schemas[entity] || [];
}

function crudSave() {
  const body = document.getElementById('crudModalBody');
  const fd = new FormData();
  fd.append('action', _crudId ? 'edit' : 'add');
  fd.append('entity', _crudEntity);
  if(_crudId) fd.append('id', _crudId);
  body.querySelectorAll('input,select,textarea').forEach(el => {
    if(el.name) fd.append(el.name, el.value);
  });
  document.getElementById('crudSaveBtn').disabled = true;
  fetch('crud_handler.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      document.getElementById('crudSaveBtn').disabled = false;
      if(d.success) {
        crudModal.hide();
        setTimeout(() => location.reload(), 300);
      } else {
        alert('Error: ' + d.message);
      }
    })
    .catch(e => {
      document.getElementById('crudSaveBtn').disabled = false;
      alert('Error: ' + e.message);
    });
}

function crudDelete(entity, id) {
  _delEntity = entity;
  _delId = id;
  delModal.show();
  document.getElementById('delConfirmBtn').onclick = () => {
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('entity', entity);
    fd.append('id', id);
    fetch('crud_handler.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        delModal.hide();
        if(d.success) {
          setTimeout(() => location.reload(), 300);
        } else {
          alert('Error: ' + d.message);
        }
      });
  };
}

// Quick delete for inline buttons
function crudQuickDelete(entity, id) {
  if(!confirm('Delete this record?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('entity', entity);
  fd.append('id', id);
  fetch('crud_handler.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if(d.success) {
        location.reload();
      } else {
        alert('Error: ' + d.message);
      }
    });
}
</script>
</body>
</html>
