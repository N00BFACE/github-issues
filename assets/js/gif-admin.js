jQuery(document).ready(function($) {
    'use strict';

    // Wait for the form to be available
    const form = document.getElementById('gif-settings-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
    e.preventDefault();

        // Validate form inputs
        const repositoryUrl = document.getElementById('github_repository_url').value.trim();
        const accessToken = document.getElementById('github_personal_access_token').value.trim();

        if (!repositoryUrl || !accessToken) {
            showMessage(gifAdmin.strings.invalidUrl, 'error');
            return;
        }

        // Validate GitHub URL format
        const githubUrlPattern = /^https:\/\/github\.com\/[^\/]+\/[^\/]+\/?$/;
        if (!githubUrlPattern.test(repositoryUrl)) {
            showMessage(gifAdmin.strings.invalidUrl, 'error');
            return;
        }
        
        // Additional validation
        if (accessToken.length < 10) {
            showMessage('Access token seems too short. Please check your GitHub personal access token.', 'error');
            return;
        }

        // Show loading state
        showLoading(true);

        // Extract owner and repo from URL
        const urlParts = repositoryUrl.replace(/\/$/, '').split('/');
        const repoOwner = urlParts[urlParts.length - 2];
        const repoName = urlParts[urlParts.length - 1];

        // Fetch issues from GitHub using native fetch API
        fetchGitHubIssues(repoOwner, repoName, accessToken);
    });

    // Handle "Use Saved Settings" button
    const useSavedSettingsBtn = document.getElementById('use-saved-settings');
    if (useSavedSettingsBtn) {
        useSavedSettingsBtn.addEventListener('click', function() {
            const repoUrlField = document.getElementById('github_repository_url');
            const tokenField = document.getElementById('github_personal_access_token');
            
            if (gifAdmin.savedSettings.repositoryUrl) {
                repoUrlField.value = gifAdmin.savedSettings.repositoryUrl;
            }
            
            if (gifAdmin.savedSettings.accessToken) {
                tokenField.value = gifAdmin.savedSettings.accessToken;
            }
            
            // Show a brief confirmation
            showMessage('Saved settings loaded successfully!', 'success');
            
            // Auto-hide the success message after 2 seconds
            setTimeout(() => {
                const messages = document.querySelectorAll('.gif-message-success');
                messages.forEach(msg => {
                    if (msg.parentNode) {
                        msg.remove();
                    }
                });
            }, 2000);
        });
    }
    
    // Add a simple test button for debugging
    const testButton = document.createElement('button');
    testButton.type = 'button';
    testButton.className = 'button button-secondary';
    testButton.textContent = 'Test Basic Fetch';
    testButton.style.marginLeft = '10px';
    
    testButton.addEventListener('click', function() {
        const repositoryUrl = document.getElementById('github_repository_url').value.trim();
        const accessToken = document.getElementById('github_personal_access_token').value.trim();
        
        if (!repositoryUrl || !accessToken) {
            showMessage('Please enter repository URL and access token first.', 'error');
            return;
        }
        
        // Extract owner and repo from URL
        const urlParts = repositoryUrl.replace(/\/$/, '').split('/');
        const repoOwner = urlParts[urlParts.length - 2];
        const repoName = urlParts[urlParts.length - 1];
        
        // Test basic issue fetching only
        testBasicFetch(repoOwner, repoName, accessToken);
    });
    
        // Insert test button after the form
    const formElement = document.getElementById('gif-settings-form');
    if (formElement) {
        formElement.parentNode.insertBefore(testButton, formElement.nextSibling);
        
        // Add debugging info
        const debugInfo = document.createElement('div');
        debugInfo.className = 'gif-debug-info';
        debugInfo.style.cssText = 'margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 4px; font-size: 12px;';
        debugInfo.innerHTML = `
            <h4>Debug Information:</h4>
            <p><strong>Browser:</strong> ${navigator.userAgent}</p>
            <p><strong>Async Support:</strong> ${typeof fetch === 'function' ? '✅ Fetch API Available' : '❌ Fetch API Not Available'}</p>
            <p><strong>Console:</strong> Open browser developer tools (F12) to see detailed logs</p>
            <p><strong>Test:</strong> Use the "Test Basic Fetch" button to test basic functionality first</p>
        `;
        formElement.parentNode.insertBefore(debugInfo, formElement.nextSibling);
    }
    
    // Simple test function for basic issue fetching
    async function testBasicFetch(owner, repo, accessToken) {
        try {
            showMessage('Testing basic issue fetch...', 'info');
            showLoading(true);
            
            console.log('Testing basic fetch for:', owner, repo);
            
            // Just fetch issues without projects
            const issues = await fetchGitHubData(
                `https://api.github.com/repos/${owner}/${repo}/issues?state=all&per_page=5`,
                accessToken,
                'test issues'
            );
            
            console.log('Test fetch successful:', issues.length, 'issues');
            showMessage(`Test successful! Found ${issues.length} issues.`, 'success');
            
            // Display basic issues
            displayIssues(issues, owner, repo);
            
        } catch (error) {
            console.error('Test fetch failed:', error);
            showMessage(`Test failed: ${error.message}`, 'error');
        } finally {
            showLoading(false);
        }
    }
    
    async function fetchGitHubIssues(owner, repo, accessToken) {
        try {
            // Get per_page from settings or default to 10
            const perPage = gifAdmin.savedSettings.perPage || 10;
            
            // Show loading message
            showMessage('Fetching issues and project data...', 'info');
            
            console.log('Starting GitHub API calls for:', owner, repo);
            
            // Fetch issues first
            console.log('Fetching issues...');
            const issues = await fetchGitHubData(
                `https://api.github.com/repos/${owner}/${repo}/issues?state=all&per_page=${perPage}`,
                accessToken,
                'issues'
            );
            
            console.log('Issues fetched:', issues.length);
            
            // Try to fetch projects (optional - won't break if it fails)
            let projectsWithColumns = [];
            try {
                console.log('Fetching projects (classic REST)...');
                let projects = await fetchGitHubProjectsClassic(
                    `https://api.github.com/repos/${owner}/${repo}/projects`,
                    accessToken,
                    'projects'
                );
                
                console.log('Projects found:', projects.length);
                
                if (projects.length === 0) {
                    // Fallback to GraphQL Projects (v2)
                    console.log('No classic projects found. Trying GraphQL (Projects v2)...');
                    try {
                        const orgOwner = owner; // owner can be user or org
                        const gql = `
                            query($owner: String!, $repo: String!, $first: Int!) {
                              repository(owner: $owner, name: $repo) {
                                projectsV2(first: $first) {
                                  nodes {
                                    id
                                    title
                                    url
                                    fields(first: 50) { nodes { id name dataType } }
                                  }
                                }
                              }
                            }
                        `;
                        const data = await fetchGitHubGraphQL(accessToken, gql, { owner: orgOwner, repo: repo, first: 20 }, 'projectsV2');
                        const projectsV2 = (data && data.repository && data.repository.projectsV2 && data.repository.projectsV2.nodes) || [];
                        // We can't get cards via a simple query without item iteration; set empty columns to maintain shape
                        projectsWithColumns = projectsV2.map(p => ({ id: p.id, name: p.title, html_url: p.url, columns: [] }));
                    } catch (e) {
                        console.warn('GraphQL Projects v2 fetch failed:', e);
                    }
                } else if (projects.length > 0) {
                    // Fetch project boards and columns for each project
                    projectsWithColumns = await Promise.all(
                        projects.map(async (project) => {
                            try {
                                console.log(`Fetching columns for project: ${project.name}`);
                                const columns = await fetchGitHubProjectsClassic(
                                    `https://api.github.com/projects/${project.id}/columns`,
                                    accessToken,
                                    'project columns'
                                );
                                
                                console.log(`Columns found for ${project.name}:`, columns.length);
                                
                                // Fetch cards (issues) in each column
                                const columnsWithCards = await Promise.all(
                                    columns.map(async (column) => {
                                        try {
                                            console.log(`Fetching cards for column: ${column.name}`);
                                            const cards = await fetchGitHubProjectsClassic(
                                                `https://api.github.com/projects/columns/${column.id}/cards`,
                                                accessToken,
                                                'project cards'
                                            );
                                            console.log(`Cards found in ${column.name}:`, cards.length);
                                            return { ...column, cards };
                                        } catch (error) {
                                            console.warn(`Could not fetch cards for column ${column.name}:`, error);
                                            return { ...column, cards: [] };
                                        }
                                    })
                                );
                                
                                return { ...project, columns: columnsWithCards };
                            } catch (error) {
                                console.warn(`Could not fetch columns for project ${project.name}:`, error);
                                return { ...project, columns: [] };
                            }
                        })
                    );
                }
            } catch (error) {
                console.warn('Project fetching failed, continuing with basic issues only:', error);
                projectsWithColumns = [];
            }
            
            // Enhance issues with project data (Classic if available)
            console.log('Enhancing issues with project data...');
            let enhancedIssues = issues.map(issue => {
                const projectData = getIssueProjectData(issue, projectsWithColumns);
                return { ...issue, project_data: projectData };
            });

            // If no classic projects found, try Projects v2 (GraphQL) per-issue to capture status
            if (projectsWithColumns.length === 0 && enhancedIssues.length > 0) {
                console.log('No classic projects detected, enriching with Projects v2 (GraphQL)...');
                const graphqlEnriched = await Promise.all(
                    enhancedIssues.map(async (issue) => {
                        try {
                            const v2 = await fetchIssueProjectsV2(owner, repo, issue.number, accessToken);
                            return { ...issue, project_data: v2 };
                        } catch (e) {
                            console.warn(`GraphQL enrichment failed for issue #${issue.number}`, e);
                            return issue;
                        }
                    })
                );
                enhancedIssues = graphqlEnriched;
            }
            
            if (projectsWithColumns.length > 0) {
                console.log('Enhanced GitHub Issues with Projects:', enhancedIssues);
                showMessage(`${gifAdmin.strings.successMessage} Found ${enhancedIssues.length} issues with project data.`, 'success');
            } else {
                console.log('GitHub Issues (no projects):', enhancedIssues);
                showMessage(`${gifAdmin.strings.successMessage} Found ${enhancedIssues.length} issues.`, 'success');
            }
            
            // Display the enhanced issues
            displayIssues(enhancedIssues, owner, repo);
            
        } catch (error) {
            console.error('Error fetching GitHub data:', error);
            let errorMessage = gifAdmin.strings.errorFetching;
            
            // Parse error message from response
            if (error.message.includes('401')) {
                errorMessage = 'Invalid access token. Please check your GitHub personal access token.';
            } else if (error.message.includes('404')) {
                errorMessage = 'Repository not found. Please check the repository URL.';
            } else if (error.message.includes('403')) {
                errorMessage = 'Access forbidden. Please check your token permissions or rate limits.';
            } else if (error.message.includes('422')) {
                errorMessage = 'Invalid repository format. Please check the repository URL.';
            }
            
            showMessage(errorMessage, 'error');
        } finally {
            showLoading(false);
        }
    }
    
    // Helper function to fetch GitHub data (REST v3)
    async function fetchGitHubData(url, accessToken, dataType) {
        const fetchOptions = {
            method: 'GET',
            headers: {
                'Authorization': `token ${accessToken}`,
                'Accept': 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version': '2022-11-28',
                'User-Agent': 'WordPress-GitHub-Issues-Fetcher'
            }
        };

        console.log(`Fetching ${dataType} from:`, url);
        
        try {
            const response = await fetch(url, fetchOptions);
            
            console.log(`Response status for ${dataType}:`, response.status);
            
            if (!response.ok) {
                if (response.status === 404) {
                    // Projects might not exist, return empty array
                    if (dataType === 'projects') {
                        console.log('No projects found in repository');
                        return [];
                    }
                }
                
                // Get error details from response
                let errorDetails = '';
                try {
                    const errorData = await response.json();
                    if (errorData.message) {
                        errorDetails = ` - ${errorData.message}`;
                    }
                } catch (e) {
                    // Could not parse error response
                }
                
                throw new Error(`HTTP ${response.status}${errorDetails}`);
            }
            
            const data = await response.json();
            console.log(`Successfully fetched ${dataType}:`, data.length || 'single item');
            return data;
            
        } catch (error) {
            console.error(`Error fetching ${dataType}:`, error);
            throw error;
        }
    }

    // Helper specifically for Projects (classic) REST endpoints (require special Accept header)
    async function fetchGitHubProjectsClassic(url, accessToken, dataType) {
        const fetchOptions = {
            method: 'GET',
        headers: {
                'Authorization': `token ${accessToken}`,
                // Required for classic projects API
                'Accept': 'application/vnd.github.inertia+json',
            'X-GitHub-Api-Version': '2022-11-28',
                'User-Agent': 'WordPress-GitHub-Issues-Fetcher'
            }
        };

        console.log(`[Classic Projects] Fetching ${dataType} from:`, url);
        try {
            const response = await fetch(url, fetchOptions);
            console.log(`[Classic Projects] Response status for ${dataType}:`, response.status);
            if (!response.ok) {
                // 404 on projects means none exist (or token lacks classic projects permission)
                if (response.status === 404) {
                    return [];
                }
                let errorDetails = '';
                try {
                    const errorData = await response.json();
                    if (errorData.message) errorDetails = ` - ${errorData.message}`;
                } catch(e) {}
                throw new Error(`HTTP ${response.status}${errorDetails}`);
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.warn(`[Classic Projects] Error fetching ${dataType}:`, error);
            throw error;
        }
    }

    // Helper for GitHub GraphQL (Projects v2)
    async function fetchGitHubGraphQL(accessToken, query, variables, dataType) {
        const res = await fetch('https://api.github.com/graphql', {
            method: 'POST',
            headers: {
                'Authorization': `bearer ${accessToken}`,
                'Content-Type': 'application/json',
                'User-Agent': 'WordPress-GitHub-Issues-Fetcher'
            },
            body: JSON.stringify({ query, variables })
        });
        console.log(`[GraphQL] Response status for ${dataType}:`, res.status);
        const json = await res.json();
        if (!res.ok || json.errors) {
            console.warn('[GraphQL] Error:', json.errors || res.statusText);
            throw new Error('GraphQL request failed');
        }
        return json.data;
    }

    // Fetch Projects v2 info for a single issue (project assignments and status)
    async function fetchIssueProjectsV2(owner, repo, issueNumber, accessToken) {
        const query = `
            query($owner: String!, $repo: String!, $number: Int!) {
              repository(owner: $owner, name: $repo) {
                issue(number: $number) {
                  projectItems(first: 20) {
                    nodes {
                      project { title url }
                      fieldValues(first: 50) {
                        nodes {
                          __typename
                          ... on ProjectV2ItemFieldTextValue { field { name } text }
                          ... on ProjectV2ItemFieldSingleSelectValue { field { name } name }
                          ... on ProjectV2ItemFieldNumberValue { field { name } number }
                          ... on ProjectV2ItemFieldIterationValue { field { name } title }
                          ... on ProjectV2ItemFieldDateValue { field { name } date }
                        }
                      }
                    }
                  }
                }
              }
            }
        `;
        const data = await fetchGitHubGraphQL(accessToken, query, { owner, repo, number: issueNumber }, `issue #${issueNumber} projectsV2`);
        const nodes = data?.repository?.issue?.projectItems?.nodes || [];
        const in_projects = [];
        let project_status = null;
        nodes.forEach(node => {
            const projectName = node?.project?.title || 'Project';
            const projectUrl = node?.project?.url || '';
            // Find a reasonable "Status" value among fieldValues
            let statusValue = '';
            const fields = node?.fieldValues?.nodes || [];
            // Prefer a field literally named "Status"
            const statusPreferred = fields.find(f => (f.field?.name || '').toLowerCase() === 'status');
            if (statusPreferred) {
                statusValue = statusPreferred.name || statusPreferred.text || statusPreferred.title || '';
            } else {
                // Fallback: first SingleSelect/Text value
                const single = fields.find(f => f.__typename === 'ProjectV2ItemFieldSingleSelectValue');
                const text = fields.find(f => f.__typename === 'ProjectV2ItemFieldTextValue');
                statusValue = (single && (single.name || '')) || (text && (text.text || '')) || '';
            }
            const column_name = statusValue || 'Unspecified';
            in_projects.push({
                project_id: '',
                project_name: projectName,
                project_url: projectUrl,
                column_id: '',
                column_name,
                card_id: '',
                note: ''
            });
            if (!project_status) {
                project_status = { project_name: projectName, column_name, column_id: '' };
            }
        });
        return { in_projects, project_status };
    }
    
    // Helper function to get project data for an issue
    function getIssueProjectData(issue, projectsWithColumns) {
        const projectData = {
            in_projects: [],
            project_status: null
        };
        
        projectsWithColumns.forEach(project => {
            project.columns.forEach(column => {
                column.cards.forEach(card => {
                    // Check if this card represents our issue
                    if (card.content_url && card.content_url.includes(`/issues/${issue.number}`)) {
                        projectData.in_projects.push({
                            project_id: project.id,
                            project_name: project.name,
                            project_url: project.html_url,
                            column_id: column.id,
                            column_name: column.name,
                            card_id: card.id,
                            note: card.note
                        });
                        
                        // Set the main project status (first project found)
                        if (!projectData.project_status) {
                            projectData.project_status = {
                                project_name: project.name,
                                column_name: column.name,
                                column_id: column.id
                            };
                        }
                    }
                });
            });
        });
        
        return projectData;
    }

    function showLoading(show) {
        const loadingElement = document.getElementById('gif-loading');
        const submitButton = document.querySelector('.gif-submit-btn');
        
        if (show) {
            loadingElement.style.display = 'block';
            submitButton.disabled = true;
        } else {
            loadingElement.style.display = 'none';
            submitButton.disabled = false;
        }
    }

    function showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.gif-message');
        existingMessages.forEach(msg => msg.remove());

        // Create new message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `gif-message gif-message-${type}`;
        messageDiv.innerHTML = `
            <p>${message}</p>
            <button type="button" class="gif-message-close" aria-label="Close message">&times;</button>
        `;

        // Insert message after the form
        const form = document.getElementById('gif-settings-form');
        form.parentNode.insertBefore(messageDiv, form.nextSibling);

        // Add close functionality
        const closeButton = messageDiv.querySelector('.gif-message-close');
        closeButton.addEventListener('click', function() {
            messageDiv.remove();
        });

        // Auto-remove success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }

    function displayIssues(issues, owner, repo) {
        // Store current issues data globally for saving
        window.currentIssuesData = {
            issues: issues,
            repository: `${owner}/${repo}`,
            repositoryUrl: `https://github.com/${owner}/${repo}`
        };

        // Create or update issues display container
        let issuesContainer = document.getElementById('gif-issues-container');
        if (!issuesContainer) {
            issuesContainer = document.createElement('div');
            issuesContainer.id = 'gif-issues-container';
            issuesContainer.className = 'gif-issues-container';
            
            const adminWrap = document.querySelector('.gif-admin-wrap');
            adminWrap.appendChild(issuesContainer);
        }

        // Generate issues HTML
        let issuesHtml = `
            <div class="gif-issues-header">
                <div class="gif-issues-header-content">
                    <h3>Repository Issues (${issues.length})</h3>
                    <div class="gif-issues-actions">
                        <button type="button" class="button button-primary" id="save-issues-btn">
                            <span class="dashicons dashicons-database-add"></span>
                            Save to Database
                        </button>
                    </div>
                </div>
                <p class="gif-repository-info">
                    <span class="dashicons dashicons-admin-site"></span>
                    Repository: <strong>${owner}/${repo}</strong>
                </p>
            </div>
            <div class="gif-issues-list">
        `;

        if (issues.length === 0) {
            issuesHtml += '<p class="gif-no-issues">No issues found in this repository.</p>';
        } else {
            issues.forEach(issue => {
                const createdDate = new Date(issue.created_at).toLocaleDateString();
                const stateClass = issue.state === 'open' ? 'open' : 'closed';
                
                // Project information
                let projectHtml = '';
                if (issue.project_data && issue.project_data.in_projects.length > 0) {
                    projectHtml = `
                        <div class="gif-issue-projects">
                            <span class="gif-project-label">📋 In Projects:</span>
                            ${issue.project_data.in_projects.map(project => `
                                <div class="gif-project-item">
                                    <a href="${project.project_url}" target="_blank" rel="noopener" class="gif-project-name">
                                        ${project.project_name}
                                    </a>
                                    <span class="gif-project-column gif-project-column-${project.column_name.toLowerCase().replace(/\s+/g, '-')}">
                                        ${project.column_name}
                                    </span>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    projectHtml = '<div class="gif-issue-projects"><span class="gif-project-label">📋 No project assigned</span></div>';
                }
                
                issuesHtml += `
                    <div class="gif-issue-item gif-issue-${stateClass}">
                        <div class="gif-issue-header">
                            <h4 class="gif-issue-title">
                                <a href="${issue.html_url}" target="_blank" rel="noopener">
                                    #${issue.number} ${issue.title}
                                </a>
                            </h4>
                            <span class="gif-issue-state gif-issue-state-${stateClass}">${issue.state}</span>
                        </div>
                        <div class="gif-issue-meta">
                            <span class="gif-issue-author">by ${issue.user.login}</span>
                            <span class="gif-issue-date">on ${createdDate}</span>
                            ${issue.labels.length > 0 ? `
                                <div class="gif-issue-labels">
                                    ${issue.labels.map(label => `<span class="gif-issue-label" style="background-color: #${label.color}">${label.name}</span>`).join('')}
                                </div>
                            ` : ''}
                        </div>
                        ${projectHtml}
                        ${issue.body ? `<div class="gif-issue-body">${issue.body.substring(0, 200)}${issue.body.length > 200 ? '...' : ''}</div>` : ''}
                    </div>
                `;
            });
        }

        issuesHtml += '</div>';
        issuesContainer.innerHTML = issuesHtml;
        
        // Add event listener for save button
        const saveButton = document.getElementById('save-issues-btn');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                saveIssuesToDatabase();
            });
        }
    }
    
    function saveIssuesToDatabase() {
        if (!window.currentIssuesData) {
            showMessage('No issues data to save', 'error');
    return;
        }
        
        const saveButton = document.getElementById('save-issues-btn');
        const originalText = saveButton.innerHTML;
        
        // Show loading state
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Saving...';
        
        // Debug: Log the data being sent
        console.log('Saving issues data:', window.currentIssuesData);
        console.log('Issues count:', window.currentIssuesData.issues.length);
        console.log('Repository:', window.currentIssuesData.repository);
        console.log('Nonce:', gifAdmin.saveIssuesNonce);
        
        // Prepare data for AJAX request
        const formData = new FormData();
        formData.append('action', 'gif_save_issues');
        formData.append('nonce', gifAdmin.saveIssuesNonce);
        formData.append('issues', JSON.stringify(window.currentIssuesData.issues));
        formData.append('repository', window.currentIssuesData.repository);
        
        // Debug: Log FormData contents
        for (let [key, value] of formData.entries()) {
            console.log(`FormData ${key}:`, value);
        }
        
        // Send AJAX request
        fetch(gifAdmin.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('AJAX Response status:', response.status);
            console.log('AJAX Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('AJAX Response data:', data);
            
            if (data.success) {
                showMessage(`${gifAdmin.strings.savedToDatabase} (${data.data.count} issues from ${data.data.repository})`, 'success');
                
                // Update button to show saved state
                saveButton.innerHTML = '<span class="dashicons dashicons-yes"></span>Saved to Database';
                saveButton.classList.add('button-success');
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    saveButton.innerHTML = originalText;
                    saveButton.classList.remove('button-success');
                    saveButton.disabled = false;
                }, 3000);
            } else {
                console.error('AJAX Error:', data);
                const errorMessage = data.data || data.message || 'Unknown error';
                showMessage('Error saving issues: ' + errorMessage, 'error');
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showMessage('Error saving issues: ' + error.message, 'error');
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        });
    }
});