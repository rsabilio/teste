<?php
//
// SIMP
// Descricao: Classe que gera paginas de modulos (exibir, alterar, inserir, listar e importar entidades)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.2.0.23
// Data: 01/02/2008
// Modificado: 04/10/2012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
global $CFG, $USUARIO;

final class modulo {


/// # METODOS PUBLICOS PARA GERAR PAGINAS


    //
    //     Gera uma pagina de listar entidades (index.php)
    //     $dados_lista
    //     - String $id_lista: identificador da lista
    //     - Array[String] $opcoes: opcoes da lista ('inserir', 'alterar', 'excluir', ou algum codigo definido no metodo "dados_opcao", que e' um ponto de extensao da classe objeto)
    //     - Array[String] $campos: vetor de campos exibidos na lista e separados por "-"
    //     - String $formatacao: forma como os campos serao dispostos, tal que $1 e' o primeiro campo, $2 e' o segundo, etc. Por exemplo: '$1 ($2)'
    //     - Array[String] $campos_consultar: lista de campos a serem consultados para cada entidade (alem dos campos pedidos para exibicao)
    //     - Array[String => Bool] $ordem: campos usados para ordenar a lista apontando para o tipo de ordenacao (crescente = true / decrescente = false)
    //     - String $index: campo usado para indexacao dos registros da lista
    //     - Int $itens_pagina: Numero maximo de registros por pagina
    //     - Array[String => String] $nomes: vetor associativo com a descricao dos elementos listados. O vetor possui os seguintes indices:
    //       - String 'singular': indica o nome do elemento no singular
    //       - String 'plural': indica o nome do elemento no plural
    //       - String 'genero': indica o genero do elemento (M - masculino, F - feminino, I - indefinido)
    //     - String $ajuda: descricao da ajuda da pagina
    //     - String $texto_antes: bloco HTML que deve aparecer antes do quadro
    //     - String $texto_depois: bloco HTML que deve aparecer depois do quadro
    //     - Array[String => String] || Array[Array[String => Mixed]] $links: vetor de links a serem exibidos no rodape da lista (indexados pela descricao do link) ou com pacotes de dados dos links na forma de array (abaixo os possiveis indices)
    //       - String 'descricao': Descricao alternativa do link
    //       - String 'link': URL do link ou nome do arquivo no mesmo modulo
    //       - String 'modulo': Nome do modulo (usado em conjunto com $arquivo)
    //       - String 'arquivo': Nome do arquivo (usado em conjunto com o $modulo)
    //       - Bool 'ajax': Usar ajax no link
    //     - condicao_sql $condicoes: condicao base para filtrar os elementos da lista (e' unida 'as condicoes do formulario de filtragem, caso ele exista)
    //     - condicao_sql $condicoes_iniciais: condicao inicial para filtar os elementos (sera usada apenas no primeiro acesso)
    //     - objeto $entidade: entidade responsavel por gerar a lista
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav e as $condicoes, caso nao sejam preenchidos)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //     $dados_form
    //     - String $funcao_form: nome da funcao que gera o formulario de filtro da lista
    //       A funcao deve receber como parametro apenas um objeto stdClass com os dados submetidos anteriormente.
    //       A funcao deve montar um formulario e imprimi-lo na tela.
    //     - String $funcao_condicoes: nome da funcao que recebe os dados do formulario, um vetor de erros por referencia e retorna o criterio de selecao de itens da lista ou false, em caso de erro
    //       A funcao deve receber os dados submetidos do formulario criado com a funcao "funcao_form", gerar um objeto da classe condicao_sql e retorna-lo, ou retornar false e preencher o vetor de erros
    //     - String $funcao_ordem: nome da funcao que recebe os dados do formulario e retorna o criterio de ordenacao do resultado
    //       A funcao deve receber os dados submetidos do formulario criado com a funcao "funcao_form", gerar um vetor com os campos usados para ordenacao apontando para o tipo de ordenacao (true = crescente / false = decrescente)
    //     - Bool $exibir_lista: indica se a lista de resultados deve ser exibida sempre ou apenas quando ativado o formulario montado pela funcao "funcao_form"
    //     - Array[String => String] $sanitizar: vetor com os nomes dos campos apontando para o tipo de sanitizacao que deve ser feita sobre ele
    //
    static public function listar_entidades($classe, $dados_lista = false, $dados_pagina = false, $dados_form = false) {
    // String $classe: nome da classe
    // Object $dados_lista: dados opcionais ($id_lista, $opcoes, $campos, $formatacao, $campos_consultar, $index, $itens_pagina, $nomes, $ordem, $ajuda, $texto_antes, $texto_depois, $links, $condicoes, $condicoes_iniciais, $entidade)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    // Object $dados_form: dados opcionais ($funcao_form, $funcao_condicoes, $funcao_ordem, $exibir_lista, $sanitizar)
    //
        global $CFG, $USUARIO;

        if (!$dados_lista)  { $dados_lista  = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }
        if (!$dados_form)   { $dados_form   = new stdClass(); }

        if (isset($dados_lista->entidade)) {
            $entidade = &$dados_lista->entidade;
        } else {
            $entidade = objeto::get_objeto($classe);
        }
        self::checar_classe($entidade, 'objeto');

        // Dados da lista
        $arquivo          = util::get_arquivo();
        $modulo           = self::get_modulo($arquivo);
        $modulo_pai       = self::get_modulo_pai($modulo);
        $modulo_rel       = self::get_modulo($arquivo, false);
        $opcoes           = isset($dados_lista->opcoes)           ? $dados_lista->opcoes           : array('exibir', 'alterar', 'excluir');
        $campos           = isset($dados_lista->campos)           ? $dados_lista->campos           : false;
        $campos_consultar = isset($dados_lista->campos_consultar) ? $dados_lista->campos_consultar : false;
        $formatacao       = isset($dados_lista->formatacao)       ? $dados_lista->formatacao       : false;
        $index            = isset($dados_lista->index)            ? $dados_lista->index            : false;
        $itens_pagina     = isset($dados_lista->itens_pagina)     ? $dados_lista->itens_pagina     : false;
        $nomes            = isset($dados_lista->nomes)            ? $dados_lista->nomes            : false;
        $ordem            = isset($dados_lista->ordem)            ? $dados_lista->ordem            : false;
        $id_lista         = isset($dados_lista->id_lista)         ? $dados_lista->id_lista         : 'lista_'.$classe;

        // Obter link
        $link             = $CFG->site;
        $remover = array('op', $entidade->get_chave());
        link::normalizar($link, $remover);

        $texto_antes      = isset($dados_lista->texto_antes)  ? $dados_lista->texto_antes  : '';
        $texto_depois     = isset($dados_lista->texto_depois) ? $dados_lista->texto_depois : '';
        if (!isset($dados_lista->links)) {
            $dados_lista->links = array('inserir.php', 'importar_csv.php', 'importar_xml.php');
        }
        $links = array();
        if (is_array($dados_lista->links)) {
            $i = 1;
            foreach ($dados_lista->links as $descricao => $l) {

                // Tipo de link (absoluto 1 / relativo 0)
                $tipo = 0;

                // Se passou um pacote de dados
                if (is_array($l)) {
                    $usar_ajax      = isset($l['ajax'])      ? $l['ajax']      : true;
                    $descricao_link = isset($l['descricao']) ? $l['descricao'] : false;
                    if (isset($l['class'])) {
                        $class = $l['class'];
                    } elseif (isset($l['link'])) {
                        $class = self::get_class_link(basename($l['link']));
                    } elseif (isset($l['arquivo'])) {
                        $class = self::get_class_link(basename($l['arquivo']));
                    } else {
                        $class = false;
                    }

                    // Se informou 'link'
                    if (isset($l['link'])) {

                        // Absoluto
                        if (strpos($l['link'], 'http://') !== false || strpos($l['link'], 'https://') !== false) {
                            $tipo = 1;
                            $link_absoluto = $l['link'];

                        // Relativo
                        } else {
                            $tipo = 0;
                            $arquivo_link = $l['link'];
                            $modulo_link = isset($l['modulo']) ? $l['modulo'] : $modulo;
                        }

                    // Se informou 'arquivo' e 'modulo'
                    } elseif (isset($l['arquivo'])) {
                        $tipo = 0;
                        $arquivo_link = $l['arquivo'];
                        $modulo_link  = isset($l['modulo']) ? $l['modulo'] : $modulo;
                    }

                // Se passou uma string
                } else {

                    // Absoluto
                    if (strpos($l, 'http://') !== false || strpos($l, 'https://') !== false) {
                        $tipo = 1;
                        $link_absoluto = $l;
                        $class = 'op'.$i;
                        $usar_ajax = true;
                        $descricao_link = $descricao;

                    // Relativo
                    } else {
                        $tipo = 0;
                        $class = self::get_class_link($l);
                        $arquivo_link = $l;
                        $modulo_link = $modulo;
                        $usar_ajax = true;
                        $descricao_link = is_numeric($descricao) ? false : $descricao;
                    }
                }

                // Link absoluto
                if ($tipo == 1) {
                    $links[] = link::texto($link_absoluto, $descricao_link, false, false, $class, true, false, false, $usar_ajax);

                // Link relativo
                } else {
                    $l = link::arquivo_modulo($USUARIO, $arquivo_link, $modulo_link, $descricao_link, '', $class, true, true, true, $usar_ajax);
                    if ($l) {
                        $links[] = $l;
                    }
                }
                $i++;
            }
        }
        if (isset($dados_lista->ajuda)) {
            $ajuda = &$dados_lista->ajuda;
        } else {
            $ajuda = "<p>A tabela a seguir apresenta a lista de ".texto::codificar($entidade->get_entidade(1))." registrados no sistema.</p>";
            if ($opcoes) {
//TODO: deveria ficar em dados_opcao
                $vt_nomes_opcoes = array(
                    'exibir'  => 'exibir',
                    'alterar' => 'alterar',
                    'excluir' => 'excluir'
                );
                $vt_opcoes = array();
                foreach ($opcoes as $opcao) {
                    if (isset($vt_nomes_opcoes[$opcao])) {
                        $vt_opcoes[] = $vt_nomes_opcoes[$opcao];
                    }
                }
                $total_opcoes = count($vt_opcoes);
                if ($total_opcoes > 1) {
                    $ultima = array_pop($vt_opcoes);
                    $st_opcoes = implode(', ', $vt_opcoes).' e '.$ultima.' os dados ';
                    switch ($entidade->get_genero()) {
                    case 'M':
                        $st_opcoes .= 'dos '.$entidade->get_entidade(1).'.';
                        break;
                    case 'F':
                        $st_opcoes .= 'das '.$entidade->get_entidade(1).'.';
                        break;
                    case 'I':
                        $st_opcoes .= 'dos(as) '.$entidade->get_entidade(1).'.';
                        break;
                    }
                    $ajuda .= "<p>As op&ccedil;&otilde;es poss&iacute;veis s&atilde;o: {$st_opcoes}</p>";
                } elseif ($total_opcoes == 1) {
                    $ultima = array_pop($vt_opcoes);
                    switch ($entidade->get_genero()) {
                    case 'M':
                        $ajuda .= "<p>A &uacute;nica op&ccedil;&atilde;o poss&iacute;vel &eacute; {$ultima} um ".$entidade->get_entidade().'</p>';
                        break;
                    case 'F':
                        $ajuda .= "<p>A &uacute;nica op&ccedil;&atilde;o poss&iacute;vel &eacute; {$ultima} uma ".$entidade->get_entidade().'</p>';
                        break;
                    case 'I':
                        $ajuda .= "<p>A &uacute;nica op&ccedil;&atilde;o poss&iacute;vel &eacute; {$ultima} um(a) ".$entidade->get_entidade().'</p>';
                        break;
                    }
                }
            }
        }

        // Sub-modulo
        if (isset($dados_pagina->submodulo)) {
            $pos = strpos($dados_pagina->submodulo, ':');

            $obj_pai = $entidade->get_objeto_rel_uu($dados_pagina->submodulo);
            $classe_pai = $obj_pai->get_classe();
            if ($pos !== false) {
                $chave_pai = substr($dados_pagina->submodulo, 0, $pos).':'.$obj_pai->get_chave();
            } else {
                $chave_pai = $obj_pai->get_chave();
            }
            $valor_chave_pai = self::get_chave_session($classe_pai);
            if (!$valor_chave_pai) {

                if (!DEVEL_BLOQUEADO) {
                    pagina::erro($USUARIO, 'O c&oacute;digo do objeto pai n&atilde;o foi especificado', '<p>Verifique se voc&ecirc; usou o m&eacute;todo "<tt>modulo::get_entidade_session</tt> neste arquivo."</p>');
                    exit(1);
                }

                header('Location: '.$CFG->wwwroot);
                exit(1);
            }
            $obj_pai->consultar('', $valor_chave_pai);
            if (!$obj_pai->pode_ser_manipulado($USUARIO)) {
                $log = new log_sistema();
                $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $obj_pai->get_valor_chave(), $obj_pai->get_classe(), $modulo.'/'.$arquivo);
                pagina::erro($USUARIO, ERRO_INSERIR);
                exit(1);
            }

            // Guardar em Sessao
            self::set_entidade_session($obj_pai, $modulo);

            // Listar apenas entidades filhas da entidade pai
            if (!isset($dados_lista->condicoes)) {
                $dados_lista->condicoes = condicao_sql::montar($chave_pai, '=', $valor_chave_pai);
            }
        }

        // Dados da pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Dados do formulario de filtragem dos dados
        $funcao_form = isset($dados_form->funcao_form) ? $dados_form->funcao_form : false;
        $funcao_condicoes = isset($dados_form->funcao_condicoes) ? $dados_form->funcao_condicoes : false;
        $funcao_ordem = isset($dados_form->funcao_ordem) ? $dados_form->funcao_ordem : false;
        $exibir_lista = isset($dados_form->exibir_lista) ? $dados_form->exibir_lista : false;
        $sanitizar    = isset($dados_form->sanitizar)    ? $dados_form->sanitizar    : false;
        $dados = formulario::get_dados();
        if ($dados || ($dados_pagina->submodulo && isset($_GET['op']))) {
            $paginacao = new paginacao($modulo, $id_lista);
            $paginacao->salvar_pagina(1);
        }

        // Exibir a pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        if ($funcao_form) {
            if (!$dados && isset($_SESSION[$modulo]['dados_lista'][$id_lista])) {
                $dados = unserialize($_SESSION[$modulo]['dados_lista'][$id_lista]);
            }
            $funcao_form($dados);

            // Se passou os dados
            if ($dados) {
                if ($sanitizar) {
                    foreach ($dados as $campo => $valor) {
                        if (isset($sanitizar[$campo])) {
                            $dados->$campo = formulario::filtrar($sanitizar[$campo], $valor);
                        }
                    }
                }

                $erros = array();
                $condicao_formulario = $funcao_condicoes($dados, $erros);
                if ($condicao_formulario) {
                    if (isset($dados_lista->condicoes)) {
                        $vt_condicoes = array();
                        $vt_condicoes[] = $condicao_formulario;
                        $vt_condicoes[] = $dados_lista->condicoes;
                        $condicoes = condicao_sql::sql_and($vt_condicoes);
                    } else {
                        $condicoes = $condicao_formulario;
                    }
                    if ($funcao_ordem) {
                        $ordem = $funcao_ordem($dados);
                    }
                    $exibir_lista = true;
                } else {
                    mensagem::erro($erros);
                    $exibir_lista = false;
                }

            // Se nao passou dados
            } else {

                if (isset($dados_lista->condicoes_iniciais)) {
                    if (isset($dados_lista->condicoes)) {
                        $vt_condicoes = array($dados_lista->condicoes, $dados_lista->condicoes_iniciais);
                        $condicoes = condicao_sql::sql_and($vt_condicoes);
                    } else {
                        $condicoes = $dados_lista->condicoes_iniciais;
                    }
                } elseif (isset($dados_lista->condicoes)) {
                    $condicoes = $dados_lista->condicoes;
                } else {
                    $condicoes = condicao_sql::vazia();
                }

            }
            if ($exibir_lista) {
                if ($texto_antes) {
                    echo $texto_antes;
                }
                $entidade->imprimir_lista($condicoes, $modulo, $id_lista, $link, $opcoes, $campos, $ordem, $index, $itens_pagina, $campos_consultar, $nomes, $formatacao);
                if ($texto_depois) {
                    echo $texto_depois;
                }
                $_SESSION[$modulo]['dados_lista'][$id_lista] = serialize($dados);
            }
        } else {
            $condicoes = isset($dados_lista->condicoes) ? $dados_lista->condicoes : condicao_sql::vazia();
            if ($texto_antes) {
                echo $texto_antes;
            }
            $entidade->imprimir_lista($condicoes, $modulo, $id_lista, $link, $opcoes, $campos, $ordem, $index, $itens_pagina, $campos_consultar, $nomes, $formatacao);
            if ($texto_depois) {
                echo $texto_depois;
            }
        }
        if ($links) {
            $pagina->listar_opcoes($links);
        }
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina de inserir entidades (inserir.php)
    //     $dados_form
    //     - Array[Array || String] $campos: vetor de campos do formulario de inserir
    //     - Object $dados: dados a serem preenchidos por padrao (organizados hierarquicamente da mesma forma como os dados submetidos)
    //     - String $prefixo: prefixo do ID do formulario
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o formulario
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o formulario de insercao.
    //     - Array[String => Mixed] $opcoes: atributos a serem setados no objeto automaticamente
    //     - String $ajuda: ajuda do formulario
    //     - String $class: classe CSS do formulario
    //     - Bool $ajax: usar ajax no formulario
    //     - objeto_formulario $entidade: entidade envolvida na insercao
    //     - Bool $outro: incluir link para cadastrar outro
    //     - String $texto_link_outro: texto do link para cadastrar outro
    //     - String $link_outro: link para cadastrar outro
    //     - String $nome_botao: nome do botao do formulario
    //     - String $aviso_acesso: aviso caso o usuario nao tenha acesso ao formulario
    //     - Int $modo_transacao: tipo de transacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido, e o vetor $opcoes com a chave da sessao)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //     - String $texto_antes: bloco HTML que deve aparecer antes do quadro/formulario
    //     - String $texto_depois: bloco HTML que deve aparecer depois do quadro/formulario
    //
    static public function inserir($classe, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_form: dados opcionais ($campos, $dados, $prefixo, $funcao_operacoes, $opcoes, $ajuda, $class, $ajax, $entidade, $outro, $texto_link_outro, $link_outro, $nome_botao, $aviso_acesso, $modo_transacao)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa, $texto_antes, $texto_depois)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
        } else {
            $entidade = objeto::get_objeto($classe);
        }
        self::checar_classe($entidade);

        // Dados do formulario
        $arquivo        = util::get_arquivo();
        $modulo         = self::get_modulo($arquivo);
        $modulo_pai     = self::get_modulo_pai($modulo);
        $modulo_rel     = self::get_modulo($arquivo, false);
        $campos         = isset($dados_form->campos)          ? $dados_form->campos         : true;
        $action         = isset($dados_form->action)          ? $dados_form->action         : $CFG->site;
        $texto_antes    = isset($dados_pagina->texto_antes)   ? $dados_pagina->texto_antes  : '';
        $texto_depois   = isset($dados_pagina->texto_depois)  ? $dados_pagina->texto_depois : '';

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('opcoes', 'prefixo', 'class', 'ajax', 'outro', 'texto_link_outro', 'link_outro', 'nome_botao', 'aviso_acesso', 'modo_transacao');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        // Incluir chave FK em opcoes, caso nao tenha preenchido opcoes
        if (isset($dados_pagina->submodulo) && !isset($extra['opcoes'])) {
            $flag = OBJETO_IGNORAR_IMPLICITOS;
            $todos_campos = $entidade->get_campos_reais($campos, $objetos, $vetores, $flag);
            $nome_obj_pai = $dados_pagina->submodulo;
            $obj_pai = $entidade->get_objeto_rel_uu($nome_obj_pai);
            $pos = strpos($nome_obj_pai, ':');
            if ($pos !== false) {
                $chave_fk = substr($nome_obj_pai, 0, $pos).':'.$entidade->get_nome_chave_rel_uu($nome_obj_pai);
            } else {
                $chave_fk = $entidade->get_nome_chave_rel_uu($nome_obj_pai);
            }
            $classe_pai = $obj_pai->get_classe();
            $chave_pai = $obj_pai->get_chave();
            $valor_chave_pai = self::get_chave_session($classe_pai);
            if (!$valor_chave_pai) {
                header('Location: '.$CFG->wwwroot);
                exit(1);
            }

            // Verificar se incluiu a FK ou o objeto relacionado entre os campos
            $setou_fk = false;
            foreach ($todos_campos as $campo) {
                if ($chave_fk == $campo || preg_match('/^'.$nome_obj_pai.':/', $campo)) {
                    $setou_fk = true;
                    break;
                }
            }
            if (!$setou_fk) {
                $extra['opcoes'] = array(
                    $chave_fk => self::get_chave_session($classe_pai)
                );
            }
        }

        if (isset($dados_form->ajuda)) {
            $ajuda = &$dados_form->ajuda;
        } else {
            switch ($entidade->get_genero()) {
            case 'M':
                $novos = 'novos';
                break;
            case 'F':
                $novos = 'novas';
                break;
            case 'I':
                $novos = 'novos(as)';
                break;
            }
            $ajuda = "<p>Este formul&aacute;rio &eacute; respons&aacute;vel pela cria&ccedil;&atilde;o de {$novos} ".$entidade->get_entidade(1)." no sistema.</p>";
        }

        // Dados do Formulario
        $dados_post = formulario::get_dados();
        $prefixo = isset($extra['prefixo']) ? $extra['prefixo'] : '';
        if (isset($dados_post->id_form) && $dados_post->id_form == $entidade->id_formulario_inserir($prefixo)) {
            $dados = $dados_post;
        } elseif (isset($dados_form->dados)) {
            $dados = $dados_form->dados;
        } else {
            $dados = null;
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Inserir dados opcionais
        if (isset($extra['opcoes'])) {
            foreach ($extra['opcoes'] as $cod => $valor) {
                $entidade->__set($cod, $valor);
            }
        }

        // Operacoes
        if (!$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_INSERIR);
            exit(1);
        }
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        echo $texto_antes;
        $entidade->formulario_inserir($dados, $campos, $action, $extra);
        echo $texto_depois;
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina de alterar entidades (alterar.php)
    //     $dados_form
    //     - Array[Array || String] $campos: vetor de campos do formulario de alterar
    //     - String $prefixo: prefixo do ID do formulario de alterar
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o formulario
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o formulario de alteracao.
    //     - Array[String => Mixed] $opcoes: atributos a serem setados no objeto automaticamente
    //     - String $class: classe CSS do formulario
    //     - Bool $ajax: usar ajax no formulario
    //     - String $nome_botao: nome do botao do formulario
    //     - String $aviso_acesso: aviso caso o usuario nao tenha acesso ao formulario
    //     - objeto_formulario $entidade: entidade envolvida na alteracao
    //     - Int $modo_transacao: tipo de transacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //     - String $texto_antes: bloco HTML que deve aparecer antes do quadro/formulario
    //     - String $texto_depois: bloco HTML que deve aparecer depois do quadro/formulario
    //
    static public function alterar($classe, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_form: dados opcionais ($campos, $prefixo, $funcao_operacoes, $opcoes, $class, $ajax, $nome_botao, $aviso_acesso, $entidade, $modo_transacao)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa, $texto_antes, $texto_depois)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        // Dados do formulario
        $arquivo        = util::get_arquivo();
        $modulo         = self::get_modulo($arquivo);
        $modulo_pai     = self::get_modulo_pai($modulo);
        $modulo_rel     = self::get_modulo($arquivo, false);
        $dados          = formulario::get_dados();
        $campos         = isset($dados_form->campos)         ? $dados_form->campos         : true;
        $action         = isset($dados_form->action)         ? $dados_form->action         : $CFG->site;
        $texto_antes    = isset($dados_pagina->texto_antes)  ? $dados_pagina->texto_antes  : '';
        $texto_depois   = isset($dados_pagina->texto_depois) ? $dados_pagina->texto_depois : '';

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('opcoes', 'prefixo', 'class', 'ajax', 'nome_botao', 'aviso_acesso', 'modo_transacao');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
            $entidade->consultar_campos($campos);
        } else {
            $entidade = util::get_entidade($classe, $campos);
        }
        self::checar_classe($entidade);

        if (isset($dados_form->ajuda)) {
            $ajuda = &$dados_form->ajuda;
        } else {
            $ajuda = "<p>Este formul&aacute;rio &eacute; respons&aacute;vel pela altera&ccedil;&atilde;o de ".$entidade->get_entidade(1)." no sistema.</p>";
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Inserir dados opcionais
        if (isset($extra['opcoes'])) {
            foreach ($extra['opcoes'] as $cod => $valor) {
                $entidade->__set($cod, $valor);
            }
        }

        // Operacoes
        if (!$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_ALTERAR);
            exit(1);
        }
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        echo $texto_antes;
        $entidade->formulario_alterar($dados, $campos, $action, $extra);
        echo $texto_depois;
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina de excluir entidades (excluir.php)
    //     $dados_form
    //     - Array[Array || String] $campos: vetor de campos exibidos para confirmacao de exclusao
    //     - Array[String] $campos_consultar: vetor de campos extra a serem consultados
    //     - String $prefixo: prefixo do ID do formulario de excluir
    //     - String $class: nome da Classe CSS
    //     - String $titulo: titulo do formulario
    //     - String $aviso: Aviso extra para o usuario
    //     - Bool $ajax: usar ajax no formulario ou nao
    //     - String $nome_botao: nome do botao do formulario
    //     - String $aviso_acesso: aviso caso o usuario nao tenha acesso ao formulario
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o formulario
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o formulario de exclusao.
    //     - objeto_formulario $entidade: entidade envolvida na exclusao
    //     - String $ajuda: ajuda do formulario
    //     - Int $modo_transacao: tipo de transacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function excluir($classe, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_form: dados opcionais ($campos, $campos_consultar, $prefixo, $class, $titulo, $aviso, $ajax, $nome_botao, $aviso_acesso, $funcao_operacoes, $ajuda, $modo_transacao, $entidade)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        // Dados do formulario
        $arquivo          = util::get_arquivo();
        $modulo           = self::get_modulo($arquivo);
        $modulo_pai       = self::get_modulo_pai($modulo);
        $modulo_rel       = self::get_modulo($arquivo, false);
        $dados            = formulario::get_dados();
        $campos           = isset($dados_form->campos)           ? $dados_form->campos           : true;
        $campos_consultar = isset($dados_form->campos_consultar) ? $dados_form->campos_consultar : array();
        $action           = isset($dados_form->action)           ? $dados_form->action           : $CFG->site;

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('titulo', 'aviso', 'prefixo', 'class', 'ajax', 'nome_botao', 'aviso_acesso', 'modo_transacao');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        $flag = OBJETO_ADICIONAR_NOMES | OBJETO_ADICIONAR_CHAVES;
        $campos_reais = objeto::get_objeto($classe)->get_campos_reais($campos, $objetos, $vetores, $flag);
        $campos_reais = array_merge($campos_reais, $campos_consultar);
        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
            $entidade->consultar_campos($campos_reais);
        } else {
            $entidade = util::get_entidade($classe, $campos_reais);
        }
        self::checar_classe($entidade);

        if (isset($dados_form->ajuda)) {
            $ajuda = &$dados_form->ajuda;
        } else {
            $ajuda = '<p>Este formul&aacute;rio destina-se a exclus&atilde;o de '.$entidade->get_entidade(1).' do sistema.</p>'.
                     '<p><strong>Aten&ccedil;&atilde;o:</strong> Os dados n&atilde;o poder&atilde;o ser recuperados ap&oacute;s a confirma&ccedil;&atilde;o.</p>';
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Operacoes
        if (!$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_EXCLUIR);
            exit(1);
        }
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        $entidade->formulario_excluir($dados, $campos, $action, $extra);
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera um formulario de relacionamento
    //     $dados_form
    //     - String $nome_vetor: nome do vetor para listar os elementos
    //     - String $classe_relacionada: nome da classe do objeto a ser relacionado
    //     - String $prefixo: prefixo do ID do formulario
    //     - Array[String] $campos: campos a serem consultados da entidade
    //     - condicao_sql $condicoes: condicoes de filtro dos elementos do vetor
    //     - Array[String] $disable: vetor de itens desabilitados
    //     - String $class: nome da Classe CSS
    //     - Bool $ajax: usar ajax no formulario ou nao
    //     - String $nome_botao: nome do botao do formulario
    //     - String $aviso_acesso: aviso caso o usuario nao tenha acesso ao formulario
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o formulario
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o formulario de relacionamento.
    //     - String $ajuda: ajuda do formulario
    //     - objeto_formulario $entidade: entidade envolvida na alteracao
    //     - Int $modo_transacao: tipo de transacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function relacionamento($classe, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_form: dados opcionais ($nome_vetor, $classe_relacionada, $prefixo, $condicoes, $disable, $class, $ajax, $nome_botao, $aviso_acesso, $funcao_operacoes, $ajuda, $entidade, $modo_transacao)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        // Dados do formulario
        $arquivo        = util::get_arquivo();
        $modulo         = self::get_modulo($arquivo);
        $modulo_pai     = self::get_modulo_pai($modulo);
        $modulo_rel     = self::get_modulo($arquivo, false);
        $dados          = formulario::get_dados();
        $campos         = isset($dados_form->campos)         ? $dados_form->campos         : true;
        $action         = isset($dados_form->action)         ? $dados_form->action         : $CFG->site;

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('condicoes', 'disable', 'prefixo', 'class', 'ajax', 'nome_botao', 'aviso_acesso', 'modo_transacao');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
            $entidade->consultar_campos($campos);
        } else {
            $entidade = util::get_entidade($classe, $campos);
        }
        self::checar_classe($entidade);

        // Nome do vetor relacionado
        if (isset($dados_form->nome_vetor)) {
            $nome_vetor = $dados_form->nome_vetor;
        } else {
            $vetores = $entidade->get_definicoes_rel_un();
            if (count($vetores) == 1) {
                $nome_vetor = array_pop(array_keys($vetores));
            } else {
                trigger_error('A classe '.$classe.' possui mais de um relacionamento 1:1', E_USER_ERROR);
            }
        }
        if (!$entidade->possui_rel_un($nome_vetor)) {
            trigger_error('A classe '.$classe.' nao possui o vetor "'.$nome_vetor.'"', E_USER_ERROR);
        }
        $def_un = $entidade->get_definicao_rel_un($nome_vetor);
        $classe_un = $def_un->classe;
        $entidade_rel = new $classe_un();

        // Classe relacionada
        if (isset($dados_form->classe_relacionada)) {
            $classe_relacionada = $dados_form->classe_relacionada;
        } else {
            $objetos = $entidade_rel->get_definicoes_rel_uu();
            if (count($objetos) == 2) {
                foreach ($objetos as $chave => $def_uu) {
                    if ($def_uu->classe == $classe) { continue; }
                    $classe_relacionada = $def_uu->classe;
                }
            } else {
                trigger_error('A classe '.$classe_un.' nao possui exatamente dois objetos relacionados', E_USER_ERROR);
            }
        }

        if (isset($dados_form->ajuda)) {
            $ajuda = &$dados_form->ajuda;
        } else {
            $ajuda = "<p>Este formul&aacute;rio &eacute; respons&aacute;vel pela associa&ccedil;&atilde;o entre ".$entidade->get_entidade(true)." e ".$entidade_rel->get_entidade(true).".</p>";
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Operacoes
        if (!$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_ALTERAR);
            exit(1);
        }
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        $entidade->formulario_relacionamento($dados, $action, $nome_vetor, $classe_relacionada, $extra);
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina de alterar entidades mediante uma confirmacao
    //     $dados_form
    //     - Array[Array || String] $campos: vetor de campos do formulario
    //     - String $prefixo: prefixo do ID do formulario de alterar
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o formulario
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o formulario de alteracao.
    //     - String $mensagem: mensagem de confirmacao
    //     - Array[String => Mixed] $opcoes: atributos a serem setados no objeto automaticamente
    //     - Array[Array || String] $campos_exibir: lista de campos a serem exibidos para confirmacao
    //     - String $class: classe CSS do formulario
    //     - Bool $ajax: usar ajax no formulario
    //     - String $nome_botao: nome do botao do formulario
    //     - String $aviso_acesso: aviso caso o usuario nao tenha acesso ao formulario
    //     - String $ajuda: ajuda do formulario
    //     - objeto_formulario_confirmacao $entidade: entidade envolvida na alteracao
    //     - Int $modo_transacao: tipo de transacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function confirmar($classe, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_form: dados opcionais ($campos, $prefixo, $funcao_operacoes, $mensagem, $opcoes, $campos_exibir, $class, $ajax, $nome_botao, $aviso_acesso, $ajuda, $entidade, $modo_transacao)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        // Dados do formulario
        $arquivo        = util::get_arquivo();
        $modulo         = self::get_modulo($arquivo);
        $modulo_pai     = self::get_modulo_pai($modulo);
        $modulo_rel     = self::get_modulo($arquivo, false);
        $dados          = formulario::get_dados();
        $mensagem       = isset($dados_form->mensagem)       ? $dados_form->mensagem       : 'Marque para confirmar';
        $campos         = isset($dados_form->campos)         ? $dados_form->campos         : array();
        $campos_exibir  = isset($dados_form->campos_exibir)  ? $dados_form->campos_exibir  : null;
        $action         = isset($dados_form->action)         ? $dados_form->action         : $CFG->site;

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('opcoes', 'prefixo', 'class', 'ajax', 'nome_botao', 'aviso_acesso', 'modo_transacao');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
        } else {
            $entidade = util::get_entidade($classe, $campos);
        }
        self::checar_classe($entidade, 'objeto_formulario_confirmacao');

        if (isset($dados_form->ajuda)) {
            $ajuda = &$dados_form->ajuda;
        } else {
            $ajuda = "<p>Este formul&aacute;rio &eacute; respons&aacute;vel pela altera&ccedil;&atilde;o de ".$entidade->get_entidade(1)." mediante uma confirma&ccedil;&atilde;o.</p>";
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Operacoes
        if (!$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_ALTERAR);
            exit(1);
        }
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        $entidade->formulario_confirmar($dados, $mensagem, $campos, $action, $extra);
        if ($campos_exibir) {
            $entidade->imprimir_dados($campos_exibir);
        }
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina de importar entidades via CSV (importar_csv.php)
    //     $dados_form
    //     - Array[String] $campos: campos obrigatorios no arquivo
    //     - Array[String => Mixed] $opcoes: dados a serem inseridos automaticamente em cada registro
    //     - String $prefixo: prefixo do ID do formulario de importar
    //     - String $class: classe CSS do formulario
    //     - String $nome_botao: nome do botao do formulario
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o formulario
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o formulario de importacao.
    //     - String $ajuda: ajuda do formulario
    //     - Int $modo_transacao: tipo de transacao
    //     - objeto_formulario $entidade: entidade responsavel por realizar a importacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function importar_csv($classe, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_form: dados opcionais ($campos, $prefixo, $class, $nome_botao, $funcao_operacoes, $ajuda, $modo_transacao, $entidade)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
        } else {
            $entidade = objeto::get_objeto($classe);
        }
        self::checar_classe($entidade);

        // Dados do formulario
        $arquivo        = util::get_arquivo();
        $modulo         = self::get_modulo($arquivo);
        $modulo_pai     = self::get_modulo_pai($modulo);
        $modulo_rel     = self::get_modulo($arquivo, false);
        $dados          = formulario::get_dados();
        $arquivos       = formulario::get_arquivos();
        $action         = isset($dados_form->action)  ? $dados_form->action : $CFG->site;

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('campos', 'opcoes', 'prefixo', 'class', 'nome_botao', 'modo_transacao');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        // Incluir chave FK em opcoes, caso nao tenha preenchido opcoes
        if (isset($dados_pagina->submodulo) && !isset($extra['opcoes'])) {
            $flag = OBJETO_IGNORAR_IMPLICITOS;
            $todos_campos = $entidade->get_campos_reais($extra['campos'], $objetos, $vetores, $flag);
            $nome_obj_pai = $dados_pagina->submodulo;
            $pos = strpos($nome_obj_pai, ':');
            $obj_pai = $entidade->get_objeto_rel_uu($nome_obj_pai);
            $classe_pai = $obj_pai->get_classe();
            if ($pos !== false) {
                $prefixo_chave = substr($nome_obj_pai, 0, $pos).':';
                $chave_fk = $prefixo_chave.$entidade->get_nome_chave_rel_uu($nome_obj_pai);
                $chave_pai = $prefixo.$obj_pai->get_chave();
            } else {
                $chave_fk = $entidade->get_nome_chave_rel_uu($nome_obj_pai);
                $chave_pai = $obj_pai->get_chave();
            }
            $valor_chave_pai = self::get_chave_session($classe_pai);
            if (!$valor_chave_pai) {
                header('Location: '.$CFG->wwwroot);
                exit(1);
            }
            $extra['opcoes'] = array(
                $chave_fk => self::get_chave_session($classe_pai)
            );
        }

        if (isset($dados_form->ajuda)) {
            $ajuda = &$dados_form->ajuda;
        } else {
            $st_campos = isset($extra['campos']) ? implode(', ', $extra['campos']) : '(nenhum)';
            $ajuda = "<p>Este formul&aacute;rio destina-se a importa&ccedil;&atilde;o de ".$entidade->get_entidade(1)." no sistema.</p>".
                     "<p>O formato do arquivo deve ser <acronym title=\"Comma-separated Values\">CSV</acronym> contendo os campos: ".
                     "{$st_campos}.</p><p>O separador padr&atilde;o e as aspas ".
                     "podem ser alterados de acordo com as caracter&iacute;sticas do arquivo.</p>".
                     "<p>Importar diretamente para o BD significa que os dados n&atilde;o passar&atilde;o por ".
                     "uma valida&ccedil;&atilde;o, logo ser&atilde;o inseridos da forma como est&atilde;o no arquivo.</p>";
            $ajuda .= "<p>Lista de campos e tipos:</p>";
            $ajuda .= "<ul>";
            foreach ($entidade->get_atributos() as $nome => $def) {
                if ($def->chave == 'PK') {
                    continue;
                }
                $ajuda .= "<li>{$nome} ({$def->tipo})</li>";
            }
            $ajuda .= "</ul>";
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Operacoes
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        $entidade->formulario_importar_csv($dados, $arquivos, $action, $extra);
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina de importar entidades via XML (importar_xml.php)
    //     $dados_form
    //     - Array[String] $campos: campos obrigatorios no arquivo
    //     - Array[String => Mixed] $opcoes: dados a serem inseridos automaticamente em cada registro
    //     - String $prefixo: prefixo do ID do formulario de importar
    //     - String $class: classe CSS do formulario
    //     - String $nome_botao: nome do botao do formulario
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o formulario
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o formulario de importacao.
    //     - String $ajuda: ajuda do formulario
    //     - Int $modo_transacao: tipo de transacao
    //     - objeto_formulario $entidade: entidade responsavel por realizar a importacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function importar_xml($classe, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_form: dados opcionais ($campos, $prefixo, $class, $nome_botao, $funcao_operacoes, $ajuda, $modo_transacao, $entidade)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
        } else {
            $entidade = objeto::get_objeto($classe);
        }
        self::checar_classe($entidade);

        // Dados do formulario
        $arquivo        = util::get_arquivo();
        $modulo         = self::get_modulo($arquivo);
        $modulo_pai     = self::get_modulo_pai($modulo);
        $modulo_rel     = self::get_modulo($arquivo, false);
        $dados          = formulario::get_dados();
        $arquivos       = formulario::get_arquivos();
        $action         = isset($dados_form->action) ? $dados_form->action : $CFG->site;

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('campos', 'opcoes', 'prefixo', 'class', 'nome_botao', 'modo_transacao');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        // Incluir chave FK em opcoes, caso nao tenha preenchido opcoes
        if (isset($dados_pagina->submodulo) && !isset($extra['opcoes'])) {
            $flag = OBJETO_IGNORAR_IMPLICITOS;
            $todos_campos = $entidade->get_campos_reais($extra['campos'], $objetos, $vetores, $flag);
            $nome_obj_pai = $dados_pagina->submodulo;
            $pos = strpos($nome_obj_pai, ':');
            $obj_pai = $entidade->get_objeto_rel_uu($nome_obj_pai);
            $classe_pai = $obj_pai->get_classe();
            if ($pos !== false) {
                $prefixo_chave = substr($nome_obj_pai, 0, $pos).':';
                $chave_fk  = $prefixo_chave.$entidade->get_nome_chave_rel_uu($nome_obj_pai);
                $chave_pai = $prefixo_chave.$obj_pai->get_chave();
            } else {
                $chave_fk = $entidade->get_nome_chave_rel_uu($nome_obj_pai);
                $chave_pai = $obj_pai->get_chave();
            }
            $valor_chave_pai = self::get_chave_session($classe_pai);
            if (!$valor_chave_pai) {
                header('Location: '.$CFG->wwwroot);
                exit(1);
            }
            $extra['opcoes'] = array(
                $chave_fk => self::get_chave_session($classe_pai)
            );
        }

        if (isset($dados_form->ajuda)) {
            $ajuda = &$dados_form->ajuda;
        } else {
            $st_campos = $campos ? implode(', ', $campos) : '(nenhum)';
            $formato = '<'.$entidade->get_tabela().">\n";
            foreach ($entidade->get_atributos() as $a) {
                $formato .= '  <'.$a->nome.'>'.$a->descricao.'</'.$a->nome.">\n";
            }
            $formato .= '</'.$entidade->get_tabela().">\n";

            $ajuda = "<p>Este formul&aacute;rio destina-se a importa&ccedil;&atilde;o de ".$entidade->get_entidade(1)." no sistema.</p>".
                     "<p>O formato do arquivo deve ser <acronym title=\"eXtensible Markup Language\">XML</acronym> contendo os campos: ".
                     "{$st_campos}.</p><p>A estrutura do XML deve seguir o modelo:</p><pre>".texto::codificar($formato).'</pre>'.
                     "<p>Importar diretamente para o BD significa que os dados n&atilde;o passar&atilde;o por ".
                     "uma valida&ccedil;&atilde;o, logo ser&atilde;o inseridos da forma como est&atilde;o no arquivo.</p>";
            $ajuda .= "<p>Lista de campos e tipos:</p>";
            $ajuda .= "<ul>";
            foreach ($entidade->get_atributos() as $nome => $def) {
                if ($def->chave == 'PK') {
                    continue;
                }
                $ajuda .= "<li>{$nome} ({$def->tipo})</li>";
            }
            $ajuda .= "</ul>";
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Operacoes
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        $entidade->formulario_importar_xml($dados, $arquivos, $action, $extra);
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina de exibir uma entidade (exibir.php)
    //     $dados_quadro
    //     - Array[String => Array || String] $campos: vetor de campos a serem exibidos no quadro
    //     - String $funcao_operacoes: nome da funcao que recebe a entidade e realiza operacoes antes de exibir o quadro
    //       A funcao deve receber um objeto da classe especificada pelo primeiro parametro deste metodo e realizar operacoes.
    //       Ela pode ser util para atribuir valores ao objeto antes de ser apresentado o quadro.
    //     - String $texto_antes: bloco HTML que deve aparecer antes do quadro
    //     - String $texto_depois: bloco HTML que deve aparecer depois do quadro
    //     - objeto $entidade: entidade envolvida na exibicao dos dados
    //     - String $ajuda: ajuda do quadro
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function exibir($classe, $dados_quadro = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // Object $dados_quadro: dados opcionais ($campos, $funcao_operacoes, $texto_antes, $texto_depois, $entidade, $ajuda)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    //
        global $CFG, $USUARIO;
        if (!$dados_quadro) { $dados_quadro = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        // Dados do quadro
        $arquivo      = util::get_arquivo();
        $modulo       = self::get_modulo($arquivo);
        $modulo_pai   = self::get_modulo_pai($modulo);
        $modulo_rel   = self::get_modulo($arquivo, false);
        $campos       = isset($dados_quadro->campos)       ? $dados_quadro->campos       : true;
        $texto_antes  = isset($dados_quadro->texto_antes)  ? $dados_quadro->texto_antes  : '';
        $texto_depois = isset($dados_quadro->texto_depois) ? $dados_quadro->texto_depois : '';

        $flag = OBJETO_ADICIONAR_NOMES | OBJETO_ADICIONAR_CHAVES;
        $campos_reais = objeto::get_objeto($classe)->get_campos_reais($campos, $objetos, $vetores, $flag);
        if (isset($dados_quadro->entidade)) {
            $entidade = $dados_quadro->entidade;
            $entidade->consultar_campos($campos_reais);
        } else {
            $entidade = util::get_entidade($classe, $campos_reais);
        }
        self::checar_classe($entidade, 'objeto');

        if (isset($dados_quadro->ajuda)) {
            $ajuda = &$dados_quadro->ajuda;
        } else {
            $ajuda = false;
        }

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Operacoes
        if (!$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_EXIBIR);
            exit(1);
        }
        if (isset($dados_quadro->funcao_operacoes) && function_exists($dados_quadro->funcao_operacoes)) {
            call_user_func($dados_quadro->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if ($ajuda) {
            mensagem::comentario($CFG->site, $ajuda);
        }
        echo $texto_antes;
        if (is_array($campos)) {
            $entidade->imprimir_dados($campos);
        } else {
            $entidade->imprimir_dados($campos, false, false);
        }
        echo $texto_depois;
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina com formulario generico
    //     $dados_form
    //     - Array[String => Array || String] $campos: campos apresentados no formulario
    //     - String $prefixo: prefixo do ID do formulario
    //     - String $funcao_operacoes: nome da funcao que realiza operacoes antes de exibir o formulario ou quadro de dados
    //       A funcao deve apenas validar os dados, executar a logica e preencher o vetor de erros ou avisos.
    //       Em geral, ela nao deve imprimir nada.
    //       Sao esperados os seguintes parametros na assinatura da funcao:
    //       - $dados: dados submetidos (obrigatorio)
    //       - $campos: campos solicitados no formulario (caso necessario)
    //       - $opcoes: campos de opcoes adicionais (caso necessario)
    //     - Array[String => Mixed] $opcoes: vetor de atributos a serem definidos automaticamente na entidade
    //     - String $class: classe CSS do formulario
    //     - Bool $ajax: indica se o formulario utiliza ajax ou nao
    //     - String $nome_botao: nome do botao de submeter os dados
    //     - String $aviso_acesso: aviso caso o usuario nao tenha acesso ao formulario
    //     - String $destino_formulario: destino do formulario ('imprimir_dados' ou 'imprimir_formulario')
    //     - String $ajuda: ajuda do formulario
    //     - objeto_formulario $entidade: entidade envolvida na operacao
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function formulario($classe, $metodo, $dados_form = false, $dados_pagina = false) {
    // String $classe: nome da classe
    // String $metodo: nome do metodo que processara os dados enviados (recebe por parametro $dados, $vt_campos e $opcoes)
    // Object $dados_form: dados opcionais ($campos, $prefixo, $funcao_operacoes, $opcoes, $class, $ajax, $nome_botao, $aviso_acesso, $destino_formulario, $ajuda, $entidade)
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    //
        global $CFG, $USUARIO;
        if (!$dados_form)   { $dados_form   = new stdClass(); }
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        // Dados do formulario
        $arquivo    = util::get_arquivo();
        $modulo     = self::get_modulo($arquivo);
        $modulo_pai = self::get_modulo_pai($modulo);
        $modulo_rel = self::get_modulo($arquivo, false);
        $dados      = formulario::get_dados();
        $campos     = isset($dados_form->campos)     ? $dados_form->campos     : true;
        $action     = isset($dados_form->action)     ? $dados_form->action     : $CFG->site;

        // Dados customizaveis
        $extra = array();
        $campos_customizaveis = array('opcoes', 'prefixo', 'class', 'ajax', 'nome_botao', 'aviso_acesso', 'destino_formulario');
        foreach ($campos_customizaveis as $campo_customizavel) {
            if (isset($dados_form->$campo_customizavel)) {
                $extra[$campo_customizavel] = $dados_form->$campo_customizavel;
            }
        }

        if (isset($dados_form->entidade)) {
            $entidade = &$dados_form->entidade;
            $entidade->consultar_campos($campos);
        } else {
            $entidade = util::get_entidade($classe, $campos);
        }
        self::checar_classe($entidade);

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo, $entidade);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }
        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Inserir dados opcionais
        if (isset($extra['opcoes'])) {
            foreach ($extra['opcoes'] as $cod => $valor) {
                $entidade->__set($cod, $valor);
            }
        }

        // Operacoes
        if (!$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_INSERIR);
            exit(1);
        }
        if (isset($dados_form->funcao_operacoes) && function_exists($dados_form->funcao_operacoes)) {
            call_user_func($dados_form->funcao_operacoes, $entidade);
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        if (isset($dados_form->ajuda)) {
            mensagem::comentario($CFG->site, $dados_form->ajuda);
        }
        $entidade->formulario_generico($dados, $campos, $action, $metodo, $extra);
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera uma pagina generica (em geral para listar opcoes ou para apresentar dados)
    //     A funcao ou metodo callback deve receber os seguintes parametros:
    //     - pagina $pagina: objeto da classe pagina que gera o hipertexto
    //     - stdClass $dados: possiveis dados submetidos pela pagina
    //     - stdClass $arquivos: possiveis arquivos submetidos pela pagina
    //     - stdClass $dados_gerais: dados enviados ao callback
    //     $dados_pagina
    //     - String $id: identificador da pagina
    //     - String $titulo: titulo da pagina
    //     - Array[String] $nav: barra de navegacao
    //     - Array[String] || String $estilos: folhas de estilos CSS
    //     - Array[String] || String $scripts: scripts em JavaScript
    //     - String $submodulo: indica que e' um submodulo, deve ser informado o nome do objeto filho da classe (usado para preencher o vetor $nav, caso nao seja preenchido)
    //     - Bool $usar_abas: indica que a pagina utiliza abas e espera que existam as variaveis globais:
    //       String $id_abas: identificador das abas, caso existam
    //       Array[String => stdClass] $abas: vetor com os dados das abas, caso existam (indexado pelo identificador da aba)
    //       String $ativa: identificador da aba ativa no momento
    //
    static public function pagina($callback, $dados_pagina = false, $dados_gerais = false) {
    // callback $callback: funcao/metodo que e' chamada pela pagina
    // Object $dados_pagina: dados opcionais ($id, $titulo, $nav, $estilos, $scripts, $submodulo, $usar_abas, $id_abas, $abas, $ativa)
    // Object $dados_gerais: dados informados ao callback
    //
        global $CFG, $USUARIO;
        if (!$dados_pagina) { $dados_pagina = new stdClass(); }

        if (!is_callable($callback)) {
            trigger_error('Callback invalido', E_USER_ERROR);
            exit(1);
        }

        // Dados do formulario
        $arquivo    = util::get_arquivo();
        $modulo     = self::get_modulo($arquivo);
        $modulo_pai = self::get_modulo_pai($modulo);
        $modulo_rel = self::get_modulo($arquivo, false);
        $dados      = formulario::get_dados();
        $arquivos   = formulario::get_arquivos();

        // Dados da Pagina
        $id = isset($dados_pagina->id) ? $dados_pagina->id : null;
        $titulo = self::get_titulo($dados_pagina, $arquivo);
        $nav = self::get_nav($dados_pagina, $modulo, $arquivo);
        if (isset($dados_pagina->estilos)) {
            $estilos = &$dados_pagina->estilos;
        } elseif (file_exists($CFG->dirmods.$modulo.'/estilos.css.php')) {
            $estilos = $CFG->wwwmods.$modulo.'/estilos.css.php';
        } else {
            $estilos = false;
        }
        if (isset($dados_pagina->scripts)) {
            $scripts = $dados_pagina->scripts;
        } else {
            $scripts = false;
        }

        $usar_abas = isset($dados_pagina->usar_abas) ? $dados_pagina->usar_abas : false;
        if ($usar_abas) {
            global $id_abas, $abas, $ativa;
            if (!isset($id_abas) || !isset($abas) || !isset($ativa)) {
                trigger_error('Nao foram definidos os parametros para as abas', E_USER_ERROR);
                return false;
            }
        }

        // Imprimir Pagina
        $pagina = new pagina($id);
        $pagina->cabecalho($titulo, $nav, $estilos, $scripts);
        $pagina->imprimir_menu($USUARIO);
        $pagina->inicio_conteudo($titulo);
        if ($usar_abas) {
            $pagina->imprimir_abas($abas, $id_abas, $ativa);
        }
        call_user_func($callback, $pagina, $dados, $arquivos, $dados_gerais);
        if ($usar_abas) {
            $pagina->fechar_abas();
        }
        $pagina->fim_conteudo();
        $pagina->rodape();
        exit(0);
    }


    //
    //     Gera os estilos do modulo
    //     $opcoes
    //     - String $icone: endereco do icone usado pelo modulo (ao lado do titulo das paginas do modulo)
    //     - Bool $com_linha: usa linhas para separar os itens das listas de entidades
    //     - Bool $vertical: coloca os nomes em uma linha e as opcoes abaixo (atalho para: com_linha = true; largura_label = 100%; largura_opcoes = 100%)
    //     A largura_label e largura_opcoes referem-se 'as listas, e a soma deles deve ser 90%
    //     - String $largura_label: largura da caixa label das listas de entidades do modulo
    //     - String $largura_opcoes: largura da caixa de opcoes das listas de entidades do modulo
    //     A largura_label_form e largura_campo_form referem-se aos formularios de inserir e alterar, e a soma deles deve ser 90%
    //     - String $largura_label_form: largura do label dos formularios (inserir ou alterar)
    //     - String $largura_campo_form: largura do campo dos formularios (inserir ou alterar)
    //
    static public function estilos($classe, $opcoes = false) {
    // String $classe: nome da classe
    // Object $opcoes: dados opcionais (icone, largura_label, largura_opcoes, largura_label_form, largura_campo_form, largura_form, com_linha)
    //
        global $CFG;
        if (!$opcoes) { $opcoes = new stdClass(); }
        $entidade = objeto::get_objeto($classe);

        // Dados do documento
        $arquivo       = util::get_arquivo();
        $modulo        = self::get_modulo($arquivo);
        $modulo_pai    = self::get_modulo_pai($modulo);
        $modulo_rel    = self::get_modulo($arquivo, false);
        $nome_entidade = texto::strip_acentos(texto::decodificar($entidade->get_entidade()));

        if (!ob_get_contents()) {
            $nome = 'estilos_'.$classe.'_'.md5(serialize($opcoes)).'.css';

            if (isset($_SESSION[__CLASS__][$nome])) {
                $ultima_mudanca = $_SESSION[__CLASS__][$nome];
            } else {
                $ultima_mudanca = $_SERVER['REQUEST_TIME'];
                $_SESSION[__CLASS__][$nome] = $ultima_mudanca;
            }

            $opcoes_http = array(
                'arquivo' => $nome,
                'tempo_expira' => TEMPO_EXPIRA,
                'compactacao' => true,
                'ultima_mudanca' => $ultima_mudanca
            );

            $last = filemtime($arquivo);
            http::cabecalho('text/css; charset='.$CFG->charset, $opcoes_http);
        }

        setlocale(LC_NUMERIC, 'C');
        echo "/* Estilos do modulo {$nome_entidade} */\n";
        if (isset($opcoes->icone)) {
            if (function_exists('getimagesize')) {
                $tamanho = getimagesize($opcoes->icone);
                $largura = $tamanho[0] ? ($tamanho[0] + 5).'px' : '25px';
            } else {
                $largura = '25px';
            }
            echo "#conteudo_principal h2.titulo {\n".
                 "  background: transparent url({$opcoes->icone}) 0% 60% no-repeat;\n".
                 "  padding-left: {$largura};\n".
                 "}\n";

        }

        // Listas
        if (isset($opcoes->vertical) && $opcoes->vertical) {
            $opcoes->largura_label  = '100%';
            $opcoes->largura_opcoes = '100%';
            $opcoes->com_linha      = true;
            echo ".lista .label,\n.lista .inativo { text-align: left; }\n";
        }
        if (isset($opcoes->largura_label)) {
            echo ".lista .label,\n.lista .inativo { width: {$opcoes->largura_label}; }\n";
        }
        if (isset($opcoes->com_linha)) {
            echo ".lista .linha { border-top: 1px solid #AAAAAA; }\n";
            echo ".lista > strong + div.linha { border: none; }\n";
        }
        if (isset($opcoes->largura_opcoes)) {
            echo ".lista .opcoes { width: {$opcoes->largura_opcoes}; }\n";
        }

        // Formularios
        if (isset($opcoes->largura_label_form)) {
            echo ".formulario div.campo label { width: {$opcoes->largura_label_form}; }\n";
        }
        if (isset($opcoes->largura_campo_form)) {
            echo ".formulario div.campo div { width: {$opcoes->largura_campo_form}; }\n";
        }
        if (isset($opcoes->largura_form)) {
            echo ".formulario { width: {$opcoes->largura_form}; }\n";
            echo ".formulario fieldset { width: 90%; }\n";
        }
    }


/// # METODOS PUBLICOS GERAIS


    //
    //     Obtem o nome do modulo
    //
    static public function get_modulo($arq, $nome_completo = true) {
    // String $arq: nome completo do arquivo que esta' dentro do modulo
    // Bool $nome_completo: obter nome completo do modulo ou apenas o nome relativo
    //
        global $CFG;
        $dir_modulo = dirname(realpath($arq));
        $modulos = basename($CFG->dirmods);
        $vt = explode(DIRECTORY_SEPARATOR, $dir_modulo);
        $modulo = array_pop($vt);
        do {
            $pop = array_pop($vt);
            if ($pop != $modulos) {
                $modulo = $pop.DIRECTORY_SEPARATOR.$modulo;
            }
        } while ($pop != $modulos && !empty($vt));
        $pos = strpos($modulo, DIRECTORY_SEPARATOR);
        if (!$nome_completo && $pos !== false) {
            $modulo = substr($modulo, $pos + 1);
        }
        if (DIRECTORY_SEPARATOR == '/') {
            $modulo = str_replace(DIRECTORY_SEPARATOR, '/', $modulo);
        }
        return $modulo;
    }


    //
    //     Obtem o nome do modulo pai
    //
    static public function get_modulo_pai($modulo) {
    // String $modulo: nome do modulo
    //
        if (DIRECTORY_SEPARATOR == '/') {
            $modulo = str_replace(DIRECTORY_SEPARATOR, '/', $modulo);
        }
        return substr($modulo, 0, strrpos($modulo, '/'));
    }


    //
    //     Obtem uma entidade via get ou via session e salva o codigo em sessao
    //
    static public function get_entidade_session($classe, $modulo = false, $campos = false) {
    // String $classe: nome da classe pai
    // String || Bool $modulo: nome do modulo ou false para obter automaticamente
    // Array[String] || Bool $campos: campos a serem consultados automaticamente, ou true para todos ou false para PK
    //
        global $USUARIO;

        if (!isset($_SESSION)) {
            trigger_error('A sessao nao foi aberta', E_USER_ERROR);
            return false;
        }

        $obj = objeto::get_objeto($classe);

        /// Obter codigo da entidade pai
        $campo = $obj->get_chave();
        $arquivo = util::get_arquivo();
        $modulo = $modulo === false ? self::get_modulo($arquivo) : $modulo;
        if (isset($_GET[$campo])) {
            $valor_campo = util::get_dado($campo, 'int');
        } elseif (isset($_SESSION[$modulo][$campo])) {
            $valor_campo = $_SESSION[$modulo][$campo];
        } else {
            $def = $obj->get_definicao_atributo($campo);
            pagina::erro($USUARIO, 'Faltou informar o campo "'.$def->descricao.'"');
        }

        // Consultar entidade
        $entidade = new $classe('', $valor_campo, $campos);
        if (isset($USUARIO) && !$entidade->pode_ser_manipulado($USUARIO)) {
            $log = new log_sistema();
            $log->inserir($USUARIO->cod_usuario, LOG_ACESSO, true, $entidade->get_valor_chave(), $entidade->get_classe(), $modulo.'/'.$arquivo);
            pagina::erro($USUARIO, ERRO_PERMISSAO);
        }

        self::set_entidade_session($entidade, $modulo);
        return $entidade;
    }


    //
    //     Define o codigo de uma entidade em sessao
    //
    static public function set_entidade_session($entidade, $modulo = false) {
    // objeto $entidade: entidade a ter sua chave salva em sessao
    // String || Bool $modulo: nome do modulo ou false para obter automaticamente
    //
        if ($modulo === false) {
            $arquivo = util::get_arquivo();
            $modulo = self::get_modulo($arquivo);
        }
        $_SESSION[$modulo][$entidade->get_chave()] = $entidade->get_valor_chave();
    }


    //
    //     Obtem o codigo de uma entidade salva em sessao com o metodo get_entidade_session
    //
    static public function get_chave_session($classe, $modulo = false) {
    // String $classe: nome da classe pai
    // String || Bool $modulo: nome do modulo ou false para obter automaticamente
    //
        if ($modulo === false) {
            $arquivo = util::get_arquivo();
            $modulo = self::get_modulo($arquivo);
        }
        $campo = objeto::get_objeto($classe)->get_chave();

        if (!isset($_SESSION[$modulo][$campo])) {
            return 0;
        }
        return $_SESSION[$modulo][$campo];
    }


/// # METODOS PRIVADOS


    //
    //     Construtor privado: utilize os metodos estaticos
    //
    private function __construct() {}


    //
    //     Checa se o objeto e' subclasse de objeto_formulario e aborta a execucao caso necessario
    //
    static private function checar_classe($obj, $classe = 'objeto_formulario') {
    // Mixed $obj: objeto a ser testado
    // String $classe: classe a ser avaliada
    //
        global $CFG, $USUARIO;
        if (!($obj instanceof $classe)) {
            $classe_obj = get_class($obj);
            pagina::erro($USUARIO, "A classe {$classe_obj} n&atilde;o &eacute; subclasse de \"{$classe}\"");
            exit(1);
        }
    }


    //
    //     Gera o titulo da pagina automaticamente
    //
    static private function get_titulo($dados_pagina, $arquivo, $entidade = false) {
    // stdClass $dados_pagina: dados da pagina
    // String $arquivo: caminho completo ao arquivo
    // objeto $entidade: entidade em questao
    //
        $titulo = false;
        if (isset($dados_pagina->titulo)) {
            $titulo = $dados_pagina->titulo;
        } elseif ($entidade) {
            switch (basename($arquivo)) {
            case 'index.php':
                if (isset($dados_pagina->submodulo)) {
                    $obj_pai = $entidade->get_objeto_rel_uu($dados_pagina->submodulo);
                    $classe_pai = $obj_pai->get_classe();
                    $obj_pai->consultar('', self::get_chave_session($classe_pai), array($obj_pai->get_campo_nome()));
                    $titulo = $entidade->get_entidade(1).' de "'.$obj_pai->get_nome().'"';
                } else {
                    $titulo = $entidade->get_entidade(1);
                }
                break;
            case 'inserir.php':
                if (isset($dados_pagina->submodulo)) {
                    $obj_pai = objeto::get_objeto($entidade->get_objeto_rel_uu($dados_pagina->submodulo)->get_classe());
                    $classe_pai = $obj_pai->get_classe();
                    $obj_pai->consultar('', self::get_chave_session($classe_pai), array($obj_pai->get_campo_nome()));
                    $titulo = 'Cadastrar '.$entidade->get_entidade().' de "'.$obj_pai->get_nome().'"';
                } else {
                    $titulo = 'Cadastrar '.$entidade->get_entidade();
                }
                break;
            case 'alterar.php':
                if (isset($dados_pagina->submodulo)) {
                    $obj_pai = objeto::get_objeto($entidade->get_objeto_rel_uu($dados_pagina->submodulo)->get_classe());
                    $classe_pai = $obj_pai->get_classe();
                    $obj_pai->consultar('', self::get_chave_session($classe_pai), array($obj_pai->get_campo_nome()));
                    $titulo = 'Alterar '.$entidade->get_entidade().' de "'.$obj_pai->get_nome().'"';
                } else {
                    $titulo = 'Alterar '.$entidade->get_entidade();
                }
                break;
            case 'excluir.php':
                if (isset($dados_pagina->submodulo)) {
                    $obj_pai = objeto::get_objeto($entidade->get_objeto_rel_uu($dados_pagina->submodulo)->get_classe());
                    $classe_pai = $obj_pai->get_classe();
                    $obj_pai->consultar('', self::get_chave_session($classe_pai), array($obj_pai->get_campo_nome()));
                    $titulo = 'Excluir '.$entidade->get_entidade().' de "'.$obj_pai->get_nome().'"';
                } else {
                    $titulo = 'Excluir '.$entidade->get_entidade();
                }
                break;
            case 'exibir.php':
                if (isset($dados_pagina->submodulo)) {
                    $obj_pai = objeto::get_objeto($entidade->get_objeto_rel_uu($dados_pagina->submodulo)->get_classe());
                    $classe_pai = $obj_pai->get_classe();
                    $obj_pai->consultar('', self::get_chave_session($classe_pai), array($obj_pai->get_campo_nome()));
                    $titulo = 'Exibir '.$entidade->get_entidade().' de "'.$obj_pai->get_nome().'"';
                } else {
                    $titulo = 'Exibir '.$entidade->get_entidade();
                }
                break;
            case 'importar_csv.php':
            case 'importar_xml.php':
                if (isset($dados_pagina->submodulo)) {
                    $obj_pai = objeto::get_objeto($entidade->get_objeto_rel_uu($dados_pagina->submodulo)->get_classe());
                    $classe_pai = $obj_pai->get_classe();
                    $obj_pai->consultar('', self::get_chave_session($classe_pai), array($obj_pai->get_campo_nome()));
                    $titulo = 'Importar '.$entidade->get_entidade(1).' para "'.$obj_pai->get_nome().'"';
                } else {
                    $titulo = 'Importar '.$entidade->get_entidade(1);
                }
                break;
            }
        }
        if (!$titulo) {
            $modulo = self::get_modulo($arquivo);
            $a = arquivo::consultar_arquivo_modulo(basename($arquivo), $modulo, array('descricao'));
            $titulo = $a->descricao;
        }
        return $titulo;
    }


    //
    //     Gera a barra de navegacao automaticamente
    //
    static private function get_nav($dados_pagina, $modulo, $arquivo) {
    // stdClass $dados_pagina: dados da pagina
    // String $modulo: nome do modulo
    // String $arquivo: caminho completo ao arquivo
    //
        $nav = array();
        if (isset($dados_pagina->nav)) {
            $nav = $dados_pagina->nav;
        } else {
            $arq = basename($arquivo);
            switch ($arq) {
            case 'index.php':
                if (isset($dados_pagina->submodulo)) {
                    $m = $modulo;
                    while ($modulo_pai = self::get_modulo_pai($m)) {
                        $subnav[] = $modulo_pai.'#index.php';
                        $m = $modulo_pai;
                    }
                    $nav[] = '#index.php';
                    foreach (array_reverse($subnav) as $item) {
                        $nav[] = $item;
                    }
                    $nav[] = $modulo.'#'.$arq;
                } else {
                    $nav[] = '#index.php';
                    $nav[] = $modulo.'#'.$arq;
                }
                break;
            default:
                if (isset($dados_pagina->submodulo)) {
                    $m = $modulo;
                    $subnav = array();
                    while ($modulo_pai = self::get_modulo_pai($m)) {
                        $subnav[] = $modulo_pai.'#index.php';
                        $m = $modulo_pai;
                    }

                    $nav[] = '#index.php';
                    foreach (array_reverse($subnav) as $item) {
                        $nav[] = $item;
                    }
                    $nav[] = $modulo.'#index.php';
                    $nav[] = $modulo.'#'.$arq;
                } else {
                    $nav[] = '#index.php';
                    $nav[] = $modulo.'#index.php';
                    $nav[] = $modulo.'#'.$arq;
                }
                break;
            }
        }
        return $nav;
    }


    //
    //     Obtem a classe CSS usada pelo link
    //
    private static function get_class_link($arquivo) {
    // String $arquivo: nome do arquivo
    //
        if (preg_match('/^inserir(_[a-z]+)*\.php$/', $arquivo)) {
            return 'inserir';
        }
        if (preg_match('/^importar(_[a-z]+)*\.php$/', $arquivo)) {
            return 'importar';
        }
        return '';
    }

}//class
