// Central Role-Based Access Control (RBAC) middleware
// Granular permission system with audit logging

const Permission = require('../models/Permission');
const AuditLog = require('../models/AuditLog');

/**
 * Legacy: requireRole(['admin', 'ceo']) — rejects unless user has one of the listed roles
 * DEPRECATED: Use requirePermission() instead for granular control
 */
function requireRole(roles) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ success: false, message: 'Not authenticated.' });
    }
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ success: false, message: 'You do not have permission for this action.' });
    }
    next();
  };
}

/**
 * NEW: requirePermission(permissionName, options)
 * 
 * @param {string} permissionName - Format: "resource:action" e.g., "farm:create", "diagnosis:read"
 * @param {object} options - Optional: { requireOwnership: true, resourceParam: 'farmId' }
 * 
 * Example:
 *   router.get('/:farmId', requirePermission('farm:read', { requireOwnership: true, resourceParam: 'farmId' }), handler)
 */
function requirePermission(permissionName, options = {}) {
  return async (req, res, next) => {
    try {
      if (!req.user) {
        return res.status(401).json({ success: false, message: 'Not authenticated.' });
      }

      // Get permission from database or cache
      const permission = await Permission.findOne({ name: permissionName });
      if (!permission) {
        console.warn(`Permission not found in database: ${permissionName}`);
        // Fail securely if permission definition is missing
        return res.status(403).json({ success: false, message: 'Permission definition not found.' });
      }

      // Check if user's role has this permission
      if (!permission.roles.includes(req.user.role)) {
        // Log unauthorized attempt
        if (permission.riskLevel === 'high' || permission.riskLevel === 'critical') {
          try {
            await new AuditLog({
              userId: req.user._id,
              action: permissionName,
              result: 'denied',
              reason: 'insufficient_role',
              resource: options.resourceId || options.resourceParam,
              ipAddress: req.ip,
            }).save();
          } catch (err) {
            console.error('Failed to log audit entry:', err);
          }
        }
        return res.status(403).json({ success: false, message: 'You do not have permission for this action.' });
      }

      // Ownership check (if required)
      if (permission.requiresOwnershipCheck && options.requireOwnership) {
        const resourceId = req.params[options.resourceParam];
        const resourceModel = options.model;

        if (!resourceId || !resourceModel) {
          return res.status(500).json({ success: false, message: 'Permission check configuration error.' });
        }

        const resource = await resourceModel.findById(resourceId);
        if (!resource) {
          return res.status(404).json({ success: false, message: 'Resource not found.' });
        }

        // Check ownership (or admin override)
        const isOwner = String(resource.owner || resource.userId) === String(req.user._id);
        const isAdmin = ['admin', 'ceo'].includes(req.user.role);

        if (!isOwner && !isAdmin) {
          try {
            await new AuditLog({
              userId: req.user._id,
              action: permissionName,
              result: 'denied',
              reason: 'not_owner',
              resource: resourceId,
              ipAddress: req.ip,
            }).save();
          } catch (err) {
            console.error('Failed to log audit entry:', err);
          }
          return res.status(403).json({ success: false, message: 'You do not have access to this resource.' });
        }
      }

      // Permission granted - log if high-risk
      if (permission.riskLevel === 'high' || permission.riskLevel === 'critical') {
        try {
          await new AuditLog({
            userId: req.user._id,
            action: permissionName,
            result: 'granted',
            resource: options.resourceId,
            ipAddress: req.ip,
          }).save();
        } catch (err) {
          console.error('Failed to log audit entry:', err);
        }
      }

      // Attach permission to request for downstream use
      req.permission = permission;
      next();
    } catch (err) {
      console.error('Permission check error:', err);
      res.status(500).json({ success: false, message: 'Permission check failed.' });
    }
  };
}

/**
 * Check multiple permissions (user must have at least one)
 * Example: requireAnyPermission(['diagnosis:escalate', 'admin:manage_users'])
 */
function requireAnyPermission(permissionNames) {
  return async (req, res, next) => {
    try {
      if (!req.user) {
        return res.status(401).json({ success: false, message: 'Not authenticated.' });
      }

      const permissions = await Permission.find({ name: { $in: permissionNames } });
      const hasPermission = permissions.some((p) => p.roles.includes(req.user.role));

      if (!hasPermission) {
        return res.status(403).json({ success: false, message: 'You do not have permission for this action.' });
      }

      next();
    } catch (err) {
      console.error('Permission check error:', err);
      res.status(500).json({ success: false, message: 'Permission check failed.' });
    }
  };
}

/**
 * Legacy shorthand: admin OR ceo roles
 */
const requireAdmin = requireRole(['admin', 'ceo']);

/**
 * Legacy shorthand: vet, agronomist, admin, ceo
 */
const requireExpert = requireRole(['vet', 'agronomist', 'admin', 'ceo']);

/**
 * Ownership check helper (used in route handlers)
 * Returns true if user is owner or admin
 */
async function checkOwnership(resource, userId, userRole) {
  const isOwner = String(resource.owner || resource.userId) === String(userId);
  const isAdmin = ['admin', 'ceo'].includes(userRole);
  return isOwner || isAdmin;
}

module.exports = {
  // Legacy (deprecated but still functional)
  requireRole,
  requireAdmin,
  requireExpert,

  // New granular permission system
  requirePermission,
  requireAnyPermission,
  checkOwnership,
};
