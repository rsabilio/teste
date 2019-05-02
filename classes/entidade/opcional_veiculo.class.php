<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Definicao da entidade opcional_veiculo
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon
// License: LICENSE.TXT
//
abstract class opcional_veiculo_base extends objeto_formulario {

    //
    //     Cria a definicao da entidade
    //
    protected function definir_entidade() {
        $this->criar_entidade(
            /* Nome Entidade   */ 'Opcional',
            /* Entidade Plural */ 'Opcionais',
            /* Genero          */ 'M',
            /* Classe          */ 'opcional_veiculo',
            /* Tabela          */ 'rel_veiculos_opcionais',
            /* Desc. Tabela    */ '',
            /* Singleton       */ false);
    }


    //
    //     Cria os atributos da classe
    //
    protected function definir_atributos() {

        // CAMPO: cod_rel_veiculo_opcional
        $atributo = new atributo('cod_rel_veiculo_opcional', 'cod_rel_veiculo_opcional', null);
        $atributo->set_tipo('int', false, 'PK');
        $atributo->set_intervalo(-2147483648, 2147483647);
        $atributo->set_validacao(false, false, true);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: cod_veiculo => gera um veiculo
        $this->adicionar_rel_uu(
            /* Classe          */ 'veiculo',
            /* Objeto gerado   */ 'veiculo',
            /* Atributo gerado */ 'cod_veiculo');

        // CAMPO: cod_opcional => gera um opcionai
        $this->adicionar_rel_uu(
            /* Classe          */ 'opcional',
            /* Objeto gerado   */ 'opcional',
            /* Atributo gerado */ 'cod_opcional');
    }
}