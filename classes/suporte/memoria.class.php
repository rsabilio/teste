<?php
//
// SIMP
// Descricao: Classe com metodos para controle de memoria
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.2
// Data: 22/02/2011
// Modificado: 10/05/2011
// Copyright (C) 2011  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('MEMORIA_MAXIMA', 17179869184 ); // 16GB

final class memoria {

    // Int Precisao de casas decimais
    private static $precisao = 2;


    //
    //     Devolve um vetor com os dados das unidades
    //
    static public function get_unidades($tipo = 'IEC') {
    // String $tipo: 'SI' (base decimal) ou 'IEC' (base binaria)
    //
        switch (strtoupper($tipo)) {
        case 'SI':
            return array(
                array('base' => 10, 'exp' => 24, 'sigla' => 'YB', 'nome' => 'Yottabyte'),
                array('base' => 10, 'exp' => 21, 'sigla' => 'ZB', 'nome' => 'Zettabyte'),
                array('base' => 10, 'exp' => 18, 'sigla' => 'EB', 'nome' => 'Exabyte'  ),
                array('base' => 10, 'exp' => 15, 'sigla' => 'PB', 'nome' => 'Petabyte' ),
                array('base' => 10, 'exp' => 12, 'sigla' => 'TB', 'nome' => 'Terabyte' ),
                array('base' => 10, 'exp' =>  9, 'sigla' => 'GB', 'nome' => 'Gigabyte' ),
                array('base' => 10, 'exp' =>  6, 'sigla' => 'MB', 'nome' => 'Megabyte' ),
                array('base' => 10, 'exp' =>  3, 'sigla' => 'KB', 'nome' => 'Kilobyte' ),
                array('base' => 10, 'exp' =>  0, 'sigla' =>  'B', 'nome' => 'byte'     )
            );
            break;
        case 'IEC':
            return array(
                array('base' => 2, 'exp' => 80, 'sigla' => 'YiB', 'nome' => 'Yottabyte'),
                array('base' => 2, 'exp' => 70, 'sigla' => 'ZiB', 'nome' => 'Zettabyte'),
                array('base' => 2, 'exp' => 60, 'sigla' => 'EiB', 'nome' => 'Exabyte'  ),
                array('base' => 2, 'exp' => 50, 'sigla' => 'PiB', 'nome' => 'Petabyte' ),
                array('base' => 2, 'exp' => 40, 'sigla' => 'TiB', 'nome' => 'Terabyte' ),
                array('base' => 2, 'exp' => 30, 'sigla' => 'GiB', 'nome' => 'Gigabyte' ),
                array('base' => 2, 'exp' => 20, 'sigla' => 'MiB', 'nome' => 'Megabyte' ),
                array('base' => 2, 'exp' => 10, 'sigla' => 'KiB', 'nome' => 'Quilobyte' ),
                array('base' => 2, 'exp' =>  0, 'sigla' =>   'B', 'nome' => 'byte'     )
            );
            break;
        }
    }


    //
    //     Define o numero de casas decimais
    //
    static public function set_precisao($precisao) {
    // Int $precisao: numero de casas decimais
    //
        self::$precisao = (int)$precisao;
    }


    //
    //     Obtem o numero de casas decimais de precisao
    //
    static public function get_precisao() {
        return self::$precisao;
    }


    //
    //     Formata os bytes com a unidade mais adequada, tornando o valor mais legivel para humanos
    //
    static public function formatar_bytes($bytes, $abbr = false, $tipo = 'IEC') {
    // Int $bytes: valor em bytes
    // Bool $abbr: colocar significado da abreviacao
    // String $tipo: tipo de unidade ('SI' ou 'IEC')
    //
        $unidades = self::get_unidades($tipo);
        foreach ($unidades as $unidade) {
            if (!$unidade['exp']) {
                $valor = $bytes;
                $abbr = $abbr ? sprintf(' <abbr title="%s">%s</abbr>', $unidade['nome'], $unidade['sigla']) : ' '.$unidade['sigla'];
                return $valor.$abbr;
            }
            $pow = pow($unidade['base'], $unidade['exp']);
            if ($bytes > $pow) {
                $valor = round($bytes / $pow, self::$precisao);
                $abbr = $abbr ? sprintf(' <abbr title="%s">%s</abbr>', $unidade['nome'], $unidade['sigla']) : ' '.$unidade['sigla'];
                return texto::numero($valor).$abbr;
            }
        }
        return false;
    }


