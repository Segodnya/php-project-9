<!DOCTYPE html>
<html lang="ru" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сайты | Анализатор страниц</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD"
        crossorigin="anonymous">
</head>

<body class="d-flex flex-column h-100">
    <!-- Header -->
    <header class="flex-shrink-0">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark px-3">
            <a class="navbar-brand" href="/">Анализатор страниц</a>
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/urls">Сайты</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($messages = getFlashMessages())) : ?>
        <div class="container-lg mt-3">
            <?php foreach ($messages as $message) : ?>
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
            <h1>Сайты</h1>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" data-test="urls">
                    <thead>
                        <tr>
                            <th class="col-1">ID</th>
                            <th>Имя</th>
                            <th class="col-2">Последняя проверка</th>
                            <th class="col-1">Код ответа</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Ensure $urls is always defined
                        $urls = $urls ?? [];
                        foreach ($urls as $url) : ?>
                            <tr>
                                <td><?= h($url['id']) ?></td>
                                <td>
                                    <a href="/urls/<?= h($url['id']) ?>"><?= h($url['name']) ?></a>
                                </td>
                                <td>
                                    <?php if (isset($url['last_check_created_at'])) : ?>
                                        <?= formatDate($url['last_check_created_at']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($url['last_check_status_code'])) : ?>
                                        <?= getStatusBadge($url['last_check_status_code']) ?>
                                    <?php endif; ?>
                                </td>
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

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN"
        crossorigin="anonymous"></script>
</body>

</html>
