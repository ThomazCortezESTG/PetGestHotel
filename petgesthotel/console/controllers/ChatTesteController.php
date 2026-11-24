<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\Animal;
use common\models\Booking;
use common\models\Conversa;
use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Só para desenvolvimento, enquanto as reservas não estão feitas: cria uma reserva confirmada
 * para testar o chat.
 *
 *   php yii chat-teste/reserva <cliente> <colaborador>
 *   php yii chat-teste/apagar <id da reserva>
 *
 * <cliente> e <colaborador> são o nome de utilizador ou o e-mail. O cliente tem de ter pelo menos
 * um animal; o colaborador tem de ter o papel colaborador ou admin.
 */
class ChatTesteController extends Controller
{
    public function actionReserva(string $cliente, string $colaborador): int
    {
        $c = User::findByUsernameOrEmail($cliente);
        $col = User::findByUsernameOrEmail($colaborador);
        if ($c === null || $col === null) {
            $this->stderr("Utilizador não encontrado (ou inativo): " . ($c === null ? $cliente : $colaborador) . "\n");
            return ExitCode::DATAERR;
        }
        if (!Conversa::podeSerResponsavel($col->id)) {
            $this->stderr("{$colaborador} não tem o papel colaborador nem admin.\n");
            return ExitCode::DATAERR;
        }
        $animal = Animal::find()->where(['id_user' => $c->id])->orderBy(['id' => SORT_ASC])->one();
        if ($animal === null) {
            $this->stderr("{$cliente} ainda não tem animais. Registe um no site primeiro.\n");
            return ExitCode::DATAERR;
        }

        $db = Yii::$app->db;
        $idQuarto = (int) $db->createCommand('SELECT id FROM {{%quarto}} ORDER BY id LIMIT 1')->queryScalar();
        if ($idQuarto === 0) {
            $db->createCommand()->insert('{{%quarto}}', ['categoria' => 'Teste', 'capacidade' => 2])->execute();
            $idQuarto = (int) $db->getLastInsertID();
        }

        $booking = new Booking([
            'datainicio' => date('Y-m-d', strtotime('+2 days')),
            'datafim' => date('Y-m-d', strtotime('+6 days')),
            'id_user' => $c->id,
            'status' => Booking::STATUS_PENDENTE,
        ]);
        $booking->save(false);
        $db->createCommand()->insert('{{%booking_animal_quarto}}', [
            'id_booking' => $booking->id,
            'id_animal' => $animal->id,
            'id_quarto' => $idQuarto,
        ])->execute();

        $booking->confirmar($col->id);

        $this->stdout("Reserva #{$booking->id} confirmada para {$animal->nome} ({$c->username}), responsável: {$col->username}.\n");
        $this->stdout("Para apagar: php yii chat-teste/apagar {$booking->id}\n");
        return ExitCode::OK;
    }

    public function actionApagar(int $id): int
    {
        $booking = Booking::findOne($id);
        if ($booking === null) {
            $this->stderr("Reserva #{$id} não existe.\n");
            return ExitCode::DATAERR;
        }
        // apaga também a conversa, as mensagens e a ligação ao animal (ON DELETE CASCADE)
        $booking->delete();
        $this->stdout("Reserva #{$id} apagada.\n");
        return ExitCode::OK;
    }
}
