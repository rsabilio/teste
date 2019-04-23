<?php
//
// SIMP
// Descricao: Classe para realizar operacoes comuns sobre vetores
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.7
// Data: 25/08/2009
// Modificado: 23/05/2011
// Copyright (C) 2009  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Tipos de filtragem de vetores
define('VETOR_PRIMEIRO', 1);
define('VETOR_ULTIMO',   2);

// Tipos de indexacao (usados pelo metodo get_vetor_atributo)
define('VETOR_REINDEXAR',        0);
define('VETOR_INDEXAR_ATRIBUTO', 1);
define('VETOR_MANTER_INDICE',    2);

final class vetor {


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Gera uma matriz indexada por um atributo dos elementos
    //
    public static function agrupar_por_atributo($vetor, $atributo, $indices = null, $usar_chave = true) {
    // Array[Object] $vetor: vetor de objetos que possuem o atributo $atributo
    // String $atributo: nome do atributo a ser usado na indexacao
    // Array[Mixed] $indices: indices a serem definidos na matriz automaticamente
    // Bool $usar_chave: indica se a chave inicial do vetor deve ser preservada no sub-vetor
    //
        $matriz = array();
        if (is_array($indices)) {
            foreach ($indices as $indice) {
                $matriz[$indice] = array();
            }
        }
        foreach ($vetor as $chave => $objeto) {
            $indice = $objeto->$atributo;
            if (!is_null($indice)) {
                if (!isset($matriz[$indice])) {
                    $matriz[$indice] = array();
                }
                if ($usar_chave) {
                    $matriz[$indice][$chave] = $objeto;
                } else {
                    $matriz[$indice][] = $objeto;
                }
            } else {
                trigger_error("O objeto nao possui o atributo \"{$atributo}\"", E_USER_ERROR);
            }
        }
        return $matriz;
    }


    //
    //     Obtem um vetor com os valores de determinado atributo dos objetos do vetor original
    //
    public static function get_vetor_atributo($vetor, $atributo, $indexacao = 1) {
    // Array[Object] $vetor: vetor de objetos que possuem o atributo $atributo
    // String $atributo: atributo a ser coletado dos objetos
    // Int $indexacao: indica o tipo de indexacao (0 = reindexar, 1 = utilizar $atributo, 2 = manter indice)
    //
        $vetor_atributo = array();
        switch (intval($indexacao)) {
        case VETOR_REINDEXAR:
            foreach ($vetor as $objeto) {
                $vetor_atributo[] = $objeto->$atributo;
            }
            break;
        case VETOR_INDEXAR_ATRIBUTO:
            foreach ($vetor as $objeto) {
                $valor = $objeto->$atributo;
                $vetor_atributo[strval($valor)] = $valor;
            }
            break;
        case VETOR_MANTER_INDICE:
            foreach ($vetor as $i => $objeto) {
                $vetor_atributo[$i] = $objeto->$atributo;
            }
            break;
        }
        return $vetor_atributo;
    }


    //
    //     Filtra objetos cujos atributos especificados sao repetidos
    //
    public static function filtrar_atributos_repetidos($vetor, $campos_unicos, $preferencia = VETOR_PRIMEIRO, $campos_gerados = true) {
    // Array[Mixed => Object] $vetor: vetor de objetos a serem filtrados
    // Array[String] $campos_unicos: campos que devem ser unicos para montar o vetor
    // Int $preferencia: prioridade utilizada para obter os dados do vetor (VETOR_PRIMEIRO ou VETOR_ULTIMO)
    // Array[String] $campos_gerados: campos que devem ser gerados nos objetos do vetor retornado (por padrao true = todos)
    //
        $objetos = array();
        $unicos  = array();

        foreach ($vetor as $item) {
            $unico = new stdClass();
            $inserir = false;

            foreach ($campos_unicos as $campo) {
                $unico->$campo = $item->$campo;
            }
            $chave = md5(var_export($unico, true));

            // Primeiro item com a chave gerada
            if (!array_key_exists($chave, $unicos)) {
                $unicos[$chave] = $item;
                $inserir = true;

            // Item ja existe no vetor
            } else {

                // Checar a preferencia para inserir ou nao
                switch ($preferencia) {
                case UTIL_PRIMEIRO:
                    $inserir = false;
                    break;

                case UTIL_ULTIMO:
                    $inserir = true;
                    break;
                }
            }

            // Montar objeto e inserir no vetor
            if ($inserir) {
                if ($campos_gerados === true) {
                    $obj = $item;
                } else {
                    $obj   = new stdClass();
                    foreach ($campos_gerados as $campo) {
                        $obj->$campo = $item->$campo;
                    }
                }
                $objetos[$chave] = $obj;
            }
        }
        return array_values($objetos);
    }


    //
    //     Obtem a soma dos valores de determinado atributo dos objetos do vetor original
    //
    public static function somar_atributo($vetor, $atributo) {
    // Array[Object] $vetor: vetor de objetos que possuem o atributo $atributo
    // String $atributo: atributo a ser coletado dos objetos e somado
    //
        $soma = 0;
        foreach ($vetor as $objeto) {
            $soma += $objeto->$atributo;
        }
        return $soma;
    }


    //
    //     Ordena um vetor utilizando um vetor de chaves e retorna o vetor ordenado
    //
    public static function ordenar_por_chaves($vetor, $chaves) {
    // Array[Mixed => Mixed] $vetor: vetor indexado de alguma forma
    // Array[Mixed] $chaves: vetor de chaves utilizadas para ordenacao
    //
        $novo = array();
        foreach ($chaves as $chave) {
            if (isset($vetor[$chave])) {
                $novo[$chave] = $vetor[$chave];
            }
        }
        return $novo;
    }


    //
    //     Retira elementos de uma matriz gerada pelo metodo vetor_associativo_hierarquico da classe objeto
    //
    public static function remover_elementos(&$vt_hierarquico, &$vt_remover) {
    // Array[String => Array[Mixed => String]] $vt_hierarquico: vetor hierarquico a ser filtrado
    // Array[Mixed => String] $vt_remover: vetor de valores a serem removidos
    //
        $vt_hierarquico_novo = array();
        $total = 0;
        foreach ($vt_hierarquico as $grupo => $vt_grupo) {
            $vt_hierarquico_novo[$grupo] = array_diff($vt_grupo, $vt_remover);
            $total += count($vt_hierarquico_novo[$grupo]);
        }
        $vt_hierarquico = $total ? $vt_hierarquico_novo : array();
    }


    //
    //     Realiza o array_unique de maneira recursiva
    //
    static public function array_unique_recursivo($vetor) {
    // Array[Mixed] $vetor: qualquer vetor
    //
        $novo = array();
        foreach ($vetor as $chave => $valor) {

            // Analisar recursivamente
            if (is_array($valor)) {
                $novo[$chave] = self::array_unique_recursivo($valor);

            // Checar se o valor ja' foi inserido em $novo
            } else {
                $existe = false;
                foreach ($novo as $chave_novo => $valor_novo) {

                    // Se ja' existe: nao inserir novamente
                    if ((string)$valor_novo === (string)$valor) {
                        $existe = true;
                        break;
                    }
                }

                // Se nao existe: inserir em $novo
                if (!$existe) {
                    $novo[$chave] = $valor;
                }
            }
        }
        return $novo;
    }

}//class
