<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Definicao da entidade marca
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon
// License: LICENSE.TXT
//
abstract class marca_base extends objeto_formulario {

    //
    //     Cria a definicao da entidade
    //
    protected function definir_entidade() {
        $this->criar_entidade(
            /* Nome Entidade   */ 'Marca',
            /* Entidade Plural */ 'Marcas',
            /* Genero          */ 'F',
            /* Classe          */ 'marca',
            /* Tabela          */ 'marcas',
            /* Desc. Tabela    */ '',
            /* Singleton       */ false);
    }


    //
    //     Cria os atributos da classe
    //
    protected function definir_atributos() {

        // CAMPO: cod_marca
        $atributo = new atributo('cod_marca', 'C&oacute;digo da Marca', null);
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