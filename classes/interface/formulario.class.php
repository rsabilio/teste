<?php
//
// SIMP
// Descricao: Classe para geracao de formularios HTML
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.1.3.13
// Data: 06/08/2007
// Modificado: 14/06/2011
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
global $CFG;

// Constantes
define('FORMULARIO_AJAX',          $CFG->ajax && $CFG->pessoal->ajax && $CFG->agent->javascript);
define('FORMULARIO_TEXTBROWSER',   $CFG->agent->texto);
define('FORMULARIO_CONTENT',       $CFG->content);
define('FORMULARIO_WWWROOT',       $CFG->wwwroot);
define('FORMULARIO_LOCALIDADE',    $CFG->localidade);
define('FORMULARIO_UTF8',          $CFG->utf8);
define('FORMULARIO_LIMITE_UPLOAD', 2097152);

class formulario {
    protected $action;   // String endereco de destino dos dados
    protected $metodo;   // String metodo de envio (post ou get)
    protected $onsubmit; // String metodo chamado ao submeter os dados
    protected $id;       // String ID de identificacao do formulario
    protected $class;    // String classe de estilo CSS
    protected $enctype;  // String Enctype utilizado (uso interno)
    protected $ajax;     // Bool Utilizar ajax ou nao
    protected $inline;   // Bool Indica se o formulario e' de uma unica linha
    protected $ids;      // Array[String] Vetor de ids dos campos
    protected $campos;   // Array[String] Vetor com os campos adicionados
    protected $erros;    // Array[String] Vetor de erros
    protected $vt_nome;  // Array[String] Vetor com os nomes utilizados para gerar o nome do campo no formulario


    //
    //     Construtor padrao
    //
    public function __construct($action, $id, $class = 'formulario', $metodo = 'post', $ajax = true, $inline = false) {
    // String $action: destino dos dados
    // String $id: id do formulario
    // String $class: estilo utilizado
    // String $metodo: metodo de envio
    // Bool $ajax: usar ajax no formulario ou nao
    // Bool $inline: indica se o formulario e' de uma unica linha
    //
        // Atributos modificaveis
        $this->action = $action;
        $this->id     = str_replace("'", '', $id);
        $this->class  = ($class !== false)  ? $class  : 'formulario';
        $this->metodo = $metodo ? $metodo : 'post';
        $this->inline = (bool)$inline;

        // Atributos internos
        $this->enctype = 'application/x-www-form-urlencoded';
        $this->ajax = FORMULARIO_AJAX && $ajax;
        $this->ids = array($this->id);
        $this->campos = array();
    }


    //
    //     Obtem os dados submetidos e filtra as strings desejadas
    //
    public static function get_dados($trim = true) {
    // Bool $trim: aplica trim
    //
        static $dados = array();
        $pos = intval($trim);
        if (!array_key_exists($pos, $dados)) {
            $dados[$pos] = empty($_POST) ? null : self::get_dados_recursivo($_POST, $trim);
        }
        return $dados[$pos];
    }


    //
    //     Recursao do metodo get_dados
    //
    private static function get_dados_recursivo($dados, $trim = true) {
    // Mixed $dados: dados da recursao
    // Bool $trim: aplica trim
    //
        $retorno = null;

        // Se e' um vetor
        if (is_array($dados)) {
            foreach ($dados as $chave => $valor) {
                if (is_numeric($chave)) {
                    if (is_null($retorno)) {
                        $retorno = array();
                    }
                    $retorno[$chave] = self::get_dados_recursivo($valor, $trim);
                } else {
                    if (is_null($retorno)) {
                        $retorno = new stdClass();
                    }
                    $retorno->$chave = self::get_dados_recursivo($valor, $trim);
                }
            }

        // Se nao e' um vetor
        } elseif (is_scalar($dados)) {

            //TODO: nao checar magic_quotes_gpc quando o Simp exigir PHP 5.3
            $quotes = ini_get('magic_quotes_gpc');
            if (!FORMULARIO_UTF8 && texto::is_utf8($dados)) {
                $dados = utf8_decode($dados);
            }
            if ($quotes) {
                $dados = stripslashes($dados);
            }
            $retorno = $trim ? trim($dados) : $dados;
        }
        return $retorno;
    }


    //
    //     Obtem os arquivos submetidos e retorna um objeto de objetos
    //
    static public function get_arquivos() {
        if (isset($_FILES) && !empty($_FILES)) {
            return self::get_dados_recursivo($_FILES);
        }
        return null;
    }


    //
    //     Monta um vetor com os dados submetidos ou dados padrao
    //
    static public function montar_dados($padrao, $submetidos) {
    // Array[String => Mixed] $padrao: campos e valores padrao
    // Object $submetidos: valores submetidos
    //
        return util::montar_objeto($padrao, $submetidos);
    }


    //
    //     Monta o icone de ajuda de um campo
    //
    static protected function montar_ajuda($ajuda) {
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        $icone_ajuda = icone::img('info', 'Ajuda');
        $icone = '';
        if (is_string($ajuda)) {
            if (!empty($ajuda)) {
                $icone = '<a href="javascript: void(0);" class="ajuda">'.$icone_ajuda.'<span>'.texto::codificar($ajuda).'</span></a>';
            } else {
                trigger_error('A ajuda esta vazia', E_USER_WARNING);
            }
        } elseif (is_array($ajuda)) {
            if (isset($ajuda['link'])) {
                if (isset($ajuda['texto'])) {
                    $texto = '<span>'.$ajuda['texto'].'</span>';
                } else {
                    $texto = '';
                }
                $icone = '<span class="ajuda"><a rel="blank" href="'.$ajuda['link'].'">'.$icone_ajuda.'</a>'.$texto.'</span>';
            } else {
                trigger_error('O vetor de ajuda precisa ter uma posicao "link"', E_USER_WARNING);
            }
        }
        return $icone;
    }


    //
    //     Define o valor dos nomes a serem usados nos campos do formulario que forem chamados apos a chamada deste metodo
    //
    public function set_nome($vt_nome) {
    // String $vt_nome: vetor com os nomes da estrutura que montara' o nome
    //
        $this->vt_nome = (array)$vt_nome;
    }


    //
    //     Obtem o valor dos nomes a serem usados nos campos do formulario
    //
    public function get_nome() {
        return $this->vt_nome;
    }


    //
    //     Monta o nome do campo baseado no vetor de nome
    //
    public function montar_nome($campo) {
    // String $campo: nome do campo
    //
        if (is_numeric($campo)) {
            trigger_error('Nao utilize name numerico em formularios', E_USER_ERROR);
            return false;
        }

        if (empty($this->vt_nome)) {
            return $campo;
        }

        // Checar se o nome ja' e' um vetor
        $pos = strpos($campo, '[');
        $vetor = '';
        if ($pos !== false) {
            $vetor = substr($campo, $pos);
            $campo = substr($campo, 0, $pos);
        }

        $nome_campo = '';
        $vt_nome = $this->vt_nome;
        $nome_campo .= array_shift($vt_nome);
        foreach ($vt_nome as $nome) {
            $nome_campo .= '['.$nome.']';
        }
        $nome_campo .= '['.$campo.']';
        return $nome_campo.$vetor;
    }


    //
    //     Monta o ID do campo baseado no vetor de nome
    //
    public function montar_id($campo) {
    // String $campo: nome do campo
    //
        if (empty($this->vt_nome)) {
            return $campo;
        }
        return implode('-', $this->vt_nome).'-'.$campo;
    }


    //
    //     Define o metodo usado no evento onsubmit do formulario
    //
    public function set_onsubmit($onsubmit) {
    // String $onsubmit: codigo JavaScript a ser executado no momento da submissao do formulario
    //
        self::formatar_aspas_script($onsubmit);
        $this->onsubmit = $onsubmit;
    }


    //
    //     Converte as aspas de um codigo JavaScript para ser inserido entre aspas duplas
    //
    static public function formatar_aspas_script(&$script) {
    // String $script: codigo a ser formatado
    //
        // Se tem aspas duplas
        if (strpos($script, '"') !== false) {

            // Se tem aspas simples tambem
            if (strpos($script, "'") !== false) {
                $script = str_replace("'", "\'", $script);
            }

            $script = str_replace('"', "'", $script);
        }
    }


