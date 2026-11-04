<?php

declare(strict_types=1);

/**
 * Layout das páginas de autenticação: formulário à esquerda e fotografia à direita,
 * sem cabeçalho nem rodapé.
 *
 * Cada vista define em $this->params:
 *  - authImage: ficheiro em web/images
 *  - authVariant: (opcional) sufixo da classe .pg-auth--*
 *
 * @var yii\web\View $this
 * @var string $content
 */

use common\widgets\Alert;
use yii\helpers\Html;

$this->render('_head');

$authClass = 'pg-auth';
if (!empty($this->params['authVariant'])) {
    $authClass .= ' pg-auth--' . $this->params['authVariant'];
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
</head>
<body>
<?php $this->beginBody() ?>

<main id="main" class="<?= $authClass ?>" role="main">
    <div class="pg-auth__panel">
        <?= Html::a(
            Html::img('@web/images/petGuestHotelLogo.png', ['alt' => Yii::$app->name]),
            Yii::$app->homeUrl,
            ['class' => 'pg-auth__logo'],
        ) ?>
        <div class="pg-auth__content">
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    </div>
    <div class="pg-auth__photo">
        <?= Html::img('@web/images/' . $this->params['authImage'], ['alt' => '']) ?>
    </div>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
