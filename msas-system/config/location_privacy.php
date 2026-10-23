<?php

/*
 * Who may see a scan's exact GPS point (App\Models\CollectionLocation::
 * canSeeExactPoint). The farmer who made the scan always may. Reports,
 * exports, research data and outbreak maps never carry the point: they use
 * State and LGA only.
 */
return [
    'exact_point_roles' => array_values(array_filter(array_map('trim',
        explode(',', env('LOCATION_EXACT_POINT_ROLES', 'admin,ceo'))))),
];