    //
    //     Converte de alguma unidade para bytes
    //
    public static function desformatar_bytes($valor, $tipo = 'IEC', $conv = null) {
    // String $valor: valor com uma unidade
    // String $tipo: tipo de unidade ('SI' ou 'IEC')
    // Array[String => Mixed] $conv: convencoes da localidade usadas no numero ou null para obter de validacao::get_convencoes_localidade()
    //
        $valor = trim(strip_tags($valor));

        if ($conv === null) {
            $conv = validacao::get_convencoes_localidade();
        }
        $separador_decimal = isset($conv['decimal_point']) ? preg_quote($conv['decimal_point']) : preg_quote('.');
        $separador_milhar  = isset($conv['thousands_sep']) ? preg_quote($conv['thousands_sep']) : '';

        if ($separador_milhar) {
            $exp = '/^(\d{1,3}(?:['.$separador_milhar.']\d{3})*(?:['.$separador_decimal.']\d+)?)[\040\h\v]*([A-Za-z]+)$/i';
        } else {
            $exp = '/^(\d+(?:['.$separador_decimal.']\d+)?)[\040\h\v]*([A-Za-z]+)$/';
        }
        if (!preg_match($exp, $valor, $matches)) {
            return false;
        }

        $inteiro = intval($matches[1]);
        $unidade = $matches[2];

        $unidades = self::get_unidades($tipo);
        foreach ($unidades as $dados_unidade) {
            if ($unidade == $dados_unidade['sigla']) {
                return $inteiro * pow($dados_unidade['base'], $dados_unidade['exp']);
            }
        }
        return false;
    }


    //
    //     Converte uma unidade de memoria usada por memory_limit para bytes
    //
    public static function desformatar_bytes_php($valor) {
    // String $valor: possivel valor positivo de memory_limit
    //
        // Apenas numeros
        if (preg_match('/^(\d+)$/', $valor, $matches)) {
            return $valor;

        // Numero + posfixo "G", "M" ou "K'
        } elseif (preg_match('/^(\d+)([GMK])$/', $valor, $matches)) {
            switch ($matches[2]) {
            case 'G':
                return $matches[1] * pow(2, 30);
            case 'M':
                return $matches[1] * pow(2, 20);
            case 'K':
                return $matches[1] * pow(2, 10);
            }
        }
        return false;
    }


    //
    //     Realiza a reserva de memoria para o PHP, caso esteja abaixo do valor reservado no momento
    //
    public static function reservar($memoria) {
    // String || Int $memoria: quantidade de memoria a ser reservada (veja diretiva memory_limit do php.ini)
    //
        $memoria_atual = ini_get('memory_limit');

        // Se a quantidade ja esta ilimitada
        if ($memoria_atual == '-1') {
            return;
        }
        $memoria_bytes_atual = self::desformatar_bytes_php($memoria_atual);

        // Se ja foi reservado mais que o desejado
        if ($memoria != '-1') {
            if (is_int($memoria) || is_float($memoria)) {
                $memoria_bytes = $memoria;
            } else {
                $memoria_bytes = self::desformatar_bytes_php($memoria);
            }
            if ($memoria_bytes < $memoria_bytes_atual) {
                return;
            }
        }

        // Reservar
        if ($memoria_bytes > MEMORIA_MAXIMA) {
            ini_set('memory_limit', MEMORIA_MAXIMA);
        } else {
            ini_set('memory_limit', number_format($memoria_bytes, 0, '.', ''));
        }
    }


    //
    //     Realiza a reserva de memoria de forma que o uso atual de memoria represente uma
    //     porcentagem sobre o total reservado no momento. Exceto quando foi reservada
    //     memoria ilimitada para o PHP.
    //     Por exemplo: se esta' usando 8M e informar o percentual 50%, entao sera' reservado 16M.
    //
    public static function reservar_percentual($percentual, $minimo = '16M') {
    // Int $percentual: valor percentual desejado para a quantidade usada ate o momento (0 a 100)
    // String $minimo: valor minimo que deve permanecer reservado, mesmo que o valor calculado seja inferior
    //
        $limite = ini_get('memory_limit');

        // Se e' infinita: nao reservar
        if ($limite == '-1') {
            return false;
        }

        $uso_bytes     = memory_get_usage(true);
        $reserva_bytes = intval($uso_bytes * 100 / $percentual);
        $minimo_bytes  = self::desformatar_bytes_php($minimo);

        // Se ficou abaixo do minimo: manter o minimo
        if ($reserva_bytes <= $minimo_bytes) {
            ini_set('memory_limit', $minimo);
            return false;
        }

        if ($reserva_bytes < MEMORIA_MAXIMA) {
            ini_set('memory_limit', $reserva_bytes);
        } else {
            ini_set('memory_limit', MEMORIA_MAXIMA);
        }
        return true;
    }


    //
    //     Obtem o percentual de memoria utilizado no momento
    //
    public static function get_percentual_utilizado() {
        $limite = ini_get('memory_limit');

        // Se e' infinita: esta' usando 0%
        if ($limite == '-1') {
            return 0;
        }

        // O limite nunca sera zero
        //if (!$limite) {
        //    return 100;
        //}

        $limite = self::desformatar_bytes_php($limite);
        $uso = memory_get_usage(true);

        return $uso * 100 / $limite;
    }
}
