<?php
//
// SIMP
// Descricao: Classe Atributo (define os atributos das classes)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.1.2
// Data: 06/08/2007
// Modificado: 26/04/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('ATRIBUTO_UTF8', $CFG->utf8);

define('ATRIBUTO_DATA_RELATIVA', 1);
define('ATRIBUTO_DATA_ABSOLUTA', 2);

define('ATRIBUTO_FORMATO_DATA',      '%d/%m/%Y');            // Formato padrao de data
define('ATRIBUTO_FORMATO_HORA',      '%H:%M:%S');            // Formato padrao de hora
define('ATRIBUTO_FORMATO_DATA_HORA', '%d/%m/%Y - %H:%M:%S'); // Formato padrao de data/hora

final class atributo {
    private $valores;

    //
    //     Retorna os valores padrao para cada caracteristica de um atributo
    //
    public static function valor_padrao($caracteristica) {
    // String $caracteristica: nome da caracteristica do atributo
    //
        switch ($caracteristica) {

        // Caracteristicas gerais
        case 'nome': return '';                      // Nome do atributo no BD
        case 'descricao': return '';                 // Descricao do atributo (nome apresentado ao usuario)
        case 'label': return false;                  // Nome do label nos formularios (string ou array indexado pelo ID do formulario)
        case 'ajuda': return false;                  // Ajuda do atributo em formularios
        case 'exemplo': return false;                // Exemplo(s) de valor que pode(m) ser assumido(s) pelo campo
        case 'tipo': return 'string';                // Tipo (int, float, string, char, bool, binario ou data)
        case 'chave': return false;                  // Indica o tipo de Chave (PK, FK, OFK, CK ou false)
        case 'minimo': return false;                 // Valor minimo do campo ou false caso nao exista
        case 'maximo': return false;                 // Valor maximo do campo ou false caso nao exista
        case 'casas_decimais': return false;         // Numero de casas decimais em numeros reais ou false para indefinido
        case 'fixo': return false;                   // Numero fixo ou variavel de casas decimais
        case 'moeda': return false;                  // Campo do tipo moeda
        case 'padrao': return null;                  // Valor padrao
        case 'pode_vazio': return true;              // Pode ou nao deixar vazio no formulario
        case 'validacao': return false;              // Tipo de validacao utilizada (consultar classe validacao)
        case 'classe': return '';                    // Nome da classe original que possui o atributo
        case 'validacao_especifica': return false;   // Nome do metodo que faz uma validacao especifica (que recebe o valor a ser validado por parametro)
        case 'filtro': return false;                 // Tipo de filtro utilizado
        case 'unico': return false;                  // O campo e' unico no BD
        case 'campo_formulario': return false;       // Tipo de campo de formulario usado por padrao
        case 'usar_valor_padrao': return true;       // Usar o valor padrao para preencher o campo no formulario

        // Caracteristicas dos campos de data
        case 'tipo_data_inicio': return ATRIBUTO_DATA_RELATIVA; // Tipo de data de inicio (relativa ou absoluta) das caracteristicas data_inicio e data_fim
        case 'data_inicio': return 10;                          // Ano inicial nos campos de data
        case 'tipo_data_fim': return ATRIBUTO_DATA_RELATIVA;    // Tipo de data de fim (relativa ou absoluta) das caracteristicas data_inicio e data_fim
        case 'data_fim': return 10;                             // Ano final nos campos de data
        case 'formato_data': return false;                      // Formato do campo de data (igual ao informado para funcao strftime)
        }
        return null;
    }


    //
    //     Retorna um vetor com as caracteristicas de um atributo
    //
    public static function get_caracteristicas() {
        return array(
            'nome',
            'descricao',
            'label',
            'ajuda',
            'exemplo',
            'tipo',
            'chave',
            'minimo',
            'maximo',
            'casas_decimais',
            'fixo',
            'moeda',
            'padrao',
            'pode_vazio',
            'validacao',
            'classe',
            'validacao_especifica',
            'filtro',
            'unico',
            'campo_formulario',
            'usar_valor_padrao',
            'tipo_data_inicio',
            'data_inicio',
            'tipo_data_fim',
            'data_fim',
            'formato_data'
        );
    }


