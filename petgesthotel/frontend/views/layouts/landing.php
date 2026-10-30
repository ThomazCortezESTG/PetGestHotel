<?php

declare(strict_types=1);

/**
 * Layout da landing page: igual ao main.php, mas sem o .container à volta
 * do conteúdo, para as secções ocuparem a largura toda.
 *
 * @var yii\web\View $this
 * @var string $content
 */

use common\widgets\Alert;
use yii\helpers\Html;

$this->render('_head');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <script>
        // Liga as animações de scroll antes de pintar a página (evita o conteúdo piscar).
        if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('pg-reveal-on');
        }
    </script>
    <title><?= Html::encode($this->title) ?></title>
</head>
<body>
<?php $this->beginBody() ?>

<?= $this->render('_header') ?>

<main id="main" role="main">
    <div class="pg-container pg-alerts"><?= Alert::widget() ?></div>
    <?= $content ?>
</main>

<?= $this->render('_footer') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
