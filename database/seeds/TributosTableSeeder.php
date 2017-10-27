<?php

use Illuminate\Database\Seeder;

class TributosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = array(
            array(1,'SPED FISCAL','Sistema Público de Escrituração Digital',1,'E',1,15),
            array(2,'EFD CONTRIBUIÇÕES','Arquivo digital instituído no Sistema Publico de Escrituração Digital',1,'F',1,15),
            array(3,'IRRF','Imposto de Renda Retido na Fonte',2,'F',1,15),
            array(4,'PIS','Programa de Integração Social',2,'F',1,15),
            array(5,'COFINS','Contribuição para Financiamento da Seguridade Social',2,'F',1,15),
            array(6,'CSRF','Contribuição Social Retida na Fonte',2,'F',1,15),
            array(7,'INSS','Contribuição Previdenciária para o Fundo de previdência (Instituto Nacional do Seguro Social)',2,'F',1,15),
            array(8,'ICMS','Imposto sobre operações relativas à circulação de mercadorias e sobre prestações de serviços de transporte interestadual, intermunicipal e de comunicação',2,'E',1,15),
            array(9,'GIA','Guia de Informação e Apuração do ICMS',1,'E',1,15),
            array(10,'DCTF','Declaração de Débitos e Créditos Tributários Federais',1,'F',1,15),
            array(11,'ISS','Imposto sobre Serviços',2,'M',1,15),
            array(12,'DIRF','Declaração do Imposto sobre a Renda Retida na Fonte',1,'F',1,120),
            array(13,'DAMEF-VAF','Declaração Anual do Movimento Econômico Fiscal - Valor Adicionado Fiscal',1,'E',1,120),
            array(14,'DECLAN-IPM','Declaração Anual para o IPM',1,'E',1,120),
            array(15,'DOT','Declaração de Obrigações Tributáveis',1,'E',1,120),
            array(16,'DIPAM','Declaração para o Índice de Participação dos Municípios',2,'M',1,15)
        );

        foreach ($data as $el) {
            DB::table('tributos')->insert([
                'id' => $el[0],
                'nome' => $el[1],
                'descricao' => $el[2],
                'categoria_id' => $el[3],
                'tipo' => $el[4],
                'recibo' => $el[5],
                'alerta' => $el[6],
                'created_at' => '2016-02-01 00:00:00',
            ]);
        }
    }
}