    //
    //     Retorna um XML com as caracteristicas do atributo
    //
    public function get_definicao_xml() {
        $vt_caracteristicas = array();
        foreach (self::get_caracteristicas() as $c) {
            $valor = $this->__get($c);
            $tipo  = util::get_tipo($valor);
            $valor = util::exibir_var($valor, UTIL_EXIBIR_TEXTO);
            $vt_caracteristicas[] = '<'.$c.' tipo="'.$tipo.'"><![CDATA['.$valor.']]></'.$c.'>';
        }
        return '<atributo>'.implode('', $vt_caracteristicas).'</atributo>';
    }


    //
    //     Retorna o tipo de mascara adequado para o atributo
    //
    public function get_mascara() {
        if ($this->__get('moeda')) {
            return 'moeda';
        }
        switch ($this->__get('tipo')) {
        case 'string':
            switch ($this->__get('validacao')) {
            case 'CPF':
            case 'CNPJ':
            case 'NUMERICO':
                $mascara = 'digitos';
                break;
            case 'LETRAS':
                $mascara = 'letras';
                break;
            default:
                $mascara = '';
                break;
            }
            return $mascara;
        case 'int':
            $mascara = $this->__get('tipo');
            if ($this->__get('minimo') >= 0 && $this->__get('maximo') >= 0) {
                $mascara = 'u'.$mascara;
            }
            return $mascara;
        case 'float':
            if ($this->__get('moeda')) {
                $mascara = 'moeda';
            } else {
                $mascara = $this->__get('tipo');
                if ($this->__get('minimo') >= 0 && $this->__get('maximo') >= 0) {
                    $mascara = 'u'.$mascara;
                }
            }
            return $mascara;
        }
        return '';
    }


    //
    //     Obtem o valor de uma caracteristica do atributo
    //
    public function __get($caracteristica) {
    // String $caracteristica: nome da caracteristica do atributo a ser obtido
    //
        if (isset($this->valores[$caracteristica])) {
            return $this->valores[$caracteristica];
        }
        return self::valor_padrao($caracteristica);
    }


