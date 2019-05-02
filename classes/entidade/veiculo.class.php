<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Definicao da entidade veiculo
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon
// License: LICENSE.TXT
//
abstract class veiculo_base extends objeto_formulario {

    //
    //     Cria a definicao da entidade
    //
    protected function definir_entidade() {
        $this->criar_entidade(
            /* Nome Entidade   */ 'Veiculo',
            /* Entidade Plural */ 'Veiculos',
            /* Genero          */ 'M',
            /* Classe          */ 'veiculo',
            /* Tabela          */ 'veiculos',
            /* Desc. Tabela    */ '',
            /* Singleton       */ false);
    }


    //
    //     Cria os atributos da classe
    //
    protected function definir_atributos() {

        // CAMPO: cod_veiculo
        $atributo = new atributo('cod_veiculo', 'Codigo do Veiculo', null);
        $atributo->set_tipo('int', false, 'PK');
        $atributo->set_intervalo(1, 10000000);
        $atributo->set_validacao(false, false, true);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: modelo
        $atributo = new atributo('modelo', 'Modelo', null);
        $atributo->set_tipo('string', false, false);
        $atributo->set_intervalo(1, 45);
        $atributo->set_validacao('TEXTO_LINHA', false, false);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: placa
        $atributo = new atributo('placa', 'Placa', null);
        $atributo->set_tipo('string', false, false);
        $atributo->set_intervalo(1, 7);
        $atributo->set_validacao('TEXTO_LINHA', false, true);
        $this->adicionar_atributo($atributo);
        unset($atributo);

        // CAMPO: cod_marca => gera um marca
        $this->adicionar_rel_uu(
            /* Classe          */ 'marca',
            /* Objeto gerado   */ 'marca',
            /* Atributo gerado */ 'cod_marca');

        // CAMPO: cod_loja => gera um loja
        $this->adicionar_rel_uu(
            /* Classe          */ 'loja',
            /* Objeto gerado   */ 'loja',
            /* Atributo gerado */ 'cod_loja');
        
        // CAMPO: opcionais_veiculos
        $this->adicionar_rel_un(
            /* nome classe     */ 'opcional_veiculo',
            /* vetor gerado    */ 'opcionais',
            /* index vetor     */ 'cod_opcional',
            /* campo impressao */ 'opcional:nome',
            /* campo ordem     */ 'opcional:nome');
        
        // CAMPO IMPLICITO: nome
        $this->adicionar_atributo_implicito('nome', 'Nome', 'get_nome_veiculo', array('modelo', 'marca:nome'));
    }
    
    //
    //     Obtem o nome do campo usado para identificar a entidade
    //
    public function get_nome_veiculo() {
        return $this->get_atributo('modelo').' - '.$this->get_atributo('marca:nome');
    }
}