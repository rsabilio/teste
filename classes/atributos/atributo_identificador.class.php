<?php
//
// SIMP
// Descricao: Classe de definicao do atributo identificador
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.2
// Data: 10/12/2009
// Modificado: 24/03/2011
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
class atributo_identificador {


    //
    //     Retorna o atributo com as caracteristicas definidas
    //
    public static function get_instancia($nome = 'identificador', $descricao = 'Identificador', $pode_vazio = true) {
    // String $nome: nome do atributo
    // String $descricao: descricao do atributo
    // Bool $pode_vazio: indica se o identificador pode ser vazio
    //
        $atributo = new atributo($nome, $descricao, '');
        $atributo->set_tipo('string', $pode_vazio);
        $atributo->set_intervalo($pode_vazio ? 0 : 1, 50);
        $atributo->set_validacao('IDENTIFICADOR', false, false);
        return $atributo;
    }

}//class
