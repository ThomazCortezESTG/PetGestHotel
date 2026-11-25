<?php

declare(strict_types=1);

/**
 * Perfil do cliente (consulta).
 *
 * @var yii\web\View $this
 * @var common\models\User $user
 * @var common\models\UserProfile $profile
 */

use frontend\assets\PerfilAsset;
use yii\helpers\Html;
use yii\helpers\Url;

PerfilAsset::register($this);

$this->title = 'O meu perfil | PetGestHotel';
$this->params['meta_description'] = 'Os seus dados de cliente no PetGestHotel.';

$nAnimais = (int) \common\models\Animal::find()->where(['id_user' => $user->id])->count();

$primeiroNome = explode(' ', trim((string) $profile->nome))[0];
$telefone = $profile->telefone ? trim(chunk_split($profile->telefone, 3, ' ')) : null;
$nascimento = $profile->data_nascimento ? Yii::$app->formatter->asDate($profile->data_nascimento, 'long') : null;

// Quanto do perfil está preenchido (os 5 campos do perfil)
$campos = ['nome' => 'nome', 'telefone' => 'telefone', 'data_nascimento' => 'data de nascimento', 'morada' => 'morada', 'n_contribuinte' => 'NIF'];
$emFalta = array_values(array_filter($campos, static fn($l, $k) => trim((string) $profile->$k) === '', ARRAY_FILTER_USE_BOTH));
$pct = (int) round((count($campos) - count($emFalta)) / count($campos) * 100);

$valor = static fn(?string $v, string $vazio = 'Não indicado'): string =>
    ($v === null || $v === '') ? '<span class="pg-acct__empty">' . $vazio . '</span>' : Html::encode($v);

$grupos = [
    'contacto' => [
        'titulo' => 'Contacto',
        'texto' => 'É por aqui que a equipa fala consigo durante a estadia.',
        'linhas' => [
            'Nome completo' => $valor($profile->nome),
            'E-mail' => Html::encode($user->email),
            'Telefone' => $valor($telefone),
        ],
    ],
    'faturacao' => [
        'titulo' => 'Faturação',
        'texto' => 'Aparece nas faturas das suas estadias.',
        'linhas' => [
            'Morada' => $valor($profile->morada),
            'NIF' => $valor($profile->n_contribuinte, 'Fatura sem NIF'),
        ],
    ],
    'pessoal' => [
        'titulo' => 'Pessoal',
        'texto' => 'Só usamos para confirmar que é maior de idade.',
        'linhas' => [
            'Data de nascimento' => $valor($nascimento),
        ],
    ],
];
?>

<div class="pg-acct" data-perfil>
    <section class="pg-acct__band" aria-labelledby="perfil-title">
        <div class="pg-acct__wrap pg-acct__band-inner">
            <div class="pg-acct__hello">
                <?= $this->render('_avatar', ['user' => $user, 'profile' => $profile, 'size' => 'xl']) ?>
                <p class="pg-acct__since">Cliente desde <?= Yii::$app->formatter->asDate($user->created_at, 'MMMM \'de\' yyyy') ?></p>
                <h1 class="pg-acct__title" id="perfil-title">Olá, <?= Html::encode($primeiroNome) ?></h1>
                <p class="pg-acct__lead">Aqui estão os dados que usamos nas suas reservas e faturas.</p>
                <a class="pg-btn pg-btn--primary pg-acct__cta" href="<?= Url::to(['update']) ?>">Editar perfil</a>
            </div>
            <?= $this->render('_tag', ['user' => $user, 'profile' => $profile]) ?>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $this->render('_nav', ['active' => 'perfil']) ?>

        <div class="pg-acct__main">
            <?php if ($pct < 100): ?>
                <div class="pg-acct__meter" style="--pct: <?= $pct ?>">
                    <div class="pg-acct__meter-text">
                        <p><strong>Perfil <?= $pct ?>% preenchido.</strong> Falta: <?= Html::encode(implode(', ', $emFalta)) ?>.</p>
                        <a href="<?= Url::to(['update']) ?>">Completar</a>
                    </div>
                    <div class="pg-acct__meter-bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Perfil preenchido"><span></span></div>
                </div>
            <?php endif; ?>

            <?php foreach ($grupos as $id => $g): ?>
                <section class="pg-acct__group" aria-labelledby="g-<?= $id ?>">
                    <div class="pg-acct__group-head">
                        <h2 id="g-<?= $id ?>"><?= $g['titulo'] ?></h2>
                        <p><?= $g['texto'] ?></p>
                    </div>
                    <dl class="pg-acct__rows">
                        <?php foreach ($g['linhas'] as $rotulo => $html): ?>
                            <div><dt><?= $rotulo ?></dt><dd><?= $html ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                    <a class="pg-acct__group-edit" href="<?= Url::to(['update', '#' => 'f-' . $id]) ?>" aria-label="Editar <?= mb_strtolower($g['titulo']) ?>">Editar</a>
                </section>
            <?php endforeach; ?>

            <section class="pg-acct__pets" aria-labelledby="g-animais">
                <div class="pg-acct__pets-art" aria-hidden="true">
                    <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="56" fill="currentColor" opacity=".12"/><g fill="currentColor"><circle cx="34" cy="52" r="9"/><circle cx="50" cy="34" r="9"/><circle cx="70" cy="34" r="9"/><circle cx="86" cy="52" r="9"/><path d="M60 56c-12 0-24 14-24 25 0 7 5 9 10 9 6 0 9-3 14-3s8 3 14 3c5 0 10-2 10-9 0-11-12-25-24-25z"/></g></svg>
                </div>
                <div>
                    <?php if ($nAnimais > 0): ?>
                        <h2 id="g-animais"><?= $nAnimais === 1 ? 'Tem 1 animal registado' : 'Tem ' . $nAnimais . ' animais registados' ?></h2>
                        <p>Confirme que as vacinas e os cuidados estão em dia antes de reservar uma estadia.</p>
                        <a class="pg-btn pg-btn--outline" href="<?= Url::to(['/pet/index']) ?>">Ver os meus animais</a>
                    <?php else: ?>
                        <h2 id="g-animais">Ainda não tem animais registados</h2>
                        <p>Para reservar uma estadia, registe primeiro o seu cão ou gato com a raça, as vacinas e os cuidados de que precisa.</p>
                        <a class="pg-btn pg-btn--primary" href="<?= Url::to(['/pet/create']) ?>">Registar animal</a>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <?= $this->render('_cropper') ?>
</div>