    //
    //     Define o valor de uma caracteristica do atributo
    //
    public function __set($caracteristica, $valor) {
    // String $caracteristica: nome da caracteristica do atributo
    // Mixed $valor: valor a ser definido
    //
        // Caracteristicas que precisam de validacao
        switch ($caracteristica) {

        // Campos enum
        case 'tipo':
            $tipos = array('int', 'float', 'string', 'char', 'bool', 'binario', 'data');
            if (!in_array($valor, $tipos)) {
                trigger_error('Tipo de atributo desconhecido: '.$valor, E_USER_WARNING);
                return false;
            }
            break;

        case 'chave':
            $tipos = array('PK', 'FK', 'OFK', 'CK', false);
            if (is_string($valor)) {
                $valor = strtoupper($valor);
            }
            if (!in_array($valor, $tipos)) { return false; }
            break;

        case 'campo_formulario':
            $tipos = array(
                'text',
                'textarea',
                'select',
                'bool',
                'bool_radio',
                'relacionamento',
                'radio',
                'hidden',
                'password',
                'file',
                'submit',
                'data',
                'hora',
                'data_hora',
                 false
            );
            if (!in_array($valor, $tipos)) {
                trigger_error('Tipo de campo de formulario desconhecido: '.$valor, E_USER_WARNING);
                return false;
            }
            break;

        case 'validacao':
            if (is_string($valor)) {
                $tipos = validacao::get_tipos();
                if (!in_array($valor, $tipos)) {
                    trigger_error('Tipo de validacao desconhecido: '.$valor, E_USER_WARNING);
                    return false;
                }
            }
            break;

        case 'tipo_data_inicio':
        case 'tipo_data_fim':
            $tipos = array(ATRIBUTO_DATA_RELATIVA, ATRIBUTO_DATA_ABSOLUTA);
            if (!in_array($valor, $tipos)) {
                trigger_error('Tipo de data desconhecido: '.$valor, E_USER_WARNING);
                return false;
            }
            break;

        // Campos numericos ou false
        case 'minimo':
        case 'maximo':
            if ($this->__get('tipo') == 'data') {
                $valor = (string)$valor;
            } else {
                if (!is_numeric($valor)) {
                    $valor = false;
                }
            }
            break;

        // Campos inteiros
        case 'casas_decimais':
        case 'data_inicio':
        case 'data_fim':
            $valor = (int)$valor;
            break;

        // Campos booleanos
        case 'moeda':
        case 'fixo':
        case 'unico':
        case 'pode_vazio':
        case 'usar_valor_padrao':
            $valor = (bool)$valor;
            break;

        // Campos de texto
        case 'nome':
        case 'descricao':
        case 'classe':
        case 'exemplo':
        case 'formato_data':
            $valor = (string)$valor;
            break;

        // Campos especiais
        case 'filtro':
        case 'validacao_especifica':
            if ($valor !== false) {
                $valor = (string)$valor;
            }
            break;
        case 'ajuda':
            if (!is_string($valor) && !is_array($valor)) {
                trigger_error('A ajuda do atributo so pode ser string ou array', E_USER_WARNING);
                $valor = false;
            }
            break;
        case 'label':
            if (!is_string($valor) && !is_array($valor)) {
                trigger_error('O label do atributo so pode ser string ou array', E_USER_WARNING);
                $valor = false;
            }
            break;

        // Valores sem tipo definido
        case 'padrao':
            break;

        default:
            trigger_error('Caracteristica do atributo desconhecida: '.$caracteristica, E_USER_WARNING);
            return false;
        }

        // Guardar valor apenas se ele for diferente do padrao
        // (Leve economia de espaco em memoria)
        if ($valor === self::valor_padrao($caracteristica)) {
            unset($this->valores[$caracteristica]);
        } else {
            $this->valores[$caracteristica] = $valor;
        }
    }


    //
    //     Construtor
    //
    public function __construct($nome, $descricao, $padrao = null) {
    // String $nome: nome do campo
    // String $descricao: descricao do campo
    // Mixed $padrao: valor padrao
    //
        $this->valores = array();

        // Definir caracteristicas basicas
        $this->__set('nome', $nome);
        $this->__set('descricao', $descricao);
        if (!is_null($padrao)) {
            $this->__set('padrao', $padrao);
        }
    }


    //
    //    Define as caracteristicas basicas do atributo
    //
    public function set_tipo($tipo, $pode_vazio = null, $chave = null) {
    // String $tipo: string, int, bool, float, char, binario ou data
    // Bool $pode_vazio: indica se o campo deve ser preenchido ou nao
    // String $chave: PK (chave primaria), FK (chave estrangeira forte), OFK (chave estrangeira fraca), CK (chave candidata) ou false (nao e' chave)
    //
        $this->__set('tipo', $tipo);
        if (!is_null($pode_vazio)) {
            $this->__set('pode_vazio', $pode_vazio);
        }
        if (!is_null($chave)) {
            $this->__set('chave', $chave);
        }
    }


    //
    //    Define o nome do label do atributo em um formulario ou varios formularios
    //
    public function set_label($label) {
    // String || Array[String => String] $label: label usado em todos formularios ou array associativo indicando o ID do formulario apontando para o label a ser utilizado
    //
        $this->__set('label', $label);
    }


    //
    //    Obtem o label a ser utilizado no formulario
    //
    public function get_label($id_form = false) {
    // String $id_form: ID do formulario ou false para qualquer um
    //
        $label = $this->__get('label');
        if ($id_form === false) {
            if (is_array($label)) {
                return array_shift($label);
            } elseif (is_string($label)) {
                return $label;
            }
            return $this->__get('descricao');
        }
        if ($label) {
            if (is_array($label)) {
                return isset($label[$id_form]) ? $label[$id_form] : $this->__get('descricao');
            }
            return $label;
        }
        return $this->__get('descricao');
    }


