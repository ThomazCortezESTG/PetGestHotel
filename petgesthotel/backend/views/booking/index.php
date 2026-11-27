<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $filtro
 * @var array $contagens [filtro => n | null]
 */

use common\models\Booking;
use common\models\Conversa;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Reservas';
$this->params['breadcrumbs'][] = $this->title;

$fmt = Yii::$app->formatter;
/** @var Booking[] $reservas */
$reservas = $dataProvider->getModels();

$tabs = [
    'pendentes' => 'Por aceitar',
    'ativas' => 'Confirmadas e em curso',
    'terminadas' => 'Terminadas',
    'todas' => 'Todas',
];
$cores = [
    Booking::STATUS_PENDENTE => 'warning',
    Booking::STATUS_CONFIRMADA => 'info',
    Booking::STATUS_EM_CURSO => 'success',
    Booking::STATUS_CONCLUIDA => 'secondary',
    Booking::STATUS_REJEITADA => 'danger',
    Booking::STATUS_CANCELADA => 'dark',
];
?>
<div class="card card-outline card-primary">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <?php foreach ($tabs as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link<?= $key === $filtro ? ' active' : '' ?>" href="<?= Url::to(['index', 'filtro' => $key]) ?>">
                        <?= $label ?>
                        <?php if (!empty($contagens[$key])): ?>
                            <span class="badge <?= $key === 'pendentes' ? 'badge-warning' : 'badge-light' ?> ml-1"><?= $contagens[$key] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card-body p-0">
        <?php if ($reservas === []): ?>
            <p class="text-muted text-center my-5 px-3">
                <?= $filtro === 'pendentes' ? 'Não há reservas à espera de resposta.' : 'Não há reservas aqui.' ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Animais</th>
                            <th>Estadia</th>
                            <th class="text-right">Preço</th>
                            <th>Estado</th>
                            <th>Responsável</th>
                            <th class="text-right"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservas as $b):
                            $noites = max(1, (int) (new DateTime($b->datainicio))->diff(new DateTime($b->datafim))->days);
                            $url = Url::to(['view', 'id' => $b->id]);
                        ?>
                            <tr>
                                <td><a href="<?= $url ?>"><?= $b->id ?></a></td>
                                <td><?= Html::encode(Conversa::pessoa($b->user)['nome']) ?></td>
                                <td><?= Html::encode(implode(', ', array_map(static fn ($a) => $a->nome, $b->animais)) ?: '—') ?></td>
                                <td class="text-nowrap">
                                    <?= $fmt->asDate($b->datainicio, 'dd/MM') ?> – <?= $fmt->asDate($b->datafim, 'dd/MM/yyyy') ?>
                                    <small class="text-muted">(<?= $noites ?> <?= $noites === 1 ? 'noite' : 'noites' ?>)</small>
                                </td>
                                <td class="text-right text-nowrap"><?= $b->preco !== null ? $fmt->asCurrency($b->preco, 'EUR') : '—' ?></td>
                                <td><span class="badge badge-<?= $cores[$b->status] ?? 'light' ?>"><?= Html::encode($b->getStatusLabel()) ?></span></td>
                                <td>
                                    <?php if ($b->conversa === null): ?>
                                        <span class="text-muted">—</span>
                                    <?php elseif ($b->conversa->colaborador === null): ?>
                                        <span class="badge badge-warning">Sem responsável</span>
                                    <?php else: ?>
                                        <?= Html::encode(Conversa::pessoa($b->conversa->colaborador)['nome']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <a class="btn btn-sm <?= $b->status === Booking::STATUS_PENDENTE ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= $url ?>">
                                        <?= $b->status === Booking::STATUS_PENDENTE ? 'Responder' : 'Abrir' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($dataProvider->pagination->pageCount > 1): ?>
        <div class="card-footer"><?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?></div>
    <?php endif; ?>
</div>
