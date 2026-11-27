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
                    <h3 class="card-title"><i class="fas fa-building mr-2"></i>Administração & Receção</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-envelope text-primary mr-2"></i> <strong>Email Geral:</strong> geral@petgesthotel.pt</li>
                        <li class="mb-2"><i class="fas fa-phone text-primary mr-2"></i> <strong>Receção Hotel:</strong> +351 244 812 450</li>
                        <li><i class="fas fa-map-pin text-primary mr-2"></i> Rua da Quinta do Bispo, 37, 2410-196 Leiria</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Suporte Técnico Interno -->
        <div class="col-md-7">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-headset mr-2"></i>Suporte Técnico da Plataforma</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Em caso de erro no sistema, problemas com reservas ou acessos de colaboradores, contacta a equipa de desenvolvimento:</p>
                    <table class="table table-bordered table-sm">
                        <thead>
                        <tr>
                            <th>Área</th>
                            <th>Responsável / Canal</th>
                            <th>Disponibilidade</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Gestão de Reservas & Clientes</td>
                            <td>suporte.reservas@petgesthotel.pt</td>
                            <td>09h00 – 19h00</td>
                        </tr>
                        <tr>
                            <td>Suporte Informático / Backoffice</td>
                            <td>ti@petgesthotel.pt</td>
                            <td>24h (Ticket Interno)</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