    //
    //     Define um intervalo minimo e maximo ao atributo
    //
    public function set_intervalo($minimo = null, $maximo = null) {
    // Int || Float || String $minimo: valor minimo para campos numericos, strings ou datas
    // Int || Float || String $maximo: valor maximo para campos numericos, strings ou datas
    //
        if (!is_null($minimo)) {
            $this->__set('minimo', $minimo);
            if ($this->__get('tipo') == 'data') {
                $data = objeto::parse_data($this->__get('minimo'));
                $this->set_data_inicio(ATRIBUTO_DATA_ABSOLUTA, $data['ano']);
            }
        }
        if (!is_null($maximo)) {
            $this->__set('maximo', $maximo);
            if ($this->__get('tipo') == 'data') {
                $data = objeto::parse_data($this->__get('maximo'));
                $this->set_data_fim(ATRIBUTO_DATA_ABSOLUTA, $data['ano']);
            }
        }
    }


    //
    //     Define uma ajuda a ser exibida nos formularios
    //
    public function set_ajuda($ajuda = false, $exemplo = false) {
    // String || Array[String => String] $ajuda: mensagem de ajuda ou vetor com as posicoes "link" e (opcionalmente) "texto"
    // String $exemplo: exemplo de preenchimento do atributo
    //
        if ($ajuda !== false) {
            $this->__set('ajuda', $ajuda);
        }
        if ($exemplo !== false) {
            $this->__set('exemplo', $exemplo);
        }
    }


    //
    //     Define a forma de validacao do atributo
    //
    public function set_validacao($validacao = null, $validacao_especifica = null, $unico = null) {
    // String $validacao: tipo de validacao (consulte o metodo validar_campo da classe suporte/validacao.class.php)
    // String $validacao_especifica: nome do metodo que faz a validacao especifica (o metodo deve receber o valor a ser validado por parametro)
    // Bool $unico: indica se o campo pode se repetir ou nao no BD
    //
        if (!is_null($validacao)) {
            $this->__set('validacao', $validacao);
        }
        if (!is_null($validacao_especifica)) {
            $this->__set('validacao_especifica', $validacao_especifica);
        }
        if (!is_null($unico)) {
            $this->__set('unico', $unico);
        }
    }


    //
    //     Define um filtro para ser usado antes de se jogar os dados no BD
    //
    public function set_filtro($filtro) {
    // String $filtro: nome do metodo da classe usado como filtro para atribuir valor ao atributo
    //
        if ($filtro) {
            $this->__set('filtro', $filtro);
        }
    }


    //
    //     Define a classe do atributo
    //
    public function set_classe($classe) {
    // String $classe: nome da classe
    //
        $this->__set('classe', $classe);
    }


    //
    //     Define o numero de casas decimais de campos float
    //
    public function set_casas_decimais($casas_decimais, $fixo = null) {
    // Int $casas_decimais: define o numero de casas decimais de campos float
    // Bool $fixo: numero fixo ou variavel de casas decimais
    //
        $this->__set('casas_decimais', abs($casas_decimais));
        if (!is_null($fixo)) {
            $this->__set('fixo', $fixo);
        }
    }


    //
    //     Define se o campo e' do tipo moeda
    //
    public function set_moeda($moeda) {
    // Bool $moeda: indica se o campo e' do tipo moeda ou nao
    //
        $this->__set('moeda', $moeda);
    }


    //
    //    Define o tipo de campo a ser usado no formulario
    //
    public function set_campo_formulario($tipo, $usar_valor_padrao = null) {
    // String $tipo: tipo de campo 'text', 'textarea', 'select', 'bool', 'relacionamento', 'radio', 'hidden', 'password', 'file', 'submit', 'data', 'hora', 'data_hora' ou false;
    // Bool $usar_valor_padrao: usar o valor padrao para preencher o campo
    //
        $this->__set('campo_formulario', $tipo);
        if (!is_null($usar_valor_padrao)) {
            $this->__set('usar_valor_padrao', $usar_valor_padrao);
        }
    }


