<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var common\models\Conversa $conversa
 * @var array $colaboradores [id => nome] (só para quem pode reatribuir)
 */

use common\models\Conversa;
use yii\helpers\Html;
use yii\helpers\Url;

$booking = $conversa->booking;
$cliente = $booking->user;
$perfil = $cliente->userProfile;
$pCliente = Conversa::pessoa($cliente);
$fmt = Yii::$app->formatter;

$this->title = 'Conversa com ' . $pCliente['nome'];
$this->params['breadcrumbs'][] = ['label' => 'Conversas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $pCliente['nome'];
?>
<?php foreach (['success', 'error'] as $tipo): ?>
    <?php if (Yii::$app->session->hasFlash($tipo)): ?>
        <div class="alert alert-<?= $tipo === 'error' ? 'danger' : 'success' ?>"><?= Html::encode(Yii::$app->session->getFlash($tipo)) ?></div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="row">
    <div class="col-lg-8 mb-3">
        <?= $this->render('@common/views/chat/_chat', [
            'conversa' => $conversa,
            'urls' => [
                'mensagens' => Url::to(['mensagens', 'id' => $conversa->id]),
                'enviar' => Url::to(['enviar', 'id' => $conversa->id]),
                'lidas' => Url::to(['lidas', 'id' => $conversa->id]),
            ],
        ]) ?>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Cliente</h3></div>
            <div class="card-body">
                <p class="mb-1"><strong><?= Html::encode($pCliente['nome']) ?></strong></p>
                <p class="mb-1"><i class="fas fa-envelope mr-1 text-muted"></i> <?= Html::a(Html::encode($cliente->email), 'mailto:' . $cliente->email) ?></p>
                <?php if (!empty($perfil->telefone)): ?>
                    <p class="mb-0"><i class="fas fa-phone mr-1 text-muted"></i> <?= Html::a(Html::encode($perfil->telefone), 'tel:' . preg_replace('/\s+/', '', $perfil->telefone)) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Reserva #<?= $booking->id ?></h3></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Estado</dt>
                    <dd><span class="badge <?= $conversa->estaAberta() ? 'badge-success' : 'badge-secondary' ?>"><?= Html::encode($booking->getStatusLabel()) ?></span></dd>
                    <dt>Estadia</dt>
                    <dd><?= $fmt->asDate($booking->datainicio, 'long') ?> a <?= $fmt->asDate($booking->datafim, 'long') ?></dd>
                    <dt>Animais</dt>
                    <dd class="mb-0">
                        <?php foreach ($booking->animais as $a): ?>
                            <?php $raca = $a->getRacaTexto(); ?>
                            <div><?= Html::encode($a->nome) ?><?= $raca ? ' <small class="text-muted">· ' . Html::encode($raca) . '</small>' : '' ?></div>
                            <?php if (trim((string) $a->observacoes) !== ''): ?>
                                <small class="text-muted d-block mb-1"><?= Html::encode($a->observacoes) ?></small>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($booking->animais === []): ?>—<?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Responsável</h3></div>
            <div class="card-body">
                <p class="<?= $colaboradores ? 'mb-3' : 'mb-0' ?>">
                    <?= $conversa->colaborador
                        ? Html::encode(Conversa::pessoa($conversa->colaborador)['nome'])
                        : '<span class="badge badge-warning">Sem responsável</span>' ?>
                </p>
                <?php if ($colaboradores): ?>
                    <?= Html::beginForm(['reatribuir', 'id' => $conversa->id], 'post') ?>
                        <label for="reatribuir-colaborador">Passar a conversa para</label>
                        <div class="input-group">
                            <?= Html::dropDownList('id_colaborador', $conversa->id_colaborador === null ? null : (string) $conversa->id_colaborador, $colaboradores, [
                                'id' => 'reatribuir-colaborador',
                                'class' => 'form-control',
                                'prompt' => 'Escolher colaborador…',
                                'required' => true,
                            ]) ?>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">Reatribuir</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">O cliente vê no chat que a conversa passou para outra pessoa.</small>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
