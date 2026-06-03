# Admin Role Management Setup Guide

## Overview
This system manages admin and moderator roles for organizations. Users who are either:
- **Main admin** of an organization (recorded in `organizations.main_admin_id`), or
- **Organization admin/moderator** (recorded in `organization_admins` table)

...will have access to "Create Event" and "Organization Dashboard" nav items in `index.php`.

---

## Database Schema

### Organizations Table
```sql
CREATE TABLE organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    main_admin_id INT NOT NULL,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (main_admin_id) REFERENCES users(id)
);
```
- **main_admin_id**: The original owner/creator of the organization
- **department_id**: Which department this organization belongs to

### Organization Admins Table
```sql
CREATE TABLE organization_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'moderator') NOT NULL DEFAULT 'moderator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (organization_id, user_id)
);
```
- **role**: 'admin' (full permissions) or 'moderator' (limited permissions)
- **UNIQUE constraint**: Prevents duplicate admin assignments

---

## Setup Instructions

### Step 1: Run Migration SQL
Execute `migrations_organizations.sql` in your MySQL database to create the tables:

```bash
mysql -u root -p suni_db < migrations_organizations.sql
```

Or manually run the SQL statements in phpMyAdmin.

### Step 2: Verify Login Files Updated
The following files have been updated with admin role checking:
- `cvsu-login.php` - Checks if user is main admin or org admin after successful login
- `guest-login.php` - Sets admin flags to false for guests
- `sign-up.php` - Initializes admin flags to false for new CvSU users
- `guest-signup.php` - Initializes admin flags to false for new guests

### Step 3: Verify index.php Updated
`index.php` now conditionally displays admin-only nav items:
```php
<?php if ($is_admin): ?>
    <li><a href="create-events.php">+ Create Event</a></li>
    <li><a href="dashboard.php">Organization Dashboard</a></li>
<?php endif; ?>
```

---

## Session Variables Set on Login

After successful login, these session variables are automatically set:

```php
$_SESSION['user_id']     // User ID
$_SESSION['email']       // User email
$_SESSION['first_name']  // User first name
$_SESSION['role']        // 'cvsu' or 'guest'
$_SESSION['is_admin']    // true/false - whether user is admin/moderator
$_SESSION['admin_role']  // 'main_admin', 'admin', 'moderator', or null
```

---

## Using admin_roles.php

The file `admin_roles.php` contains helper functions for managing admin roles.

### Import and use:
```php
<?php
require_once 'admin_roles.php';

// Check if user is admin
$status = checkUserAdminStatus($user_id);
if ($status['is_admin']) {
    echo "User is " . $status['admin_role'];
}

// Get all organizations where user is main admin
$orgs = getUserMainAdminOrganizations($user_id);

// Get all organizations where user is admin/moderator
$admin_roles = getUserOrgAdminRoles($user_id);

// Create new organization
$org_id = createOrganization('My Organization', $dept_id, $main_admin_id, '/path/to/logo.png');

// Add admin to organization
addOrgAdmin($org_id, $user_id, 'admin');

// Remove admin from organization
removeOrgAdmin($org_id, $user_id);

// Update admin role
updateOrgAdminRole($org_id, $user_id, 'moderator');

// Get all admins of an organization
$admins = getOrgAdmins($org_id);

// Delete organization
deleteOrganization($org_id);
?>
```

---

## Admin Role Hierarchy

1. **Main Admin** (highest)
   - Original creator/owner of organization
   - Full control over organization
   - Set in `organizations.main_admin_id`

2. **Admin**
   - Helper admin for organization
   - Can manage events and moderators
   - Set in `organization_admins` with role='admin'

3. **Moderator**
   - Limited permissions
   - Can help with event management
   - Set in `organization_admins` with role='moderator'

4. **Regular User** (no admin role)
   - Cannot access admin features

---

## Example: Assigning an Admin

```php
<?php
require_once 'admin_roles.php';

// Admin ID from admin panel form
$org_id = 5;
$new_admin_id = 12;

// Add as admin
if (addOrgAdmin($org_id, $new_admin_id, 'admin')) {
    echo "Admin added successfully!";
    // Admin will see admin nav items after logging in next time
} else {
    echo "Failed to add admin";
}
?>
```

---

## Testing the Setup

1. **Create an organization** (manually in DB or via admin panel):
   ```sql
   INSERT INTO organizations (name, department_id, main_admin_id, logo) 
   VALUES ('Test Organization', 1, 5, NULL);
   ```

2. **Log in as main_admin_id (user ID 5)**
   - Should see "Create Event" and "Organization Dashboard" in navbar

3. **Log in as a regular user**
   - Should NOT see those nav items

4. **Add user as moderator**:
   ```sql
   INSERT INTO organization_admins (organization_id, user_id, role) 
   VALUES (1, 8, 'moderator');
   ```

5. **Log in as user ID 8**
   - Should now see "Create Event" and "Organization Dashboard"

---

## Troubleshooting

### Admin nav items not showing after login:
- Check that `$_SESSION['is_admin']` is being set in login files
- Verify `index.php` has `session_start()` at the top
- Check browser cookies - session may have expired

### User not recognized as admin:
- Verify user exists in `users` table
- Check `organizations.main_admin_id` or `organization_admins.user_id` are correct
- Query DB directly: `SELECT * FROM organization_admins WHERE user_id = X;`

### SQL errors when running migration:
- Ensure `users` and `departments` tables exist first
- Check that `suni_db` database is selected
- Verify foreign key references are correct

---

## Security Notes

- Always verify `$_SESSION['user_id']` and `$_SESSION['is_admin']` on the server side
- Do not rely on client-side visibility for admin checks
- Use `admin_roles.php` functions for all admin-related queries
- Implement proper permission checks in `create-events.php` and `dashboard.php`

---

## Next Steps

1. Run the migration SQL
2. Insert test organizations and admins in the database
3. Test login flows with admin and regular users
4. Update `create-events.php` and `dashboard.php` to verify admin status
5. Add permission checks to protect admin-only pages
