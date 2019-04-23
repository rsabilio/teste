<?php
//
// SIMP
// Descricao: Classe que oferece varias listas uteis
// Autor: Rodrigo Pereira Moreira && Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rpmoreira@tecnolivre.com.br
// Versao: 1.0.0.37
// Data: 20/08/2007
// Modificado: 09/05/2011
// Copyright (C) 2007  Rodrigo Pereira Moreira
// License: LICENSE.TXT
//
define('LISTAS_LOCALIDADE',  $CFG->localidade);
define('LISTAS_CHARSET',     $CFG->charset);
define('LISTAS_UTF8',        $CFG->utf8);
define('LISTAS_DIR_MODULOS', $CFG->dirmods);
define('LISTAS_DIR_CLASSES', $CFG->dirclasses);

final class listas {


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Retorna um vetor com um intervalo de numeros
    //
    static public function numeros($inicio, $fim, $formato = false, $passo = 1, $formatar_chave = false) {
    // Int $inicio: inicio do vetor
    // Int $fim: fim do vetor
    // String $formato: formato usado na funcao sprintf para exibicao do numero ao usuario
    // Int $passo: saltar de N em N
    // Bool $formatar_chave: indica se o valor da chave deve ser formatado com texto::numero
    //
        $vetor = array();

        if (!$formato) {
            $formato = '%d';
        }

        $passo = abs($passo);

        if ($inicio < $fim) {
            for ($i = $inicio; $i <= $fim; $i += $passo) {
                $chave = $formatar_chave ? texto::numero($i) : $i;
                $vetor[$chave] = sprintf($formato, $i);
            }
        } else {
            for ($i = $fim; $i >= $fim; $i -= $passo) {
                $chave = $formatar_chave ? texto::numero($i) : $i;
                $vetor[$chave] = sprintf($formato, $i);
            }
        }

        return $vetor;
    }


    //
    //     Retorna um vetor com nomes de fontes
    //
    static public function get_fontes($padrao = true) {
    // Bool $padrao: incluir o padrao no vetor
    //
        include(dirname(__FILE__).'/listas/fontes.php');
        if ($padrao) {
            $vetor['padrao'] = 'Padr&atilde;o';
        }
        return $vetor;
    }


    //
    //     Retorna um vetor com nomes de estados brasileiros
    //
    static public function get_estados($nenhum = true) {
    // Bool $nenhum: opcao para selecionar nenhum
    //
        include(dirname(__FILE__).'/listas/estados.php');
        if ($nenhum) {
            $vetor['--'] = '(Nenhum)';
        }
        return $vetor;
    }


    //
    //     Retorna um vetor com os codigos das regioes de telefonia no Brasil
    //
    static public function get_codigos_telefone() {
        include(dirname(__FILE__).'/listas/codigos_telefone.php');
        return $vetor;
    }


    //
    //     Retorna um vetor com as localidades permitidas
    //
    static public function get_locales() {
        static $locales = null;
        if ($locales === null) {
            $locales = array();
            include(dirname(__FILE__).'/listas/localidades.php');

            $locale = setlocale(LC_ALL, 0);
            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                foreach ($vetor as $nome => $codigos) {
                    $codigo = $codigos[1];
                    if ($codigo && setlocale(LC_ALL, $codigo)) {
                        $locales[$nome] = $codigo;
                    }
                }
            } else {
                foreach ($vetor as $nome => $codigos) {
                    $codigo = $codigos[0].(LISTAS_CHARSET ? '.'.strtoupper(LISTAS_CHARSET) : '');
                    if ($codigos[0] && setlocale(LC_ALL, $codigo)) {
                        $locales[$nome] = $codigo;
                    }
                }
            }
            setlocale(LC_ALL, $locale);
        }

