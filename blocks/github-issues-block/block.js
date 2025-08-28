(function() {
    console.log('GitHub Issues Block: Script loading...');
    
    // Check if required dependencies are available
    if (!wp.blocks || !wp.blockEditor || !wp.components || !wp.i18n || !wp.element) {
        console.error('GitHub Issues Block: Required dependencies not available');
        return;
    }
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { 
        PanelBody, 
        TextControl, 
        SelectControl, 
        ToggleControl, 
        RangeControl,
        Notice 
    } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;
    
    console.log('GitHub Issues Block: Dependencies loaded, registering block...');

    registerBlockType('github-issues/github-issues', {
        title: __('GitHub Issues', 'github-issues'),
        description: __('Display GitHub issues from your saved repository.', 'github-issues'),
        icon: 'admin-site',
        category: 'widgets',
        keywords: [
            __('github', 'github-issues'),
            __('issues', 'github-issues'),
            __('repository', 'github-issues')
        ],
        supports: {
            html: false,
            align: ['wide', 'full']
        },

        attributes: {
            showCount: {
                type: 'number',
                default: 10
            },
            showState: {
                type: 'string',
                default: 'all'
            },
            showLabels: {
                type: 'boolean',
                default: true
            },
            showAuthor: {
                type: 'boolean',
                default: true
            },
            showDate: {
                type: 'boolean',
                default: true
            },
            showBody: {
                type: 'boolean',
                default: true
            },
            layout: {
                type: 'string',
                default: 'list'
            },
            title: {
                type: 'string',
                default: 'GitHub Issues'
            },
            showHeader: {
                type: 'boolean',
                default: true
            },
            perPage: {
                type: 'number',
                default: 10
            },
            enablePagination: {
                type: 'boolean',
                default: true
            },
            projectFilter: {
                type: 'string',
                default: ''
            },
            columnFilter: {
                type: 'string',
                default: ''
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const {
                showCount,
                showState,
                showLabels,
                showAuthor,
                showDate,
                showBody,
                layout,
                title,
                showHeader,
                perPage,
                enablePagination,
                projectFilter,
                columnFilter
            } = attributes;

            // Check if we have saved issues
            const hasIssues = gifBlockData.hasIssues;
            const savedIssues = gifBlockData.savedIssues;

            function renderPreview() {
                if (!hasIssues) {
                    return el(Notice, {
                        status: 'warning',
                        isDismissible: false
                    }, [
                        el('p', {}, __('No GitHub issues found. ', 'github-issues')),
                        el('a', {
                            href: gifBlockData.adminUrl,
                            target: '_blank'
                        }, __('Fetch issues from the admin panel', 'github-issues'))
                    ]);
                }

                // Filter issues based on settings
                let issues = savedIssues.issues || [];
                
                if (showState !== 'all') {
                    issues = issues.filter(issue => issue.state === showState);
                }

                if (showCount > 0) {
                    issues = issues.slice(0, showCount);
                }

                return el('div', {
                    className: `gif-issues-frontend gif-gutenberg-block gif-layout-${layout} gif-editor-preview`
                }, [
                    // Header
                    showHeader && el('div', {
                        className: 'gif-issues-frontend-header',
                        key: 'header'
                    }, [
                        el('h3', {
                            className: 'gif-issues-title',
                            key: 'title'
                        }, title),
                        savedIssues.repository && el('p', {
                            className: 'gif-repository-name',
                            key: 'repo'
                        }, [
                            el('span', { className: 'dashicons dashicons-admin-site' }),
                            'Repository: ',
                            el('strong', {}, savedIssues.repository)
                        ])
                    ]),

                    // Issues list
                    el('div', {
                        className: 'gif-issues-frontend-list',
                        key: 'issues-list'
                    }, issues.length === 0 ? 
                        el('p', {
                            className: 'gif-no-issues'
                        }, __('No issues found matching the current filters.', 'github-issues'))
                        :
                        issues.map((issue, index) => 
                            el('div', {
                                key: index,
                                className: `gif-issue-item gif-issue-${issue.state}`
                            }, [
                                // Issue header
                                el('div', {
                                    className: 'gif-issue-header',
                                    key: 'header'
                                }, [
                                    el('h4', {
                                        className: 'gif-issue-title',
                                        key: 'title'
                                    }, [
                                        el('a', {
                                            href: issue.html_url,
                                            target: '_blank',
                                            rel: 'noopener'
                                        }, `#${issue.number} ${issue.title}`)
                                    ]),
                                    el('span', {
                                        className: `gif-issue-state gif-issue-state-${issue.state}`,
                                        key: 'state'
                                    }, issue.state.charAt(0).toUpperCase() + issue.state.slice(1))
                                ]),

                                // Issue meta
                                (showAuthor || showDate || (showLabels && issue.labels && issue.labels.length > 0)) && 
                                el('div', {
                                    className: 'gif-issue-meta',
                                    key: 'meta'
                                }, [
                                    showAuthor && issue.user && el('span', {
                                        className: 'gif-issue-author',
                                        key: 'author'
                                    }, `by ${issue.user.login}`),
                                    
                                    showDate && issue.created_at && el('span', {
                                        className: 'gif-issue-date',
                                        key: 'date'
                                    }, `on ${new Date(issue.created_at).toLocaleDateString()}`),
                                    
                                    showLabels && issue.labels && issue.labels.length > 0 && 
                                    el('div', {
                                        className: 'gif-issue-labels',
                                        key: 'labels'
                                    }, issue.labels.map((label, labelIndex) => 
                                        el('span', {
                                            key: labelIndex,
                                            className: 'gif-issue-label',
                                            style: { backgroundColor: `#${label.color}` }
                                        }, label.name)
                                    ))
                                ]),
                                
                                // Project information
                                issue.project_data && issue.project_data.in_projects && issue.project_data.in_projects.length > 0 && 
                                el('div', {
                                    className: 'gif-issue-projects',
                                    key: 'projects'
                                }, [
                                    el('span', {
                                        className: 'gif-project-label',
                                        key: 'label'
                                    }, '📋 In Projects:'),
                                    
                                    ...issue.project_data.in_projects.map((project, projectIndex) => 
                                        el('div', {
                                            key: projectIndex,
                                            className: 'gif-project-item'
                                        }, [
                                            el('a', {
                                                href: project.project_url,
                                                target: '_blank',
                                                rel: 'noopener',
                                                className: 'gif-project-name'
                                            }, project.project_name),
                                            
                                            el('span', {
                                                className: `gif-project-column gif-project-column-${project.column_name.toLowerCase().replace(/\s+/g, '-')}`,
                                                key: 'column'
                                            }, project.column_name)
                                        ])
                                    )
                                ]),

                                // Issue body
                                showBody && issue.body && el('div', {
                                    className: 'gif-issue-body',
                                    key: 'body'
                                }, [
                                    el('p', {}, issue.body.substring(0, 150) + (issue.body.length > 150 ? '...' : ''))
                                ])
                            ])
                        )
                    )
                ]);
            }

            return el(Fragment, {}, [
                // Inspector Controls
                el(InspectorControls, {}, [
                    el(PanelBody, {
                        title: __('Display Settings', 'github-issues'),
                        initialOpen: true
                    }, [
                        el(ToggleControl, {
                            label: __('Show Header', 'github-issues'),
                            checked: showHeader,
                            onChange: (value) => setAttributes({ showHeader: value })
                        }),

                        showHeader && el(TextControl, {
                            label: __('Custom Title', 'github-issues'),
                            value: title,
                            onChange: (value) => setAttributes({ title: value }),
                            placeholder: __('GitHub Issues', 'github-issues')
                        }),

                        el(RangeControl, {
                            label: __('Number of Issues', 'github-issues'),
                            value: showCount,
                            onChange: (value) => setAttributes({ showCount: value }),
                            min: 1,
                            max: 50
                        }),

                        el(SelectControl, {
                            label: __('Issue State', 'github-issues'),
                            value: showState,
                            options: [
                                { label: __('All Issues', 'github-issues'), value: 'all' },
                                { label: __('Open Issues', 'github-issues'), value: 'open' },
                                { label: __('Closed Issues', 'github-issues'), value: 'closed' }
                            ],
                            onChange: (value) => setAttributes({ showState: value })
                        }),

                        el(SelectControl, {
                            label: __('Layout', 'github-issues'),
                            value: layout,
                            options: [
                                { label: __('List View', 'github-issues'), value: 'list' },
                                { label: __('Grid View', 'github-issues'), value: 'grid' }
                            ],
                            onChange: (value) => setAttributes({ layout: value })
                        }),

                        el(ToggleControl, {
                            label: __('Enable Pagination', 'github-issues'),
                            checked: attributes.enablePagination,
                            onChange: (value) => setAttributes({ enablePagination: value })
                        }),

                        attributes.enablePagination && el(RangeControl, {
                            label: __('Issues Per Page', 'github-issues'),
                            value: attributes.perPage,
                            onChange: (value) => setAttributes({ perPage: value }),
                            min: 5,
                            max: 50
                        })
                    ]),

                    el(PanelBody, {
                        title: __('Project Filtering', 'github-issues'),
                        initialOpen: false
                    }, [
                        el(TextControl, {
                            label: __('Filter by Project Name', 'github-issues'),
                            value: attributes.projectFilter,
                            onChange: (value) => setAttributes({ projectFilter: value }),
                            placeholder: __('e.g., Sprint 1, Backend, Frontend', 'github-issues'),
                            help: __('Leave empty to show all projects', 'github-issues')
                        }),

                        el(TextControl, {
                            label: __('Filter by Column Name', 'github-issues'),
                            value: attributes.columnFilter,
                            onChange: (value) => setAttributes({ columnFilter: value }),
                            placeholder: __('e.g., To Do, In Progress, Done', 'github-issues'),
                            help: __('Leave empty to show all columns', 'github-issues')
                        })
                    ]),

                    el(PanelBody, {
                        title: __('Content Settings', 'github-issues'),
                        initialOpen: false
                    }, [
                        el(ToggleControl, {
                            label: __('Show Author', 'github-issues'),
                            checked: showAuthor,
                            onChange: (value) => setAttributes({ showAuthor: value })
                        }),

                        el(ToggleControl, {
                            label: __('Show Date', 'github-issues'),
                            checked: showDate,
                            onChange: (value) => setAttributes({ showDate: value })
                        }),

                        el(ToggleControl, {
                            label: __('Show Labels', 'github-issues'),
                            checked: showLabels,
                            onChange: (value) => setAttributes({ showLabels: value })
                        }),

                        el(ToggleControl, {
                            label: __('Show Issue Body', 'github-issues'),
                            checked: showBody,
                            onChange: (value) => setAttributes({ showBody: value })
                        })
                    ])
                ]),

                // Block Preview
                renderPreview()
            ]);
        },

        save: function() {
            // Return null since we're using server-side rendering
            return null;
        }
    });
    
    console.log('GitHub Issues Block: Block registered successfully!');
})();
