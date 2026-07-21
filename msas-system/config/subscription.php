<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    | plan_level: used for hierarchical comparison (basic < pro < premium)
    | limits: -1 means unlimited
    | features: list of feature keys gating access
    */
    'plans' => [

        'basic' => [
            'name'        => 'Basic Plan',
            'plan_level'  => 1,
            'badge_color' => '#1FA84A',
            'price'       => [
                'monthly' => 2500,
                'yearly'  => 25000,
            ],
            'trial_days'  => 14,
            'limits'      => [
                'livestock_records'   => 50,
                'reports_per_month'   => 5,
                'farm_staff'          => 1,
                'farms'               => 1,
                'ai_scans_per_month'  => 10,
            ],
            'features' => [
                'livestock_registration',
                'basic_health_records',
                'feeding_schedule',
                'vaccination_reminders',
                'farm_activity_log',
                'advisory_content',
                'basic_dashboard',
                'faq_chatbot_support',
                'monthly_reports',
                'mobile_access',
            ],
            'description' => 'Perfect for smallholder farmers starting their digital journey.',
            'highlights'  => [
                'Up to 50 livestock records',
                'Basic health records',
                'Feeding & vaccination reminders',
                'Farm activity logging',
                'Monthly farm reports',
                'Mobile app access',
            ],
        ],

        'pro' => [
            'name'        => 'Pro Plan',
            'plan_level'  => 2,
            'badge_color' => '#2D9CDB',
            'price'       => [
                'monthly' => 10000,
                'yearly'  => 100000,
            ],
            'trial_days'  => 14,
            'limits'      => [
                'livestock_records'   => -1,
                'reports_per_month'   => -1,
                'farm_staff'          => 5,
                'farms'               => 3,
                'ai_scans_per_month'  => -1,
            ],
            'features' => [
                // All Basic features
                'livestock_registration',
                'basic_health_records',
                'feeding_schedule',
                'vaccination_reminders',
                'farm_activity_log',
                'advisory_content',
                'basic_dashboard',
                'faq_chatbot_support',
                'monthly_reports',
                'mobile_access',
                // Pro additions
                'unlimited_livestock',
                'advanced_health_records',
                'breeding_reproduction',
                'production_tracking',
                'vet_service_requests',
                'digital_farm_records',
                'performance_dashboard',
                'productivity_analytics',
                'inventory_management',
                'financial_records',
                'geo_tagged_farm',
                'pdf_excel_reports',
                'direct_messaging',
                'priority_support',
                'profitability_analysis',
                'disease_alerts',
                'custom_reminders',
                'benchmarking',
            ],
            'description' => 'For growing farms that want to boost productivity and make data-driven decisions.',
            'highlights'  => [
                'Unlimited livestock records',
                'Advanced health & breeding records',
                'Production tracking (milk, meat, eggs)',
                'Veterinary service requests',
                'Inventory & financial management',
                'PDF & Excel report downloads',
                'Direct messaging with vets & extension workers',
                'Priority support',
            ],
        ],

        'premium' => [
            'name'        => 'Premium Plan',
            'plan_level'  => 3,
            'badge_color' => '#F4A300',
            'price'       => [
                'monthly' => 35000,
                'yearly'  => 350000,
            ],
            'trial_days'  => 14,
            'limits'      => [
                'livestock_records'   => -1,
                'reports_per_month'   => -1,
                'farm_staff'          => -1,
                'farms'               => -1,
                'ai_scans_per_month'  => -1,
            ],
            'features' => [
                // All Basic + Pro features
                'livestock_registration',
                'basic_health_records',
                'feeding_schedule',
                'vaccination_reminders',
                'farm_activity_log',
                'advisory_content',
                'basic_dashboard',
                'faq_chatbot_support',
                'monthly_reports',
                'mobile_access',
                'unlimited_livestock',
                'advanced_health_records',
                'breeding_reproduction',
                'production_tracking',
                'vet_service_requests',
                'digital_farm_records',
                'performance_dashboard',
                'productivity_analytics',
                'inventory_management',
                'financial_records',
                'geo_tagged_farm',
                'pdf_excel_reports',
                'direct_messaging',
                'priority_support',
                'profitability_analysis',
                'disease_alerts',
                'custom_reminders',
                'benchmarking',
                // Premium additions
                'ai_recommendations',
                'predictive_disease_monitoring',
                'advanced_farm_intelligence',
                'executive_analytics',
                'market_price_intelligence',
                'supply_chain_management',
                'multi_farm_management',
                'multi_user_access',
                'integrated_financial_management',
                'livestock_traceability',
                'custom_kpi_dashboards',
                'advanced_forecasting',
                'priority_vet_consultation',
                'dedicated_account_manager',
                'api_integration',
                'data_export',
                'support_24_7',
                'onboarding_assistance',
                'training_sessions',
                'quarterly_reviews',
            ],
            'description' => 'Enterprise-grade tools for commercial farms, agribusinesses, and cooperatives.',
            'highlights'  => [
                'AI-powered recommendations',
                'Predictive disease monitoring',
                'Multi-farm & multi-user management',
                'Market price intelligence',
                'Supply chain management',
                'Livestock digital traceability',
                'Executive analytics & KPI dashboards',
                'Dedicated account manager',
                '24/7 priority support & quarterly reviews',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Professional Role Plans (vets, dealers, logistics, agribusiness, etc.)
    |--------------------------------------------------------------------------
    */
    'professional_starter' => [
        'name'        => 'Professional Starter',
        'plan_level'  => 1,
        'badge_color' => '#1FA84A',
        'price'       => [
            'monthly' => 15000,
            'yearly'  => 150000,
        ],
        'trial_days'  => 14,
        'limits'      => [
            'product_listings'    => 50,
            'orders_per_month'    => 100,
            'team_members'        => 2,
            'analytics_history'   => 90,
        ],
        'features' => [
            'role_dashboard',
            'product_listings',
            'order_management',
            'marketplace_presence',
            'basic_analytics',
            'email_support',
            'mobile_access',
        ],
        'description' => 'For professionals and small businesses getting started on the MSAS platform.',
        'highlights'  => [
            'Up to 50 product/service listings',
            'Order & request management',
            'Marketplace presence & discoverability',
            'Basic analytics dashboard',
            'Email & in-app support',
            'Mobile app access',
        ],
    ],

    'professional_business' => [
        'name'        => 'Professional Business',
        'plan_level'  => 2,
        'badge_color' => '#2D9CDB',
        'price'       => [
            'monthly' => 35000,
            'yearly'  => 350000,
        ],
        'trial_days'  => 14,
        'limits'      => [
            'product_listings'    => -1,
            'orders_per_month'    => -1,
            'team_members'        => 10,
            'analytics_history'   => -1,
        ],
        'features' => [
            'role_dashboard',
            'product_listings',
            'order_management',
            'marketplace_presence',
            'basic_analytics',
            'email_support',
            'mobile_access',
            'unlimited_listings',
            'priority_placement',
            'advanced_analytics',
            'pdf_excel_reports',
            'api_integration',
            'priority_support',
            'dedicated_account_manager',
        ],
        'description' => 'Full-featured plan for growing businesses and established professionals.',
        'highlights'  => [
            'Unlimited listings & orders',
            'Priority marketplace placement',
            'Advanced analytics & PDF/Excel reports',
            'Up to 10 team members',
            'API integration access',
            'Dedicated account manager',
            '24/7 priority support',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'trial'     => ['label' => 'Free Trial',  'color' => '#2D9CDB'],
        'active'    => ['label' => 'Active',       'color' => '#1FA84A'],
        'expired'   => ['label' => 'Expired',      'color' => '#dc2626'],
        'cancelled' => ['label' => 'Cancelled',    'color' => '#94a3b8'],
        'suspended' => ['label' => 'Suspended',    'color' => '#F4A300'],
        'none'      => ['label' => 'No Plan',      'color' => '#64748b'],
    ],
];
