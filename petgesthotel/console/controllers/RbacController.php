<?php

declare(strict_types=1);

namespace console\controllers;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{
    public function actionInit(): void
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();

        // 1. Permissões

        // animal
        $createAnimal = $auth->createPermission('createAnimal');
        $auth->add($createAnimal);
        $editOwnAnimal = $auth->createPermission('editOwnAnimal');
        $auth->add($editOwnAnimal);
        $deleteOwnAnimal = $auth->createPermission('deleteOwnAnimal');
        $auth->add($deleteOwnAnimal);
        $viewOwnAnimal = $auth->createPermission('viewOwnAnimal');
        $auth->add($viewOwnAnimal);

        //colaboradores animais
        $viewAnimal = $auth->createPermission('viewAnimal');
        $auth->add($viewAnimal);
        $changeAnimalStatus = $auth->createPermission('changeAnimalStatus');
        $auth->add($changeAnimalStatus);

        // booking
        $createBooking = $auth->createPermission('createBooking');
        $auth->add($createBooking);
        $editBooking = $auth->createPermission('editBooking');
        $auth->add($editBooking);
        $cancelBooking = $auth->createPermission('cancelBooking');
        $auth->add($cancelBooking);
        $viewBooking = $auth->createPermission('viewBooking');
        $auth->add($viewBooking);
        $approveBooking = $auth->createPermission('approveBooking');
        $auth->add($approveBooking);
        $rejectBooking = $auth->createPermission('rejectBooking');
        $auth->add($rejectBooking);
        $checkIn = $auth->createPermission('checkIn');
        $auth->add($checkIn);
        $checkOut = $auth->createPermission('checkOut');
        $auth->add($checkOut);
        $addExtraService = $auth->createPermission('addExtraService');
        $auth->add($addExtraService);
        $payBooking = $auth->createPermission('payBooking');
        $auth->add($payBooking);

        // histórico
        $viewHistorico = $auth->createPermission('viewHistorico');
        $auth->add($viewHistorico);
        $createHistorico = $auth->createPermission('createHistorico');
        $auth->add($createHistorico);
        $editHistorico = $auth->createPermission('editHistorico');
        $auth->add($editHistorico);
        $deleteHistorico = $auth->createPermission('deleteHistorico');
        $auth->add($deleteHistorico);

        // faturas
        $viewInvoice = $auth->createPermission('viewInvoice');
        $auth->add($viewInvoice);
        $createInvoice = $auth->createPermission('createInvoice');
        $auth->add($createInvoice);

        // mensagens e media
        $sendMessage = $auth->createPermission('sendMessage');
        $auth->add($sendMessage);
        $uploadMedia = $auth->createPermission('uploadMedia');
        $auth->add($uploadMedia);

        // pesquisa
        $searchClient = $auth->createPermission('searchClient');
        $auth->add($searchClient);

        // perfil
        $editProfile = $auth->createPermission('editProfile');
        $auth->add($editProfile);

        // gerir users
        $grantColaborador = $auth->createPermission('grantColaborador');
        $auth->add($grantColaborador);
        $revokeColaborador = $auth->createPermission('revokeColaborador');
        $auth->add($revokeColaborador);
        $disableAccount = $auth->createPermission('disableAccount');
        $auth->add($disableAccount);
        $enableAccount = $auth->createPermission('enableAccount');
        $auth->add($enableAccount);

        // aceder ao backend
        $accessBackend = $auth->createPermission('accessBackend');
        $auth->add($accessBackend);

        // gestão do hotel
        $manageQuarto = $auth->createPermission('manageQuarto');
        $auth->add($manageQuarto);
        $manageServicoExtra = $auth->createPermission('manageServicoExtra');
        $auth->add($manageServicoExtra);
        $viewReports = $auth->createPermission('viewReports');
        $auth->add($viewReports);

        // 2. Roles

        $cliente = $auth->createRole('cliente');
        $auth->add($cliente);
        $auth->addChild($cliente, $createAnimal);
        $auth->addChild($cliente, $editOwnAnimal);
        $auth->addChild($cliente, $deleteOwnAnimal);
        $auth->addChild($cliente, $viewAnimal);
        $auth->addChild($cliente, $createBooking);
        $auth->addChild($cliente, $editBooking);
        $auth->addChild($cliente, $cancelBooking);
        $auth->addChild($cliente, $viewBooking);
        $auth->addChild($cliente, $payBooking);
        $auth->addChild($cliente, $addExtraService);
        $auth->addChild($cliente, $viewInvoice);
        $auth->addChild($cliente, $sendMessage);
        $auth->addChild($cliente, $viewHistorico);
        $auth->addChild($cliente, $editProfile);

        $colaborador = $auth->createRole('colaborador');
        $auth->add($colaborador);
        $auth->addChild($colaborador, $accessBackend);
        $auth->addChild($colaborador, $viewAnimal);
        $auth->addChild($colaborador, $changeAnimalStatus);
        $auth->addChild($colaborador, $viewBooking);
        $auth->addChild($colaborador, $approveBooking);
        $auth->addChild($colaborador, $rejectBooking);
        $auth->addChild($colaborador, $checkIn);
        $auth->addChild($colaborador, $checkOut);
        $auth->addChild($colaborador, $createInvoice);
        $auth->addChild($colaborador, $sendMessage);
        $auth->addChild($colaborador, $uploadMedia);
        $auth->addChild($colaborador, $searchClient);
        $auth->addChild($colaborador, $viewHistorico);
        $auth->addChild($colaborador, $createHistorico);
        $auth->addChild($colaborador, $editHistorico);
        $auth->addChild($colaborador, $deleteHistorico);

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $colaborador); // herda tudo do colaborador
        $auth->addChild($admin, $grantColaborador);
        $auth->addChild($admin, $revokeColaborador);
        $auth->addChild($admin, $disableAccount);
        $auth->addChild($admin, $enableAccount);
        $auth->addChild($admin, $manageQuarto);
        $auth->addChild($admin, $manageServicoExtra);
        $auth->addChild($admin, $viewReports);

        // 3. Atribuir admin ao utilizador com id 1 (ajusta o id)
        $auth->assign($admin, 5);

        echo "RBAC inicializado.\n";
    }
}