    //
    //    Define o ano de inicio dos campos de data
    //
    public function set_data_inicio($tipo_data, $inicio) {
    // Int $tipo_data: tipo de caracteristicas das datas (ATRIBUTO_DATA_RELATIVA ou ATRIBUTO_DATA_ABSOLUTA)
    // Int $inicio: valor de data_inicio (numero de anos no passado em data relativa / ano de inicio em data absoluta)
    //
        $this->__set('tipo_data_inicio', $tipo_data);
        $this->__set('data_inicio', $inicio);
    }


    //
    //    Define o ano de fim dos campos de data
    //
    public function set_data_fim($tipo_data, $fim) {
    // Int $tipo_data: tipo de caracteristicas da data (ATRIBUTO_DATA_RELATIVA ou ATRIBUTO_DATA_ABSOLUTA)
    // Int $fim: valor de data_fim (numero de anos no futuro em data relativa / ano de termino em data absoluta)
    //
        $this->__set('tipo_data_fim', $tipo_data);
        $this->__set('data_fim', $fim);
    }


    //
    //    Define o formato do atributo do tipo data
    //
    public function set_formato_data($formato) {
    // String $formato: formato de data igual ao aceito pela funcao strftime
    //
        $this->__set('formato_data', $formato);
    }


    //
    //     Indica se um determinado valor e' considerado nulo para o tipo do atributo
    //
    public function is_null($valor) {
    // Mixed $valor: valor a ser testado
    //
        switch ($this->__get('tipo')) {
        case 'data':
            $data = objeto::parse_data($valor, false);
            return is_null($valor) ||
                   ($data['dia'] == 0 && $data['mes'] == 0 && $data['ano'] == 0 && $data['hora'] == 0 && $data['minuto'] == 0 && $data['segundo'] == 0);
        default:
            return is_null($valor);
        }
    }


