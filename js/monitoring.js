document.addEventListener('DOMContentLoaded', () => {
  // Initialize DataTables with smaller pagination
  $('.datatable').each(function() {
    $(this).DataTable({
      responsive: true,
      pageLength: 5,
      lengthMenu: [[5, 10, 25], [5, 10, 25]],
      order: [[0, 'desc']], // Sort by date desc
      language: {
        emptyTable: "No data",
        zeroRecords: "No matches",
        lengthMenu: "Show _MENU_",
        info: "_START_-_END_ of _TOTAL_",
        infoEmpty: "0 records",
        infoFiltered: "(from _MAX_)",
        search: "",
        searchPlaceholder: "Search..."
      },
      dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
           "<'row'<'col-sm-12'tr>>" +
           "<'row'<'col-sm-5'i><'col-sm-7'p>>"
    });
  });

  // clickable rows
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', e => {
      if (e.target.closest('button') || e.target.closest('a')) return;
      window.location = row.dataset.href;
    });
  });

  // details modal
  document.getElementById('dataModal')
    .addEventListener('show.bs.modal', e => {
      const btn = e.relatedTarget;
      ['date','ip','description','location','category']
        .forEach(f => document.getElementById(`modal-${f}`)
                         .textContent = btn.dataset[f]);
    });

  // Set auto-refresh interval (15 minutes)
  const intervalMs = 900000;
  let intervalId = null;
  let scheduleId = null;

  function startAutoRefresh() {
    // Clear any existing interval
    if (intervalId) clearInterval(intervalId);
    // Start new interval
    intervalId = setInterval(refreshLatency, intervalMs);
  }

  // Start auto-refresh on load
  startAutoRefresh();

  // Manual refresh button - triggers refresh and resets auto timer
  document.getElementById('refreshAll').addEventListener('click', () => {
    refreshLatency();
    startAutoRefresh();
  });

  // Schedule Daily Resume (skip Sundays) with SweetAlert2
  document.getElementById('scheduleResume')
    .addEventListener('click', () => {
      const t = document.getElementById('resumeTime').value;
      if (!t) {
        return Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Please select a time first.'
        });
      }
      const [h,m,s] = t.split(':').map((v,i)=>Number(v)||(i===2?0:0));
      if (scheduleId) clearTimeout(scheduleId);

      (function scheduleNext(){
        const now = new Date();
        let resumeAt = new Date(
          now.getFullYear(), now.getMonth(), now.getDate(),
          h, m, s
        );
        // bump if past or Sunday (day 0)
        while (resumeAt <= now || resumeAt.getDay() === 0) {
          resumeAt.setDate(resumeAt.getDate() + 1);
        }
        scheduleId = setTimeout(() => {
          showLoader();
          refreshLatency();
          startAutoRefresh();
          scheduleNext();
        }, resumeAt - now);
      })();

      Swal.fire({
        icon: 'success',
        title: 'Scheduled',
        text: `Ping will resume daily at ${t}, skipping Sundays.`,
        timer: 3000,
        showConfirmButton: false
      });
    });
});

// loader UI
let loaderTimer, loaderStart;
function showLoader() {
  loaderStart = Date.now();
  document.getElementById('loader').classList.remove('d-none');
  loaderTimer = setInterval(() => {
    const s = Math.floor((Date.now() - loaderStart)/1000);
    document.getElementById('loader-text').textContent = `Loading... ${s}s`;
  }, 500);
}
function hideLoader() {
  clearInterval(loaderTimer);
  document.getElementById('loader-text').textContent = 'Loading... 0s';
  document.getElementById('loader').classList.add('d-none');
}

// ping + update all tables
function refreshLatency() {
  showLoader();
  fetch('/backend/get_latency.php')
    .then(res => res.ok ? res.json() : Promise.reject())
    .then(data => {
      data.forEach(r => {
        // Get safe ID for the element
        const category = r.category ? r.category.toLowerCase().replace(/\s+/g, '-') : 'uncategorized';
        const statusId = `status-${category}-${r.id}`;
        
        // Update status if element exists
        const statusElement = document.getElementById(statusId);
        if (statusElement) {
          statusElement.innerHTML = `
            <span class="badge badge-smaller ${r.status === 'online' ? 'bg-success' : 'bg-danger'}">
              ${r.status === 'online' ? 'Online' : 'Offline'}
            </span>
            <span class="badge badge-smaller ${r.latency >= 100 ? 'bg-warning' : 'bg-info'}">
              ${r.latency} ms
            </span>
            <span class="badge badge-smaller ${r.latency >= 100 ? 'bg-danger' : 'bg-success'}">
              ${r.latency >= 100 ? 'High Latency' : 'Low Latency'}
            </span>`;
        }
      });
    })
    .catch(console.error)
    .finally(hideLoader);
}


