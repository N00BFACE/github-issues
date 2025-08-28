# GitHub Issues Plugin Usage Guide

## 🚀 Enhanced Features

### ✅ **Project Integration**
- **Project Data Fetching**: Automatically fetches GitHub projects and project boards
- **Issue Status Tracking**: Shows which project and column each issue is in
- **Project Filtering**: Filter issues by project name or column status
- **Visual Project Indicators**: Color-coded project columns and status badges

### ✅ **Advanced Filtering**
- **State Filtering**: Open, closed, or all issues
- **Project Filtering**: Filter by specific project names
- **Column Filtering**: Filter by project column status (To Do, In Progress, Done, etc.)
- **Combined Filters**: Use multiple filters together for precise results

## 📋 **Usage Examples**

### **1. Gutenberg Block**

#### **Basic Block**
1. Add "GitHub Issues" block to any post/page
2. Configure display options in block settings
3. Preview issues with project data in real-time

#### **Block Settings**
- **Display Settings**: Title, header, layout, pagination
- **Content Settings**: Author, date, labels, body content
- **Project Filtering**: Filter by project name or column
- **Pagination**: Enable/disable and set issues per page

#### **Project Filtering in Block**
```javascript
// Filter issues in "Sprint 1" project
projectFilter: "Sprint 1"

// Filter issues in "In Progress" column
columnFilter: "In Progress"

// Combined filtering
projectFilter: "Backend"
columnFilter: "Review"
```

### **2. Shortcode Usage**

#### **Basic Shortcode**
```php
[github_issues]
```

#### **With Project Filtering**
```php
// Filter by project name
[github_issues project="Sprint 1"]

// Filter by column status
[github_issues column="In Progress"]

// Combined filtering
[github_issues project="Backend" column="Review" count="5"]

// With pagination
[github_issues per_page="10" project="Frontend"]
```

#### **Complete Shortcode Options**
```php
[github_issues 
    count="15"           // Total issues to display
    state="open"         // all, open, or closed
    labels="true"        // Show/hide labels
    author="true"        // Show/hide author
    date="true"          // Show/hide date
    body="false"         // Show/hide issue body
    layout="grid"        // list or grid
    per_page="10"        // Issues per page
    project="Sprint 1"   // Filter by project name
    column="In Progress" // Filter by column status
]
```

### **3. PHP Integration**

#### **Basic Usage**
```php
$gif_public = new GifPublic();
$issues = $gif_public->render_issues();
```

#### **With Project Filtering**
```php
$attributes = array(
    'show_count' => 10,
    'show_state' => 'open',
    'project_filter' => 'Sprint 1',
    'column_filter' => 'In Progress',
    'per_page' => 5,
    'page' => 1
);

$html = $gif_public->render_issues($attributes);
```

#### **Get Raw Data**
```php
$saved_issues = $gif_public->get_saved_issues();

foreach ($saved_issues['issues'] as $issue) {
    if (isset($issue['project_data']['in_projects'])) {
        foreach ($issue['project_data']['in_projects'] as $project) {
            echo "Issue #{$issue['number']} is in project: {$project['project_name']}";
            echo "Status: {$project['column_name']}";
        }
    }
}
```

## 🎯 **Project Data Structure**

### **Enhanced Issue Data**
```php
$issue = array(
    'id' => 123,
    'number' => 456,
    'title' => 'Bug fix needed',
    'state' => 'open',
    'user' => array('login' => 'username'),
    'labels' => array(...),
    'project_data' => array(
        'in_projects' => array(
            array(
                'project_id' => 789,
                'project_name' => 'Sprint 1',
                'project_url' => 'https://github.com/orgs/org/projects/789',
                'column_id' => 101,
                'column_name' => 'In Progress',
                'card_id' => 202,
                'note' => 'Additional notes'
            )
        ),
        'project_status' => array(
            'project_name' => 'Sprint 1',
            'column_name' => 'In Progress',
            'column_id' => 101
        )
    )
);
```

### **Project Column Types**
- **🟠 To Do / Backlog**: Orange badges
- **🔵 In Progress / Working**: Blue badges  
- **🟣 Review / Testing**: Purple badges
- **🟢 Done / Completed / Merged**: Green badges
- **🔴 Blocked / Waiting**: Red badges
- **🟡 QA / QA Testing**: Orange-red badges
- **🟤 Deployed / Production**: Brown badges

## 🔧 **Advanced Configuration**

### **GitHub API Requirements**
- **Personal Access Token**: Must have `repo` scope permissions
- **Project Access**: Repository must have projects enabled
- **Rate Limits**: Respects GitHub API rate limiting

### **Performance Optimization**
- **Caching**: Issues and project data cached in database
- **Batch Fetching**: Efficient API calls for projects and columns
- **Lazy Loading**: Project data loaded only when needed

### **Error Handling**
- **Graceful Degradation**: Works without projects if none exist
- **API Failures**: Continues with basic issue data if project fetch fails
- **User Feedback**: Clear messages for configuration issues

## 📱 **Responsive Design**

### **Mobile Optimization**
- **Touch-friendly**: Optimized for mobile devices
- **Responsive Layout**: Adaptive grid and list views
- **Mobile Pagination**: Stacked pagination controls
- **Project Display**: Optimized project information layout

### **Cross-browser Support**
- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **Mobile Browsers**: iOS Safari, Chrome Mobile
- **Legacy Support**: IE11+ (with polyfills)

## 🎨 **Customization**

### **CSS Customization**
```css
/* Custom project column colors */
.gif-project-column-custom-status {
    background-color: #your-color !important;
}

/* Custom project item styling */
.gif-project-item {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
```

### **Template Overrides**
- **Custom Templates**: Override default rendering
- **Hook System**: WordPress action/filter hooks
- **Child Theme Support**: Compatible with child themes

## 🚨 **Troubleshooting**

### **Common Issues**

#### **Projects Not Showing**
- Check if repository has projects enabled
- Verify personal access token has `repo` scope
- Check browser console for API errors

#### **Filtering Not Working**
- Ensure project names match exactly (case-sensitive)
- Check column names match project board columns
- Verify issues are assigned to projects

#### **Performance Issues**
- Reduce issues per page
- Use specific project/column filters
- Check GitHub API rate limits

### **Debug Information**
```php
// Enable WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug log
tail -f wp-content/debug.log
```

## 📚 **API Reference**

### **GifPublic Methods**
- `render_issues($attributes)` - Render issues with filtering
- `get_saved_issues()` - Get raw issue data
- `shortcode_display_issues($atts)` - Shortcode handler

### **Available Attributes**
- `show_count`, `show_state`, `show_labels`
- `show_author`, `show_date`, `show_body`
- `layout`, `per_page`, `page`
- `project_filter`, `column_filter`

### **Filter Combinations**
```php
// Multiple filters work together
$attributes = array(
    'show_state' => 'open',
    'project_filter' => 'Sprint 1',
    'column_filter' => 'In Progress',
    'per_page' => 5
);
```

---

**Need Help?** Check the main README.md for development setup and testing information.
