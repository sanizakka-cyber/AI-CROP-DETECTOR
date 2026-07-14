// server/models/Permission.js
/**
 * Fine-grained permission system for RBAC
 * Links actions to roles with optional constraints
 */

const mongoose = require('mongoose');

const permissionSchema = new mongoose.Schema(
  {
    name: {
      type: String,
      required: true,
      unique: true,
      // Format: "resource:action" e.g., "farm:create", "diagnosis:escalate"
      match: /^[a-z_]+:[a-z_]+$/,
    },
    description: String,
    category: {
      type: String,
      enum: [
        'user',
        'farm',
        'animal',
        'crop',
        'diagnosis',
        'consultation',
        'treatment',
        'marketplace',
        'expert',
        'analytics',
        'admin',
      ],
    },
    // Which actions require ownership check (default: false)
    // If true, middleware checks if user owns the resource
    requiresOwnershipCheck: {
      type: Boolean,
      default: false,
    },
    // Risk level for audit logging
    riskLevel: {
      type: String,
      enum: ['low', 'medium', 'high', 'critical'],
      default: 'low',
    },
    // Roles that have this permission
    roles: [
      {
        type: String,
        enum: ['farmer', 'vet', 'agronomist', 'agro-dealer', 'admin', 'extension-officer', 'ceo', 'researcher'],
      },
    ],
  },
  { timestamps: true }
);

// Index for fast permission lookups
permissionSchema.index({ name: 1 });
permissionSchema.index({ roles: 1 });
permissionSchema.index({ category: 1 });

module.exports = mongoose.model('Permission', permissionSchema);
