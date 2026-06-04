<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zugriff verweigert — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center">
        <h1 class="display-4">403</h1>
        <p class="lead">Kein Zugriff. Du bist nicht als Admin eingetragen.</p>
        <p class="text-muted">Bitte zuerst auf der Hauptseite mit TeamSpeak einloggen und dann einen Admin bitten, deine cldbid in <code>admin_cldbids</code> einzutragen.</p>
        <a href="<?= htmlspecialchars($loginUrl ?? '../') ?>" class="btn btn-primary">Zur Hauptseite</a>
    </div>
</body>
</html>
