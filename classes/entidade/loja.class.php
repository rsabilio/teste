<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Definicao da entidade loja
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon
// License: LICENSE.TXT
//
abstract class loja_base extends objeto_formulario {

    //
    //     Cria a definicao da entidade
    //
    protected function definir_entidade() {
        $this->criar_entidade(
            /* Nome Entidade   */ 'Loja',
            /* Entidade Plural */ 'Lojas',
            /* Genero          */ 'F',
            /* Classe          */ 'loja',
            /* Tabela          */ 'lojas',
            /* Desc. Tabela    */ '',
            /* Singleton       */ false);
    }


    //
    //     Cria os atributos da classe
    //
    protected function definir_atributos() {

        // CAMPO: cod_loja
        $atributo = new atributo('cod_loja', 'Codigo da Loja', null);
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

        // CAMPO: cidade
        $atributo = new atributo('cidade', 'Cidade', null);
        $atributo->set_tipo('string', false, false);
        $atributo->set_intervalo(1, 45);
        $atributo->set_validacao('TEXTO_LINHA', false, false);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: uf
        $atributo = new atributo('uf', 'UF', null);
        $atributo->set_tipo('string', false, false);
        $atributo->set_intervalo(1, 2);
        $atributo->set_validacao('TEXTO_LINHA', false, false);
        $this->adicionar_atributo($atributo);
        unset($atributo);
    }
}