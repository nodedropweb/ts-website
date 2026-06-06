<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(__a('ADMIN_403_TITLE')) ?></title>
    <link rel="stylesheet" href="../lib/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center">
        <h1 class="display-4">403</h1>
        <p class="lead"><?= htmlspecialchars(__a('ADMIN_403_TEXT')) ?></p>
        <p class="text-muted"><?= __a('ADMIN_403_HINT') ?></p>
        <a href="<?= htmlspecialchars($loginUrl ?? '../') ?>" class="btn btn-primary"><?= htmlspecialchars(__a('ADMIN_403_BTN')) ?></a>
    </div>
</body>
</html>
