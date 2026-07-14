// server/models/AuditLog.js
/**
 * Audit trail for compliance and security
 * Logs all high-risk actions and permission checks
 */

const mongoose = require('mongoose');

const auditLogSchema = new mongoose.Schema(
  {
    userId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: true,
      index: true,
    },
    // Action performed: format "resource:action" e.g., "farm:create", "diagnosis:escalate"
    action: {
      type: String,
      required: true,
      index: true,
    },
    // Result: 'granted', 'denied', 'completed', 'failed'
    result: {
      type: String,
      enum: ['granted', 'denied', 'completed', 'failed'],
      default: 'completed',
      index: true,
    },
    // If denied, why: 'insufficient_role', 'not_owner', 'not_verified', etc.
    reason: String,
    // Resource affected (farm ID, diagnosis ID, etc.)
    resource: mongoose.Schema.Types.ObjectId,
    // Resource type for clarity
    resourceType: String,
    // HTTP method
    httpMethod: { type: String, enum: ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] },
    // Request path
    path: String,
    // IP address for security tracking
    ipAddress: String,
    // User agent for device tracking
    userAgent: String,
    // Additional metadata
    metadata: mongoose.Schema.Types.Mixed,
    // Timestamp (auto-added by schema)
  },
  {
    timestamps: true,
    indexes: [
      { userId: 1, createdAt: -1 },
      { action: 1, result: 1, createdAt: -1 },
      { resource: 1, createdAt: -1 },
      { result: 1, createdAt: -1 }, // For "denied" queries
    ],
  }
);

// Auto-expire old audit logs after 1 year (set TTL index)
// Adjust based on compliance requirements
auditLogSchema.index({ createdAt: 1 }, { expireAfterSeconds: 31536000 });

module.exports = mongoose.model('AuditLog', auditLogSchema);
