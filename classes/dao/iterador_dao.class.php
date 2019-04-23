<?php
//
// SIMP
// Descricao: Classe de iteracao de registros consultados do BD
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.3
// Data: 29/09/2009
// Modificado: 31/03/2011
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
final class iterador_dao implements Iterator, Countable {

    // driver_objeto $dao: camada DAO
    private $dao;

    // objeto $objeto: entidade derivada da classe objeto
    private $objeto;

    // Mixed $resultados: elemento que guarda os resultados de uma consulta do BD
    private $resultados;

    // Mixed $estrutura_consulta: dados da consulta
    private $estrutura_consulta;

    // Object $corrente: elemento corrente
    private $valor_corrente;

    // Int $posicao_corrente: posicao do elemento corrente
    private $posicao_corrente;

    // Int $tamanho: numero de elementos dos resultados
    private $tamanho = 0;

    // Bool $montar_objeto: indica se deve ser montado um objeto entidade ou apenas um objeto stdClass
    private $montar_objeto = false;


    //
    //     Construtor
    //
    public function __construct($dao, $objeto, $resultados, $estrutura_consulta) {
    // objeto_dao $dao: objeto dao
    // objeto $objeto: entidade derivada da classe objeto
    // Mixed $resultados: lista de resultados do SELECT
    // Mixed $estrutura_consulta: dados estruturados do SELECT
    //
        $this->dao                = $dao;
        $this->objeto             = clone($objeto);
        $this->resultados         = $resultados;
        $this->estrutura_consulta = $estrutura_consulta;
        $this->valor_corrente     = false;
        $this->posicao_corrente   = 0;
        $this->montar_objeto      = false;
        $this->tamanho            = $this->dao->quantidade_registros($this->resultados);
    }


    //
    //     Modifica a flag indicando se deve ser montado o objeto entidade
    //
    public function set_montar_objeto($montar) {
    // Bool $montar: montar ou nao o objeto
    //
        $this->montar_objeto = (bool)$montar;
    }


    //
    //     Reinicia o iterador para a primeira posicao
    //
    public function rewind() {
        if ($this->dao->rewind($this->resultados)) {
            $this->posicao_corrente = 0;
            return true;
        }
        return false;
    }


    //
    //     Obtem o valor corrente do iterador
    //
    public function current() {
        if (!$this->valor_corrente) {
            $this->valor_corrente = $this->dao->fetch_object($this->resultados);
        }
        if ($this->montar_objeto) {
            $dados = $this->dao->gerar_objeto($this->objeto, $this->valor_corrente, $this->estrutura_consulta);

            $obj = objeto::get_objeto($this->objeto->get_classe());
            objeto::set_flag_bd(true);
            $obj->set_valores($dados);
            objeto::set_flag_bd(false);
            return $obj;
        } else {
            return $this->dao->gerar_objeto($this->objeto, $this->valor_corrente, $this->estrutura_consulta);
        }
    }


    //
    //     Obtem a chave corrente do iterador
    //
    public function key() {
        return $this->posicao_corrente;
    }


    //
    //     Avanca uma posicao do iterador e retorna o proximo elemento
    //
    public function next() {
        $this->valor_corrente = $this->dao->fetch_object($this->resultados);
        $this->posicao_corrente += 1;
    }


    //
    //     Indica se o iterador chegou ao final
    //
    public function valid() {
        return $this->posicao_corrente < $this->tamanho;
    }


    //
    //     Retorna o numero de elementos do iterador
    //
    public function size() {
        return $this->tamanho;
    }


    //
    //     Retorna o numero de elementos do iterador
    //
    public function count() {
        return $this->size();
    }

}//class
