// Configuration
const REFRESH_INTERVAL = 15 * 60 * 1000; // 15 minutes

let refreshTimer;
let progressInterval;
let loaderTimeout;
let offlineTable, latencyTable; // Store DataTable instances

// Initialize tables when document is ready
$(document).ready(function() {
    // Initialize DataTables
    offlineTable = $('#offlineTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        language: {
            emptyTable: "No offline devices"
        }
    });
    
    latencyTable = $('#latencyTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true
    });
    
    // Start the auto-refresh timer
    startRefreshTimer();
    
    // Listen for events from monitoring page
    window.addEventListener('storage', function(e) {
        if (e.key === 'networkDataUpdated') {
            refreshData(false); // Refresh without forcing ping
        }
    });
    
    // Manual refresh button
    $('#refreshBtn').on('click', function() {
        showLoader(); // Show loader right away
        refreshData(true); // Force update
    });
    
    // Initialize monthly statistics tables
    var monthlyTable = $('#monthlyTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: 'Excel',
                className: 'btn btn-success btn-sm d-none',
                title: 'Monthly Network Statistics'
            },
            {
                extend: 'pdf',
                text: 'PDF',
                className: 'btn btn-danger btn-sm d-none',
                title: 'Monthly Network Statistics'
            }
        ],
        paging: false,
        searching: false,
        info: false
    });
    
    var detailedIPTable = $('#detailedIPTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: 'Excel',
                className: 'btn btn-success btn-sm d-none',
                title: 'Detailed IP Monthly Statistics'
            },
            {
                extend: 'pdf',
                text: 'PDF',
                className: 'btn btn-danger btn-sm d-none',
                title: 'Detailed IP Monthly Statistics'
            }
        ],
        pageLength: 25
    });
    
    // Export buttons functionality
    $('#exportMonthlyExcel').click(function() {
        monthlyTable.button('.buttons-excel').trigger();
    });
    
    $('#exportMonthlyPDF').click(function() {
        monthlyTable.button('.buttons-pdf').trigger();
    });
    
    // Month form submission
    $('#monthForm').on('submit', function(e) {
        e.preventDefault();
        let month = $('#monthSelect').val();
        let year = $('#yearSelect').val();
        window.location.href = 'dashboard.php?month=' + month + '&year=' + year;
    });
});

function startRefreshTimer() {
    clearTimeout(refreshTimer);
    refreshTimer = setTimeout(() => {
        // Force full page reload after 15min timeout
        window.location.reload();
    }, REFRESH_INTERVAL);

    // Update progress bar
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = '0%';

        clearInterval(progressInterval);
        progressInterval = setInterval(() => {
            const currentWidth = parseFloat(progressBar.style.width) || 0;
            if (currentWidth < 100) {
                progressBar.style.width = (currentWidth + (100 / (REFRESH_INTERVAL / 1000))) + '%';
            }
        }, 1000);
    }
}

function refreshData(forceUpdate = false) {
    showLoader();
    
    const url = forceUpdate ? '?refresh=true&force=true' : '?refresh=true';
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success(data) {
            updateDashboard(data);
            $('#lastUpdated').text('Last updated: ' + data.timestamp);
            startRefreshTimer();
            
            // short delay so the UI updates are visible under the loader
            setTimeout(() => {
                hideLoader();
                
                if (data.newlyOfflineDevices?.length) {
                    showOfflineAlert(data.newlyOfflineDevices);
                }
            }, 500);
            
            // broadcast to other tabs
            localStorage.setItem('networkDataUpdated', Date.now());
        },
        error(xhr, status, error) {
            console.error('Error refreshing data:', error);
            
            // Clear and update tables on error
            offlineTable.clear().draw();
            latencyTable.clear().draw();
            
            startRefreshTimer();
            hideLoader();
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
    // Update summary counters
    animateCounter($('.card-value.total'), data.summary.total);
    animateCounter($('.card-value.online'), data.summary.online);
    animateCounter($('.card-value.offline'), data.summary.offline);
  
    // Update category cards
    for (const [category, stats] of Object.entries(data.categories)) {
        const card = $(`.category-card .card-header:contains("${category}")`).closest('.category-card');
        if (!card.length) continue;
        
        card.find('.d-flex:contains("Total IPs") .fw-bold').text(stats.total);
        card.find('.d-flex:contains("Online") .online').text(stats.online);
        card.find('.d-flex:contains("Offline") .offline').text(stats.offline);
        card.find('.d-flex:contains("Avg Latency") .fw-bold').text(stats.avg_latency + ' ms');
    }
  
    // Update offline devices table using DataTables API
    offlineTable.clear();
    if (data.offlineDevices && data.offlineDevices.length) {
        data.offlineDevices.forEach(d => {
            offlineTable.row.add([
                d.ip_address,
                d.location || 'Unknown',
                d.category || 'Unknown',
                d.description || 'No description',
                '<span class="badge bg-danger badge-sm">Offline</span>'
            ]);
        });
    }
    offlineTable.draw();
  
    // Update latency table using DataTables API
    latencyTable.clear();
    if (data.highLatencyDevices && data.highLatencyDevices.length) {
        data.highLatencyDevices.forEach(d => {
            const isHigh = parseFloat(d.latency) > 100;
            const badgeClass = isHigh ? 'bg-danger' : 'bg-success';
            const status = isHigh ? 'High' : 'Low';
            
            // Add the row data
            const row = latencyTable.row.add([
                d.ip_address,
                d.location || 'Unknown',
                d.category || 'Unknown',
                d.description || 'No description',
                `<div class="d-flex justify-content-between align-items-center">
                  <span>${d.latency} ms</span>
                  <span class="badge ${badgeClass} badge-sm">${status}</span>
                </div>`
            ]).draw(false).node();
            
            // Add row class if high latency
            if (isHigh) {
                $(row).addClass('high-latency');
            } else {
                $(row).addClass('low-latency');
            }
        });
    }
    latencyTable.draw();
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