<?php
/* @var $content string */

use yii\bootstrap4\Breadcrumbs;
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <?php
                        if (!is_null($this->title)) {
                            echo \yii\helpers\Html::encode($this->title);
                        } else {
                            echo \yii\helpers\Inflector::camelize($this->context->id);
                        }
                        ?>
                    </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <?php
                    echo Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        'options' => [
                            'class' => 'breadcrumb float-sm-right'
                        ]
                    ]);
                    ?>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <?php // mensagens flash (setFlash) de qualquer página: success, info, warning, error ?>
        <?php foreach (Yii::$app->session->getAllFlashes() as $tipo => $mensagens): ?>
            <?php foreach ((array) $mensagens as $mensagem): ?>
                <div class="container-fluid">
                    <div class="alert alert-<?= ['error' => 'danger', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info'][$tipo] ?? 'success' ?> alert-dismissible fade show" role="<?= in_array($tipo, ['error', 'danger'], true) ? 'alert' : 'status' ?>">
                        <?= \yii\helpers\Html::encode($mensagem) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?= $content ?><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>