<?php

use common\models\Conversa;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/** @var string $assetDir */

$conversasNavbar = [];
$totalConversasNavbar = 0;

if (!Yii::$app->user->isGuest) {
    $eu = Yii::$app->user->id;
    $queryConversas = Conversa::find()
            ->with(['booking.user.userProfile', 'ultimaMensagem'])
            ->where(['or', ['id_colaborador' => $eu], ['id_colaborador' => null]]);

    $totalConversasNavbar = (int) (clone $queryConversas)->count();
    $conversasNavbar = $queryConversas
            ->orderBy(['updated_at' => SORT_DESC])
            ->limit(3)
            ->all();
}
?>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= Url::home() ?>" class="nav-link">Home</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= Url::to(['/site/contact']) ?>" class="nav-link">Contactos e Suporte</a>
        </li>
        <li class="nav-item dropdown">
            <a id="dropdownSubMenu1" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Dropdown</a>
            <ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 shadow">
                <li>
                    <a href="<?= str_replace('/backend/web', '/frontend/web', Url::home(true)) ?>" class="dropdown-item">
                        <i class="fas fa-globe mr-1"></i> Ir para o Frontend
                    </a>
                </li>
                <li><a href="#" class="dropdown-item">Some other action</a></li>
                <li><?= Html::a('Terminar Sessão', ['site/logout'], ['data-method' => 'post', 'class' => 'dropdown-item']) ?></li>

                <li class="dropdown-divider"></li>

                <!-- Level two dropdown-->
                <li class="dropdown-submenu dropdown-hover">
                    <a id="dropdownSubMenu2" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="dropdown-item dropdown-toggle">Hover for action</a>
                    <ul aria-labelledby="dropdownSubMenu2" class="dropdown-menu border-0 shadow">
                        <li>
                            <a tabindex="-1" href="#" class="dropdown-item">level 2</a>
                        </li>

                        <!-- Level three dropdown-->
                        <li class="dropdown-submenu">
                            <a id="dropdownSubMenu3" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="dropdown-item dropdown-toggle">level 2</a>
                            <ul aria-labelledby="dropdownSubMenu3" class="dropdown-menu border-0 shadow">
                                <li><a href="#" class="dropdown-item">3rd level</a></li>
                                <li><a href="#" class="dropdown-item">3rd level</a></li>
                            </ul>
                        </li>
                        <!-- End Level three -->

                        <li><a href="#" class="dropdown-item">level 2</a></li>
                        <li><a href="#" class="dropdown-item">level 2</a></li>
                    </ul>
                </li>
                <!-- End Level two -->
            </ul>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Messages Dropdown Menu (Conversas Dinâmicas) -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-comments"></i>
                <?php if ($totalConversasNavbar > 0): ?>
                    <span class="badge badge-danger navbar-badge"><?= $totalConversasNavbar ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <?php if (empty($conversasNavbar)): ?>
                    <span class="dropdown-item dropdown-header">Sem conversas pendentes</span>
                <?php else: ?>
                    <?php foreach ($conversasNavbar as $conv): ?>
                        <?php
                        $cliente = ($conv->booking && $conv->booking->user) ? $conv->booking->user : null;
                        $nomeCliente = ($cliente && $cliente->userProfile && !empty($cliente->userProfile->nome))
                                ? $cliente->userProfile->nome
                                : ($cliente ? $cliente->username : 'Cliente #' . $conv->id);

                        $textoUltima = 'Sem mensagens ainda';
                        if ($conv->ultimaMensagem) {
                            if (isset($conv->ultimaMensagem->conteudo)) {
                                $textoUltima = $conv->ultimaMensagem->conteudo;
                            } elseif (isset($conv->ultimaMensagem->mensagem)) {
                                $textoUltima = $conv->ultimaMensagem->mensagem;
                            } elseif (isset($conv->ultimaMensagem->texto)) {
                                $textoUltima = $conv->ultimaMensagem->texto;
                            }
                        }

                        $dataUltima = ($conv->ultimaMensagem && isset($conv->ultimaMensagem->created_at))
                                ? $conv->ultimaMensagem->created_at
                                : $conv->updated_at;
                        ?>
                        <a href="<?= Url::to(['/conversa/view', 'id' => $conv->id]) ?>" class="dropdown-item">
                            <!-- Message Start -->
                            <div class="media">
                                <img src="<?= $assetDir ?>/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                                <div class="media-body">
                                    <h3 class="dropdown-item-title">
                                        <?= Html::encode($nomeCliente) ?>
                                        <span class="float-right text-sm <?= empty($conv->id_colaborador) ? 'text-danger' : 'text-muted' ?>">
                                            <i class="fas fa-circle"></i>
                                        </span>
                                    </h3>
                                    <p class="text-sm"><?= Html::encode(StringHelper::truncate($textoUltima, 35)) ?></p>
                                    <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> <?= Html::encode($dataUltima) ?></p>
                                </div>
                            </div>
                            <!-- Message End -->
                        </a>
                        <div class="dropdown-divider"></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= Url::to(['/conversa/index']) ?>" class="dropdown-item dropdown-footer">Ver Todas as Conversas</a>
            </div>
        </li>

        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">15</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-header">15 Notifications</span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-envelope mr-2"></i> 4 new messages
                    <span class="float-right text-muted text-sm">3 mins</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-users mr-2"></i> 8 friend requests
                    <span class="float-right text-muted text-sm">12 hours</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-file mr-2"></i> 3 new reports
                    <span class="float-right text-muted text-sm">2 days</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                <i class="fas fa-th-large"></i>
            </a>
        </li>
    </ul>
</nav>
<!-- /.navbar -->