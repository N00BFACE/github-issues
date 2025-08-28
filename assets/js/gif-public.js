jQuery(document).ready(function($) {
    'use strict';

    // Initialize frontend functionality
    initGitHubIssuesFrontend();

    function initGitHubIssuesFrontend() {
        // Add smooth scrolling for issue links
        $('.gif-issue-title a').on('click', function(e) {
            // Let the default behavior happen (open in new tab)
            // This is just for potential future enhancements
        });

        // Add copy link functionality (optional enhancement)
        addCopyLinkFunctionality();

        // Add issue filtering (if needed in future)
        initializeFiltering();
    }

    function addCopyLinkFunctionality() {
        // Add a copy link button to each issue (optional feature)
        $('.gif-issue-item').each(function() {
            const $issue = $(this);
            const issueLink = $issue.find('.gif-issue-title a').attr('href');
            
            if (issueLink) {
                // Add copy button (hidden by default, shown on hover)
                const $copyBtn = $('<button class="gif-copy-link" title="Copy link to issue">📋</button>');
                $issue.find('.gif-issue-header').append($copyBtn);
                
                $copyBtn.on('click', function(e) {
                    e.preventDefault();
                    copyToClipboard(issueLink);
                    
                    // Show feedback
                    const originalText = $copyBtn.text();
                    $copyBtn.text('✓').addClass('copied');
                    
                    setTimeout(() => {
                        $copyBtn.text(originalText).removeClass('copied');
                    }, 2000);
                });
            }
        });
    }

    function copyToClipboard(text) {
        // Modern clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
            } catch (err) {
                console.error('Failed to copy text: ', err);
            }
            
            document.body.removeChild(textArea);
        }
    }

    function initializeFiltering() {
        // Check if we have multiple issues and should show filtering
        const $issuesList = $('.gif-issues-frontend-list');
        const $issues = $issuesList.find('.gif-issue-item');
        
        if ($issues.length > 5) {
            // Add a simple state filter
            const $header = $('.gif-issues-frontend-header');
            const $filterContainer = $('<div class="gif-issues-filter"></div>');
            
            $filterContainer.html(`
                <div class="gif-filter-buttons">
                    <button class="gif-filter-btn active" data-filter="all">All</button>
                    <button class="gif-filter-btn" data-filter="open">Open</button>
                    <button class="gif-filter-btn" data-filter="closed">Closed</button>
                </div>
            `);
            
            $header.append($filterContainer);
            
            // Add filter functionality
            $('.gif-filter-btn').on('click', function() {
                const $btn = $(this);
                const filter = $btn.data('filter');
                
                // Update active button
                $('.gif-filter-btn').removeClass('active');
                $btn.addClass('active');
                
                // Filter issues
                if (filter === 'all') {
                    $issues.show();
                } else {
                    $issues.hide();
                    $(`.gif-issue-${filter}`).show();
                }
            });
        }
    }

    // Add some CSS for the dynamic elements
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .gif-copy-link {
                background: none;
                border: none;
                font-size: 14px;
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.2s ease;
                margin-left: 8px;
            }
            
            .gif-issue-item:hover .gif-copy-link {
                opacity: 0.6;
            }
            
            .gif-copy-link:hover {
                opacity: 1 !important;
            }
            
            .gif-copy-link.copied {
                color: #28a745;
            }
            
            .gif-issues-filter {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #e1e4e8;
            }
            
            .gif-filter-buttons {
                display: flex;
                gap: 8px;
            }
            
            .gif-filter-btn {
                padding: 6px 12px;
                border: 1px solid #d0d7de;
                background: #f6f8fa;
                color: #24292e;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.2s ease;
            }
            
            .gif-filter-btn:hover {
                background: #f3f4f6;
                border-color: #d0d7de;
            }
            
            .gif-filter-btn.active {
                background: #0366d6;
                color: white;
                border-color: #0366d6;
            }
            
            @media (max-width: 480px) {
                .gif-filter-buttons {
                    flex-wrap: wrap;
                }
                
                .gif-filter-btn {
                    font-size: 13px;
                    padding: 5px 10px;
                }
            }
        `)
        .appendTo('head');
});
