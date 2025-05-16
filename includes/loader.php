<style>
    #loader {
      position: fixed;
      top: 20px;               /* position near top */
      left: 50%;               
      transform: translateX(-50%);
      z-index: 1050;
      width: auto;
      /* box-shadow removed to eliminate blur effect */
      /* border-radius and padding retained */
      border-radius: 8px;
      padding: 10px 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      background: transparent;  /* transparent instead of ‘none’ */
    }

    .loader-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 5px;
    }

    .spinner-wrapper {
      position: relative;
      width: 30px;
      height: 30px;
    }

    .spinner-ring {
      position: absolute;
      width: 100%;
      height: 100%;
      border: 2px solid transparent;
      border-top-color: #3498db;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    .spinner-ring:nth-child(2) {
      width: 80%;
      height: 80%;
      top: 10%;
      left: 10%;
      animation-duration: 0.8s;
      border-top-color: #e74c3c;
    }

    .spinner-ring:nth-child(3) {
      width: 60%;
      height: 60%;
      top: 20%;
      left: 20%;
      animation-duration: 0.6s;
      border-top-color: #f1c40f;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    #loader-text {
      font-size: 12px;
      color: #666;
      margin: 5px 0;
    }

    .progress-container {
      width: 100%;
      height: 3px;
      background-color: #f0f0f0;
      border-radius: 2px;
      overflow: hidden;
      margin: 5px 0;
    }

    .progress-bar {
      height: 100%;
      background-color: #3498db;
      width: 0%;
      transition: width 0.3s ease;
    }

    .loader-dots {
      display: flex;
      gap: 5px;
    }

    .loader-dot {
      width: 5px;
      height: 5px;
      background-color: #999;
      border-radius: 50%;
      animation: pulse 1s infinite alternate;
    }

    .loader-dot:nth-child(2) {
      animation-delay: 0.2s;
    }

    .loader-dot:nth-child(3) {
      animation-delay: 0.4s;
    }

    @keyframes pulse {
      0% { transform: scale(0.8); opacity: 0.5; }
      100% { transform: scale(1.2); opacity: 1; }
    }

    /* Utility to hide loader */
    .d-none {
      display: none !important;
    }
  </style>


  <div id="loader" class="d-none">
    <div class="loader-container">
      <div class="spinner-wrapper">
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
      </div>
      <div id="loader-text">Loading... 0s</div>
      <div class="progress-container">
        <div class="progress-bar"></div>
      </div>
      <div class="loader-dots">
        <div class="loader-dot"></div>
        <div class="loader-dot"></div>
        <div class="loader-dot"></div>
      </div>
    </div>
  </div>

