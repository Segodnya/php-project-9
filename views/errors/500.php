<div class="container-lg mt-5">
    <div class="row">
        <div class="col-12 col-md-10 col-lg-8 mx-auto">
            <h1 class="display-3">500</h1>
            <p class="lead">Внутренняя ошибка сервера</p>
            <p>Что-то пошло не так. Пожалуйста, попробуйте позже.</p>
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-danger">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>
            <a href="/" class="btn btn-primary">Вернуться на главную</a>
        </div>
    </div>
</div>