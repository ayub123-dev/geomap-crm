<?php

$rootDir = dirname(__DIR__);
$envFile = $rootDir . DIRECTORY_SEPARATOR . '.env';
$successMessage = '';
$errorMessage = '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['_install_csrf_token'])) {
    $_SESSION['_install_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION['_install_csrf_token'];

$form = array(
    'app_name' => 'GeoMap CRM',
    'app_timezone' => 'Asia/Jakarta',
    'db_driver' => 'mysql',
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'geomap_crm',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',
    'db_encrypt' => 'false',
    'db_trust_cert' => 'true',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['_csrf_token'] ?? '');
    if ($postedToken === '' || !hash_equals($csrfToken, $postedToken)) {
        $errorMessage = 'Token CSRF tidak valid. Silakan refresh halaman install.';
    }

    foreach ($form as $key => $default) {
        $form[$key] = trim((string) ($_POST[$key] ?? $default));
    }

    if (!in_array($form['db_driver'], array('mysql', 'sqlsrv'), true)) {
        $errorMessage = 'Driver database harus "mysql" atau "sqlsrv".';
    }

    if ($errorMessage === '') {
        try {
            if ($form['db_driver'] === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $form['db_host'],
                    $form['db_port'] ?: '3306',
                    $form['db_name'],
                    $form['db_charset'] ?: 'utf8mb4'
                );
            } else {
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s',
                    $form['db_host'],
                    $form['db_port'] ?: '1433',
                    $form['db_name'],
                    $form['db_encrypt'] ?: 'false',
                    $form['db_trust_cert'] ?: 'true'
                );
            }

            $pdo = new PDO($dsn, $form['db_user'], $form['db_pass'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ));
            unset($pdo);

            $envContent = implode(PHP_EOL, array(
                'APP_NAME="' . addslashes($form['app_name']) . '"',
                'APP_TIMEZONE="' . addslashes($form['app_timezone']) . '"',
                'DB_DRIVER="' . addslashes($form['db_driver']) . '"',
                'DB_HOST="' . addslashes($form['db_host']) . '"',
                'DB_PORT="' . addslashes($form['db_port']) . '"',
                'DB_NAME="' . addslashes($form['db_name']) . '"',
                'DB_USER="' . addslashes($form['db_user']) . '"',
                'DB_PASS="' . addslashes($form['db_pass']) . '"',
                'DB_CHARSET="' . addslashes($form['db_charset']) . '"',
                'DB_ENCRYPT="' . addslashes($form['db_encrypt']) . '"',
                'DB_TRUST_CERT="' . addslashes($form['db_trust_cert']) . '"',
                '',
            ));

            file_put_contents($envFile, $envContent);
            $successMessage = 'Konfigurasi berhasil disimpan ke file .env.';
        } catch (Throwable $exception) {
            $errorMessage = 'Gagal koneksi ke database: ' . $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installer GeoMap CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h4 mb-1">Installer GeoMap CRM</h1>
                        <p class="text-muted mb-4">Pilih database MySQL atau SQL Server, lalu simpan konfigurasi environment.</p>

                        <?php if ($successMessage !== ''): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($successMessage); ?>
                                <a href="../index.php" class="alert-link">Buka aplikasi</a>.
                            </div>
                        <?php endif; ?>

                        <?php if ($errorMessage !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Aplikasi</label>
                                    <input type="text" class="form-control" name="app_name" value="<?php echo htmlspecialchars($form['app_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Timezone</label>
                                    <input type="text" class="form-control" name="app_timezone" value="<?php echo htmlspecialchars($form['app_timezone']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DB Driver</label>
                                    <select name="db_driver" id="db_driver" class="form-select">
                                        <option value="mysql"<?php echo $form['db_driver'] === 'mysql' ? ' selected' : ''; ?>>MySQL</option>
                                        <option value="sqlsrv"<?php echo $form['db_driver'] === 'sqlsrv' ? ' selected' : ''; ?>>SQL Server (sqlsrv)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DB Host</label>
                                    <input type="text" class="form-control" name="db_host" value="<?php echo htmlspecialchars($form['db_host']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DB Port</label>
                                    <input type="text" id="db_port" class="form-control" name="db_port" value="<?php echo htmlspecialchars($form['db_port']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DB Name</label>
                                    <input type="text" class="form-control" name="db_name" value="<?php echo htmlspecialchars($form['db_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DB User</label>
                                    <input type="text" class="form-control" name="db_user" value="<?php echo htmlspecialchars($form['db_user']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DB Password</label>
                                    <input type="password" class="form-control" name="db_pass" value="<?php echo htmlspecialchars($form['db_pass']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">DB Charset (MySQL)</label>
                                    <input type="text" class="form-control" name="db_charset" value="<?php echo htmlspecialchars($form['db_charset']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Encrypt (SQL Server)</label>
                                    <select class="form-select" name="db_encrypt">
                                        <option value="false"<?php echo $form['db_encrypt'] === 'false' ? ' selected' : ''; ?>>false</option>
                                        <option value="true"<?php echo $form['db_encrypt'] === 'true' ? ' selected' : ''; ?>>true</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Trust Cert (SQL Server)</label>
                                    <select class="form-select" name="db_trust_cert">
                                        <option value="true"<?php echo $form['db_trust_cert'] === 'true' ? ' selected' : ''; ?>>true</option>
                                        <option value="false"<?php echo $form['db_trust_cert'] === 'false' ? ' selected' : ''; ?>>false</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">Simpan Konfigurasi</button>
                                <a href="../index.php" class="btn btn-outline-secondary">Kembali ke App</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            var select = document.getElementById('db_driver');
            var port = document.getElementById('db_port');
            if (!select || !port) {
                return;
            }

            select.addEventListener('change', function () {
                if (select.value === 'mysql' && port.value.trim() === '1433') {
                    port.value = '3306';
                }
                if (select.value === 'sqlsrv' && port.value.trim() === '3306') {
                    port.value = '1433';
                }
            });
        })();
    </script>
</body>
</html>
