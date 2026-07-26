<?php
/**
 * Collapsible dashboard sidebar for admin/ and org-admin/.
 */

function admin_sidebar_items(): array
{
    return [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'href' => 'admin/dashboard.php'],
        ['icon' => 'bi-building', 'label' => 'Organizations', 'href' => 'admin/tenants/index.php'],
        ['icon' => 'bi-people', 'label' => 'Users', 'href' => 'admin/users/index.php'],
        ['icon' => 'bi-credit-card', 'label' => 'Subscriptions', 'href' => 'admin/subscriptions/plans.php'],
        ['icon' => 'bi-bar-chart', 'label' => 'Reports', 'href' => 'admin/reports/usage.php'],
        ['icon' => 'bi-gear', 'label' => 'Settings', 'href' => 'admin/settings/general.php'],
    ];
}

function org_admin_sidebar_items(): array
{
    return [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'href' => 'org-admin/index.php'],
        ['icon' => 'bi-geo-alt', 'label' => 'Farms', 'href' => 'org-admin/farms/index.php'],
        ['icon' => 'bi-flower1', 'label' => 'Crop Management', 'href' => 'org-admin/crops/index.php'],
        ['icon' => 'bi-egg-fried', 'label' => 'Livestock', 'href' => 'org-admin/livestock/index.php'],
        ['icon' => 'bi-person-workspace', 'label' => 'Workers', 'href' => 'org-admin/workers/index.php'],
        ['icon' => 'bi-box-seam', 'label' => 'Inventory', 'href' => 'org-admin/inventory/index.php'],
        ['icon' => 'bi-truck', 'label' => 'Procurement', 'href' => 'org-admin/procurement/suppliers.php'],
        ['icon' => 'bi-cash-coin', 'label' => 'Finance', 'href' => 'org-admin/finance/income.php'],
        ['icon' => 'bi-qr-code', 'label' => 'Traceability', 'href' => 'org-admin/traceability/batches.php'],
        ['icon' => 'bi-graph-up-arrow', 'label' => 'Reports', 'href' => 'org-admin/reporting/dashboard.php'],
        ['icon' => 'bi-gear', 'label' => 'Settings', 'href' => 'org-admin/settings/profile.php'],
    ];
}

function render_app_sidebar(string $context, string $activePath, ?array $organization = null): void
{
    $items = $context === 'admin' ? admin_sidebar_items() : org_admin_sidebar_items();
    $brandHref = base_url($context === 'admin' ? 'admin/dashboard.php' : 'org-admin/index.php');
    $brandLabel = $context === 'admin' ? 'Platform Admin' : e($organization['name'] ?? 'Farm Dashboard');

    echo '<aside class="app-sidebar" id="appSidebar">';
    echo '<div class="sidebar-brand"><a href="' . $brandHref . '"><i class="bi bi-flower1"></i> <span>' . $brandLabel . '</span></a></div>';
    echo '<nav class="sidebar-nav"><ul class="list-unstyled">';

    foreach ($items as $item) {
        $isActive = str_contains($activePath, dirname($item['href'])) || str_ends_with($activePath, basename($item['href']));
        $class = $isActive ? 'active' : '';
        echo '<li class="' . $class . '"><a href="' . base_url($item['href']) . '"><i class="bi ' . $item['icon'] . '"></i><span>' . e($item['label']) . '</span></a></li>';
    }

    echo '</ul></nav>';
    echo '<div class="sidebar-footer"><a href="' . base_url('public/contact.php') . '"><i class="bi bi-life-preserver"></i><span>Help & Support</span></a></div>';
    echo '</aside>';
    echo '<div class="sidebar-backdrop" id="sidebarBackdrop"></div>';
}
