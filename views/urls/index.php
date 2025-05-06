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