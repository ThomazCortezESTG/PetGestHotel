<?php

declare(strict_types=1);

/**
 * Perfil do cliente (criar na primeira vez / editar depois).
 *
 * @var yii\web\View $this
 * @var common\models\User $user
 * @var common\models\UserProfile $profile
 * @var bool $isNew
 */

use frontend\assets\PerfilAsset;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

PerfilAsset::register($this);

$this->title = ($isNew ? 'Complete o seu perfil' : 'Editar perfil') . ' | PetGestHotel';
$this->params['meta_description'] = 'Dados pessoais do cliente PetGestHotel.';

$opcional = ' <span class="pg-acct__optional">opcional</span>';
?>

<div class="pg-acct pg-acct--edit" data-perfil>
    <section class="pg-acct__band pg-acct__band--slim" aria-labelledby="perfil-title">
        <div class="pg-acct__wrap">
            <?php if ($isNew): ?>
                <ol class="pg-acct__steps" aria-label="Primeiros passos">
                    <li aria-current="step"><span>1</span>O seu perfil</li>
                    <li><span>2</span>O seu animal</li>
                </ol>
                <h1 class="pg-acct__title" id="perfil-title">Complete o seu perfil</h1>
                <p class="pg-acct__lead">Precisamos destes dados para confirmar reservas e emitir faturas. Demora um minuto.</p>
            <?php else: ?>
                <a class="pg-acct__back" href="<?= Url::to(['index']) ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    O meu perfil
                </a>
                <h1 class="pg-acct__title" id="perfil-title">Editar perfil</h1>
                <p class="pg-acct__lead">Mantenha o contacto atualizado para a equipa falar consigo durante a estadia.</p>
            <?php endif; ?>
        </div>
    </section>

    <div class="pg-acct__wrap pg-acct__body">
        <?= $isNew ? '' : $this->render('_nav', ['active' => 'perfil']) ?>

        <div class="pg-acct__main">
            <?php $form = ActiveForm::begin(['id' => 'form-perfil', 'options' => ['class' => 'pg-acct__form', 'novalidate' => true]]); ?>

                <div class="pg-acct__group" id="f-fotografia" role="group" aria-labelledby="h-fotografia">
                    <div class="pg-acct__group-head"><h2 id="h-fotografia">Fotografia</h2><p>Ajuda a equipa a reconhecê-lo quando vier deixar ou buscar o seu animal.</p></div>
                    <div class="pg-acct__fields pg-acct__photo">
                        <?= $this->render('_avatar', ['user' => $user, 'profile' => $profile, 'size' => 'lg']) ?>
                        <div class="pg-acct__photo-actions">
                            <button type="button" class="pg-btn pg-btn--outline" data-avatar-pick><?= $profile->avatar ? 'Alterar fotografia' : 'Carregar fotografia' ?></button>
                            <button type="button" class="pg-acct__photo-remove" data-avatar-remove<?= $profile->avatar ? '' : ' hidden' ?>>Remover</button>
                            <p class="pg-acct__photo-hint">JPG, PNG ou WebP. Pode ajustar o recorte antes de guardar.</p>
                        </div>
                    </div>
                </div>

                <div class="pg-acct__group" id="f-contacto" role="group" aria-labelledby="h-contacto">
                    <div class="pg-acct__group-head"><h2 id="h-contacto">Contacto</h2><p>É por aqui que a equipa fala consigo durante a estadia.</p></div>
                    <div class="pg-acct__fields">
                        <div class="pg-acct__account">
                            <span>E-mail da conta</span>
                            <strong><?= Html::encode($user->email) ?></strong>
                        </div>
                        <?= $form->field($profile, 'nome')
                            ->textInput(['autofocus' => $isNew, 'autocomplete' => 'name', 'maxlength' => 255, 'data-tag' => 'nome'])
                            ->label('Nome completo') ?>
                        <?= $form->field($profile, 'telefone')
                            ->textInput(['type' => 'tel', 'inputmode' => 'numeric', 'autocomplete' => 'tel-national', 'placeholder' => '912 345 678', 'maxlength' => 11, 'data-tag' => 'telefone']) ?>
                    </div>
                </div>

                <div class="pg-acct__group" id="f-faturacao" role="group" aria-labelledby="h-faturacao">
                    <div class="pg-acct__group-head"><h2 id="h-faturacao">Faturação</h2><p>Aparece nas faturas das suas estadias.</p></div>
                    <div class="pg-acct__fields">
                        <?= $form->field($profile, 'morada')
                            ->textInput(['autocomplete' => 'street-address', 'placeholder' => 'Rua, número, código postal e localidade', 'maxlength' => 255])
                            ->label('Morada' . $opcional) ?>
                        <?= $form->field($profile, 'n_contribuinte')
                            ->textInput(['inputmode' => 'numeric', 'placeholder' => '123456789', 'maxlength' => 9, 'data-tag' => 'n_contribuinte'])
                            ->label('NIF' . $opcional)
                            ->hint('Sem NIF, a fatura sai como consumidor final.') ?>
                    </div>
                </div>

                <div class="pg-acct__group" id="f-pessoal" role="group" aria-labelledby="h-pessoal">
                    <div class="pg-acct__group-head"><h2 id="h-pessoal">Pessoal</h2><p>Só usamos para confirmar que é maior de idade.</p></div>
                    <div class="pg-acct__fields">
                        <?= $form->field($profile, 'data_nascimento')
                            ->input('date', ['autocomplete' => 'bday', 'max' => date('Y-m-d', strtotime('-18 years'))])
                            ->label('Data de nascimento' . $opcional) ?>
                    </div>
                </div>

                <div class="pg-acct__savebar" data-savebar>
                    <p class="pg-acct__savebar-text" aria-live="polite" data-savebar-text><?= $isNew ? 'Pode alterar estes dados mais tarde.' : 'Sem alterações.' ?></p>
                    <div class="pg-acct__savebar-actions">
                        <?php if (!$isNew): ?>
                            <a class="pg-btn pg-btn--outline" href="<?= Url::to(['index']) ?>">Cancelar</a>
                        <?php endif; ?>
                        <?= Html::submitButton($isNew ? 'Guardar e continuar' : 'Guardar alterações', ['class' => 'pg-btn pg-btn--primary']) ?>
                    </div>
                </div>

            <?php ActiveForm::end(); ?>

            <aside class="pg-acct__preview" aria-label="Pré-visualização">
                <p class="pg-acct__preview-label">Assim fica a sua etiqueta de cliente</p>
                <?= $this->render('_tag', ['user' => $user, 'profile' => $profile]) ?>
            </aside>
        </div>
    </div>
    <?= $this->render('_cropper') ?>
</div>
