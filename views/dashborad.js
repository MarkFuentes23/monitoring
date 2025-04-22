
const refreshInterval = 15 * 60 * 1000; 
let refreshTimer;
let progressInterval;

// Loader timers
let loaderTimer, loaderStart;

// Kick things off on page load
$(document).ready(function() {
  startRefreshTimer();

  // Manual refresh button
  $('#refreshBtn').on('click', function() {
    refreshData(true);
  });

  // Cross‑tab trigger
  window.addEventListener('storage', function(e) {
    if (e.key === 'networkDataUpdated') {
      refreshData(false);
    }
  });
});

function startRefreshTimer() {
  clearTimeout(refreshTimer);
  refreshTimer = setTimeout(refreshData, refreshInterval);

  // reset the *refresh‑countdown* bar
  const progressBar = document.querySelector('.progress-bar');
  progressBar.style.width = '0%';

  clearInterval(progressInterval);
  // advance 100% over the 15‑minute span
  progressInterval = setInterval(() => {
    const currentPct = parseFloat(progressBar.style.width) || 0;
    const step = 100 / (refreshInterval / 1000);
    if (currentPct < 100) {
      progressBar.style.width = (currentPct + step) + '%';
    }
  }, 1000);
}

function refreshData(forceUpdate = false) {
  showLoader();

  const url = forceUpdate
    ? '?refresh=true&force=true'
    : '?refresh=true';

  $.ajax({
    url: url,
    type: 'GET',
    dataType: 'json'
  })
  .done(function(data) {
    updateDashboard(data);
    $('#lastUpdated').text('Last updated: ' + data.timestamp);
    startRefreshTimer();
  })
  .fail(function(xhr, status, error) {
    console.error('Error refreshing data:', error);
    startRefreshTimer();
  })
  .always(function() {
    hideLoader();
  });
}

// === your existing DOM‑update logic ===
function updateDashboard(data) {
  // … exactly as you had it …
}

// === replaced loader functions ===
// these come from your DataTables script and handle jQuery + clearInterval cleanly
function showLoader() {
  loaderStart = Date.now();
  $('#loader').removeClass('d-none');
  loaderTimer = setInterval(() => {
    const secs = Math.floor((Date.now() - loaderStart) / 1000);
    $('#loader-text').text(`Loading... ${secs}s`);
  }, 500);
}

function hideLoader() {
  clearInterval(loaderTimer);
  $('#loader-text').text('Loading... 0s');
  $('#loader').addClass('d-none');
}
