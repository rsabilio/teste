<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Definicao da entidade situacao_reserva
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon
// License: LICENSE.TXT
//
abstract class situacao_reserva_base extends objeto_formulario {

    //
    //     Cria a definicao da entidade
    //
    protected function definir_entidade() {
        $this->criar_entidade(
            /* Nome Entidade   */ 'Situa&ccedil;&atilde;o',
            /* Entidade Plural */ 'Situa&ccedil;&otilde;es das Reservas',
            /* Genero          */ 'F',
            /* Classe          */ 'situacao_reserva',
            /* Tabela          */ 'situacoes_reservas',
            /* Desc. Tabela    */ '',
            /* Singleton       */ false);
    }


    //
    //     Cria os atributos da classe
    //
    protected function definir_atributos() {

        // CAMPO: cod_situacao_reserva
        $atributo = new atributo('cod_situacao_reserva', 'Codigo da Situa&ccedil;&atilde;o da Reserva', null);
        $atributo->set_tipo('int', false, 'PK');
        $atributo->set_intervalo(1, 10000000);
        $atributo->set_validacao(false, false, true);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: nome
        $atributo = new atributo('nome', 'Nome', null);
        $atributo->set_tipo('string', false, false);
        $atributo->set_intervalo(1, 45);
        $atributo->set_validacao('TEXTO_LINHA', false, false);
        $this->adicionar_atributo($atributo);
        unset($atributo);
    }
}