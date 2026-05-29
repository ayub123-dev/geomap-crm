<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Rbac;
use App\Services\DashboardService;

$appName = getenv('APP_NAME') ?: 'GeoMap CRM';
$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($baseUrl === '/' || $baseUrl === '.') {
    $baseUrl = '';
}

$logoutAction = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
if ($logoutAction === 'logout') {
    Auth::logout();
    header('Location: ' . $baseUrl . '/index.php');
    exit;
}

$loginError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string) ($_POST['action'] ?? '') === 'login') {
    if (!Csrf::validate((string) ($_POST['_csrf_token'] ?? ''))) {
        $loginError = 'Token CSRF tidak valid. Silakan refresh halaman.';
    } else {
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (Auth::login($username, $password)) {
            header('Location: ' . $baseUrl . '/index.php');
            exit;
        }
        $loginError = 'Username atau password tidak valid.';
    }
}

$currentUser = Auth::currentUser();
if (!$currentUser) {
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo htmlspecialchars(Csrf::token()); ?>">
        <title><?php echo htmlspecialchars($appName . ' - Login'); ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo htmlspecialchars($baseUrl . '/assets/css/app.css'); ?>" rel="stylesheet">
    </head>
    <body class="bg-light" data-base-url="<?php echo htmlspecialchars($baseUrl); ?>" data-csrf-token="<?php echo htmlspecialchars(Csrf::token()); ?>">
        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h1 class="h4 mb-2">Login <?php echo htmlspecialchars($appName); ?></h1>
                            <p class="text-muted mb-4">Masuk dengan akun yang memiliki role di sistem.</p>

                            <?php if ($loginError !== ''): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($loginError); ?></div>
                            <?php endif; ?>

                            <form method="post" novalidate>
                                <input type="hidden" name="action" value="login">
                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required autofocus>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Masuk</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/app.js'); ?>"></script>
    </body>
    </html>
    <?php
    exit;
}

$modules = array(
    'dashboard' => array(
        'title' => 'Dashboard',
        'permission' => 'laporan.view',
    ),
    'customer_inti' => array(
        'title' => 'Master Customer Inti',
        'permission' => 'customer_inti.view',
    ),
    'customer_existing' => array(
        'title' => 'Master Customer Existing',
        'permission' => 'customer_existing.view',
    ),
    'salesman' => array(
        'title' => 'Salesman',
        'permission' => 'salesman.view',
    ),
    'map' => array(
        'title' => 'GeoMap Intelligence',
        'permission' => 'salesman.view',
    ),
    'users_roles' => array(
        'title' => 'User & Role',
        'permission' => 'users.manage',
    ),
    'customers' => array(
        'title' => 'Customer Management',
        'permission' => 'customer_inti.view',
    ),
);

$visibleModules = array();
foreach ($modules as $key => $config) {
    if (Rbac::hasPermission($config['permission'])) {
        $visibleModules[$key] = $config;
    }
}

$requestedModule = isset($_GET['module']) ? trim((string) $_GET['module']) : 'dashboard';
$accessDeniedMessage = '';
if (!array_key_exists($requestedModule, $modules)) {
    $requestedModule = 'dashboard';
}

if (!array_key_exists($requestedModule, $visibleModules)) {
    if (!empty($visibleModules)) {
        reset($visibleModules);
        $fallbackKey = key($visibleModules);
        if ($requestedModule !== $fallbackKey) {
            $accessDeniedMessage = 'Anda tidak memiliki permission untuk mengakses modul yang diminta.';
        }
        $requestedModule = $fallbackKey;
    } else {
        $requestedModule = '';
        $accessDeniedMessage = 'Akun Anda belum memiliki permission untuk membuka modul apa pun.';
    }
}

$pageTitle = $requestedModule !== '' ? $modules[$requestedModule]['title'] : 'Setup Akses';

$dbReady = true;
$dbError = '';
$dashboardStats = array(
    'total_customers' => 0,
    'active_customers' => 0,
    'inactive_customers' => 0,
    'geotagged_customers' => 0,
);

if ($requestedModule === 'dashboard') {
    try {
        $dashboardStats = (new DashboardService())->stats();
    } catch (\Throwable $exception) {
        $dbReady = false;
        $dbError = $exception->getMessage();
    }
}

