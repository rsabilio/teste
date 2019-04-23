<?php
//
// SIMP
// Descricao: Classe de obtencao dos icones
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.3
// Data: 12/11/2008
// Modificado: 10/08/2010
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('ICONE_DIRICONES', $CFG->dirimgs.'icones/');
define('ICONE_WWWICONES', $CFG->wwwimgs.'icones/');

final class icone {


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Obtem o endereco de um icone
    //
    static public function endereco($nome_icone) {
    // String $nome_icone: nome do icone
    //
        if (is_file(ICONE_DIRICONES.$nome_icone.'.gif')) {
            return ICONE_WWWICONES.$nome_icone.'.gif';
        } elseif (is_file(ICONE_DIRICONES.$nome_icone.'.png')) {
            return ICONE_WWWICONES.$nome_icone.'.png';
        } elseif (is_file(ICONE_DIRICONES.$nome_icone.'.jpg')) {
            return ICONE_WWWICONES.$nome_icone.'.jpg';
        }
        trigger_error('Icone desconhecido "'.$nome_icone.'"', E_USER_WARNING);
        return false;
    }


    //
    //     Retona a imagem de um icone
    //
    static public function img($nome_icone, $descricao = false, $class = false, $id = false) {
    // String $nome_icone: nome do icone
    // String $descricao: descricao do icone
    // String $class: classe CSS do icone
    // String $id: ID do icone
    //
        global $CFG;
        $src = self::endereco($nome_icone);

        switch ($CFG->cookies['tamanho_icones']) {
        case '0':
            $fator = 0.8; // 80% do tamanho original
            $tamanho = imagem::tamanho($src);
            $tamanho_html = sprintf('width="%0.2f." height="%0.2f"', $tamanho[0] * $fator, $tamanho[1] * $fator);
            break;
        case '1':
        default:
            $tamanho_html = imagem::tamanho_html($src);
            break;
        case '2':
            $fator = 1.2; // 120% do tamanho original
            $tamanho = imagem::tamanho($src);
            $tamanho_html = sprintf('width="%0.2f." height="%0.2f"', $tamanho[0] * $fator, $tamanho[1] * $fator);
            break;
        case '3':
            $fator = 2; // 200% do tamanho original
            $tamanho = imagem::tamanho($src);
            $tamanho_html = sprintf('width="%0.2f." height="%0.2f"', $tamanho[0] * $fator, $tamanho[1] * $fator);
            break;
        }

        $alt = $descricao ? ' alt="'.$descricao.'"' : '';
        $title = $descricao ? ' title="'.$descricao.'"' : '';
        $class = $class ? ' class="'.$class.'"' : '';
        $id = $id ? ' id="'.$id.'"' : '';
        return '<img src="'.$src.'" '.$tamanho_html.$alt.$title.$class.$id.' />';
    }

}//class
