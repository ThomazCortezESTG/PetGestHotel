<?php

declare(strict_types=1);

namespace common\components;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Yii;

/**
 * Publica avisos no Mosquitto (MQTT). Configuração em common/config/params.php ("mqtt.*").
 *
 * Os avisos levam só ids (ex.: {"tipo":"mensagem","conversa":3,"mensagem":57}), nunca o texto:
 * qualquer cliente MQTT pode subscrever os tópicos, por isso o conteúdo vai-se sempre buscar
 * ao servidor (API ou site), que confirma quem está a pedir.
 *
 * Se o Mosquitto estiver em baixo, o aviso perde-se mas o resto funciona: a mensagem já está
 * guardada na base de dados e as páginas voltam a pedi-la de tempos a tempos.
 */
final class Mqtt
{
    /** Tópico de uma conversa: <prefixo>/conversa/<id> */
    public static function topicoConversa(int $idConversa): string
    {
        return self::prefixo() . '/conversa/' . $idConversa;
    }

    public static function prefixo(): string
    {
        return rtrim((string) (Yii::$app->params['mqtt.topicPrefix'] ?? 'petgest'), '/');
    }

    public static function publicar(string $topico, array $dados): bool
    {
        $p = Yii::$app->params;
        if (($p['mqtt.enabled'] ?? true) === false) {
            return false;
        }

        try {
            $settings = (new ConnectionSettings())
                ->setConnectTimeout(1)
                ->setSocketTimeout(1);
            if (!empty($p['mqtt.username'])) {
                $settings = $settings->setUsername($p['mqtt.username'])->setPassword($p['mqtt.password'] ?? null);
            }

            $client = new MqttClient(
                $p['mqtt.host'] ?? '127.0.0.1',
                (int) ($p['mqtt.port'] ?? 1883),
                'petgest-php-' . bin2hex(random_bytes(4))
            );
            $client->connect($settings, true);
            $client->publish($topico, json_encode($dados, JSON_UNESCAPED_UNICODE), MqttClient::QOS_AT_MOST_ONCE);
            $client->disconnect();
            return true;
        } catch (\Throwable $e) {
            Yii::warning('Não foi possível publicar no MQTT (' . $topico . '): ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