    //
    //     Converte um valor recebido pelo usuario para a notacao do Simp
    //
    public function filtrar($valor) {
    // Mixed $valor: valor a ser filtrado
    //
        switch ($this->__get('tipo')) {
        case 'string':
            if (!ATRIBUTO_UTF8 && texto::is_utf8($valor)) {
                $valor = utf8_decode($valor);
            }
            return (string)$valor;
        case 'int':
            if (is_int($valor)) {
                return $valor;
            } elseif (is_float($valor)) {
                return round($valor);
            } elseif (is_string($valor)) {
                $conv = validacao::get_convencoes_localidade();
                $sinal = preg_quote($conv['positive_sign'].$conv['negative_sign']);
                $milhar = preg_quote($conv['thousands_sep']);
                $exp = '/^'.
                       '(['.$sinal.']?)'.
                       ($milhar === '' ? '(\d+)' : '(\d{1,3}(?:['.$milhar.']?\d{3})*)').
                       '$/';
                if (preg_match($exp, $valor, $matches)) {

                    // Converter sinal para padrao PHP
                    $tr = array();
                    if ($conv['positive_sign'] !== '') {
                        $tr[$conv['positive_sign']] = '+';
                    }
                    $tr[$conv['negative_sign']] = '-';
                    $sinal_valor = strtr($matches[1], $tr);

                    // Converter parte inteira para padrao do PHP
                    if ($conv['thousands_sep'] !== '') {
                        $tr = array(
                            $conv['thousands_sep'] => ''
                        );
                        $inteiro_valor = strtr($matches[2], $tr);
                    } else {
                        $inteiro_valor = $matches[2];
                    }

                    $simp_valor = $sinal_valor.$inteiro_valor;
                    $valor = (float)$simp_valor;
                    if ($valor > PHP_INT_MAX) {
                        return $valor;
                    }
                    return (int)$simp_valor;
                }
            } elseif (is_bool($valor)) {
                return $valor ? 1 : 0;
            }
            return null;

        case 'float':
            if (is_int($valor) || is_float($valor)) {
                if ($this->__get('casas_decimais')) {
                    $valor = round($valor, $this->__get('casas_decimais'));
                }
                return (float)$valor;
            } elseif (is_string($valor)) {
                $conv = validacao::get_convencoes_localidade();
                $sinal = preg_quote($conv['positive_sign'].$conv['negative_sign']);
                $milhar = preg_quote($conv['thousands_sep']);
                $decimal = preg_quote($conv['decimal_point']);
                $exp = '/^'.
                       '(['.$sinal.']?)'.                                          // Sinal
                       ($milhar ? '(\d{1,3}(?:['.$milhar.']?\d{3})*)' : '(\d+)').  // Inteiro
                       '(?:['.$decimal.'](\d+))?'.                                 // Decimal
                       '$/';
                if (preg_match($exp, $valor, $matches)) {

                    // Converter sinal para padrao PHP
                    $tr = array();
                    if ($conv['positive_sign'] !== '') {
                        $tr[$conv['positive_sign']] = '+';
                    }
                    $tr[$conv['negative_sign']] = '-';
                    $sinal_valor = strtr($matches[1], $tr);

                    // Converter parte inteira para padrao do PHP
                    if ($conv['thousands_sep'] !== '') {
                        $tr = array(
                            $conv['thousands_sep'] => ''
                        );
                        $inteiro_valor = strtr($matches[2], $tr);
                    } else {
                        $inteiro_valor = $matches[2];
                    }

                    // Converter parte decimal para padrao do PHP
                    $decimal_valor = isset($matches[3]) ? $matches[3] : '0';

                    $simp_valor = $sinal_valor.$inteiro_valor.'.'.$decimal_valor;

                    $valor = (float)$simp_valor;
                    if ($this->__get('casas_decimais')) {
                        $valor = round($valor, $this->__get('casas_decimais'));
                    }
                    return $valor;
                }
            } elseif (is_bool($valor)) {
                return $valor ? 1.0 : 0.0;
            }
            return null;
        case 'char':
            if (!ATRIBUTO_UTF8 && texto::is_utf8($valor)) {
                $valor = utf8_decode($valor);
            }
            $s = (string)$valor;
            return substr($s, 0, 1);
        case 'bool':
            return (bool)$valor;
        case 'binario':
            return (string)$valor;
        case 'data':
            $data = objeto::parse_data($valor);
            return sprintf('%02d-%02d-%04d-%02d-%02d-%02d',
                           $data['dia'], $data['mes'], $data['ano'],
                           $data['hora'], $data['minuto'], $data['segundo']);
        }
        return null;
    }


