<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Contactos & Suporte';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-contact">
    <div class="row">
        <!-- Coluna Esquerda: Contactos Úteis e Emergência -->
        <div class="col-md-5">
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-ambulance mr-2"></i>Emergência Veterinária 24h</h3>
                </div>
                <div class="card-body">
                    <p><strong>Clínica Veterinária Parceira (Leiria):</strong></p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone-alt text-danger mr-2"></i> <strong>Urgências:</strong> +351 244 000 111</li>
                        <li><i class="fas fa-map-marker-alt text-muted mr-2"></i> Av. Marquês de Pombal, Leiria</li>
                        <li><i class="fas fa-clock text-muted mr-2"></i> Atendimento permanente 24h/7d</li>
                    </ul>
                </div>
            </div>

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-headset mr-2"></i>Equipa & Suporte Técnico</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-user-shield text-primary mr-2"></i> <strong>Administração:</strong> +351 910 000 222</li>
                        <li class="mb-2"><i class="fas fa-envelope text-primary mr-2"></i> <strong>Email Interno:</strong> equipa@petgesthotel.pt</li>
                        <li><i class="fas fa-tools text-primary mr-2"></i> <strong>Suporte Informático:</strong> suporte@petgesthotel.pt</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Formulário de Reporte Interno -->
        <div class="col-md-7">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paper-plane mr-2"></i>Reportar Ocorrência ou Pedido Interno</h3>
                </div>
                <?= Html::beginForm(['/site/contact']) ?>
                <div class="card-body">
                    <div class="form-group">
                        <label for="colaborador">Colaborador</label>
                        <input type="text" id="colaborador" class="form-control" value="<?= Html::encode(Yii::$app->user->identity->username ?? 'Utilizador') ?>" disabled>

                    <div class="form-group">
                        <label for="assunto">Assunto / Categoria</label>
                        <select name="assunto" id="assunto" class="form-control" required>
                            <option value="">-- Selecione o motivo --</option>
                            <option value="Problema Técnico no BackOffice">Problema Técnico no BackOffice</option>
                            <option value="Ocorrência com Animal / Alojamento">Ocorrência com Animal / Alojamento</option>
                            <option value="Falta de Stock (Ração / Medicamentos)">Falta de Stock (Ração / Medicamentos)</option>
                            <option value="Outro Assunto">Outro Assunto</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mensagem">Descrição detalhada</label>
                        <textarea name="mensagem" id="mensagem" rows="5" class="form-control" placeholder="Descreva a situação..." required></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <?= Html::submitButton('<i class="fas fa-check mr-1"></i> Enviar Registo', ['class' => 'btn btn-info']) ?>
                </div>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
</div>