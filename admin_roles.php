<?php
/**
 * Admin Role Management Functions
 * 
 * These functions help manage admin and moderator roles for organizations
 * Use these in your admin panel to assign/remove admins
 */

require_once 'db.php';

/**
 * Check if a user is an admin or moderator
 * @param int $user_id
 * @return array|false Returns ['is_admin' => bool, 'admin_role' => string|null] or false
 */
function checkUserAdminStatus($user_id) {
    global $conn;
    
    // Check if user is a main admin
    $stmt = $conn->prepare('SELECT id FROM organizations WHERE main_admin_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        return ['is_admin' => true, 'admin_role' => 'main_admin'];
    }
    
    // Check if user is an organization admin or moderator
    $stmt = $conn->prepare('SELECT role FROM organization_admins WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        return ['is_admin' => true, 'admin_role' => $admin['role']];
    }
    
    return ['is_admin' => false, 'admin_role' => null];
}

/**
 * Get all organizations where user is a main admin
 * @param int $user_id
 * @return array
 */
function getUserMainAdminOrganizations($user_id) {
    global $conn;
    
    $stmt = $conn->prepare('SELECT o.*, d.name as department_name FROM organizations o 
                            JOIN departments d ON o.department_id = d.id 
                            WHERE o.main_admin_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all organizations where user is an admin or moderator
 * @param int $user_id
 * @return array
 */
function getUserOrgAdminRoles($user_id) {
    global $conn;
    
    $stmt = $conn->prepare('SELECT oa.*, o.name as org_name, o.department_id, d.name as department_name 
                            FROM organization_admins oa 
                            JOIN organizations o ON oa.organization_id = o.id 
                            JOIN departments d ON o.department_id = d.id 
                            WHERE oa.user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create a new organization with main admin
 * @param string $name Organization name
 * @param int $department_id Department ID
 * @param int $main_admin_id Main admin user ID
 * @param string|null $logo Logo URL
 * @return int|false Returns organization ID or false on error
 */
function createOrganization($name, $department_id, $main_admin_id, $logo = null) {
    global $conn;
    
    $stmt = $conn->prepare('INSERT INTO organizations (name, department_id, main_admin_id, logo) 
                            VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssis', $name, $department_id, $main_admin_id, $logo);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

/**
 * Add an admin or moderator to an organization
 * @param int $org_id Organization ID
 * @param int $user_id User ID
 * @param string $role 'admin' or 'moderator'
 * @return bool
 */
function addOrgAdmin($org_id, $user_id, $role = 'moderator') {
    global $conn;
    
    if (!in_array($role, ['admin', 'moderator'])) {
        return false;
    }
    
    $stmt = $conn->prepare('INSERT INTO organization_admins (organization_id, user_id, role) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE role = ?');
    $stmt->bind_param('iiss', $org_id, $user_id, $role, $role);
    
    return $stmt->execute();
}

/**
 * Remove an admin or moderator from an organization
 * @param int $org_id Organization ID
 * @param int $user_id User ID
 * @return bool
 */
function removeOrgAdmin($org_id, $user_id) {
    global $conn;
    
    $stmt = $conn->prepare('DELETE FROM organization_admins WHERE organization_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $org_id, $user_id);
    
    return $stmt->execute();
}

/**
 * Update admin role (admin to moderator or vice versa)
 * @param int $org_id Organization ID
 * @param int $user_id User ID
 * @param string $new_role 'admin' or 'moderator'
 * @return bool
 */
function updateOrgAdminRole($org_id, $user_id, $new_role) {
    global $conn;
    
    if (!in_array($new_role, ['admin', 'moderator'])) {
        return false;
    }
    
    $stmt = $conn->prepare('UPDATE organization_admins SET role = ? WHERE organization_id = ? AND user_id = ?');
    $stmt->bind_param('sii', $new_role, $org_id, $user_id);
    
    return $stmt->execute();
}

/**
 * Get all admins/moderators of an organization
 * @param int $org_id Organization ID
 * @return array
 */
function getOrgAdmins($org_id) {
    global $conn;
    
    $stmt = $conn->prepare('SELECT oa.*, u.first_name, u.last_name, u.email 
                            FROM organization_admins oa 
                            JOIN users u ON oa.user_id = u.id 
                            WHERE oa.organization_id = ?');
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Delete an organization (only if it exists)
 * @param int $org_id Organization ID
 * @return bool
 */
function deleteOrganization($org_id) {
    global $conn;
    
    $stmt = $conn->prepare('DELETE FROM organizations WHERE id = ?');
    $stmt->bind_param('i', $org_id);
    
    return $stmt->execute();
}

?>