    //
    //     Retorna a forma como um valor deve ser exibido caso tenha o tipo do atributo corrente
    //
    public function exibir($valor) {
    // Mixed $valor: valor a ser exibido
    //
        switch ($this->__get('tipo')) {
        case 'string':
            if ($this->__get('pode_vazio') && $valor === '') {
                $valor = 'N&atilde;o definido';
            }
            break;
        case 'char':
        case 'binario':
            break;
        case 'int':
            $valor = texto::numero($valor, 0);
            break;
        case 'float':
        case 'double':

            // Exibe no formato de moeda
            if ($this->__get('moeda')) {
                $valor = texto::money_format($valor);

            // Exibe um numero de casas decimais especificado
            } else {
                $valor = texto::numero($valor, $this->__get('casas_decimais'), $this->__get('fixo'));
            }
            break;
        case 'bool':
            $valor = $valor ? 'Sim' : 'N&atilde;o';
            break;
        case 'data':
            $data = objeto::parse_data($valor);

            // Se informou um formato
            if ($this->__get('formato_data')) {
                switch ($this->__get('campo_formulario')) {
                case 'data':
                    if ((int)$data['ano'] == 0) {
                        return 'Nenhuma';
                    }
                    break;
                case 'data_hora':
                default:
                    if ((int)$data['ano'] + (int)$data['hora'] + (int)$data['minuto'] + (int)$data['segundo'] == 0) {
                        return 'Nenhuma';
                    }
                    break;
                }

                $time = mktime($data['hora'], $data['minuto'], $data['segundo'], $data['mes'], $data['dia'], $data['ano']);

                // Se o timestamp eh valido
                if ((int)strftime('%Y') == (int)$data['ano']) {
                    $valor = strftime($this->__get('formato_data'), $time);

                // Se o timestamp eh muito grande
                } else {
                    $datetime = DateTime::createFromFormat('d-m-Y-H-i-s', $valor);
                    if (!$datetime) {
                        trigger_error('Erro ao formatar data: '.$valor, E_USER_WARNING);
                        return $data['dia'].'/'.$data['mes'].'/'.$data['ano'].' - '.$data['hora'].':'.$data['minuto'].':'.$data['segundo'];
                    }
                    $valor = self::formatar_datetime($this->__get('formato_data'), $datetime);
                }

            // Formato padrao
            } else {
                $tr = array(
                    '%d' => sprintf('%02d', $data['dia']),
                    '%m' => sprintf('%02d', $data['mes']),
                    '%Y' => sprintf('%04d', $data['ano']),
                    '%H' => sprintf('%02d', $data['hora']),
                    '%M' => sprintf('%02d', $data['minuto']),
                    '%S' => sprintf('%02d', $data['segundo'])
                );
                switch ($this->__get('campo_formulario')) {
                case 'data':
                    if ((int)$data['ano'] == 0) {
                        return 'Nenhuma';
                    }
                    $str = ATRIBUTO_FORMATO_DATA;
                    break;
                case 'hora':
                    $str = ATRIBUTO_FORMATO_HORA;
                    break;
                case 'data_hora':
                default:
                    if ((int)$data['ano'] + (int)$data['hora'] + (int)$data['minuto'] + (int)$data['segundo'] == 0) {
                        return 'Nenhuma';
                    }
                    $str = ATRIBUTO_FORMATO_DATA_HORA;
                    break;
                }
                $valor = strtr($str, $tr);
            }
            break;
        }
        return texto::codificar($valor);
    }


    //
    //     Formata um objeto DateTime com um formato aceito por strftime
    //
    private static function formatar_datetime($formato, $datetime) {
    // String $formato: formato aceito por strftime
    // DateTime $datetime: datetime
    //
        // formato de strftime / formato de date
        $conv = array(
            '%d' => 'd',
            '%a' => 'D',
            '%e' => 'j',
            '%A' => 'l',
            '%u' => 'N',
            '%w' => 'w',
            '%j' => 'z',
            '%V' => 'W',
            '%B' => 'F',
            '%m' => 'm',
            '%b' => 'M',
            '%h' => 'M',
            '%G' => 'o',
            '%Y' => 'Y',
            '%y' => 'y',
            '%P' => 'a',
            '%p' => 'A',
            '%l' => 'g',
            '%I' => 'h',
            '%H' => 'H',
            '%M' => 'i',
            '%S' => 's',
            '%z' => 'O',
            '%Z' => 'T',
            '%s' => 'U'
        );

        $semanas = listas::get_semanas(false);
        $semanas_abreviado = listas::get_semanas(true);
        $meses = listas::get_meses(false, false);
        $meses_abreviado = listas::get_meses(false, true);

        preg_match_all('/(%[^%]|[^%]+|%%)/', $formato, $matches);
        $valor = '';
        foreach ($matches[1] as $match) {
            if ($match == '%%') {
                $valor .= '%';
            } elseif (substr($match, 0, 1) == '%') {

                // Tratar manualmente os codigos que sofrem interferencia de setlocale
                switch ($match) {

                // Semana abreviado
                case '%a':
                    $valor .= $semanas_abreviado[$datetime->format('w')];
                    break;

                // Semana
                case '%A':
                    $valor .= $semanas[$datetime->format('w')];
                    break;

                // Mes abreviado
                case '%h':
                case '%b':
                    $valor .= $meses_abreviado[(int)$datetime->format('n')];
                    break;

                // Mes
                case '%B':
                    $valor .= $meses[(int)$datetime->format('n')];
                    break;

                default:
                    $valor .= $datetime->format($conv[$match]);
                    break;
                }
            } else {
                $valor .= $match;
            }
        }
        return $valor;
    }

}//class
