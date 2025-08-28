# GitHub Issues Plugin - Troubleshooting Guide

## 🚨 Common Error: "Error fetching issues. Please check your credentials and try again."

This error can occur for several reasons. Follow this troubleshooting guide step by step.

## 🔍 **Step 1: Check Browser Console**

1. **Open Developer Tools**: Press `F12` or right-click → "Inspect"
2. **Go to Console Tab**: Look for error messages
3. **Check for JavaScript Errors**: Red error messages indicate code issues
4. **Look for Network Errors**: Failed API requests will show here

## 🔑 **Step 2: Verify GitHub Token**

### **Token Requirements**
- ✅ **Scope**: Must have `repo` permissions
- ✅ **Format**: Should start with `ghp_` (new format) or `gho_` (old format)
- ✅ **Length**: Should be 40+ characters
- ✅ **Status**: Must be active and not expired

### **How to Check Token**
1. Go to [GitHub Settings → Tokens](https://github.com/settings/tokens)
2. Find your token in the list
3. Verify it has `repo` scope checked
4. Check if it's expired or revoked

### **Create New Token**
1. Go to [GitHub Settings → Tokens](https://github.com/settings/tokens)
2. Click "Generate new token (classic)"
3. Give it a descriptive name
4. **Check `repo` scope** (this is crucial!)
5. Click "Generate token"
6. Copy the new token immediately

## 🏗️ **Step 3: Verify Repository Access**

### **Repository Requirements**
- ✅ **Public Repository**: Should be accessible without authentication
- ✅ **Private Repository**: Your token must have access
- ✅ **URL Format**: Must be `https://github.com/username/repository`
- ✅ **Repository Exists**: Check if the repository is accessible in browser

### **Test Repository Access**
1. Open the repository URL in your browser
2. If it's private, make sure you're logged in
3. Verify you can see the repository content

## 🌐 **Step 4: Check Network Issues**

### **CORS Issues**
- **Symptom**: "CORS error" in console
- **Solution**: This is usually a server-side issue, not client-side

### **Rate Limiting**
- **Symptom**: "API rate limit exceeded" error
- **Solution**: Wait 1 hour or use authenticated requests

### **Network Connectivity**
- **Symptom**: "Network error" or timeout
- **Solution**: Check your internet connection

## 🧪 **Step 5: Use Test Button**

The plugin now includes a "Test Basic Fetch" button:

1. **Enter Repository URL**: `https://github.com/username/repository`
2. **Enter Access Token**: Your GitHub personal access token
3. **Click "Test Basic Fetch"**: This tests basic functionality without projects
4. **Check Console**: Look for detailed logging

## 📊 **Step 6: Check API Responses**

### **Expected Console Output**
```
Starting GitHub API calls for: username, repository
Fetching issues...
Response status for issues: 200
Issues fetched: 5
Fetching projects...
Response status for projects: 200
Projects found: 2
```

### **Common Error Status Codes**
- **401**: Unauthorized - Check your token
- **403**: Forbidden - Check token permissions
- **404**: Not found - Check repository URL
- **422**: Validation failed - Check repository format
- **429**: Rate limited - Wait and try again

## 🔧 **Step 7: Advanced Debugging**

### **Enable WordPress Debugging**
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### **Check Debug Log**
```bash
tail -f wp-content/debug.log
```

### **Test API Manually**
Use curl to test the GitHub API:
```bash
curl -H "Authorization: token YOUR_TOKEN" \
     -H "Accept: application/vnd.github.v3+json" \
     https://api.github.com/repos/username/repository/issues
```

## 🚀 **Step 8: Common Solutions**

### **Solution 1: Token Permissions**
```bash
# Check token scopes
curl -H "Authorization: token YOUR_TOKEN" \
     https://api.github.com/user
```

### **Solution 2: Repository Access**
```bash
# Test repository access
curl -H "Authorization: token YOUR_TOKEN" \
     https://api.github.com/repos/username/repository
```

### **Solution 3: Rate Limiting**
```bash
# Check rate limit status
curl -H "Authorization: token YOUR_TOKEN" \
     https://api.github.com/rate_limit
```

## 📱 **Step 9: Browser-Specific Issues**

### **Chrome/Firefox**
- ✅ Usually works well
- Check for browser extensions blocking requests

### **Safari**
- ⚠️ May have stricter CORS policies
- Try Chrome or Firefox

### **Mobile Browsers**
- ⚠️ May have limited debugging tools
- Use desktop browser for testing

## 🎯 **Step 10: Still Having Issues?**

### **Collect Debug Information**
1. **Browser Console**: Copy all error messages
2. **Network Tab**: Check failed requests
3. **Token Status**: Verify token permissions
4. **Repository URL**: Confirm it's correct

### **Contact Support**
Include in your message:
- Error message from console
- Repository URL (without token)
- Browser and version
- WordPress version
- Plugin version

## 🔄 **Quick Fix Checklist**

- [ ] Token has `repo` scope
- [ ] Repository URL is correct
- [ ] Repository is accessible
- [ ] Browser console shows no JavaScript errors
- [ ] Network requests are successful
- [ ] No rate limiting errors
- [ ] Token is not expired

## 💡 **Pro Tips**

1. **Start Simple**: Use "Test Basic Fetch" first
2. **Check Console**: Always look at browser console for errors
3. **Verify Token**: Make sure token has correct permissions
4. **Test Repository**: Verify repository is accessible
5. **Use Desktop**: Mobile browsers have limited debugging

---

**Need More Help?** Check the main README.md and USAGE.md files for additional information.
