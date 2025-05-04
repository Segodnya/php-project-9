<!DOCTYPE html>
<html lang="ru" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сайт: <?= h($url['name']) ?> | Анализатор страниц</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
</head>
<body class="d-flex flex-column h-100">
    <!-- Header -->
    <header class="flex-shrink-0">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark px-3">
            <a class="navbar-brand" href="/">Анализатор страниц</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/urls">Сайты</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($messages = getFlashMessages())): ?>
            <div class="container-lg mt-3">
                <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= h($message['type']) ?> alert-dismissible fade show" role="alert">
                            <?= h($message['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                <?php endforeach; ?>
            </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container-lg mt-5">
            <h1>Сайт: <?= h($url['name']) ?></h1>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" data-test="url">
                    <tbody>
                        <tr>
                            <td>ID</td>
                            <td><?= h($url['id']) ?></td>
                        </tr>
                        <tr>
                            <td>Имя</td>
                            <td><?= h($url['name']) ?></td>
                        </tr>
                        <tr>
                            <td>Дата создания</td>
                            <td><?= formatDate($url['created_at']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2 class="mt-5">Проверки</h2>
            <div class="d-flex gap-2">
                <form method="post" action="/urls/<?= h($url['id']) ?>/checks">
                    <input type="submit" class="btn btn-primary" value="Запустить проверку">
                </form>
                <a href="/urls" class="btn btn-outline-primary">Вернуться</a>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover" data-test="checks">
                    <thead>
                        <tr>
                            <th class="col-1">ID</th>
                            <th class="col-1">Код ответа</th>
                            <th>h1</th>
                            <th>title</th>
                            <th>description</th>
                            <th class="col-2">Дата создания</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks as $check): ?>
                                <tr>
                                    <td><?= h($check['id']) ?></td>
                                    <td><?= getStatusBadge($check['status_code']) ?></td>
                                    <td><?= h($check['h1']) ?></td>
                                    <td><?= h($check['title']) ?></td>
                                    <td><?= h($check['description']) ?></td>
                                    <td><?= formatDate($check['created_at']) ?></td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer border-top py-3 mt-5 bg-light">
        <div class="container-lg">
            <div class="text-center">
                created by Your Name
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html> 