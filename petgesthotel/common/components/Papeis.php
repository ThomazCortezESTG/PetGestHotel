<?php

declare(strict_types=1);

namespace common\components;

use common\models\Conversa;
use Yii;

/**
 * Papel de cada utilizador: cliente, colaborador ou admin (um de cada vez).
 * Os papéis e as permissões estão definidos no RBAC (console/controllers/RbacController.php);
 * aqui só se escolhe qual deles cada pessoa tem.
 */
final class Papeis
{
    public const CLIENTE = 'cliente';
    public const COLABORADOR = 'colaborador';
    public const ADMIN = 'admin';

    public static function lista(): array
    {
        return [
            self::CLIENTE => 'Cliente',
            self::COLABORADOR => 'Colaborador',
            self::ADMIN => 'Admin',
        ];
    }

    /**
     * O papel principal (admin > colaborador > cliente), ou null se não tiver nenhum.
     */
    public static function de(int $idUser): ?string
    {
        $tem = array_keys(Yii::$app->authManager->getRolesByUser($idUser));
        foreach ([self::ADMIN, self::COLABORADOR, self::CLIENTE] as $papel) {
            if (in_array($papel, $tem, true)) {
                return $papel;
            }
        }
        return null;
    }

    public static function nome(?string $papel): string
    {
        return self::lista()[$papel] ?? 'Sem papel';
    }

    /**
     * Muda o papel de um utilizador. Quem deixa de ser da equipa larga as conversas de que era
     * responsável: passam a "sem responsável" e o cliente é avisado no chat.
     *
     * @throws \InvalidArgumentException com uma mensagem para mostrar
     */
    public static function mudar(int $idUser, string $papel, int $idQuemMuda): void
    {
        if (!isset(self::lista()[$papel])) {
            throw new \InvalidArgumentException('Papel inválido.');
        }
        $atual = self::de($idUser);
        if ($atual === $papel) {
            return;
        }
        if ($idUser === $idQuemMuda) {
            throw new \InvalidArgumentException('Não pode mudar o seu próprio papel (ficava sem acesso à gestão). Peça a outro admin.');
        }

        $auth = Yii::$app->authManager;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach (array_keys(self::lista()) as $p) {
                $role = $auth->getRole($p);
                if ($role !== null && $auth->getAssignment($p, $idUser) !== null) {
                    $auth->revoke($role, $idUser);
                }
            }
            $auth->assign($auth->getRole($papel), $idUser);

            if ($papel === self::CLIENTE) {
                Conversa::largarResponsavel($idUser);
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
