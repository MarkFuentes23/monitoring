//dashboard.js = js
const refreshInterval = 15 * 60 * 1000; // 15 minutes in milliseconds
let refreshTimer;
let progressInterval;
let loaderTimeout;

function startRefreshTimer() {
    clearTimeout(refreshTimer);
    refreshTimer = setTimeout(() => {
      // Force full page reload after 15min timeout
      window.location.reload();
    }, refreshInterval);

// Update progress bar
const progressBar = document.querySelector('.progress-bar');
progressBar.style.width = '0%';

clearInterval(progressInterval);
progressInterval = setInterval(() => {
 const currentWidth = parseFloat(progressBar.style.width) || 0;
 if (currentWidth < 100) {
     progressBar.style.width = (currentWidth + (100 / (refreshInterval / 1000))) + '%';
 }
}, 1000);
}

function refreshData(forceUpdate = false) {
    showLoader();
  
    $('#offlineTable tbody').html('<tr><td colspan="4" class="text-center">Loading offline devices...</td></tr>');
    $('#latencyTable tbody').html('<tr><td colspan="4" class="text-center">Loading latency data...</td></tr>');
  
    const url = forceUpdate ? '?refresh=true&force=true' : '?refresh=true';
  
    $.ajax({
      url,
      type: 'GET',
      dataType: 'json',
      success(data) {
        updateDashboard(data);
        $('#lastUpdated').text('Last updated: ' + data.timestamp);
        startRefreshTimer();
  
        // short delay so the UI updates are visible under the loader
        setTimeout(() => {
          // hide loader AND reload the full page to pick up any PHP changes
          hideLoader(true);
  
          if (data.newlyOfflineDevices?.length) {
            showOfflineAlert(data.newlyOfflineDevices);
          }
        }, 500);
  
        // broadcast to other tabs
        localStorage.setItem('networkDataUpdated', Date.now());
      },
      error(xhr, status, error) {
        console.error('Error refreshing data:', error);
        $('#offlineTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        $('#latencyTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        startRefreshTimer();
        hideLoader();  // no reload on error
      }
    });
  }
  

function showOfflineAlert(offlineDevices) {
// Create alert content with device list
let alertContent = '<div class="offline-alert-content">';
alertContent += '<ul style="text-align: left; padding-left: 20px;">';

offlineDevices.forEach(device => {
 alertContent += `<li><strong>${device.ip_address}</strong> - ${device.location} (${device.category})</li>`;
});

alertContent += '</ul></div>';

// Show SweetAlert2
Swal.fire({
 title: 'Network Alert!',
 html: `<div>The following ${offlineDevices.length > 1 ? 'devices are' : 'device is'} now offline:</div>` + alertContent,
 icon: 'error',
 confirmButtonText: 'Acknowledge',
 confirmButtonColor: '#dc3545',
 showCloseButton: true,
 customClass: {
     popup: 'offline-alert-popup',
     title: 'offline-alert-title'
 }
});

// Add some CSS to ensure the alert is prominent
const style = document.createElement('style');
style.textContent = `
 .offline-alert-popup {
     border-left: 5px solid #dc3545;
 }
 .offline-alert-title {
     color: #dc3545;
     font-weight: bold;
 }
 .offline-alert-content {
     margin-top: 15px;
 }
`;
document.head.appendChild(style);
}

function updateDashboard(data) {
    // now selects your <h2 class="card-value …">
    animateCounter($('.card-value.total'),   data.summary.total);
    animateCounter($('.card-value.online'),  data.summary.online);
    animateCounter($('.card-value.offline'), data.summary.offline);
  
    // Category cards (unchanged)…
    for (const [category, stats] of Object.entries(data.categories)) {
      const card = $(`.card-header:contains("${category}")`).closest('.card');
      if (!card.length) continue;
      card.find('p:contains("Total IPs:")').html(`<strong>Total IPs:</strong> ${stats.total}`);
      card.find('p:contains("Online:")').html(`<strong>Online:</strong> <span class="online">${stats.online}</span>`);
      card.find('p:contains("Offline:")').html(`<strong>Offline:</strong> <span class="offline">${stats.offline}</span>`);
      card.find('p:contains("Avg Latency:")').html(`<strong>Avg Latency:</strong> ${stats.avg_latency} ms`);
    }
  
    // Offline-devices table…
    let offlineHtml = '';
    if (data.offlineDevices.length) {
      data.offlineDevices.forEach(d => {
        offlineHtml += `
          <tr>
            <td>${d.ip_address}</td>
            <td>${d.location||'Unknown'}</td>
            <td>${d.category||'Unknown'}</td>
            <td><span class="badge bg-danger">Offline</span></td>
          </tr>`;
      });
    } else {
      offlineHtml = '<tr><td colspan="4" class="text-center">No offline devices</td></tr>';
    }
    $('#offlineTable tbody').html(offlineHtml);
  
    // High-latency table…
    let latencyHtml = '';
    if (data.highLatencyDevices.length) {
      data.highLatencyDevices.forEach(d => {
        const cls = d.latency > 200 ? 'high-latency' : '';
        latencyHtml += `
          <tr class="${cls}">
            <td>${d.ip_address}</td>
            <td>${d.location||'Unknown'}</td>
            <td>${d.category||'Unknown'}</td>
            <td>${d.latency} ms</td>
          </tr>`;
      });
    } else {
      latencyHtml = '<tr><td colspan="4" class="text-center">No high latency devices</td></tr>';
    }
    $('#latencyTable tbody').html(latencyHtml);
  }
  

// Animate counter for smoother transitions
function animateCounter(element, targetValue) {
const $element = $(element);
const startValue = parseInt($element.text()) || 0;
const duration = 1000; // 1 second
const frameRate = 60;
const increment = (targetValue - startValue) / (duration / (1000 / frameRate));

let currentValue = startValue;
const counter = setInterval(() => {
 currentValue += increment;
 if ((increment > 0 && currentValue >= targetValue) || 
     (increment < 0 && currentValue <= targetValue)) {
     clearInterval(counter);
     $element.text(targetValue);
 } else {
     $element.text(Math.round(currentValue));
 }
}, 1000 / frameRate);
}

let loaderTimer, loaderStart;
function showLoader() {
    clearTimeout(loaderTimeout);
    loaderStart = Date.now();
    document.getElementById('loader').classList.remove('d-none');
    clearInterval(loaderTimer);
    loaderTimer = setInterval(() => {
      const s = Math.floor((Date.now() - loaderStart) / 1000);
      document.getElementById('loader-text').textContent = `Loading… ${s}s`;
    }, 500);
    loaderTimeout = setTimeout(() => {
      hideLoader();
      console.error('Loading timeout occurred');
      Swal.fire({
        title: 'Loading Error',
        text: 'The operation timed out. Please try again later.',
        icon: 'error',
        confirmButtonText: 'OK'
      });
    }, 60000);
  }
  

  function hideLoader(reloadPage = false) {
    clearInterval(loaderTimer);
    clearTimeout(loaderTimeout);
    document.getElementById('loader-text').textContent = 'Loading… 0s';
    document.getElementById('loader').classList.add('d-none');
    if (reloadPage) {
      window.location.reload();
    }
  }

// For the refresh button, modify the click handler to show the loader immediately
$('#refreshBtn').on('click', function() {
    showLoader(); // Show loader right away
    refreshData(true); // Force update
});
// Manual refresh button
$('#refreshBtn').on('click', function() {
refreshData(true); // Force update
});

// Start the auto-refresh timer when page loads
$(document).ready(function() {
startRefreshTimer();

// Listen for events from monitoring page
window.addEventListener('storage', function(e) {
 if (e.key === 'networkDataUpdated') {
     refreshData(false); // Refresh without forcing ping
 }
});
});



$(document).ready(function() {
    $('#offlineTable, #latencyTable').DataTable({
    paging:   true,   // enable pagination
    searching:true,   // enable search box
    ordering: true,   // enable column sorting
    info:     true    // show “Showing X to Y of Z entries”
    });
    });