    //
    //     Retorna o cabecalho de um formulario
    //
    public function abrir_formulario($exibir_carregar = true) {
    // Bool $exibir_carregar: exibe o "carregando..."
    //
        // Gerar campo
        $class    = $this->class ? " class=\"{$this->class}\"" : '';
        $id       = $this->id ? " id=\"{$this->id}\"" : '';
        $method   = " method=\"{$this->metodo}\"";
        $action   = " action=\"{$this->action}\"";
        $enctype  = " enctype=\"{$this->enctype}\"";
        $carregar = $exibir_carregar ? 1 : 0;

        if (!$this->onsubmit) {
            $onsubmit = $this->ajax ? ' onsubmit="return submeter(this, '.$carregar.');"' : '';
        } else {
            $onsubmit = " onsubmit=\"{$this->onsubmit}\"";
        }

        // Retornar
        return "<form{$action}{$class}{$id}{$method}{$enctype}{$onsubmit}>";
    }


    //
    //     Encerra um formulario
    //
    public function fechar_formulario() {
        return '<p class="fim">&nbsp;</p>'.
               "</form>\n";
    }


    //
    //     Define o titulo do formulario
    //
    public function titulo_formulario($titulo, $return = false) {
    // String $titulo: imprime o titulo do formulario
    // Bool $return: retornar ou adicionar ao formulario
    //
        $r = "<h3>{$titulo}</h3>";
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Inicia um bloco de campos
    //
    public function inicio_bloco($titulo, $return = false, $class = '', $id = false) {
    // String $titulo: titulo do bloco
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String $id: ID do fieldset
    //
        $class = $class ? " class=\"{$class}\"" : '';
        if ($id) {
            $id = $this->montar_id($id);
            $id = " id=\"{$id}\"";
        } else {
            $id = '';
        }
        $r = "<fieldset{$class}{$id}>".
             "<legend>{$titulo}</legend>";
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Encerra um bloco de campos
    //
    public function fim_bloco($return = false) {
    // Bool $return: retornar ou adicionar ao formulario
    //
        $r = '</fieldset>';
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo textarea
    //
    public function campo_textarea($name, $id, $valor = '', $cols = '30', $rows = '5', $label = '', $disable = false, $return = false, $class = '', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // String $valor: valor preenchido automaticamente
    // Int $cols: numero de colunas
    // Int $rows: numero de linhas
    // String $label: nome do label
    // Bool $disable: campo deseabilitado ou nao
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        // Gerar campo
        $cols = (int)$cols ? (int)$cols : 30;
        $rows = (int)$rows ? (int)$rows : 5;

        $disabled = $disable ? ' disabled="disabled"' : '';
        $class = $class ? " class=\"{$class}\"" : '';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        util::get_cdata($cdata, $fcdata, false);

        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }
        $r .= "<textarea name=\"{$name_completo}\" id=\"{$id_completo}\" cols=\"{$cols}\" rows=\"{$rows}\"{$disabled}{$class}>".
              $cdata.$valor.$fcdata.
              "</textarea>";
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo select
    //
    public function campo_select($name, $id, $vetor, $select, $label = '', $disable = false, $return = false, $class = '', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // Array[Mixed => String] || Array[Mixed => Array[Mixed => String]] $vetor: vetor ou vetor de vetores com os dados do select
    // Mixed $select: chave que sera selecionada automaticamente
    // String $label: nome do label
    // Bool $disable: campo desabilitado ou nao
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        // Gerar Campo
        $disabled = $disable ? ' disabled="disabled"' : '';
        $class = $class ? " class=\"{$class}\"" : '';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }
        if (is_array($vetor) && !empty($vetor)) {
            $r .= "<select id=\"{$id_completo}\" name=\"{$name_completo}\"{$disabled}{$class}>";
            foreach ($vetor as $cod => $valor) {

                // Select com grupos
                if (is_array($valor)) {
                    $r .= "<optgroup label=\"".texto::codificar($cod)."\">";
                    if (!empty($valor)) {
                        foreach ($valor as $cod2 => $valor2) {
                            $selected = $this->comparar_valor_select($cod2, $select) ? ' selected="selected"' : '';
                            $cod2 = self::filtrar_numero_php($cod2);
                            $valor2 = texto::codificar($valor2);
                            $r .= "<option value=\"{$cod2}\"{$selected}>{$valor2}</option>";
                        }
                    } else {
                        $r .= '<option disabled="disabled">[nenhum item]</option>';
                    }
                    $r .= '</optgroup>';

                // Select sem grupos
                } else {
                    $selected = $this->comparar_valor_select($cod, $select) ? ' selected="selected"' : '';
                    $cod = self::filtrar_numero_php($cod);
                    $valor = texto::codificar($valor);
                    $r .= "<option value=\"{$cod}\"{$selected}>{$valor}</option>";
                }
            }
            $r .= '</select>';

        // Nenhum item disponivel para selecao
        } else {
            if ($label) {
                $r .= $this->campo_aviso('Nenhum(a) '.$label.' dispon&iacute;vel para sele&ccedil;&atilde;o', true);
            } else {
                $r .= $this->campo_aviso('Nenhum &iacute;tem dispon&iacute;vel para sele&ccedil;&atilde;o', true);
            }
        }
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Compara dois valores para determinar se o elemento do select esta selecionado
    //
    private function comparar_valor_select($valor_submetido, $valor_option) {
    // Mixed $valor_submetido: Valor submetido
    // Mixed $valor_option: Valor do campo option
    //
        if ($valor_submetido === false) {
            return false;
        }

        if (is_int($valor_submetido)) {
            $valor_submetido = texto::numero($valor_submetido, 0);
        } elseif (is_float($valor_submetido)) {
            $valor_submetido = texto::numero($valor_submetido, false);
        }

        if (is_int($valor_option)) {
            $valor_option = texto::numero($valor_option, 0);
        } elseif (is_float($valor_option)) {
            $valor_option = texto::numero($valor_option, false);
        }
        return strcmp($valor_submetido, $valor_option) == 0;
    }


    //
    //     Campo listbox (select multiplo)
    //
    public function campo_listbox($name, $id, $vetor, $selects, $label = '', $numero_itens_visiveis = 10, $disable = false, $return = false, $class = 'dupla', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // Array[Mixed => String] || Array[Mixed => Array[Mixed => String]] $vetor: vetor ou vetor de vetores com os dados do select
    // Array[Mixed => String] || Mixed $selects: chaves ou chave que sera(ao) selecionada(s) automaticamente
    // String $label: nome do label
    // Int $numero_itens_visiveis: numero de itens que ficarao visiveis no listbox. O restante dos itens serao acessiveis rolando a barra de rolagem (Padrao: 10)
    // Bool || Array[Mixed] $disable: true desabilita toda lista, false nao desabilita nada, e um vetor indica as opcoes especificas a serem desabilitadas
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS (padrao "dupla" indica que o JavaScript pode reestruturar a lista para ficar dupla e os elementos trafegam de uma lista para outra)
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        // Gerar Campo
        $disabled      = $disable === true ? ' disabled="disabled"' : '';
        $class         = $class ? " class=\"{$class}\"" : '';
        $name_completo = $this->montar_nome($name);
        $id_completo   = $this->montar_id($id);
        $selects       = is_array($selects) ? $selects : array($selects); // se as chaves informadas nao forem um vetor, criar um vetor com a chave informada

        // Campos desabilitados, mas marcados
        $hidden = '';

        $r = '';
        $r .= $this->campo_aviso('Segure a tecla "Ctrl" enquanto seleciona um item para conseguir marcar mais de um elemento.');
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }
        if (is_array($vetor) && !empty($vetor)) {
            $r .= "<select id=\"{$id_completo}\" name=\"{$name_completo}[]\" multiple=\"multiple\" size=\"{$numero_itens_visiveis}\" {$disabled}{$class}>";
            foreach ($vetor as $cod => $valor) {

                // Select com grupos
                if (is_array($valor)) {
                    $r .= "<optgroup label=\"".texto::codificar($cod)."\" title=\"".texto::codificar($cod)."\">";
                    if (!empty($valor)) {
                        foreach ($valor as $cod2 => $valor2) {

                            $achou = false;
                            foreach ($selects as $cod_option) {
                                if ($this->comparar_valor_select($cod2, $cod_option)) {
                                    $achou = true;
                                    break;
                                }
                            }

                            $selected = $achou ? ' selected="selected"' : '';
                            $disabled = is_array($disable) && in_array($cod2, $disable);
                            $cod2 = self::filtrar_numero_php($cod2);
                            $valor2 = texto::codificar($valor2);

                            if ($disabled) {
                                $hidden .= $this->campo_hidden($name_completo.'[]', $cod2, true);
                                $r .= "<option value=\"{$cod2}\" title=\"{$valor2}\"{$selected} disabled=\"disabled\">{$valor2}".($selected ? ' (selecionado)' : '')."</option>";
                            } else {
                                $r .= "<option value=\"{$cod2}\" title=\"{$valor2}\"{$selected}>{$valor2}</option>";
                            }
                        }
                    } else {
                        $r .= '<option disabled="disabled">[nenhum item]</option>';
                    }
                    $r .= '</optgroup>';

                // Select sem grupos
                } else {
                    $achou = false;
                    foreach ($selects as $cod_option) {
                        if ($this->comparar_valor_select($cod, $cod_option)) {
                            $achou = true;
                            break;
                        }
                    }

                    $selected = $achou ? ' selected="selected"' : '';
                    $disabled = is_array($disable) && in_array($cod, $disable);
                    $cod = self::filtrar_numero_php($cod);
                    $valor = texto::codificar($valor);

                    if ($disabled) {
                        if ($selected) {
                            $hidden .= $this->campo_hidden($name_completo.'[]', $cod, true);
                        }
                        $r .= "<option value=\"{$cod}\" title=\"{$valor}\"{$selected} disabled=\"disabled\">{$valor}".($selected ? ' (selecionado)' : '')."</option>";
                    } else {
                        $r .= "<option value=\"{$cod}\" title=\"{$valor}\"{$selected}>{$valor}</option>";
                    }
                }
            }
            $r .= '</select>';

        // Nenhum item disponivel para selecao
        } else {
            if ($label) {
                $r .= $this->campo_aviso('Nenhum(a) '.$label.' dispon&iacute;vel para sele&ccedil;&atilde;o', true);
            } else {
                $r .= $this->campo_aviso('Nenhum &iacute;tem dispon&iacute;vel para sele&ccedil;&atilde;o', true);
            }
        }
        $r .= $hidden;
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo radiobox
    //
    public function campo_radio($name, $id, $vetor, $check = '', $label = '', $disable = array(), $return = false, $class = '', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id das opcoes
    // Array[Mixed => String] $vetor: vetor com os codigos e valores possiveis
    // String $check: valor selecionado
    // String $label: nome do label
    // Array[Mixed] $disable: valores desativados
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        if (!is_array($vetor)) {
            $this->erros[] = 'Atributo "$vetor" n&atilde;o &eacute; do tipo vetor (tipo informado: '.util::get_tipo($vetor).')';
            return false;
        }

        $class = $class ? " class=\"{$class}\"" : '';
        $name_completo = $this->montar_nome($name);

        // Gerar campo
        $r = '';
        if ($label) {
            $icone_ajuda = self::montar_ajuda($ajuda);
            $r .= '<fieldset class="escolha">';
            $r .= "<legend>{$label}{$icone_ajuda}</legend>";
        }
        $i = 0;
        foreach ($vetor as $cod => $valor) {
            $disabled = '';
            if ($disable && in_array($cod, $disable)) {
                $disabled = ' disabled="disabled"';
            }
            $checked = (strcmp($check, $cod) == 0) ? ' checked="checked"' : '';

            $cod = self::filtrar_numero_php($cod);
            $valor = texto::codificar($valor);
            $id_radio = $this->montar_id('radio_'.$id.'_'.$i);
            $input = "<input type=\"radio\" name=\"{$name_completo}\" id=\"{$id_radio}\" value=\"{$cod}\"{$checked}{$disabled}{$class} />";
            $r .= '<label for="'.$id_radio.'"'.($label ? ' class="escolha"' : '').'>'.$input.' '.$valor.'</label>';
            $i++;
        }
        if ($label) {
            $r .= '</fieldset>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo booleano de confirmacao
    //
    public function campo_bool($name, $id, $label = '', $valor = 0, $disable = false, $return = false, $class = '', $ajuda = '') {
    // String $name: nome do campo
    // String $id: id das opcoes
    // String $label: nome do label
    // Bool $valor: Marcar como true ou false
    // Bool $disable: campo desativado ou nao
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String $ajuda: texto da ajuda do campo
    //
        $valor = $valor ? 1 : 0;

        // Gerar campo
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo_bool\">";
            $r .= "<label for=\"{$id_completo}\">";
        }

        if ($disable) {
            $hidden = $this->campo_hidden($name, $valor);
            $disabled = ' disabled="disabled"';
        } else {
            $hidden = $this->campo_hidden($name, 0);
            $disabled = '';
        }
        $class = $class ? " class=\"{$class}\"" : '';

        if ($valor) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }

        $r .= "{$hidden}<input type=\"checkbox\" name=\"{$name_completo}\" id=\"{$id_completo}\" value=\"1\"{$checked}{$disabled}{$class} />";

        if ($label) {
            if ($ajuda) {
                $texto_ajuda = ' ('.$ajuda.')';
            } else {
                $texto_ajuda = '';
            }
            $r .= " {$label}{$texto_ajuda}</label>".
                  '</div>';
        }

        if ($return) {
            return $r;
        }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo booleano tradicional
    //
    public function campo_bool_radio($name, $id, $label = '', $valor = 0, $disable = false, $return = false, $class = '', $ajuda = '') {
    // String $name: nome do campo
    // String $id: id das opcoes
    // String $label: nome do label
    // Bool $valor: Marcar como true ou false
    // Bool $disable: campo desativado ou nao
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String $ajuda: texto da ajuda do campo
    //
        $valor = $valor ? 1 : 0;

        // Gerar campo
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label(false, $label, true, '', $ajuda);
            $r .= '<div class="sim_nao">';
        }

        if ($disable) {
            $disabled = ' disabled="disabled"';
        } else {
            $disabled = '';
        }
        $class = $class ? " class=\"{$class}\"" : '';

        if ($valor) {
            $checked_sim = ' checked="checked"';
            $checked_nao = '';
        } else {
            $checked_sim = '';
            $checked_nao = ' checked="checked"';
        }
        $r .= '<label class="escolha"><input type="radio" id="'.$id_completo.'_sim" name="'.$name_completo.'" value="1"'.$disabled.$checked_sim.' /> Sim</label>';
        $r .= '<label class="escolha"><input type="radio" id="'.$id_completo.'_nao" name="'.$name_completo.'" value="0"'.$disabled.$checked_nao.' /> N&atilde;o</label>';

        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        if ($return) {
            return $r;
        }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo checkbox
    //
    public function campo_checkbox($name, $id, $vetor, $check = array(), $label = '', $colunas = 1, $disable = array(), $marcador = false, $return = false, $class = '', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id das opcoes
    // Array[Mixed => String] $vetor: vetor com os codigos e valores possiveis
    // Array[Mixed] $check: valores selecionados
    // String $label: nome do label
    // Int $colunas: numero de colunas apresentadas
    // Array[Mixed] $disable: vetor de codigos desativados
    // Bool $marcador: inclui um botao de marcar/desmarcar todos (usando javascript)
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        if (!is_array($vetor)) {
            $this->erros[] = 'Atributo "$vetor" n&atilde;o &eacute; do tipo vetor (tipo informado: '.util::get_tipo($vetor).')';
            return false;
        }
        if (!is_array($check)) {
            $this->erros[] = 'Atributo "$check" n&atilde;o &eacute; do tipo vetor (tipo informado: '.util::get_tipo($check).')';
            return false;
        }
        $class = $class ? " class=\"{$class}\"" : '';
        $name_completo = $this->montar_nome($name);
        $id_fieldset = 'fieldset_'.$id;

        // Definir a quantidade de itens por coluna
        if ($colunas) {
            $quantidade = ceil(count($vetor) / $colunas);
        } else {
            $quantidade = count($vetor);
        }

        $chaves  = array_keys($vetor);
        $valores = array_values($vetor);

        // Gerar Campo
        $r = '';
        if ($label) {
            $class_fieldset = 'escolha';
            if ($marcador) {
                $class_fieldset .= ' com_marcador';
            }
            $icone_ajuda = self::montar_ajuda($ajuda);
            $r .= "<fieldset class=\"{$class_fieldset}\" id=\"{$id_fieldset}\">";
            $r .= "<legend>{$label}{$icone_ajuda}</legend>";
        }

        $i = 0;
        $encerrar = false;
        $r .= '<div class="colunas">';
        for ($coluna = 0; $coluna < $colunas && !$encerrar; $coluna++) {
            $r .= '<div class="coluna">';
            if ($quantidade) {
                for ($j = 0; $j < $quantidade && !$encerrar; $j++, $i++) {
                    if (!isset($chaves[$i])) {
                        $encerrar = true;
                        break;
                    }
                    $cod   = self::filtrar_numero_php($chaves[$i]);
                    $valor = texto::codificar($valores[$i]);
                    $disabled = '';
                    if ($disable && in_array($cod, $disable)) {
                        $disabled = ' disabled="disabled"';
                    }
                    $checked = '';
                    if ($check && in_array($cod, $check)) {
                        $checked = ' checked="checked"';
                    }

                    $id_checkbox = $this->montar_id('checkbox_'.$id.'_'.$i);
                    $input = "<input type=\"checkbox\" name=\"{$name_completo}[]\" id=\"{$id_checkbox}\" value=\"{$cod}\"{$checked}{$disabled}{$class} />";
                    if ($disabled && $checked) {
                        $input .= $this->campo_hidden("{$name}[]", $cod, 1);
                    }
                    $r .= '<label for="'.$id_checkbox.'"'.($label ? ' class="escolha"' : '').'>'.$input.' '.$valor.'</label>';
                }
            } else {
                $r .= "Nenhum item para sele&ccedil;&atilde;o";
            }
            $r .= '</div>';
        }
        $r .= '<br class="clear" />'.
              '</div>';

        if ($label) {
            $r .= '</fieldset>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo hidden
    //
    public function campo_hidden($name, $valor, $return = false) {
    // String $name: nome do campo
    // String $valor: valor do campo
    // Bool $return: retornar ou adicionar ao formulario
    //
        $valor = self::filtrar_numero_php($valor);
        $name_completo = $this->montar_nome($name);

        if ($return) {
            return "<input type=\"hidden\" name=\"{$name_completo}\" value=\"{$valor}\" />";
        }
        $r = '<p>'.
             "<input type=\"hidden\" name=\"{$name_completo}\" value=\"{$valor}\" />".
             '</p>';
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo text
    //
    public function campo_text($name, $id, $valor, $maxlength = 128, $size = 30, $label = '', $disable = false, $return = false, $class = '', $mascara = false, $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // String $valor: valor do campo
    // Int $maxlength: capacidade do campo
    // Int $size: largura do campo
    // String $label: nome do label
    // Bool $disable: indica se o campo esta desativado
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String $mascara: mascara JavaScript para entrada de dados (digitos, letras, moeda, int, uint, float, ufloat)
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        // Class
        $class = $class ? " class=\"{$class}\"" : ' class="texto"';
        $disabled = $disable ? ' disabled="disabled"' : '';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        // Gerar campo
        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }

        // Mascara
        if ($mascara) {
            $local = FORMULARIO_LOCALIDADE;
            $eventos = " onkeydown=\"this.valor_antigo = this.value;\"".
                       " onkeyup=\"return mascara(event, this, '{$mascara}', 0, '{$local}');\"".
                       " onblur=\"return mascara(event, this, '{$mascara}', 1, '{$local}');\"";
        } else {
            $eventos = '';
        }

        // Valor
        $valor = self::filtrar_valor_mascara($valor, $mascara);
        $valor = texto::codificar($valor);

        $r .= "<input type=\"text\" name=\"{$name_completo}\" id=\"{$id_completo}\" value=\"{$valor}\" ".
              "size=\"{$size}\" maxlength=\"{$maxlength}\"{$class}{$disabled}{$eventos} />";
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo password
    //
    public function campo_password($name, $id, $maxlength = 128, $size = 30, $label = '', $disable = false, $return = false, $class = '', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // Int $maxlength: capacidade do campo
    // Int $size: largura do campo
    // String $label: nome do label
    // Bool $disable: indica se o campo esta desativado
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        // Class
        $class = $class ? " class=\"{$class}\"" : ' class="texto"';
        $disabled = $disable ? ' disabled="disabled"' : '';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        // Gerar campo
        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }

        $r .= "<input type=\"password\" name=\"{$name_completo}\" id=\"{$id_completo}\" value=\"\" ".
              "size=\"{$size}\" maxlength=\"{$maxlength}\"{$class}{$disabled} />";
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo file (muda o metodo para post e o enctype para multipart/form-data)
    //
    public function campo_file($name, $id, $label = '', $limite = FORMULARIO_LIMITE_UPLOAD, $disable = false, $return = false, $class = '', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // String $label: nome do label
    // Int $limite: numero maximo em bytes para serem enviados
    // Bool $disable: indica se o campo esta desativado
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        if (!ini_get('file_uploads')) {
            return $this->campo_aviso('Este sistema n&atilde;o est&aacute; configurado para aceitar o envio de arquivos.', $return);
        }

        // Forcar o method e enctype
        $this->metodo  = 'post';
        $this->enctype = 'multipart/form-data';
        $this->ajax    = false;

        if (is_numeric($limite)) {
            $max_upload = ini_get('upload_max_filesize');
            $max_upload = memoria::desformatar_bytes_php($max_upload);
            $limite = min($limite, $max_upload);
        } else {
            $limite = FORMULARIO_LIMITE_UPLOAD;
        }

        $class = $class ? " class=\"{$class}\"" : ' class="file"';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        // Gerar campo
        $r = '';

        $r .= $this->campo_aviso('Tamanho m&aacute;ximo permitido: '.memoria::formatar_bytes($limite, true).' ('.$limite.' bytes)', true);

        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }

        $disabled = $disable ? ' disabled="disabled"' : '';
        $r .= $this->campo_hidden('MAX_FILE_SIZE', $limite, 1);
        $r .= "<input type=\"file\" name=\"{$name_completo}\" id=\"{$id_completo}\"{$class}{$disabled} />";
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo image
    //
    public function campo_image($name, $id, $src, $label = true, $return = false, $class = false, $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // Int $src: endereco da imagem
    // Bool $label: descricao do campo
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        // Class
        $class = $class ? " class=\"{$class}\"" : '';
        $size = imagem::tamanho_html($src);
        $id_completo = $this->montar_id($id);

        // Gerar campo
        $i = '';
        if ($label) {
            $i .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $i .= $this->label($id, $label, true, '', $ajuda);
            $i .= '<div>';
        }

        $i .= "<input type=\"image\" name=\"{$name}\" id=\"{$id_completo}\" ismap=\"ismap\" src=\"{$src}\" {$size}{$class} />";
        if ($label) {
            $i .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $i; }
        $this->campos[] = $i;
    }


    //
    //     Imprime um campo submit
    //
    public function campo_submit($name, $id, $valor, $label = true, $reset = false, $disable = false, $return = false, $ajuda = false, $ajax = true) {
    // String $name: nome do botao
    // String $id: id do botao
    // String $valor: texto do botao
    // Bool $label: usar caixa (simula label)
    // Bool $reset: imprime um botao de reset ou nao
    // Bool $disable: indica se o campo esta desativado
    // Bool $return: retornar ou adicionar ao formulario
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    // Bool $ajax: usar ajax no botao
    //
        $label = $label && !$this->inline;
        $disabled = $disable ? ' disabled="disabled"' : '';
        $valor = texto::codificar($valor);
        $id_completo = $this->montar_id($id);

        // Gerar campos
        $r = '';
        if ($label) {
            $r .= '<div class="block">';
        }
        $class_ajax = $ajax ? '' : ' noajax';
        $r .= "<input type=\"submit\" name=\"{$name}\" id=\"{$id_completo}\" value=\"{$valor}\" title=\"{$valor}\" class=\"botao{$class_ajax}\"{$disabled} />";
        if ($reset) {
            $r .= " <input type=\"reset\" value=\"Restaurar\" class=\"botao\" title=\"Restaurar formul&aacute;rio para os valores padr&atilde;o\" />";
        }
        if ($label) {
            $icone_ajuda = self::montar_ajuda($ajuda);
            $r .= $icone_ajuda.'</div>';
        }

        // Armazenar Campos
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime uma lista de campos submit lado a lado
    //
    public function campos_submit($campos, $label = true, $return = false, $ajuda = false, $ajax = true) {
    // Array[String => Array[String => String]] $campos: vetor com informacoes sobre os campos (indexado pelo name do campo)
    // Bool $label: usar caixa (simula label)
    // Bool $return: retornar ou adicionar ao formulario
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    // Bool $ajax: usar ajax no botao
    //
        foreach ($campos as $name => $campo) {
            $id = isset($campo['id']) ? $campo['id'] : $name;
            $ajax_campo = isset($campo['ajax']) ? $campo['ajax'] : true;
            $disable = isset($campo['disable']) ? $campo['disable'] : false;
            $vetor[] = $this->campo_submit($name, $id, $campo['valor'], false, false, $disable, true, false, $ajax_campo);
        }

        $r = '';
        if ($label) {
            $r .= '<div class="block">';
        }
        $r .= implode(' ', $vetor);
        if ($label) {
            $icone_ajuda = self::montar_ajuda($ajuda);
            $r .= $icone_ajuda.'</div>';
        }
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo para duplicar um elemento do formulario
    //
    public function campo_clone($id_elemento, $nome = 'item', $limite = false, $return = false, $ajuda = false) {
    // String $id_elemento: identificador do elemento a ser duplicado
    // String $nome: nome do elemento duplicado
    // Int $limite: numero maximo de clones
    // Bool $return: retornar ou adicionar ao formulario
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        self::formatar_aspas_script($elemento);
        $r = '';
        if (FORMULARIO_AJAX) {
            $icone_ajuda = self::montar_ajuda($ajuda);

            $limite = is_numeric($limite) ? abs($limite) : 0;

            $info_clone = array(
                'id' => $id_elemento,
                'limite' => $limite,
                'nome' => texto::decodificar($nome)
            );
            $info_clone_json = json_encode($info_clone);

            $r = '<div class="block area_clones">'.
                 '<div class="container"></div>'.
                 '<meta name="info_clone" content="'.htmlspecialchars($info_clone_json).'" />'.
                 $icone_ajuda.
                 '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo de hora
    //
    public function campo_hora($prefixo, $hora, $minuto = false, $segundo = false, $label = '', $return = false, $ajuda = false) {
    // String $prefixo: prefixo usado no nome dos campos
    // Int $hora: valor da hora
    // Int $minuto: valor do minuto (false para omitir)
    // Int $segundo: valor do segundo (false para omitir)
    // String $label: nome do label
    // Bool $return: retornar ou adicionar no formulario
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        if ($prefixo) { $prefixo .= '_'; }
        $id_hora    = $prefixo.'hora';
        $id_minuto  = $prefixo.'minuto';
        $id_segundo = $prefixo.'segundo';

        $vt_horas    = listas::numeros(0, 23, '%02d');
        $vt_minutos  = listas::numeros(0, 59, '%02d');
        $vt_segundos = listas::numeros(0, 59, '%02d');

        $r = '';
        if ($label) {
            $r .= '<div class="campo">';
            $r .= $this->label($id_hora, $label, true, '', $ajuda);
            $r .= '<div class="data_hora">';
        }
        $r .= $this->campo_select($id_hora, $id_hora, $vt_horas,  $hora, 0, 0, 1, 'hora');
        if ($minuto !== false) {
            $r .= '<span>:</span>';
            $r .= $this->campo_select($id_minuto, $id_minuto, $vt_minutos, $minuto, 0, 0, 1, 'minuto');
            if ($segundo !== false) {
                $r .= '<span>:</span>';
                $r .= $this->campo_select($id_segundo, $id_segundo, $vt_segundos, $segundo, 0, 0, 1, 'segundo');
            }
        }

        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Adicionar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo de data
    //
    public function campo_data($prefixo, $dia, $mes, $ano, $label = '', $anos_passado = 5, $anos_futuro = 5, $nenhum = false, $return = false, $ajuda = false) {
    // String $prefixo: prefixo usado no nome dos campos
    // Int $dia: valor do dia
    // Int $mes: valor do mes
    // Int $ano: valor do ano
    // String $label: nome do label
    // Int $anos_passado: numero de anos no passado
    // Int $anos_futuro: numero de anos no futuro
    // Bool $nenhum: indica se deve colocar a opcao de nenhuma data
    // Bool $return: retornar ou adicionar no formulario
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        if ($prefixo) { $prefixo .= '_'; }
        $id_dia = $prefixo.'dia';
        $id_mes = $prefixo.'mes';
        $id_ano = $prefixo.'ano';

        $vt_dias  = listas::get_dias($nenhum);
        $vt_meses = listas::get_meses($nenhum);
        $vt_anos  = listas::get_anos($anos_passado, $anos_futuro, $nenhum);

        $r = '';
        if ($label) {
            $r .= '<div class="campo" id="'.$prefixo.'area_data">';
            $r .= $this->label($id_dia, $label, true, '', $ajuda);
            $r .= '<div class="data">';
        }

        $r .= $this->campo_select($id_dia, $id_dia, $vt_dias,  $dia, 0, 0, 1, 'dia');
        $r .= "<span>/</span>";
        $r .= $this->campo_select($id_mes, $id_mes, $vt_meses, $mes, 0, 0, 1, 'mes');
        $r .= "<span>/</span>";
        if ((isset($vt_anos[$ano]) || intval($ano) == 0) && count($vt_anos) < 150) {
            $r .= $this->campo_select($id_ano, $id_ano, $vt_anos,  $ano, 0, 0, 1, 'ano');
        } else {
            $ano = (intval($ano) == 0) ? '0' : sprintf('%04d', $ano);
            $r .= $this->campo_text($id_ano, $id_ano, $ano, 4, 4, false, false, true, 'texto ano');
        }

        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Adicionar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //      Converte os campos de uma data para um timestamp
    //
    public static function data_para_time(&$obj, $prefixo, $time = 0) {
    // Object $obj: objeto que recebeu os dados do formulario
    // String $prefixo: prefixo usado no campo data do formulario
    // Int $time: time com valores padrao
    //
        // Montar IDs dos campos
        if ($prefixo) { $prefixo .= '_'; }
        $id_hora    = $prefixo.'hora';
        $id_minuto  = $prefixo.'minuto';
        $id_segundo = $prefixo.'segundo';
        $id_dia     = $prefixo.'dia';
        $id_mes     = $prefixo.'mes';
        $id_ano     = $prefixo.'ano';

        // Obter valores padrao
        if ($time) {
            list($dia_p, $mes_p, $ano_p) = util::get_data_completa($time);
            list($hora_p, $minuto_p, $segundo_p) = util::get_hora_completa($time);
        } else {
            list($hora_p, $minuto_p, $segundo_p, $dia_p, $mes_p, $ano_p) = array(0, 0, 0, 0, 0, 0);
        }

        // Obter valores
        $hora    = $obj->possui_auxiliar($id_hora)    ? $obj->get_auxiliar($id_hora)    : $hora_p;
        $minuto  = $obj->possui_auxiliar($id_minuto)  ? $obj->get_auxiliar($id_minuto)  : $minuto_p;
        $segundo = $obj->possui_auxiliar($id_segundo) ? $obj->get_auxiliar($id_segundo) : $segundo_p;
        $dia     = $obj->possui_auxiliar($id_dia)     ? $obj->get_auxiliar($id_dia)     : $dia_p;
        $mes     = $obj->possui_auxiliar($id_mes)     ? $obj->get_auxiliar($id_mes)     : $mes_p;
        $ano     = $obj->possui_auxiliar($id_ano)     ? $obj->get_auxiliar($id_ano)     : $ano_p;

        return mktime((int)$hora, (int)$minuto, (int)$segundo, (int)$mes, (int)$dia, (int)$ano);
    }


    //
    //     Campo de telefone
    //
    public function campo_telefone($prefixo, $ddd, $numero, $label = '', $disable = false, $return = false, $ajuda = false) {
    // String $prefixo: prefixo usado no nome dos campos
    // String $ddd: codigo do DDD
    // String $numero: numero do telefone
    // String $label: nome do label
    // Bool $disable: indica se o campo esta desativado
    // Bool $return: retornar ou adicionar no formulario
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        if ($prefixo) { $prefixo .= '_'; }
        $id_ddd    = $prefixo.'ddd';
        $id_numero = $prefixo.'numero';

        $r = '';
        if ($label) {
            $r .= '<div class="campo">';
            $r .= $this->label($id_ddd, $label, true, '', $ajuda);
            $r .= '<div class="telefone">';
        }

        $vt_codigos = listas::get_codigos_telefone();

        $r .= '(';
        $r .= $this->campo_select($id_ddd, $id_ddd, $vt_codigos, $ddd, false, $disable, true, 'ddd');
        $r .= '<span>) </span>';
        $r .= $this->campo_text($id_numero, $id_numero, $numero, 8, 8, false, $disable, true, 'numero');
        $r .= ' <small>(s&oacute;&nbsp;n&uacute;meros)</small>';

        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Adicionar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo de telefone comercial (com ramal)
    //
    public function campo_telefone_comercial($prefixo, $ddd, $numero, $ramal, $label = '', $disable = false, $return = false, $ajuda = false) {
    // String $prefixo: prefixo usado no nome dos campos
    // String $ddd: codigo do DDD
    // String $numero: numero do telefone
    // String $ramal: ramal do telefone
    // String $label: nome do label
    // Bool $disable: indica se o campo esta desativado
    // Bool $return: retornar ou adicionar no formulario
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        if ($prefixo) { $prefixo .= '_'; }
        $id_ddd    = $prefixo.'ddd';
        $id_numero = $prefixo.'numero';
        $id_ramal  = $prefixo.'ramal';

        $r = '';
        if ($label) {
            $r .= '<div class="campo">';
            $r .= $this->label($id_ddd, $label, true, '', $ajuda);
            $r .= '<div class="telefone">';
        }

        $vt_codigos = listas::get_codigos_telefone();

        $r .= '(';
        $r .= $this->campo_select($id_ddd, $id_ddd, $vt_codigos, false, $disable, true, 'ddd');
        $r .= '<span>) </span>';
        $r .= $this->campo_text($id_numero, $id_numero, $numero, 8, 8, false, $disable, true, 'numero');
        $r .= ' <span>ramal:</span>';
        $r .= $this->campo_text($id_ramal, $id_ramal, $ramal, 4, 4, false, $disable, true, 'ramal');

        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Adicionar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Quebra um telefone em partes
    //
    public static function parse_telefone($telefone) {
    // String $telefone: valor do telefone a ser quebrado
    //
        $retorno = array(
            'pais'   => '',
            'ddd'    => '',
            'numero' => '',
            'ramal'  => ''
        );
        if (preg_match('/^(?i:\+([^\040]+)[\040])?\(([^\)]+)\)[\040]([^\040]+)(?i:[\040](.+))?$/', $telefone, $match)) {
            $retorno['pais']   = $match[1];
            $retorno['ddd']    = $match[2];
            $retorno['numero'] = str_replace('-', '', $match[3]);
            $retorno['ramal']  = isset($match[4]) ? $match[4] : '';
        }
        return $retorno;
    }


    //
    //     Junta as componentes de um telefone
    //
    public static function juntar_telefone($prefixo, $dados) {
    // String $prefixo: prefixo usado nos telefones
    // Object $dados: dados submetidos
    //
        if ($prefixo) {
            $prefixo .= '_';
        }
        $id_pais   = $prefixo.'pais';
        $id_ddd    = $prefixo.'ddd';
        $id_numero = $prefixo.'numero';
        $id_ramal  = $prefixo.'ramal';

        $pais   = isset($dados->$id_pais)   ? $dados->$id_pais   : false;
        $ddd    = isset($dados->$id_ddd)    ? $dados->$id_ddd    : false;
        $numero = isset($dados->$id_numero) ? $dados->$id_numero : false;
        $ramal  = isset($dados->$id_ramal)  ? $dados->$id_ramal  : false;

        if (empty($numero)) {
            return '';
        }

        $retorno = '';
        if ($pais === false) {
            $retorno .= '+55 '; // Codigo do Brasil
        } else {
            $retorno .= '+'.$pais.' ';
        }
        $retorno .= '('.$ddd.') ';
        $retorno .= substr($numero, 0, 4).'-'.substr($numero, 4);
        if ($ramal) {
            $retorno .= ' '.$ramal;
        }
        return $retorno;
    }


    //
    //     Campo Relacionamento
    //
    public function campo_relacionamento($name, $id, $classe, $campo_preencher, $campo_exibir, $condicoes = null, $valor = '', $maxlength = 128, $size = 30, $label = '', $readonly = false, $return = false, $class = '', $mascara = false, $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // String $classe: nome da classe entidade do relacionamento
    // String $campo_preencher: nome do campo que sera' enviado pelo formulario
    // String $campo_exibir: nome do campo que sera' exibido ao usuario para escolha
    // condicao_sql $condicoes: condicoes da consulta
    // String $valor: valor preenchido automaticamente
    // Int $maxlength: tamanho maximo do campo
    // Int $size: largura do campo sem CSS
    // String $label: nome do label
    // Bool $readonly: campo editavel ou nao
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String $mascara: mascara JavaScript para entrada de dados (digitos, letras, moeda, int, uint, float, ufloat)
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        $obj = objeto::get_objeto($classe);

        $entidade = $obj->get_entidade();
        $dados = base64_encode(implode(';', array($classe, $campo_preencher, $campo_exibir, base64_encode(serialize($condicoes)))));
        $link = FORMULARIO_WWWROOT.'webservice/relacionamento.xml.php?dados='.$dados;

        $r = $this->campo_relacionamento_xml($name, $id, $link, $valor, $maxlength, $size, $label, $readonly, 1, $class, $mascara, $ajuda);

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo Relacionamento que utiliza um arquivo XML para dados externos
    //
    public function campo_relacionamento_xml($name, $id, $link, $valor = '', $maxlength = 128, $size = 30, $label = '', $readonly = false, $return = false, $class, $mascara = false, $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // String $link: url do arquivo que lista os codigos e valores no formato XML
    // String $valor: valor preenchido automaticamente
    // Int $maxlength: tamanho maximo do campo
    // Int $size: largura do campo sem CSS
    // String $label: nome do label
    // Bool $readonly: campo editavel ou nao
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String $mascara: mascara JavaScript para entrada de dados (digitos, letras, moeda, int, uint, float, ufloat)
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        $class = $class ? " class=\"{$class}\"" : ' class="busca"';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        // Mascara
        if ($mascara) {
            $local = FORMULARIO_LOCALIDADE;
            $eventos = " onkeydown=\"this.valor_antigo = this.value;\"".
                       " onkeyup=\"return mascara(event, this, '{$mascara}', 0, '{$local}');\"".
                       " onblur=\"return mascara(event, this, '{$mascara}', 1, '{$local}');\"";
        } else {
            $eventos = '';
        }

        if ($readonly) {
            $eventos .= " onfocus=\"this.setAttribute('readonly', 'readonly'); return false;\"";
        }

        // Valor
        $valor = self::filtrar_valor_mascara($valor, $mascara);

        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }
        $r .= "<input type=\"text\" name=\"{$name_completo}\" id=\"{$id_completo}\" value=\"{$valor}\" ".
              "maxlength=\"{$maxlength}\" size=\"{$size}\"{$class}{$eventos} />";

        // Navegadores modo texto devem ir para uma pagina mais acessivel
        if (FORMULARIO_TEXTBROWSER) {
            $r .= ' <a href="'.FORMULARIO_WWWROOT.'webservice/relacionamento.php?link='.urlencode($link).'" title="Buscar Valor">Buscar</a>';
        } else {
            $r .= " <a class=\"relacionamento\" href=\"{$link}\" title=\"Buscar Valor\">Buscar</a>";
        }
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo Hierarquico que gera uma janela de selecao sob demanda de acordo com a estrutura de um arquivo XML especificado
    //
    public function campo_hierarquico($name, $id, $link, $valor = '', $maxlength = 128, $size = 30, $label = '', $readonly = false, $return = false, $class = false, $mascara = false, $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // String $link: url do arquivo que lista os codigos e valores no formato XML
    // String $valor: valor preenchido automaticamente
    // Int $maxlength: tamanho maximo do campo
    // Int $size: largura do campo sem CSS
    // String $label: nome do label
    // Bool $readonly: campo editavel ou nao
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String $mascara: mascara JavaScript para entrada de dados (digitos, letras, moeda, int, uint, float, ufloat)
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        $class = $class ? " class=\"{$class}\"" : ' class="busca"';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        // Mascara
        if ($mascara) {
            $local = FORMULARIO_LOCALIDADE;
            $eventos = " onkeydown=\"this.valor_antigo = this.value;\"".
                       " onkeyup=\"return mascara(event, this, '{$mascara}', 0, '{$local}');\"".
                       " onblur=\"return mascara(event, this, '{$mascara}', 1, '{$local}');\"";
        } else {
            $eventos = '';
        }

        if ($readonly) {
            $eventos .= " onfocus=\"this.setAttribute('readonly', 'readonly'); return false;\"";
        }

        // Valor
        $valor = self::filtrar_valor_mascara($valor, $mascara);

        $r = '';
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }
        $r .= "<input type=\"text\" name=\"{$name_completo}\" id=\"{$id_completo}\" value=\"{$valor}\" ".
              "maxlength=\"{$maxlength}\" size=\"{$size}\"{$class}{$eventos} />";

        // Navegadores modo texto devem ir para uma pagina mais acessivel
        if (FORMULARIO_TEXTBROWSER) {
            $r .= ' <a href="'.FORMULARIO_WWWROOT.'webservice/hierarquia.php?link='.urlencode($link).'" title="Buscar Valor">Buscar</a>';
        } else {
            $r .= " <a class=\"hierarquia\" href=\"{$link}\" title=\"Buscar Valor\">Buscar</a>";
        }
        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Campo de Texto com busca dinamica
    //
    public function campo_busca($name, $id, $classe, $campo, $valor, $condicoes = null, $maxlength = 128, $size = 30, $label = '', $return = false, $class = '', $ajuda = false) {
    // String $name: nome do campo
    // String $id: id do campo
    // String $classe: nome da entidade de busca
    // String $campo: nome do campo de busca
    // String $valor: valor do campo
    // condicao_sql $condicoes: condicoes extras utilizados na filtragem
    // Int $maxlength: capacidade do campo
    // Int $size: largura do campo
    // String $label: nome do label
    // Bool $return: retornar ou adicionar ao formulario
    // String $class: classe de estilos CSS
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        $class = $class ? " class=\"{$class}\"" : ' class="texto"';
        $name_completo = $this->montar_nome($name);
        $id_completo = $this->montar_id($id);

        // Valor
        $valor = self::filtrar_valor_mascara($valor);

        $r = '';
        if (FORMULARIO_AJAX) {
            $id_parametros = md5(sprintf('%0.3f%04d', microtime(true), mt_rand(0, 9999)));
            $parametros = array(
                'classe'    => $classe,
                'campo'     => $campo,
                'condicoes' => $condicoes
            );
            self::salvar_parametros_busca($id_parametros, $parametros);
            $r .= '<div class="info_aguarde_sugestao" id="'.$id_parametros.'">Campo com sugest&otilde;es (digite e aguarde as sugest&otilde;es)</div>';
        }
        if ($label) {
            $r .= "<div id=\"area_{$id_completo}\" class=\"campo\">";
            $r .= $this->label($id, $label, true, '', $ajuda);
            $r .= '<div>';
        }
        $r .= "<input type=\"text\" name=\"{$name_completo}\" id=\"{$id_completo}\" value=\"{$valor}\" ".
              "maxlength=\"{$maxlength}\" size=\"{$size}\"{$class} />".
              "<div class=\"resultado_campo_busca\"></div>";

        if ($label) {
            $r .= '</div>'.
                  '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Salva os parametros de um campo de busca
    //
    public static function salvar_parametros_busca($id_parametros, $parametros) {
    // String $id_parametros: identificador dos parametros
    // Mixed $parametros: parametros a serem salvos
    //
        $_SESSION[__CLASS__]['parametros_busca'][$id_parametros] = serialize($parametros);

        // Manter apenas 10 itens no vetor
        while (count($_SESSION[__CLASS__]['parametros_busca']) > 10) {
            array_shift($_SESSION[__CLASS__]['parametros_busca']);
        }

        return true;
    }


    //
    //     Obtem os parametros de um campo de busca
    //
    public static function get_parametros_busca($id_parametros) {
    // String $id_parametros: identificador dos parametros
    //
        if (!isset($_SESSION[__CLASS__]['parametros_busca'][$id_parametros])) {
            return false;
        }
        return unserialize($_SESSION[__CLASS__]['parametros_busca'][$id_parametros]);
    }


    //
    //     Campo captcha (para evitar que robos submetam dados por formularios abertos na web)
    //
    public function campo_captcha($return = false) {
    // Bool $return: indica se o campo sera retornado ou adicionado ao formulario
    //
        static $ja_gerou = false;
        if ($ja_gerou) { return; }
        $ja_gerou = true;

        $id = 'captcha';

        simp_autoload('captcha');

        // Se e' um navegador grafico
        if (!FORMULARIO_TEXTBROWSER && extension_loaded('gd')) {
            $w = CAPTCHA_LARGURA;
            $h = CAPTCHA_ALTURA;

            // MD5 baseado no time apenas para forcar que a imagem seja carregada novamente
            $md5 = md5(microtime());

            $r = $this->campo_aviso('Escreva o texto da imagem no campo abaixo. '.
                                    'Este procedimento serve para garantir que voc&ecirc; '.
                                    '&eacute; um ser humano e n&atilde;o um computador programado '.
                                    'maliciosamente para boicotar o sistema. Caso n&atilde;o entenda '.
                                    'o texto, clique em "Mudar imagem".', 1);

            $src = FORMULARIO_WWWROOT."webservice/captcha.jpg.php?c=".$md5;

            $r .= "<div id=\"area_{$id}\" class=\"campo\">";
            $r .= $this->label($id, 'Escreva');
            $r .= '<div class="captcha_imagem">';
            $r .= "<img id=\"img_captcha\" src=\"{$src}\" alt=\"Captcha\" title=\"Escreva o que est&aacute; escrito na imagem\" width=\"{$w}\" height=\"{$h}\" />";
            $r .= "<input id=\"{$id}\" name=\"{$id}\" type=\"text\" maxlength=\"".CAPTCHA_TAMANHO."\" size=\"".CAPTCHA_TAMANHO."\" class=\"texto\" />";
            $r .= '</div>';
            $r .= '</div>';


        // Se e' um navegador modo texto
        } else {
            $captcha = captcha::gerar_texto();

            $r  = "<p>Pergunta: {$captcha->pergunta}</p>";
            $r .= "<div id=\"area_{$id}\" class=\"captcha_texto campo\">";
            $r .= $this->label($id, 'Resposta');
            $r .= "<div><input id=\"{$id}\" name=\"{$id}\" type=\"text\" maxlength=\"20\" class=\"texto\" /></div>";
            $r .= '</div>';
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um campo de aviso
    //
    public function campo_aviso($texto, $return = false) {
    // String $texto: texto a ser exibido
    // Bool $return: retornar ou adicionar ao formulario
    //
        if ($this->inline) {
            $r = "<span class=\"comentario\">{$texto}</span>";
        } else {
            $r = "<div class=\"block\"><div class=\"comentario\">{$texto}</div></div>";
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Adiciona um campo generico
    //
    public function campo_generico($campo, $return = false) {
    // String $campo: conteudo HTML do campo generico
    // Bool $return: retorna ou imprime o campo
    //
        if ($return) { return $campo; }
        $this->campos[] = $campo;
    }


    //
    //     Imprime um campo de informacao
    //
    public function campo_informacao($texto, $return = false) {
    // String $texto: texto a ser exibido
    // Bool $return: retornar ou adicionar ao formulario
    //
        if ($this->inline) {
            $r = "<span>{$texto}</span>";
        } else {
            $r = "<div class=\"block\">{$texto}</div>";
        }

        // Armazenar Campo
        if ($return) { return $r; }
        $this->campos[] = $r;
    }


    //
    //     Imprime um label
    //
    public function label($id, $nome, $dois_pontos = true, $class = '', $ajuda = false) {
    // String $id: ID de destino
    // String $nome: Nome do label
    // Bool $dois_pontos: colocar dois pontos
    // String $class: classe CSS para aplicao ao label
    // String || Array[String => String] $ajuda: texto da ajuda do campo ou vetor com posicoes "link" e (opcionalmente) "texto"
    //
        $class = $class ? " class=\"{$class}\"" : '';
        $icone_ajuda = $ajuda ? self::montar_ajuda($ajuda) : '';
        if ($id) {
            $id = $this->montar_id($id);
            return "<label id=\"label_{$id}\" for=\"{$id}\"{$class}>{$nome}{$icone_ajuda}</label>";
        } else {
            return "<label{$class}>{$nome}{$icone_ajuda}</label>";
        }
    }


    //
    //     Insere uma meta-informacao sobre determinado campo do formulario
    //
    public function meta_informacao($id_campo, $valor, $return = false) {
    // String $id_campo: identificador do campo ao qual estara associada a meta-informacao
    // String $valor: valor da meta-informacao
    // Bool $return: indica se a meta-informacao deve ser retornada ou inserida no formulario
    //
        $m = "<meta name=\"{$id_campo}\" content=\"{$valor}\" />";
        if ($return) { return $m; }
        $this->campos[] = $m;
    }


    //
    //     Imprime ou retorna um formulario na forma de string
    //
    public function imprimir($return = false) {
    // Bool $return: retornar ou imprimir o formulario
    //
        $f = $this->abrir_formulario();
        foreach ($this->campos as $campo) {
            if ($campo) {
                $f .= $campo;
            }
        }
        $f .= $this->fechar_formulario();
        if (!empty($this->erros)) {
            foreach ($this->erros as $erro) {
                $f .= mensagem::erro($erro, 1);
            }
        }
        if ($return) {
            return $f;
        }
        echo $f;
    }


    //
    //     Filtra um valor de acordo com o tipo
    //
    public static function filtrar($tipo, $valor) {
    // String $tipo: tipo do Simp, exceto data (int, float, string, char, bool, binario)
    // String || Array[String] $valor: valor recebido pelo formulario
    //
        static $atributos = array();
        if (!isset($atributos[$tipo])) {
            $atributos[$tipo] = new atributo('filtro', 'Filtro', null);
            $atributos[$tipo]->set_tipo($tipo, true);
        }
        if (is_array($valor)) {
            $retorno = array();
            foreach ($valor as $k => $v) {
                $retorno[$k] = self::filtrar($tipo, $v);
            }
        } else {
            if (is_null($valor)) {
                $valor = '';
            }
            if (is_scalar($valor)) {
                $retorno = $atributos[$tipo]->filtrar($valor);
            } else {
                $retorno = null;
            }
        }
        return $retorno;
    }


    //
    //     Valida um valor de acordo com o tipo (se os valores passados podem ser convertidos para o tipo especificado)
    //
    public static function validar($nome, $tipo, $valor, &$erros = array()) {
    // String $nome: nome do campo
    // String $tipo: tipo do Simp, exceto data (int, float, string, char, bool, binario)
    // String || Array[String] $valor: valor recebido pelo formulario
    // Array[String] $erros: vetor de erros
    //
        static $atributos = array();
        if (!isset($atributos[$tipo])) {
            $atributos[$tipo] = new atributo('validacao', $nome, null);
            $atributos[$tipo]->set_tipo($tipo, true);
        }

        $erros = array();
        $validacao = validacao::get_instancia();

        $r = true;
        if (is_array($valor)) {
            $retorno = array();
            foreach ($valor as $k => $v) {
                if (!$validacao->validar_atributo($atributos[$tipo], $v, $erros)) {
                    $r = false;
                }
            }
        } elseif (is_scalar($valor)) {
            if (!$validacao->validar_atributo($atributos[$tipo], $valor, $erros)) {
                $r = false;
            }
        }
        return $r;
    }


    //
    //     Converte um valor para string no formato que deve ser exibido
    //
    private static function filtrar_valor_mascara($valor, $mascara = false) {
    // Mixed $valor: valor em Int, Float, Char ou String
    // String $mascara: mascara JavaScript para entrada de dados (digitos, letras, moeda, int, uint, float, ufloat)
    //
        if (is_int($valor) || is_float($valor)) {
            switch (util::get_tipo($valor)) {
            case 'int':
                $valor = texto::numero($valor, 0);
                break;
            case 'float':
                switch ($mascara) {
                case 'moeda':
                    $valor = texto::numero($valor, 2, true);
                    break;
                case 'int':
                case 'uint':
                    $valor = texto::numero($valor, 0);
                    break;
                case 'float':
                case 'ufloat':
                    $sub = abs($valor) - floor(abs($valor));
                    $casas_decimais = max(0, strlen($sub) - 2);
                    $valor = texto::numero($valor, $casas_decimais);
                    break;
                }
            }
        }
        $valor = texto::codificar($valor);
        return $valor;
    }


    //
    //     Codifica um numero para ser usado em campos de formulario que escondem o valor
    //
    private static function filtrar_numero_php($valor) {
    // Mixed $valor: valor a ser codificado
    //
        if (is_int($valor)) {
            $valor = texto::numero($valor, 0, false, 'C');
        } elseif (is_float($valor)) {
            $valor = texto::numero($valor, false, false, 'C');
        }
        $valor = texto::codificar($valor);
        return $valor;
    }


    //
    //     Busca um nome pelo codigo em um arquivo XML de campo hierarquico
    //
    public static function get_nome_por_codigo($arquivo, $codigo, &$erro = '') {
    // String $arquivo: nome completo do arquivo
    // String $codigo: codigo a ser buscado no arquivo XML
    // String $erro: erro ocorrido
    //
        if (!file_exists($arquivo)) {
            $erro = 'Arquivo de &aacute;reas n&atilde;o existe ('.$arquivo.')';
            return false;
        }
        if (is_dir($arquivo)) {
            $erro = $arquivo.' &eacute; um diret&oacute;rio e n&atilde;o um arquivo';
            return false;
        }
        $xml = simplexml_load_file($arquivo);
        if (!$xml) {
            $erro = 'Arquivo de &aacute;reas do CNPq est&aacute; com problemas';
            return false;
        }
        return self::buscar_nome_por_codigo($xml, $codigo, $erro);
    }


    //
    //     Busca o nome recursivamente na estrutura XML de campo hierarquico
    //
    public static function buscar_nome_por_codigo(&$xml, $codigo, &$erro) {
    // Object $xml: objeto da classe SimpleXMLElement
    // String $codigo: codigo a ser buscado na estrutura
    // String $erro: erro ocorrido
    //
        if (isset($xml['valor']) && $xml['valor'] == $codigo) {
            return $xml['nome'];
        }
        foreach ($xml->children() as $child) {
            if ($nome = self::buscar_nome_por_codigo($child, $codigo)) {
                return $nome;
            }
        }
        $erro = 'Elemento n&atilde;o encontrado';
        return false;
    }


    //
    //     Retorna o erro de upload na forma textual
    //
    public static function get_erro_upload($cod_erro) {
    // Int $cod_erro: codigo do erro
    //
        switch ($cod_erro) {
        case UPLOAD_ERR_INI_SIZE:
            return 'Arquivo maior que o limite imposto pelo servidor';
        case UPLOAD_ERR_FORM_SIZE:
            return 'O arquivo ultrapassou o limite de tamanho imposto pelo formul&aacute;rio';
        case UPLOAD_ERR_PARTIAL:
            return 'Erro para completar o upload de todo o arquivo (nada foi feito)';
        case UPLOAD_ERR_NO_FILE:
            return 'Erro inesperado: nenhum arquivo foi enviado';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'N&atilde;o existe um diret&oacute;rio tempor&aacute;rio para armazenar o arquivo '.
                   '(Erro na configura&ccedil;&atilde;o do servidor)';
        case UPLOAD_ERR_CANT_WRITE:
            return 'N&atilde;o foi poss&iacute;vel criar o arquivo tempor&aacute;rio no servidor';
        }
        return 'Erro inesperado';
    }

}//class
