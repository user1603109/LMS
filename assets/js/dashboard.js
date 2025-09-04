// ASC Library Management System - Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    const searchBtn = document.getElementById('searchBtn');

    if (globalSearch && searchBtn) {
        searchBtn.addEventListener('click', performGlobalSearch);
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performGlobalSearch();
            }
        });
    }

    // Auto-refresh statistics every 5 minutes
    setInterval(refreshStatistics, 300000);

    // Initialize sidebar state
    initializeSidebar();

    // Add smooth scrolling to anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

function performGlobalSearch() {
    const searchTerm = document.getElementById('globalSearch').value.trim();
    
    if (searchTerm === '') {
        showAlert('Please enter a search term.', 'warning');
        return;
    }

    // Show loading state
    const searchBtn = document.getElementById('searchBtn');
    const originalContent = searchBtn.innerHTML;
    searchBtn.innerHTML = '<span class="loading"></span>';
    searchBtn.disabled = true;

    // Perform search via AJAX
    fetch('api/search.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            query: searchTerm,
            type: 'global'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySearchResults(data.results);
        } else {
            showAlert(data.message || 'Search failed. Please try again.', 'danger');
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        showAlert('Search failed. Please try again.', 'danger');
    })
    .finally(() => {
        // Restore button state
        searchBtn.innerHTML = originalContent;
        searchBtn.disabled = false;
    });
}

function displaySearchResults(results) {
    // Create modal for search results
    const modalHtml = `
        <div class="modal fade" id="searchResultsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-search me-2"></i>Search Results
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${generateSearchResultsHtml(results)}
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('searchResultsModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('searchResultsModal'));
    modal.show();
}

function generateSearchResultsHtml(results) {
    if (results.length === 0) {
        return `
            <div class="text-center py-4">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="text-muted">No results found for your search.</p>
            </div>
        `;
    }

    let html = '<div class="search-results">';
    
    results.forEach(result => {
        html += `
            <div class="search-result-item">
                <div class="result-icon">
                    <i class="fas fa-${getResultIcon(result.type)}"></i>
                </div>
                <div class="result-content">
                    <h6 class="result-title">
                        <a href="${result.url}" class="text-decoration-none">${result.title}</a>
                    </h6>
                    <p class="result-meta text-muted">
                        ${result.type} • ${result.date}
                    </p>
                    <p class="result-description">${result.description}</p>
                </div>
            </div>
        `;
    });

    html += '</div>';
    return html;
}

function getResultIcon(type) {
    const icons = {
        'book': 'book',
        'patron': 'user',
        'academic_coursework': 'graduation-cap',
        'electronic_resource': 'laptop',
        'circulation': 'exchange-alt'
    };
    return icons[type] || 'file';
}

function refreshStatistics() {
    fetch('api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatisticsCards(data.stats);
            }
        })
        .catch(error => {
            console.error('Failed to refresh statistics:', error);
        });
}

function updateStatisticsCards(stats) {
    // Update each statistic card with animation
    const cards = [
        { selector: '.stat-card-primary .stat-number', value: stats.books },
        { selector: '.stat-card-success .stat-number', value: stats.patrons },
        { selector: '.stat-card-warning .stat-number', value: stats.circulations },
        { selector: '.stat-card-danger .stat-number', value: stats.overdue }
    ];

    cards.forEach(card => {
        const element = document.querySelector(card.selector);
        if (element) {
            animateNumber(element, parseInt(element.textContent.replace(/,/g, '')), card.value);
        }
    });
}

function animateNumber(element, start, end) {
    const duration = 1000;
    const startTime = performance.now();

    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.round(start + (end - start) * progress);
        element.textContent = current.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }

    requestAnimationFrame(updateNumber);
}

function initializeSidebar() {
    // Remember sidebar state
    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'open') {
        // Auto-open sidebar on desktop
        if (window.innerWidth >= 992) {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                const offcanvas = new bootstrap.Offcanvas(sidebar);
                offcanvas.show();
            }
        }
    }

    // Save sidebar state when toggled
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.addEventListener('hidden.bs.offcanvas', function() {
            localStorage.setItem('sidebarState', 'closed');
        });
        
        sidebar.addEventListener('shown.bs.offcanvas', function() {
            localStorage.setItem('sidebarState', 'open');
        });
    }
}

function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 100px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', alertHtml);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Export functions for use in other modules
window.ASC = {
    showAlert,
    formatDate,
    formatCurrency,
    performGlobalSearch
};