
<?php
// These functions handle the exclusion UI and processing

/**
 * Handles the AJAX request to toggle exclusion status of an offline event
 */
function handleExclusionToggle() {
  
    // Validate input
    $remarkId = filter_input(INPUT_POST, 'remark_id', FILTER_VALIDATE_INT);
    $exclude = filter_input(INPUT_POST, 'exclude', FILTER_VALIDATE_BOOLEAN);
    
    if (!$remarkId) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid remark ID']);
        exit;
    }
    
    // Process the exclusion toggle
    $success = toggleRemarkExclusion($remarkId, $_SESSION['user_id'], $exclude);
    
    if ($success) {
        echo json_encode([
            'status' => 'success', 
            'message' => $exclude ? 'Remark excluded successfully' : 'Exclusion removed',
            'is_excluded' => $exclude
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update exclusion status']);
    }
    exit;
}

/**
 * Adds the exclusion button to the report table
 */
function renderExclusionButton($remarkId, $isExcluded) {
    $buttonClass = $isExcluded ? 'btn-warning' : 'btn-outline-secondary';
    $buttonText = $isExcluded ? 'Revert' : 'Exclude';
    $newState = $isExcluded ? '0' : '1';
    
    return <<<HTML
    <button class="exclusion-toggle btn btn-sm $buttonClass" 
            data-remark-id="$remarkId" 
            data-exclude="$newState">
        $buttonText
    </button>
HTML;
}

// Add this to your JavaScript file
// This handles the AJAX request and updates the UI accordingly
?>