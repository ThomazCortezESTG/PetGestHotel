# Mosquitto (chat em tempo real)

O chat guarda as mensagens na base de dados. O Mosquitto serve só para **avisar** quem tem a conversa aberta de que há uma mensagem nova, para não ser preciso estar sempre a perguntar ao servidor.

```
Enviar:   app/site ──HTTP──> PHP ──grava──> MySQL
                              └──publica──> Mosquitto  (tópico petgest/conversa/<id>)
Receber:  Mosquitto ──aviso──> app Android (porta 1883) / browser (porta 9001)
          app/site ──HTTP──> PHP ──> mensagens novas
```

Se o Mosquitto estiver desligado, o chat continua a funcionar: as páginas perguntam ao servidor de 15 em 15 segundos.

## Instalar no Windows (WAMP)

1. Instalar o Mosquitto (https://mosquitto.org/download/) com a opção de serviço. Fica a arrancar sozinho com o Windows, por isso não é preciso nenhuma janela aberta.
2. Num **PowerShell como administrador**, na pasta do projeto:

   ```powershell
   Copy-Item mosquitto\petgest.conf "C:\Program Files\mosquitto\mosquitto.conf" -Force
   Restart-Service mosquitto
   ```

3. Confirmar que as portas estão abertas:

   ```powershell
   Get-NetTCPConnection -State Listen -LocalPort 1883,9001
   ```

Para testar a app num **telemóvel real** (e não no emulador), o Firewall do Windows tem de deixar entrar ligações nas portas 1883 e 9001.

## Instalar no servidor de produção (Linux)

```bash
sudo apt install mosquitto
sudo cp mosquitto/petgest.conf /etc/mosquitto/conf.d/petgest.conf
sudo systemctl enable --now mosquitto
sudo systemctl restart mosquitto
```

Abrir as portas 1883 e 9001 na firewall do servidor. Se o Mosquitto estiver noutra máquina ou noutras portas, mudar `mqtt.*` em `common/config/params-local.php`.

## Para a app Android

- Broker: `tcp://<servidor>:1883`. No emulador, o PC é `10.0.2.2`.
- Depois do login, `GET api/conversas` devolve em cada conversa o campo `mqtt_topic`, por exemplo `petgest/conversa/3`. Subscrever esse tópico.
- Cada aviso que chega é um JSON como este:

  ```json
  {"tipo": "mensagem", "conversa": 3, "mensagem": 57}
  ```

  Quando chega um aviso, pedir `GET api/conversas/3/mensagens?depois=<id da última mensagem que a app já tem>`.
- Enviar: `POST api/conversas/3/mensagens` com `{"texto": "..."}`. A resposta já é a mensagem criada (código 201).
- Ao abrir a conversa: `POST api/conversas/3/lida`.
- Fotografias e vídeos (só o colaborador os envia): a mensagem traz `"anexo": {"tipo": "imagem" | "video", "mime", "tamanho", "url"}`. O `url` pede-se com o mesmo cabeçalho `Authorization`. Os vídeos aceitam `Range`, o que permite ao player saltar para meio do vídeo.

### Notificações

- `GET api/auth/me` traz `mqtt_topic`, por exemplo `petgest/utilizador/12`. Subscrever também este tópico.
- Cada aviso nesse tópico quer dizer "as notificações mudaram": pedir `GET api/notificacoes`.
- `POST api/notificacoes/{id}/lida` marca uma notificação como lida; `POST api/notificacoes/lidas` marca todas.
- Abrir uma conversa (`POST api/conversas/{id}/lida`) também marca como lida a notificação dessa conversa.

## Segurança

Qualquer pessoa que chegue às portas do Mosquitto pode subscrever os tópicos. É por isso que os avisos levam só ids e o texto vem sempre da API ou do site, que confirmam quem pede. No pior caso, alguém consegue ver *quando* há atividade numa conversa, mas nunca o que foi escrito.
