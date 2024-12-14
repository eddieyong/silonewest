<?php

// Define role-based permissions
$role_permissions = [
    'Admin' => [
        'dashboard' => true,
        'inventory' => true,
        'stock' => true,
        'vehicles' => true,
        'users' => true,
        'suppliers' => true,
        'purchase_orders' => true,
        'delivery_orders' => true,
        'notifications' => true,
        'history' => true,
        'warehouse' => true
    ],
    'Storekeeper' => [
        'dashboard' => true, // Limited to expected deliveries and recent activities
        'inventory' => true, // Full access for management
        'stock' => true,    // Full access for stock in/out
        'vehicles' => false,
        'users' => false,
        'suppliers' => false,
        'purchase_orders' => true, // View only
        'delivery_orders' => true, // View only
        'notifications' => true,   // Limited to stock and delivery notifications
        'history' => true,        // Limited to inventory and stock activities
        'warehouse' => true       // Access to warehouse management
    ],
    'Coordinator' => [
        'dashboard' => true,      // Limited to expected deliveries
        'inventory' => true,      // View only
        'stock' => false,
        'vehicles' => false,
        'users' => false,
        'suppliers' => false,
        'purchase_orders' => true, // View only
        'delivery_orders' => true, // View only
        'notifications' => true,   // Limited to delivery notifications
        'history' => false,
        'warehouse' => false
    ],
    'Driver' => [
        'dashboard' => true,      // Limited to assigned deliveries
        'inventory' => false,
        'stock' => true,         // View only for dates and times
        'vehicles' => true,      // Limited to vehicle schedules
        'users' => false,
        'suppliers' => false,
        'purchase_orders' => true, // View only assigned POs
        'delivery_orders' => true, // View only assigned DOs
        'notifications' => true,   // Limited to delivery notifications
        'history' => false,
        'warehouse' => false
    ]
];

// Function to check if user has permission
function hasPermission($role, $permission) {
    global $role_permissions;
    
    // Admin always has all permissions
    if ($role === 'Admin') {
        return true;
    }
    
    // Check if role exists and has the permission
    return isset($role_permissions[$role]) && 
           isset($role_permissions[$role][$permission]) && 
           $role_permissions[$role][$permission];
}

// Function to check if user has view-only permission
function isViewOnly($role, $permission) {
    if ($role === 'Admin') {
        return false; // Admin has full access
    }
    
    $view_only_permissions = [
        'Coordinator' => ['inventory', 'purchase_orders', 'delivery_orders'],
        'Driver' => ['stock', 'purchase_orders', 'delivery_orders', 'vehicles'],
        'Storekeeper' => ['purchase_orders', 'delivery_orders']
    ];
    
    return isset($view_only_permissions[$role]) && 
           in_array($permission, $view_only_permissions[$role]);
}

// Function to get dashboard content based on role
function getDashboardPermissions($role) {
    switch($role) {
        case 'Admin':
            return [
                'show_all_notifications' => true,
                'show_full_history' => true,
                'show_all_metrics' => true
            ];
        case 'Storekeeper':
            return [
                'show_expected_deliveries' => true,
                'show_recent_activities' => true,
                'show_inventory_metrics' => true,
                'show_stock_notifications' => true
            ];
        case 'Coordinator':
            return [
                'show_expected_deliveries' => true,
                'show_inventory_view' => true,
                'show_delivery_notifications' => true
            ];
        case 'Driver':
            return [
                'show_assigned_deliveries' => true,
                'show_vehicle_schedule' => true,
                'show_delivery_notifications' => true
            ];
        default:
            return [];
    }
}

function hasStockPermission($role) {
    return in_array($role, ['Admin', 'Storekeeper']);
} 