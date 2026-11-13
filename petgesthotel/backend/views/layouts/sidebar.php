<?php
/** @var \yii\web\View $this */
/** @var string $assetDir */

use yii\helpers\Html;
use yii\helpers\Url;
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?= Url::to(['/site/index']) ?>" class="brand-link">
        <img src="<?=$assetDir?>/img/AdminLTELogo.png" alt="PetGestHotel Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">PetGestHotel</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?=$assetDir?>/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <?= Yii::$app->user->isGuest ? 'Visitante' : Html::encode(Yii::$app->user->identity->username) ?>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <?php
            echo \hail812\adminlte\widgets\Menu::widget([
                    'items' => [
                            ['label' => 'Dashboard', 'icon' => 'tachometer-alt', 'url' => ['/site/index']],

                            ['label' => 'GESTÃO', 'header' => true],
                            [
                                    'label' => 'Utilizadores',
                                    'icon' => 'users',
                                    'url' => ['/user/index'],
                                    'visible' => Yii::$app->user->can('admin'),
                            ],
                        // Podes adicionar aqui as próximas secções do PetGestHotel (Animais, Quartos, Reservas, etc.)

                            ['label' => 'FERRAMENTAS DEV', 'header' => true, 'visible' => YII_ENV_DEV],
                            ['label' => 'Gii',  'icon' => 'file-code', 'url' => ['/gii'], 'target' => '_blank', 'visible' => YII_ENV_DEV],
                            ['label' => 'Debug', 'icon' => 'bug', 'url' => ['/debug'], 'target' => '_blank', 'visible' => YII_ENV_DEV],
                    ],
            ]);
            ?>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>