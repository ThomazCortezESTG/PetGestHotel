<?php

use yii\db\Migration;

class m261120_143816_seed_more_racas extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('{{%raca}}', ['nome', 'descricao', 'tipo'], [

            // ---------- CÃES ----------
            ['Labrador Retriever', 'Porte médio-grande, pelo curto e denso, temperamento dócil e brincalhão.', 'Cão'],
            ['Golden Retriever', 'Pelo longo e dourado, muito afetuoso, inteligente e fácil de treinar.', 'Cão'],
            ['Pastor Alemão', 'Grande e musculado, muito inteligente e leal, usado em trabalho e proteção.', 'Cão'],
            ['Bulldog Inglês', 'Corpo compacto e robusto, cara enrugada e focinho achatado.', 'Cão'],
            ['Bulldog Francês', 'Pequeno e musculado, orelhas de morcego, focinho achatado.', 'Cão'],
            ['Boxer', 'Porte médio-grande, musculado, focinho curto, enérgico e brincalhão.', 'Cão'],
            ['Rottweiler', 'Grande e poderoso, pelo curto preto com marcas castanhas, muito leal.', 'Cão'],
            ['Doberman', 'Elegante e atlético, pelo curto, muito alerta e inteligente.', 'Cão'],
            ['Pastor Belga Malinois', 'Porte médio-grande, pelo curto acastanhado, muito ativo e trabalhador.', 'Cão'],
            ['Border Collie', 'Muito inteligente e ágil, pelo médio preto e branco, cão de pastoreio.', 'Cão'],
            ['Cocker Spaniel', 'Porte médio, orelhas longas e caídas, pelo sedoso, muito carinhoso.', 'Cão'],
            ['Spaniel Bretão', 'Porte médio, ágil e enérgico, pelo ondulado, ótimo cão de caça.', 'Cão'],
            ['Setter Irlandês', 'Elegante, pelo longo e acaju, muito sociável e ativo.', 'Cão'],
            ['Pointer Inglês', 'Esguio e atlético, pelo curto, excelente cão de caça.', 'Cão'],
            ['Shih Tzu', 'Pequeno, pelo longo e sedoso, cara achatada, muito afetuoso.', 'Cão'],
            ['Yorkshire Terrier', 'Minúsculo, pelo longo e sedoso azul-aço e castanho, corajoso.', 'Cão'],
            ['Maltês', 'Pequeno, pelo longo, liso e branco-puro, muito meigo.', 'Cão'],
            ['Pomerânia', 'Pequeno e fofo, pelo duplo e volumoso, aspeto de bola de pelo.', 'Cão'],
            ['Pug', 'Pequeno e robusto, cara enrugada, focinho achatado, muito sociável.', 'Cão'],
            ['Jack Russell Terrier', 'Pequeno e muito enérgico, pelo curto branco com manchas, teimoso.', 'Cão'],
            ['West Highland White Terrier', 'Pequeno, pelo branco e áspero, confiante e vivo.', 'Cão'],
            ['Schnauzer', 'Pelo áspero, barba e sobrancelhas marcadas, inteligente e alerta.', 'Cão'],
            ['Cavalier King Charles Spaniel', 'Pequeno, pelo sedoso, orelhas longas, muito dócil.', 'Cão'],
            ['Samoiedo', 'Pelo branco, denso e fofo, sorriso característico, muito sociável.', 'Cão'],
            ['Akita Inu', 'Grande e imponente, pelo denso, muito leal e reservado.', 'Cão'],
            ['Shiba Inu', 'Pequeno-médio, aspeto de raposa, independente e vivo.', 'Cão'],
            ['Chow Chow', 'Pelo denso tipo juba, língua azul-escura, independente.', 'Cão'],
            ['Dálmata', 'Pelo curto branco com manchas pretas, enérgico e resistente.', 'Cão'],
            ['Grande Dinamarquês', 'Gigante e elegante, pelo curto, temperamento calmo e gentil.', 'Cão'],
            ['São Bernardo', 'Gigante, pelo denso, paciente e dócil, histórico cão de salvamento.', 'Cão'],
            ['Terra Nova', 'Gigante, pelo denso e impermeável, excelente nadador, muito doce.', 'Cão'],
            ['Mastim Napolitano', 'Gigante e maciço, pele solta e enrugada, protetor.', 'Cão'],
            ['Cão da Serra da Estrela', 'Grande, pelo longo ou curto, guardião de rebanhos, raça portuguesa.', 'Cão'],
            ['Cão de Água Português', 'Médio, pelo encaracolado ou ondulado, excelente nadador, raça portuguesa.', 'Cão'],
            ['Podengo Português', 'Esguio e ágil, orelhas eretas, caçador, raça portuguesa.', 'Cão'],
            ['Perdigueiro Português', 'Médio, pelo curto, excelente cão de caça, raça portuguesa.', 'Cão'],
            ['Cão de Castro Laboreiro', 'Grande, pelo denso tigrado, guardião de gado, raça portuguesa.', 'Cão'],
            ['Rafeiro do Alentejo', 'Gigante e robusto, pelo curto, guardião de rebanhos, raça portuguesa.', 'Cão'],
            ['Cão de Fila de São Miguel', 'Médio-grande, pelo curto tigrado, pastor e guardião, raça açoriana.', 'Cão'],
            ['Pastor Australiano', 'Médio, pelo denso com padrões merle, muito ativo e inteligente.', 'Cão'],
            ['Whippet', 'Esguio e veloz, pelo curto, calmo em casa e rápido ao ar livre.', 'Cão'],
            ['Basset Hound', 'Corpo longo, pernas curtas, orelhas muito longas, olfato apurado.', 'Cão'],
            ['Cão sem raça definida (SRD)', 'Cruzamento de raças, características variadas.', 'Cão'],

            // ---------- GATOS ----------
            ['Chartreux', 'Pelo azul-acinzentado denso, olhos cor de cobre, corpo robusto.', 'Gato'],
            ['Burmês', 'Corpo médio e musculado, pelo curto acetinado, muito sociável.', 'Gato'],
            ['Devon Rex', 'Pelo curto e ondulado, orelhas muito grandes, cara de duende.', 'Gato'],
            ['Cornish Rex', 'Corpo esguio, pelo muito curto e ondulado, muito ativo.', 'Gato'],
            ['American Shorthair', 'Corpo robusto, pelo curto e denso, temperamento equilibrado.', 'Gato'],
            ['Exótico de Pelo Curto', 'Aspeto de persa com pelo curto, cara achatada, calmo.', 'Gato'],
            ['Oriental', 'Corpo esguio, orelhas muito grandes, vocal e sociável.', 'Gato'],
            ['Somali', 'Semelhante ao abissínio, mas de pelo semi-longo, cauda farta.', 'Gato'],
            ['Balinês', 'Siamês de pelo semi-longo, olhos azuis, elegante e vocal.', 'Gato'],
            ['Himalaio', 'Cruzamento de persa e siamês, pelo longo, olhos azuis.', 'Gato'],
            ['Tonquinês', 'Corpo médio, pelo curto acetinado, olhos azul-esverdeados.', 'Gato'],
            ['Savannah', 'Grande e esguio, pelo manchado, pernas longas, muito ativo.', 'Gato'],
            ['Ocicat', 'Pelo curto manchado, aspeto selvagem, mas dócil.', 'Gato'],
            ['Egyptian Mau', 'Pelo curto manchado de forma natural, muito rápido.', 'Gato'],
            ['Munchkin', 'Pernas muito curtas, corpo médio, brincalhão.', 'Gato'],
            ['Selkirk Rex', 'Pelo encaracolado e macio, corpo robusto, dócil.', 'Gato'],
            ['Bobtail Japonês', 'Cauda curta e fofa, corpo esguio, ativo e sociável.', 'Gato'],
            ['Snowshoe', 'Pelo curto, patas brancas, olhos azuis, muito afetuoso.', 'Gato'],
            ['Toyger', 'Pelo com padrão tigrado, aspeto de mini tigre, sociável.', 'Gato'],
            ['Gato sem raça definida (SRD)', 'Cruzamento de raças, características variadas.', 'Gato']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%raca}}', ['>', 'id', 21]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261120_143816_seed_more_racas cannot be reverted.\n";

        return false;
    }
    */
}