$currentRoles = array();
foreach (($currentUser['roles'] ?? array()) as $role) {
    if (!empty($role['name'])) {
        $currentRoles[] = $role['name'];
    } elseif (!empty($role['code'])) {
        $currentRoles[] = $role['code'];
    }
}
$roleLabel = empty($currentRoles) ? '-' : implode(', ', $currentRoles);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(Csrf::token()); ?>">
    <title><?php echo htmlspecialchars($appName . ' - ' . $pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.awesome-markers/2.0.4/leaflet.awesome-markers.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?php echo htmlspecialchars($baseUrl . '/assets/css/app.css'); ?>" rel="stylesheet">
</head>
<body data-base-url="<?php echo htmlspecialchars($baseUrl); ?>" data-csrf-token="<?php echo htmlspecialchars(Csrf::token()); ?>">
    <header class="app-header">
        <nav class="navbar-modern" aria-label="Navigasi utama">
            <div class="navbar-shell">
                <a class="brand-link" href="<?php echo htmlspecialchars($baseUrl . '/index.php'); ?>">
                    <span class="brand-badge"><i class="fa-solid fa-map-location-dot"></i></span>
                    <span class="brand-copy">
                        <span class="brand-name"><?php echo htmlspecialchars($appName); ?></span>
                        <span class="brand-tag">Enterprise Geo Intelligence</span>
                    </span>
                </a>

                <button class="navbar-toggler-modern" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="navbar-collapse collapse" id="mainNavbar">
                    <ul class="nav-list">
                        <?php if (isset($visibleModules['dashboard'])): ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo $requestedModule === 'dashboard' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($baseUrl . '/index.php?module=dashboard'); ?>">
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($visibleModules['customer_inti'])): ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo $requestedModule === 'customer_inti' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($baseUrl . '/index.php?module=customer_inti'); ?>">
                                    <span>Master Customer Inti</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($visibleModules['customer_existing'])): ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo $requestedModule === 'customer_existing' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($baseUrl . '/index.php?module=customer_existing'); ?>">
                                    <span>Master Customer Existing</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php $showOperations = isset($visibleModules['salesman']) || isset($visibleModules['map']); ?>
                        <?php if ($showOperations): ?>
                            <li class="nav-item nav-mega">
                                <details <?php echo in_array($requestedModule, array('salesman', 'map'), true) ? 'open' : ''; ?>>
                                    <summary class="nav-link">
                                        <span>Operations</span>
                                        <i class="fa-solid fa-chevron-down nav-arrow"></i>
                                    </summary>
                                    <div class="nav-mega-menu">
                                        <div class="mega-col">
                                            <h6>Operasional</h6>
                                            <?php if (isset($visibleModules['salesman'])): ?>
                                                <a href="<?php echo htmlspecialchars($baseUrl . '/index.php?module=salesman'); ?>" class="dropdown-link<?php echo $requestedModule === 'salesman' ? ' active' : ''; ?>">Salesman</a>
                                            <?php endif; ?>
                                            <?php if (isset($visibleModules['map'])): ?>
                                                <a href="<?php echo htmlspecialchars($baseUrl . '/index.php?module=map'); ?>" class="dropdown-link<?php echo $requestedModule === 'map' ? ' active' : ''; ?>">Map</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </details>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($visibleModules['users_roles'])): ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo $requestedModule === 'users_roles' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($baseUrl . '/index.php?module=users_roles'); ?>">
                                    <span>User &amp; Role</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <div class="ms-lg-auto mt-3 mt-lg-0 small text-lg-end">
                        <div class="fw-semibold"><?php echo htmlspecialchars((string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'User')); ?></div>
                        <div class="text-muted mb-1"><?php echo htmlspecialchars($roleLabel); ?></div>
                        <a class="link-danger text-decoration-none" href="<?php echo htmlspecialchars($baseUrl . '/index.php?action=logout'); ?>">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container-fluid px-3 px-md-4 py-4">
        <section class="mb-3">
            <h1 class="h3 fw-semibold mb-1"><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="text-muted mb-0">Enterprise CRM berbasis geospasial dengan arsitektur native PHP 7.x.</p>
        </section>

        <div id="globalAlerts">
            <?php if ($accessDeniedMessage !== ''): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($accessDeniedMessage); ?></div>
            <?php endif; ?>

            <?php if (!$dbReady): ?>
                <div class="alert alert-warning">
                    <strong>Koneksi database belum siap.</strong>
                    Jalankan installer terlebih dahulu di
                    <a class="alert-link" href="<?php echo htmlspecialchars($baseUrl . '/install/index.php'); ?>">halaman instalasi</a>.
                    <div class="small mt-1"><?php echo htmlspecialchars($dbError); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($requestedModule === ''): ?>
            <div class="alert alert-danger">Akses ditolak. Silakan jalankan auto-fix akses di bawah ini.</div>
            <?php
                $modulePath = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'access_setup' . DIRECTORY_SEPARATOR . 'index.php';
                if (is_file($modulePath)) {
                    include $modulePath;
                }
            ?>
        <?php else: ?>
            <?php
                $modulePath = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $requestedModule . DIRECTORY_SEPARATOR . 'index.php';
                if (is_file($modulePath)) {
                    include $modulePath;
                } else {
                    echo '<div class="alert alert-danger">File modul tidak ditemukan.</div>';
                }
            ?>
        <?php endif; ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/app.js'); ?>"></script>

    <?php if ($requestedModule === '' || in_array($requestedModule, array('dashboard', 'map', 'salesman'), true)): ?>
        <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.awesome-markers/2.0.4/leaflet.awesome-markers.js"></script>
    <?php endif; ?>

    <?php if ($requestedModule === ''): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/access_setup.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($requestedModule === 'dashboard'): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/customer_inti.js'); ?>"></script>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/map.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($requestedModule === 'customers'): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/customers.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($requestedModule === 'customer_inti'): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/customer_inti.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($requestedModule === 'customer_existing'): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/customer_existing.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($requestedModule === 'salesman'): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/salesman.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($requestedModule === 'map'): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/map.js'); ?>"></script>
    <?php endif; ?>
    <?php if ($requestedModule === 'users_roles'): ?>
        <script src="<?php echo htmlspecialchars($baseUrl . '/assets/js/users_roles.js'); ?>"></script>
    <?php endif; ?>
</body>
</html>
