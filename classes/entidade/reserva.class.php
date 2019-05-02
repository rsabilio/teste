<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Definicao da entidade reserva
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon
// License: LICENSE.TXT
//
abstract class reserva_base extends objeto_formulario {

    //
    //     Cria a definicao da entidade
    //
    protected function definir_entidade() {
        $this->criar_entidade(
            /* Nome Entidade   */ 'Reserva',
            /* Entidade Plural */ 'Reservas',
            /* Genero          */ 'F',
            /* Classe          */ 'reserva',
            /* Tabela          */ 'reservas',
            /* Desc. Tabela    */ 'reserva de veiculos',
            /* Singleton       */ false);
    }


    //
    //     Cria os atributos da classe
    //
    protected function definir_atributos() {

        // CAMPO: cod_reserva
        $atributo = new atributo('cod_reserva', 'cod_reserva', null);
        $atributo->set_tipo('int', false, 'PK');
        $atributo->set_intervalo(0, 4294967295);
        $atributo->set_validacao(false, false, true);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: data_reserva
        $atributo = new atributo('data_reserva', 'data_reserva', null);
        $atributo->set_tipo('data', false, false);
        $atributo->set_intervalo(false, false);
        $atributo->set_validacao(false, false, false);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: data_retirada
        $atributo = new atributo('data_retirada', 'data_retirada', null);
        $atributo->set_tipo('data', false, false);
        $atributo->set_intervalo(false, false);
        $atributo->set_validacao(false, false, false);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: data_devolucao
        $atributo = new atributo('data_devolucao', 'data_devolucao', null);
        $atributo->set_tipo('string', false, false);
        $atributo->set_intervalo(1, 45);
        $atributo->set_validacao('TEXTO_LINHA', false, false);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: cod_usuario => gera um 
        $this->adicionar_rel_uu(
            /* Classe          */ '',
            /* Objeto gerado   */ '',
            /* Atributo gerado */ 'cod_usuario');

        // CAMPO: cod_veiculo => gera um veiculo
        $this->adicionar_rel_uu(
            /* Classe          */ 'veiculo',
            /* Objeto gerado   */ 'veiculo',
            /* Atributo gerado */ 'cod_veiculo');

        // CAMPO: cod_situacao_reserva => gera um situacao_reserva
        $this->adicionar_rel_uu(
            /* Classe          */ 'situacao_reserva',
            /* Objeto gerado   */ 'situacao_reserva',
            /* Atributo gerado */ 'cod_situacao_reserva');

        // CAMPO: cod_loja_retirada => gera um loja
        $this->adicionar_rel_uu(
            /* Classe          */ 'loja',
            /* Objeto gerado   */ 'loja',
            /* Atributo gerado */ 'cod_loja_retirada');

        // CAMPO: cod_loja_devolucao => gera um loja
        $this->adicionar_rel_uu(
            /* Classe          */ 'loja',
            /* Objeto gerado   */ 'loja',
            /* Atributo gerado */ 'cod_loja_devolucao');
    }
}