        return $locales;
    }


    //
    //     Retorna vetor com codigos de linguas
    //
    static public function get_linguas() {
        include(dirname(__FILE__).'/listas/linguas.php');
        return $vetor;
    }


    //
    //     Obtem a descricao dos caracteres de controle ASCII
    //
    public static function get_caracteres_controle() {
        include(dirname(__FILE__).'/listas/caracteres_controle.php');
        return $vetor;
    }


    //
    //     Obtem as faixas de caracteres Unicode
    //
    public static function get_faixas_unicode() {
        include(dirname(__FILE__).'/listas/faixas_unicode.php');
        return $vetor;
    }


    //
    //     Retorna vetor com os meses
    //
    static public function get_meses($nenhum = false, $abreviado = false) {
    // Bool $nenhum: indica se deve incluir a opcao "Nenhum"
    // Bool $abreviado: indica se deve mostrar os nomes de forma abreviada ou nao
    //
        static $cache = array();
        if (isset($cache[(int)$nenhum][(int)$abreviado])) {
            return $cache[(int)$nenhum][(int)$abreviado];
        }

        setlocale(LC_ALL, LISTAS_LOCALIDADE);

        $vt_meses = array();
        if ($nenhum) {
            $vt_meses[0] = '--';
        }
        if (function_exists('nl_langinfo')) {
            for ($i = 1; $i <= 12; $i++) {
                if ($abreviado) {
                    $vt_meses[$i] = ucfirst(nl_langinfo(constant('ABMON_'.$i)));
                } else {
                    $vt_meses[$i] = ucfirst(nl_langinfo(constant('MON_'.$i)));
                }
            }
        } else {
            $ano = (int)strftime('%Y');
            for ($mes = 1; $mes <= 12; $mes++) {
                $time = mktime(0, 0, 0, $mes, 1, $ano);
                if ($abreviado) {
                    $vt_meses[$mes] = ucfirst(strftime('%b', $time));
                } else {
                    $vt_meses[$mes] = ucfirst(strftime('%B', $time));
                }
            }
        }
        if (LISTAS_UTF8) {
            foreach ($vt_meses as &$mes) {
                $mes = utf8_encode(utf8_decode($mes));
            }
        }
        $cache[(int)$nenhum][(int)$abreviado] = $vt_meses;
        return $vt_meses;
    }


    //
    //     Retorna um vetor com os nomes dos dias da semana (0 = Domingo, 6 = Sabado)
    //
    static public function get_semanas($abreviado = false) {
    // Bool $abreviado: retorna a lista com os nomes abreviados
    //
        static $cache = array();

        if (isset($cache[(int)$abreviado])) {
            return $cache[(int)$abreviado];
        }

        setlocale(LC_ALL, LISTAS_LOCALIDADE);
        $vt_semana = array();
        if (function_exists('nl_langinfo')) {
            if ($abreviado) {
                for ($i = 1; $i <= 7; $i++) {
                    $vt_semana[] = nl_langinfo(constant('ABDAY_'.$i));
                }
            } else {
                for ($i = 1; $i <= 7; $i++) {
                    $vt_semana[] = nl_langinfo(constant('DAY_'.$i));
                }
            }
        } else {
            list($dia, $mes, $ano) = util::get_data_completa();
            $time = mktime(0, 0, 0, $mes, $dia, $ano);
            if (strftime('%u') === false) {
                $dados_data = getdate($time);
                $dia_semana = $dados_data['wday'];
            } else {
                $dia_semana = (int)strftime('%u', $time);
            }
            $dia += 7 - ($dia_semana % 7);
            for ($i = 0; $i < 7; $i++, $dia++) {
                $time = mktime(0, 0, 0, $mes, $dia, $ano);
                $nome = $abreviado ? strftime('%a', $time) : strftime('%A', $time);
                $vt_semana[] = ucfirst($nome);
            }
        }
        if (LISTAS_UTF8) {
            $vt_semana = array_map('utf8_decode', $vt_semana);
            $vt_semana = array_map('utf8_encode', $vt_semana);
        }
        $cache[(int)$abreviado] = $vt_semana;
        return $vt_semana;
    }


    //
    //     Retorna um vetor com os dias do mes
    //
    static public function get_dias($nenhum = false) {
    // Bool $nenhum: indica se deve incluir a opcao "Nenhum"
    //
        if ($nenhum) {
            return array_merge(array(0 => '--'), self::numeros(1, 31));
        }
        return self::numeros(1, 31);
    }


    //
    //     Retorna vetor com os anos
    //
    static public function get_anos($passado = 20, $futuro = 5, $nenhum = false, $formatar = false) {
    // Int $passado: numero de anos anteriores ao atual
    // Int $futuro: numero de anos posteriores ao atual
    // Bool $nenhum: indica se deve incluir a opcao "Nenhum"
    // Bool $formatar: formata o valor a ser enviado
    //
        $anos = array();
        if ($nenhum) {
            $anos[0] = '----';
        }
        $atual = (int)strftime('%Y');
        for ($i = $atual - $passado; $i <= $atual + $futuro; $i++) {
            if ($formatar) {
                $i2 = texto::numero($i);
                $anos[$i2] = $i2;
            } else {
                $anos[$i] = texto::numero($i);
            }
        }
        return $anos;
    }


    //
    //     Retorna um vetor com os semestres
    //
    static public function get_semestres() {
        return array(1 => '1&ordm; Semestre',
                     2 => '2&ordm; Semestre');
    }


    //
    //    Obtem as classes de um diretorio recursivamente e, opcionalmente, que extendem determinada classe
    //
    static public function get_classes($diretorio, $classe_base = false, $apenas_instanciaveis = true) {
    // String $diretorio: caminho para o diretorio a ser percorrido
    // String $classe_base: filtra apenas as classes filhas da classe base indicada
    // Bool $apenas_instanciaveis: filtra apenas as classes instanciaveis
    //
        $classes = array();
        if (!is_dir($diretorio)) {
            trigger_error('O diretorio "'.$diretorio.'" nao existe', E_USER_WARNING);
            return $classes;
        }

        $reflexao_base = false;
        if ($classe_base) {
            simp_autoload($classe_base);
            $reflexao_base = new ReflectionClass($classe_base);
            if ($reflexao_base->isFinal()) {
                trigger_error('A classe base "'.$classe_base.'" eh final e nao pode ter filhas', E_USER_WARNING);
                return $classes;
            }
        }
        self::get_classes_recursivo($diretorio, $classes, $reflexao_base, $apenas_instanciaveis);
        return $classes;
    }


    //
    //     Recursao do metodo get_classes
    //
    static private function get_classes_recursivo($diretorio, &$classes, $reflexao_base = false, $apenas_instanciaveis = true) {
    // String $diretorio: caminho para o diretorio
    // Array[String => String] $classes: classes encontradas no diretorio
    // Bool || ReflectionClass $reflexao_base: filtra apenas as classes filhas da classe base indicada por sua reflexao
    // Bool $apenas_instanciaveis: filtra apenas as classes instanciaveis
    //
        $dir = opendir($diretorio);
        if (!$dir) {
            return false;
        }
        while (($item = readdir($dir)) !== false) {
            if (substr($item, 0, 1) == '.') { continue; }
            if (is_dir($diretorio.$item)) {
                self::get_classes_recursivo($diretorio.$item.'/', $classes, $reflexao_base, $apenas_instanciaveis);
            } elseif (preg_match('/^([A-Za-z0-9-_\.]+).class.php$/', $item, $match)) {
                $classe = $match[1];
                if (isset($classes[$classe])) {
                    continue;
                }
                if ($reflexao_base) {
                    simp_autoload($classe);
                    $reflexao_filha = new ReflectionClass($classe);
                    if ($reflexao_filha->isSubclassOf($reflexao_base)) {
                        if (!$apenas_instanciaveis || $reflexao_filha->isInstantiable()) {
                            $classes[$classe] = $classe;
                        }
                    }
                    unset($reflexao_filha);
                } else {
                    $classes[$classe] = $classe;
                }
            }
        }
        closedir($dir);
    }


    //
    //     Obtem um vetor com as entities html apontando para o respectivo caractere
    //
    static public function get_html_entities($tipo = 1) {
    // Int $tipo: 1 =  indexado por entities e apontando para os valores / 2 = indexado pelos valores e apontando para as entities
    //
        static $st_entities = array();

        if (empty($st_entities)) {
            $arq = dirname(__FILE__).'/dtd/entities.php';
            if (!is_file($arq)) {
                trigger_error('Diretorio classes/suporte/dtd/ nao foi encontrado', E_USER_WARNING);
                return array();
            }
            include($arq);
            $st_entities[1] = $entities1;
            $st_entities[2] = $entities2;
        }
        return $st_entities[$tipo];
    }


    //
    //     Retorna um vetor indexado pelo nome das entidades do sistema e que aponta para a descricao da entidade
    //
    static public function get_entidades($cache = true) {
    // Bool $cache: obter entidades da cache para otimizar
    //
        global $CFG;
        $id = cache_arquivo::get_id();

        // Se tem em cache: retornar
        if ($cache && cache_arquivo::em_cache($id)) {
            return cache_arquivo::get_valor($id);
        }

        // Se nao tem em cache: consultar
        $classes_nomes = array();

        $total_entidades = count(glob($CFG->dirclasses.'extensao/*.class.php'));
        $reserva = number_format(16 + (2 * $total_entidades), 0, '.', '');
        memoria::reservar($reserva.'M');

        $classes = self::get_classes(LISTAS_DIR_CLASSES.'/extensao/', 'objeto', true);
        $i = 0;
        foreach ($classes as $classe) {
            $classes_nomes[$classe] = objeto::get_objeto($classe)->get_entidade();
            $i++;

            // A cada 10 classes percorridas: limpar as definicoes para guardar memoria
            if ($i % 10 == 0) {
                objeto::limpar_definicoes_classes();
            }
        }
        objeto::limpar_definicoes_classes();

        asort($classes_nomes);

        // Guardar em cache
        cache_arquivo::set_valor($id, $classes_nomes);

        return $classes_nomes;
    }


    //
    //     Retorna um vetor de modulos
    //
    static public function get_modulos() {
        $id = cache_arquivo::get_id();

        // Se tem em cache: retornar
        if (cache_arquivo::em_cache($id)) {
            return cache_arquivo::get_valor($id);
        }

        // Se nao tem em cache: consultar
        $modulos = self::get_modulos_recursivo(LISTAS_DIR_MODULOS);

        // Guardar em cache
        cache_arquivo::set_valor($id, $modulos);

        return $modulos;
    }


    //
    //     Retorna um vetor de modulos de um diretorio
    //
    static private function get_modulos_recursivo($dir_modulos, $prefixo = '') {
    // String $dir_modulos: diretorio de modulos
    // String $prefixo: prefixo a ser adicionado
    //
        $vet = array();
        $dir = opendir($dir_modulos);
        if ($dir) {
            while (($item = readdir($dir)) !== false) {
                if ($item == '.' || $item == '..' || $item == '.svn') {
                    continue;
                }
                if (is_dir($dir_modulos.'/'.$item)) {
                    $vet[$prefixo.$item] = $prefixo.$item;
                    $vet2 = self::get_modulos_recursivo($dir_modulos.'/'.$item.'/', $prefixo.$item.'/');

                    $vet = $vet + $vet2;
                }
            }
            asort($vet);
        }
        return $vet;
    }

}//class
