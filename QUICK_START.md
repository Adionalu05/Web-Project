# Quick Start Guide

## Get Started in 3 Steps

### Step 1: Start Your Server
```bash
php -S localhost:8000
```

### Step 2: Open in Browser
Visit: `http://localhost:8000`

### Step 3: Create Your Account
1. Click "Register"
2. Fill in username, email, password
3. Click "Register"
4. Login with your credentials

## Now You Can:

**Upload Files**
- Add documents with titles and tags
- Organize by categories
- Up to 10 MB per file

**Organize**
- Assign documents to categories (Tickets, Contracts, Reports, Other)
- Add custom tags with comma-separated values
- Example: `important, urgent, 2024`

**Search**
- Search by document title
- Filter by category
- Filter by tags
- Combine multiple filters

**Manage**
- Download documents anytime
- Delete documents you no longer need
- View file details (size, format, upload date)

## File Management Tips

### Categories
Use predefined categories to organize your documents:
- **Tickets** - Support requests and issues
- **Contracts** - Legal and business documents
- **Reports** - Analysis and summary documents
- **Other** - Everything else

### Tags
Create custom tags for flexible organization:
- Use comma-separated values
- Examples: `urgent`, `confidential`, `Q1-2024`, `client-xyz`
- Search by any tag you've created

### Search
Click "Search Documents" and:
1. Type keywords to search titles/descriptions
2. Select a category to narrow results
3. Select a tag to further filter
4. Results update dynamically

## Supported File Types

- 📄 PDF, DOCX, DOC, TXT
- 📊 XLSX, XLS
- 🖼️ JPG, JPEG, PNG

## File Size Limit
Maximum: **10 MB** per file

## Session Duration
Your session lasts **24 hours** from login.
Click "Logout" to end your session early.

## Security
- Your password is securely hashed
- Sessions are token-based
- Only you can access your files
- All data is encrypted in transit

## Keyboard Shortcuts
- Enter in upload form = Submit upload
- Tab = Navigate between form fields

## Troubleshooting

**Can't login?**
- Check your username and password
- Make sure you've registered first

**File won't upload?**
- Check file size (max 10 MB)
- Verify file type is supported
- Try a different file

**Can't find a document?**
- Use search with keywords from title
- Try filtering by category
- Check if you uploaded it to a different account

## Tips & Tricks

1. **Batch Tagging**: Upload related documents with the same tags
2. **Quick Filter**: Use categories for broad organization, tags for specifics
3. **Naming**: Use clear, descriptive titles for easy searching
4. **Regular Cleanup**: Delete old documents you don't need
5. **Categories First**: Assign a category with every upload

## Need More Help?

See full documentation in `README.md` for:
- Installation instructions
- Database schema details
- API endpoint documentation
- Advanced features
- Security policies

Enjoy managing your files! 📁✨
