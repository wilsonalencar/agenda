<?php

use Illuminate\Database\Seeder;

class CategoriasTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = array(
            array('Obrigações','Obrigação tributária é toda obrigação que surge quando se consuma um fato imponível previsto na legislação tributária. É considerado como um vínculo que une o credor (ativo) e o devedor (passivo) para o pagamento de alguma dívida. Também pode ser considerada como obrigação tributária a própria prestação que o devedor tem que cumprir. Sendo assim, ocorrido o fato gerador, sempre decorrente de lei, nasce a obrigação tributária (nascimento compulsório).'),
            array('Impostos','Os impostos são um tipo de tributos, e não há uma destinação específica para os recursos obtidos por meio de seu recolhimento. Geralmente são utilizados para o financiamento de serviços públicos, como educação e segurança.'),
            array('Taxas','As taxas são os valores cobrados do contribuinte por um serviço prestado pelo poder público, como a taxa de lixo urbano ou a taxa para a confecção do passaporte.'),
            array('Contribuições','Podem ser de dois tipos: de melhoria ou especiais. No primeiro caso estão as contribuições cobradas em uma situação que representa um benefício ao contribuinte, como uma obra pública que valorizou seu imóvel. Já as contribuições especiais são cobradas quando há uma destinação específica para um determinado grupo, como o PIS (Programa de Integração Social) e PASEP (Programa de Formação do Patrimônio do Servidor Público), que são direcionados a um fundo dos trabalhadores do setor privado e público.')
        );

        foreach ($data as $el) {
            DB::table('categorias')->insert([
                'nome' => $el[0],
                'descricao' => $el[1],
                'created_at' => '2016-02-01 00:00:00',
            ]);
        }
    }
}
