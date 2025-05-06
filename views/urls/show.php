<div class="container-lg mt-5">
    <h1>Сайт: <?= h($url['name'] ?? '') ?></h1>

    <div class="table-responsive">
        <table class="table table-bordered table-hover" data-test="url">
            <tbody>
                <tr>
                    <td>ID</td>
                    <td><?= h($url['id'] ?? '') ?></td>
                </tr>
                <tr>
                    <td>Имя</td>
                    <td><?= h($url['name'] ?? '') ?></td>
                </tr>
                <tr>
                    <td>Дата создания</td>
                    <td><?= isset($url['created_at']) ? formatDate($url['created_at']) : '' ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 class="mt-5">Проверки</h2>
    <div class="d-flex gap-2">
        <form method="post" action="/urls/<?= h($url['id'] ?? '') ?>/checks">
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
                <?php
                // Ensure $checks is always defined
                $checks = $checks ?? [];
                foreach ($checks as $check) : ?>
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