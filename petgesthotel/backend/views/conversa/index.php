<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $filtro
 * @var bool $admin
 * @var int $semResponsavel
 */

use backend\controllers\ConversaController;
use common\models\Conversa;
use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Conversas';
$this->params['breadcrumbs'][] = $this->title;

$eu = (int) Yii::$app->user->id;
$fmt = Yii::$app->formatter;
/** @var Conversa[] $conversas */
$conversas = $dataProvider->getModels();

$tabs = [ConversaController::FILTRO_MINHAS => 'As minhas'];
if ($admin) {
    $tabs[ConversaController::FILTRO_SEM_RESPONSAVEL] = 'Sem responsável' . ($semResponsavel > 0 ? ' <span class="badge badge-warning">' . $semResponsavel . '</span>' : '');
    $tabs[ConversaController::FILTRO_TODAS] = 'Todas';
}
?>
<div class="card card-outline card-primary">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <?php foreach ($tabs as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link<?= $key === $filtro ? ' active' : '' ?>" href="<?= Url::to(['index', 'filtro' => $key]) ?>"><?= $label ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card-body p-0">
        <?php if ($conversas === []): ?>
            <p class="text-muted text-center my-5 px-3">
                <?= $filtro === ConversaController::FILTRO_MINHAS
                    ? 'Não tem conversas. Quando confirmar uma reserva, fica responsável pela conversa com o cliente.'
                    : 'Não há conversas aqui.' ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Animais</th>
                            <th>Estadia</th>
                            <th>Estado</th>
                            <?php if ($filtro !== ConversaController::FILTRO_MINHAS): ?><th>Responsável</th><?php endif; ?>
                            <th>Última mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conversas as $c):
                            $b = $c->booking;
                            $naoLidas = $c->naoLidas($eu);
                            $ultima = $c->ultimaMensagem;
                            $ativa = $c->estaAberta();
                            $url = Url::to(['view', 'id' => $c->id]);
                        ?>
                            <tr class="<?= $naoLidas > 0 ? 'font-weight-bold' : '' ?><?= $ativa ? '' : ' text-muted' ?>" style="cursor:pointer" onclick="location.href='<?= $url ?>'">
                                <td>
                                    <a href="<?= $url ?>"><?= Html::encode(Conversa::pessoa($b->user)['nome']) ?></a>
                                    <?php if ($naoLidas > 0): ?>
                                        <span class="badge badge-danger ml-1" title="Mensagens por ler"><?= $naoLidas ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= Html::encode(implode(', ', array_map(static fn ($a) => $a->nome, $b->animais)) ?: '—') ?></td>
                                <td class="text-nowrap"><?= $fmt->asDate($b->datainicio, 'dd/MM') ?> – <?= $fmt->asDate($b->datafim, 'dd/MM/yyyy') ?></td>
                                <td><span class="badge <?= $ativa ? 'badge-success' : 'badge-secondary' ?>"><?= Html::encode($b->getStatusLabel()) ?></span></td>
                                <?php if ($filtro !== ConversaController::FILTRO_MINHAS): ?>
                                    <td><?= $c->colaborador ? Html::encode(Conversa::pessoa($c->colaborador)['nome']) : '<span class="badge badge-warning">Sem responsável</span>' ?></td>
                                <?php endif; ?>
                                <td style="max-width: 320px">
                                    <?php if ($ultima): ?>
                                        <div class="text-truncate"><?= Html::encode($ultima->texto) ?></div>
                                        <small class="text-muted"><?= $fmt->asRelativeTime($ultima->created_at) ?></small>
                                    <?php endif; ?>
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
