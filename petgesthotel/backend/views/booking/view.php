<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var common\models\Booking $booking
 * @var bool $podeEscolher pode escolher outro responsável ao aceitar (admin)
 * @var array $colaboradores [id => "Nome · n reservas ativas"]
 */

use common\models\Booking;
use common\models\Conversa;
use yii\helpers\Html;
use yii\helpers\Url;

$fmt = Yii::$app->formatter;
$cliente = $booking->user;
$perfil = $cliente->userProfile;
$pCliente = Conversa::pessoa($cliente);
$noites = max(1, (int) (new DateTime($booking->datainicio))->diff(new DateTime($booking->datafim))->days);
$user = Yii::$app->user;

$this->title = 'Reserva #' . $booking->id;
$this->params['breadcrumbs'][] = ['label' => 'Reservas', 'url' => ['index']];
$this->params['breadcrumbs'][] = '#' . $booking->id;

$cores = [
    Booking::STATUS_PENDENTE => 'warning',
    Booking::STATUS_CONFIRMADA => 'info',
    Booking::STATUS_EM_CURSO => 'success',
    Booking::STATUS_CONCLUIDA => 'secondary',
    Booking::STATUS_REJEITADA => 'danger',
    Booking::STATUS_CANCELADA => 'dark',
];
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Estadia</h3>
                <div class="card-tools">
                    <span class="badge badge-<?= $cores[$booking->status] ?? 'light' ?>" style="font-size: 90%"><?= Html::encode($booking->getStatusLabel()) ?></span>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Entrada</dt>
                    <dd class="col-sm-9"><?= $fmt->asDate($booking->datainicio, 'full') ?></dd>
                    <dt class="col-sm-3">Saída</dt>
                    <dd class="col-sm-9"><?= $fmt->asDate($booking->datafim, 'full') ?> <small class="text-muted">(<?= $noites ?> <?= $noites === 1 ? 'noite' : 'noites' ?>)</small></dd>
                    <dt class="col-sm-3">Preço</dt>
                    <dd class="col-sm-9"><?= $booking->preco !== null ? $fmt->asCurrency($booking->preco, 'EUR') : '—' ?></dd>
                    <?php if ($booking->status === Booking::STATUS_REJEITADA && $booking->motivo_rejeicao): ?>
                        <dt class="col-sm-3">Motivo</dt>
                        <dd class="col-sm-9"><?= Html::encode($booking->motivo_rejeicao) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Animais, quartos e serviços</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Animal</th><th>Quarto</th><th>Serviços extra</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booking->bookingAnimalQuartos as $baq):
                            $a = $baq->animal;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= Html::encode($a->nome) ?></strong>
                                    <?php if ($raca = $a->getRacaTexto()): ?><br><small class="text-muted"><?= Html::encode($raca) ?></small><?php endif; ?>
                                    <?php if (trim((string) $a->vacinas) !== ''): ?><br><small>Vacinas: <?= Html::encode($a->vacinas) ?></small><?php endif; ?>
                                    <?php if (trim((string) $a->observacoes) !== ''): ?><br><small class="text-danger"><?= Html::encode($a->observacoes) ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <?= Html::encode($baq->quarto->categoria ?? '—') ?>
                                    <?php $baq->populateRelation('booking', $booking); ?>
                                    <?php if ($baq->temDatasProprias()): ?>
                                        <br><span class="badge badge-info"><?= $fmt->asDate($baq->getEntrada(), 'dd/MM') ?> – <?= $fmt->asDate($baq->getSaida(), 'dd/MM') ?> · <?= $baq->getNoites() ?> <?= $baq->getNoites() === 1 ? 'noite' : 'noites' ?></span>
                                    <?php endif; ?>
                                    <?php if ($baq->quarto): ?><br><small class="text-muted"><?= $fmt->asCurrency($baq->quarto->preco, 'EUR') ?>/noite</small><?php endif; ?>
                                </td>
                                <td>
                                    <?= Html::encode(implode(', ', array_map(static fn ($s) => $s->nome, $baq->servicos)) ?: '—') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($booking->bookingAnimalQuartos === []): ?>
                            <tr><td colspan="3" class="text-muted">Sem animais associados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php // ---------------- ações conforme o estado ---------------- ?>
        <?php if ($booking->status === Booking::STATUS_PENDENTE): ?>
            <?php if ($user->can('approveBooking')): ?>
                <div class="card card-success card-outline">
                    <div class="card-header"><h3 class="card-title">Aceitar reserva</h3></div>
                    <div class="card-body">
                        <?= Html::beginForm(['aceitar', 'id' => $booking->id], 'post') ?>
                            <?php if ($podeEscolher): ?>
                                <div class="form-group">
                                    <label for="aceitar-responsavel">Colaborador responsável</label>
                                    <?= Html::dropDownList('id_colaborador', (string) $user->id, $colaboradores, ['id' => 'aceitar-responsavel', 'class' => 'form-control']) ?>
                                    <small class="form-text text-muted">É quem fala com o cliente no chat durante a estadia. Pode mudar depois na conversa.</small>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Ao aceitar, fica responsável por esta reserva e abre-se o chat com o cliente.</p>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success btn-block">Aceitar reserva</button>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($user->can('rejectBooking')): ?>
                <div class="card card-danger card-outline">
                    <div class="card-header"><h3 class="card-title">Rejeitar reserva</h3></div>
                    <div class="card-body">
                        <?= Html::beginForm(['rejeitar', 'id' => $booking->id], 'post') ?>
                            <div class="form-group">
                                <label for="rejeitar-motivo">Motivo (o cliente vai ver)</label>
                                <textarea id="rejeitar-motivo" name="motivo" class="form-control" rows="3" maxlength="500" required placeholder="Ex.: Não temos quartos disponíveis nessas datas."></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-block">Rejeitar reserva</button>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ($booking->status === Booking::STATUS_CONFIRMADA && $user->can('checkIn')): ?>
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <p>Quando <?= Html::encode(implode(', ', array_map(static fn ($a) => $a->nome, $booking->animais)) ?: 'o animal') ?> chegar ao hotel:</p>
                    <?= Html::beginForm(['checkin', 'id' => $booking->id], 'post') ?>
                        <button type="submit" class="btn btn-primary btn-block">Fazer check-in</button>
                    <?= Html::endForm() ?>
                </div>
            </div>

        <?php elseif ($booking->status === Booking::STATUS_EM_CURSO && $user->can('checkOut')): ?>
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <p>Quando o cliente vier buscar <?= Html::encode(implode(', ', array_map(static fn ($a) => $a->nome, $booking->animais)) ?: 'o animal') ?>:</p>
                    <?= Html::beginForm(['checkout', 'id' => $booking->id], 'post', [
                        'onsubmit' => "return confirm('Terminar a estadia? A conversa com o cliente fica só para leitura.')",
                    ]) ?>
                        <button type="submit" class="btn btn-primary btn-block">Fazer check-out</button>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endif; ?>

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

        <?php if ($booking->conversa !== null): ?>
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title">Conversa com o cliente</h3></div>
                <div class="card-body">
                    <p class="mb-2">Responsável:
                        <?= $booking->conversa->colaborador
                            ? '<strong>' . Html::encode(Conversa::pessoa($booking->conversa->colaborador)['nome']) . '</strong>'
                            : '<span class="badge badge-warning">Sem responsável</span>' ?>
                    </p>
                    <?php if ($booking->conversa->podeVer((int) $user->id)): ?>
                        <a class="btn btn-outline-primary btn-block" href="<?= Url::to(['/conversa/view', 'id' => $booking->conversa->id]) ?>">
                            <i class="fas fa-comments mr-1"></i> Abrir conversa
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
