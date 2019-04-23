<?php
//
// SIMP
// Descricao: Classe que controla o layout da pagina
// Autor: Rubens Takiguti Ribeiro && Rodrigo Pereira Moreira
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.79
// Data: 21/05/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//

// Constantes
define('PAGINA_PRINT', true);   // Layout para impressao

final class pagina {
    private $id_pagina;

    private static $imprimiu_cabecalho       = false;
    private static $imprimiu_menu            = false;
    private static $imprimiu_rodape          = false;
    private static $imprimiu_inicio_conteudo = false;
    private static $imprimiu_fim_conteudo    = false;
    private static $contador_abas            = 0;
    public  static $salvou_cookies           = -1;

    // Lista de arquivos RSS
    private $rss;


    //
    //     Construtor padrao
    //
    public function __construct($id_pagina = false) {
    // String $id_pagina: ID da pagina (colocado na tag body)
    //
        $this->id_pagina = $id_pagina;
        $this->rss = array();
    }


    //
    //     Destrutor padrao
    //
    public function __destruct() {
        while (self::$contador_abas) {
            self::fechar_abas();
        }
        if (self::$imprimiu_cabecalho && !self::$imprimiu_rodape) {
            $e = "Erro ao imprimir rodap&eacute;: Rodap&eacute; omitido";
            mensagem::erro($e);
            $this->rodape();
        }
    }


    //
    //     Checa se o navegador pode aplicar folha de estilos
    //
    static public function pode_css() {
        global $CFG;
        static $pode = 0;
        if ($pode !== 0) { return $pode; }

        // Se o navegador nem da suporte: nao pode
        if (!$CFG->agent->css) {
            $pode = false;
            return false;
        }

        $versao = explode('.', $CFG->agent->versao_navegador);

        // Se o navegador da suporte, checar a versao
        switch (strtolower($CFG->agent->navegador)) {
        case 'mozilla':
            $necessario = array(1, 5);
            break;
        case 'firefox':
            $necessario = array(1, 5);
            break;
        case 'ie':
            $necessario = array(4, 0);
            break;
        case 'opera':
            $necessario = array(7, 0);
            break;
        default:
            $pode = true;
            return true;
        }

        // Se a versao e' muito antiga: nao pode
        if (intval($versao[0]) < $necessario[0]) {
            $pode = false;
            return false;
        } elseif (intval($versao[0]) == $necessario[0]) {
            if (intval($versao[1]) < $necessario[1]) {
                $pode = false;
                return false;
            }
        }

        // Se nao conhece ou a versao e' compativel: pode
        $pode = true;
        return true;
    }


    //
    //     Adiciona um RSS da pagina
    //
    public function adicionar_rss($link, $descricao = '') {
    // String $link: link do Feed
    // String $descricao: descricao do Feed
    //
        $this->rss[$link] = $descricao;
    }


    //
    //     Imprime o cabecalho dos arquivos
    //
    public function cabecalho($titulo = '', $nav = array(), $estilos = false, $scripts = false) {
    // String $titulo: titulo da pagina
    // Array[String => String] $nav: vetor associativo de modulos e scripts
    // Array[String] || String $estilos: vetor de arquivos CSS ou nome do arquivo para adicionar
    // Array[String] || String $scripts: vetor de arquivos JavaScript ou nome do arquivo para adicionar
    //
        global $CFG;

        // Nao pode chamar a funcao mais de uma vez
        if (self::$imprimiu_cabecalho) {
            $e = "Erro ao imprimir cabe&ccedil;alho: Cabe&ccedil;alho duplicado";
            mensagem::erro($e);
            return;
        }
        self::$imprimiu_cabecalho = true;

        // Determinar se obter o nome do BD ou do proprio vetor nav
        $bd = true;
        if (!empty($nav)) {
            $chaves = array_keys($nav);
            $bd = is_int(array_pop($chaves));
        }

        // Definir estilos extras
        if (!DEVEL_BLOQUEADO) {
            if (is_string($estilos)) {
                $estilos = array($estilos);
            } elseif (!$estilos) {
                $estilos = array();
            }
            $estilos[] = $CFG->wwwlayout.'devel.css.php';
        }

        // Titulo
        if (!empty($titulo)) {
            $t = $CFG->titulo.' - '.strip_tags($titulo);
        } else {
            $t = $CFG->titulo.' - '.$CFG->descricao;
        }
        $t = texto::codificar($t);

        // Descricao
        $descricao = texto::codificar($CFG->descricao);

        // Nome do arquivo
        $nome = str_replace('.php', '.xhtml', basename($_SERVER['SCRIPT_FILENAME']));
        if ($CFG->xml) {
            $nome = str_replace('.xhtml', '.xml', $nome);
        }

        // Salvar cookies
        self::$salvou_cookies = cookie::salvar($CFG->cookies);

        // Se o servidor esta' muito ocupado
        if ($CFG->load_avg > LOAD_AVG_MAX_ALERTA) {
            $this->sistema_indisponivel();
            exit(1);
        }

        // HEADER HTTP
        $opcoes_http = array(
            'arquivo' => $nome,
            'compactacao' => true,
            'tempo_expira' => 0,
            'extra' => array(
                'Content-Script-Type' => 'text/javascript; charset='.$CFG->charset,
                'Content-Style-Type'  => 'text/css; charset='.$CFG->charset
            )
        );
        http::cabecalho($CFG->content.'; charset='.$CFG->charset, $opcoes_http);

        // INICIO DO CODIGO
        if ($CFG->content != 'text/html') {
            echo "<?xml version=\"1.0\" encoding=\"{$CFG->charset}\" standalone=\"no\"?>\n";
            if (self::pode_css()) {
                if (!$CFG->agent->movel) {
                    if ($CFG->pessoal->tema) {
                        echo "<?xml-stylesheet href=\"{$CFG->wwwlayout}{$CFG->pessoal->tema}/index.css.php\" type=\"text/css\" title=\"Layout {$CFG->pessoal->tema}\" media=\"screen\" charset=\"{$CFG->charset}\" alternate=\"no\"?>\n";
                    }
                    echo "<?xml-stylesheet href=\"{$CFG->wwwlayout}pessoal.css.php\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" alternate=\"no\"?>\n";

                    // Estilos das pagina
                    if ($estilos) {
                        if (is_array($estilos)) {
                            foreach ($estilos as $e) {
                                echo "<?xml-stylesheet href=\"{$e}\" type=\"text/css\" media=\"screen\" charset=\"{$CFG->charset}\" alternate=\"no\"?>\n";
                            }
                        } else {
                            echo "<?xml-stylesheet href=\"{$estilos}\" type=\"text/css\" media=\"screen\" charset=\"{$CFG->charset}\" alternate=\"no\"?>\n";
                        }
                    }

                    if (PAGINA_PRINT) {
                        echo "<?xml-stylesheet href=\"{$CFG->wwwlayout}print.css.php\" type=\"text/css\" media=\"print\" charset=\"{$CFG->charset}\" alternate=\"no\"?>\n";
                    }
                } else {
                    echo "<?xml-stylesheet href=\"{$CFG->wwwlayout}handheld.css.php\" type=\"text/css\" media=\"all\" charset=\"{$CFG->charset}\" alternate=\"no\"?>\n";
                }
            }
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"{$CFG->wwwroot}dtd/xhtml1-20020801/DTD/xhtml1-strict.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$CFG->lingua}\" dir=\"ltr\">\n";
        } else {
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"{$CFG->wwwroot}dtd/xhtml1-20020801/DTD/xhtml1-strict.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"{$CFG->lingua}\" xml:lang=\"{$CFG->lingua}\" dir=\"ltr\">\n";
        }
        echo "<head profile=\"{$CFG->wwwroot}rdf/descricao.rdf.php\">\n";
        echo "  <title>{$t}</title>\n";
        if (isset($CFG->wwwroot) && $CFG->wwwroot) {
            echo "  <base id=\"wwwroot\" href=\"{$CFG->wwwroot}\" />\n";
        }

        if (!$CFG->xml) {
            echo "\n  <!-- METADADOS -->\n";
            echo "  <meta http-equiv=\"content-type\" content=\"{$CFG->content}; charset={$CFG->charset}\" />\n";
            echo "  <meta name=\"title\" xml:lang=\"{$CFG->lingua}\" content=\"{$t}\" />\n";
            echo "  <meta name=\"description\" xml:lang=\"{$CFG->lingua}\" content=\"{$descricao}\" />\n";
            echo "  <meta name=\"keywords\" xml:lang=\"{$CFG->lingua}\" content=\"{$CFG->palavras}\" />\n";
            echo "  <meta name=\"author\" content=\"{$CFG->autor}\" />\n";
            echo "  <meta name=\"language\" content=\"{$CFG->lingua}\" />\n";
            echo "  <meta name=\"robots\" content=\"index,follow\" />\n";
            echo "  <meta name=\"generator\" content=\"SIMP ".VERSAO_SIMP."\" />\n";

            echo "\n  <!-- LINKS -->\n";
            echo "  <link rel=\"home\" type=\"{$CFG->content}\" charset=\"{$CFG->charset}\" href=\"{$CFG->wwwroot}\" title=\"Home\" />\n";
            echo "  <link rel=\"help\" type=\"{$CFG->content}\" charset=\"{$CFG->charset}\" href=\"{$CFG->wwwmods}ajuda/index.php\" title=\"Ajuda\" />\n";
            echo "  <link rel=\"glossary\" type=\"{$CFG->content}\" charset=\"{$CFG->charset}\" href=\"{$CFG->wwwmods}ajuda/glossario.php\"  title=\"Gloss&aacute;rio\" />\n";
            echo "  <link rel=\"author\" type=\"{$CFG->content}\" charset=\"{$CFG->charset}\" href=\"{$CFG->wwwmods}ajuda/creditos.php\" title=\"Cr&eacute;ditos\" />\n";
            echo "  <link rel=\"copyright\" type=\"{$CFG->content}\" charset=\"{$CFG->charset}\" href=\"{$CFG->wwwmods}ajuda/licenca.php\" title=\"Licen&ccedil;a\" />\n";
            echo "  <link rel=\"icon\" type=\"image/x-icon\" href=\"{$CFG->wwwroot}favicon.ico\" title=\"&Iacute;cone\" />\n";
            echo "  <link rel=\"meta\" type=\"application/rdf+xml\" href=\"{$CFG->wwwroot}rdf/descricao.rdf.php\" title=\"Descri&ccedil;&atilde;o\" />\n";
        }

        // Se o navegador suporta CSS
        if (self::pode_css()) {
            if ($CFG->content == 'text/html') {

                echo $CFG->xml ? '' : "\n  <!-- ESTILOS -->\n";
                if (!$CFG->agent->movel) {
                    if ($CFG->pessoal->tema) {
                        echo "  <link rel=\"stylesheet\" type=\"text/css\" charset=\"utf-8\" media=\"screen\" href=\"{$CFG->wwwlayout}{$CFG->pessoal->tema}/index.css.php\" />\n";
                    }

                    // Estilos das pagina
                    if ($estilos) {
                        if (is_array($estilos)) {
                            foreach ($estilos as $e) {
                                echo "  <link rel=\"stylesheet\" type=\"text/css\" charset=\"utf-8\" media=\"screen\" href=\"{$e}\" />\n";
                            }
                        } else {
                            echo "  <link rel=\"stylesheet\" type=\"text/css\" charset=\"utf-8\" media=\"screen\" href=\"{$estilos}\" />\n";
                        }
                    }

                    if (PAGINA_PRINT) {
                        echo "  <link rel=\"stylesheet\" type=\"text/css\" charset=\"utf-8\" media=\"print\" href=\"{$CFG->wwwlayout}print.css.php\" />\n";
                    }
                } else {
                    echo "  <link rel=\"stylesheet\" type=\"text/css\" charset=\"utf-8\" media=\"all\" href=\"{$CFG->wwwlayout}handheld.css.php\" />\n";
                }

                // Configuracoes pessoais
                echo "  <link rel=\"stylesheet\" type=\"text/css\" charset=\"utf-8\" media=\"screen\" href=\"{$CFG->wwwlayout}pessoal.css.php\" />\n";
            }
        } else {
            echo $CFG->xml ? '' : "\n  <!-- NAVEGADOR SEM SUPORTE A CSS -->\n";
        }

        // Se o navegador suporta JavaScript
        if ($CFG->agent->javascript) {

            echo $CFG->xml ? '' : "\n  <!-- SCRIPTS -->\n";
            if (file_exists($CFG->dirroot.'javascript/javascript.js')) {
                echo "  <script type=\"text/javascript\" charset=\"utf-8\" defer=\"defer\" src=\"{$CFG->wwwroot}javascript/javascript.js\" xml:space=\"preserve\"></script>\n";
            } else {
                echo "  <script type=\"text/javascript\" charset=\"utf-8\" defer=\"defer\" src=\"{$CFG->wwwroot}javascript/javascript.js.php\" xml:space=\"preserve\"></script>\n";
            }

            if ($scripts) {
                if (is_string($scripts)) {
                    $scripts = array($scripts);
                }
                foreach ($scripts as $s) {
                    echo "  <script type=\"text/javascript\" charset=\"utf-8\" defer=\"defer\" src=\"{$s}\" xml:space=\"preserve\"></script>\n";
                }
            }
        } else {
            echo $CFG->xml ? '' : "\n  <!-- NAVEGADOR SEM SUPORTE A JAVASCRIPT -->\n";
        }

        // Feeds RSS
        if ($this->rss) {
            echo $CFG->xml ? '' : "\n  <!-- FEEDS RSS -->\n";
            foreach ($this->rss as $link => $descricao_link) {
                $descricao_link = texto::codificar($descricao_link);
                $title = $descricao_link ? "title=\"{$descricao_link}\"" : '';
                echo "  <link rel=\"alternate\" type=\"application/rss+xml\" href=\"{$link}\" {$title}/>\n";
            }
        }

        echo "</head>\n";

        $t = texto::codificar($CFG->titulo);
        $id_body = $this->id_pagina ? " id=\"{$this->id_pagina}\"" : '';
        echo "<body{$id_body}>\n";
        echo "<div id=\"container\">\n\n";

        if (!$CFG->xml) {
            echo "<!-- TITULO -->\n";
            echo "<div id=\"titulo_pagina\">\n";
            echo "  <h1><a accesskey=\"C\" href=\"{$CFG->wwwroot}\" title=\"{$descricao} v.{$CFG->versao}\">{$t}</a></h1>\n";
            echo "  <em>{$descricao}</em>\n";
            echo "</div>\n";
            echo "<!-- FIM TITULO -->\n\n";
        }

        $this->imprimir_navegacao($nav, $bd, $t);
        $hr = ($CFG->agent->engine == 'mshtml' && (int)$CFG->agent->versao_navegador < 7) ? '' : "<hr />\n\n";
        echo $hr;

        echo $CFG->xml ? '' : "<!-- CONTEUDO -->\n";
        echo "<div id=\"conteudo\">\n";
    }


    //
    //     Imprime a barra de navegacao
    //
    private function imprimir_navegacao($nav, $bd = true, $titulo = '') {
    // Array[String] || Array[String => String] $nav: vetor de nome de modulos e arquivos ou vetor de links e nomes
    // Bool $bd: obter os dados no BD ou diretamente do vetor
    // String $titulo: titulo usado caso o vetor esteja vazio
    //
        global $CFG, $USUARIO;
        echo $CFG->xml ? '' : "<!-- BARRA DE NAVEGACAO -->\n";
        echo "<div id=\"navegacao\">\n";
        echo "  <strong class=\"hide\">Navega&ccedil;&atilde;o:</strong>\n";
        if (is_array($nav) && !empty($nav)) {

            // Obter os dados do bd
            if ($bd) {
                $ultimo = count($nav) - 1;
                foreach ($nav as $i => $modulo_arquivo) {
                    $parametros = '';

                    list($modulo, $arquivo) = explode('#', $modulo_arquivo);
                    if (DIRECTORY_SEPARATOR != '/') {
                        $modulo = str_replace(DIRECTORY_SEPARATOR, '/', $modulo);
                    }
                    $pos = strpos($arquivo, '?');
                    if ($pos !== false) {
                        $parametros = substr($arquivo, $pos + 1);
                        $arquivo = substr($arquivo, 0, $pos);
                    }
                    $dados_arq = false;
                    if (is_object($USUARIO) && $USUARIO->existe()) {
                        $dados_arq = $USUARIO->get_arquivo($modulo, $arquivo);
                    }
                    if (!$dados_arq) {
                        $dados_arq = arquivo::consultar_arquivo_modulo($arquivo, $modulo, array('descricao'));
                    }

                    if ($modulo) {
                        $link = $CFG->wwwmods.$modulo.'/'.$arquivo;
                    } else {
                        $link = $CFG->wwwroot.$arquivo;
                    }
                    if ($parametros) {
                        $link .= '?'.$parametros;
                    }

                    $descricao = $dados_arq->exibir('descricao');
                    if ($i < $ultimo) {
                        link::texto($link, $descricao);
                        echo " <em>&raquo;</em>\n";
                    } else {
                        echo "  <span title=\"{$descricao}\">{$descricao}</span>\n";
                    }
                }

            // Usar os dados do proprio vetor
            } else {
                foreach ($nav as $link => $nome) {
                    if (!empty($link)) {
                        link::texto($link, $nome);
                        echo "  <em>&raquo;</em>\n";
                    } else {
                        echo "  <span title=\"{$nome}\">{$nome}</span>\n";
                    }
                }
            }
        } else {
            echo "  <span title=\"{$titulo}\">{$titulo}</span>\n";
        }
        echo "</div>\n";
        echo $CFG->xml ? '' : "<!-- FIM BARRA DE NAVEGACAO -->\n\n";
    }


    //
    //     Imprime o menu baseado nos grupos do usuario
    //
    public function imprimir_menu(&$usuario, $return = false) {
    // Object $usuario: usuario para o qual o menu e' apresentado
    // Bool $return: retornar ou imprimir o menu
    //
        global $CFG;

        if ($CFG->xml) {
            return;
        }

        // O cabecalho tem que esta inicializado
        if (!self::$imprimiu_cabecalho) {
            $this->cabecalho();
            $e = "Erro ao imprimir o Menu: Cabe&ccedil;alho omitido.\n";
            mensagem::erro($e);

        // Nao pode chamar a funcao mais de uma vez
        } elseif (self::$imprimiu_menu) {
            $e = "Erro ao imprimir o Menu: Menu duplicado.\n";
            mensagem::erro($e);
            return;
        }
        self::$imprimiu_menu = true;

        // Imprimir menu
        $opcoes = array();

        $m  = "<div id=\"conteudo_secundario\">\n\n";

        $m .= "<!-- MENU -->\n";
        $m .= "<div id=\"menu\">\n";
        $m .= "  <h2 class=\"hide\">Menu de Op&ccedil;&otilde;es</h2>\n";
        foreach ($usuario->grupos as $usuario_grupo) {
            $grupo = &$usuario_grupo->grupo;
            $buf = '';
            $entrou = false;

            $buf .= "  <strong>{$grupo->nome}</strong>\n";
            $buf .= "  <ul>\n";
            foreach ($grupo->permissoes as $permissao) {
                if (!$permissao->visivel) { continue; }
                $descricao = $permissao->arquivo->exibir('descricao');
                $link      = $permissao->arquivo->link;

                if (!isset($opcoes[$link])) {
                    $buf .= "    <li><span class=\"hide\">[</span>".
                            link::texto($link, $descricao, $descricao, '', '', 1).
                            "<span class=\"hide\">]</span></li>\n";
                    $opcoes[$link] = 1;
                    $entrou = true;
                }
            }
            $buf .= "  </ul>\n";

            // Se tem alguma opcao
            if ($entrou) {
                $m .= $buf;
            } else {
                $m .= "  <strong>{$grupo->nome}</strong>\n".
                      "  <p>Nenhuma Op&ccedil;&atilde;o</p>\n";
            }
        }

        // Se nao faz parte de nenhum grupo
        $possui_opcoes = !empty($opcoes);
        if (!$possui_opcoes) {
            if (empty($usuario->grupos)) {
                $admin = new usuario('login', 'admin');
                $email_admin = $admin->email;

                $m .= "  <strong>Aviso</strong>\n";
                $m .= "  <p>Voc&ecirc; n&atilde;o faz parte de nenhum grupo. Solicite ao administrador do ";
                $m .= "Sistema para acrescent&aacute;-lo(a) no(s) grupo(s) necess&aacute;rio(s).</p>\n";
                $m .= "  <p>E-mail: {$email_admin}</p>\n";
            }
        }

        $id = ($CFG->agent->engine == 'mshtml' && (int)$CFG->agent->versao_navegador < 7) ? ' id="rodape_menu"' : '';
        $m .= "  <div{$id}>\n";
        $m .= "    <p>\n";

        // Editar
        if ($possui_opcoes) {
            $l = $CFG->wwwmods.'usuarios/alterar.php';
            $m .= link::texto($l, $usuario->login, 'Alterar Dados Pessoais', 'login_usuario', '', 1).'<span> | </span>';
        } else {
            $m .= "<em id=\"login_usuario\">{$usuario->login}</em><span> | </span>";
        }

        // Opcoes
        $l = $CFG->wwwmods.'config_pessoal/index.php';
        $m .= link::texto($l, 'Op&ccedil;&otilde;es', 'Op&ccedil;&otilde;es Pessoais', 'opcoes', '', 1).'<span> | </span>';

        // Ajuda
        $l = $CFG->wwwmods.'ajuda/index.php';
        $m .= link::texto($l, 'Ajuda', 'Ajuda', 'ajuda', '', 1).'<span> | </span>';

        // Sair
        $l = $CFG->wwwmods.'login/sair.php';
        $m .= "      <a id=\"saida\" href=\"{$l}\" title=\"Sair do Sistema\">Sair</a>\n";

        $m .= "    </p>\n";
        $m .= "    <p><em id=\"data_local\">".strftime($CFG->formato_data, $CFG->time)."</em> - <em id=\"hora_local\">".strftime('%H:%M', $CFG->time)."</em></p>\n";
        $m .= "  </div>\n";

        $m .= "</div>\n";
        $m .= "<!-- FIM MENU -->\n\n";

        if (!isset($_COOKIE['omitir_avisos'])) {
            $m .= '<p id="aviso_sair">';
            $m .= 'Depois de usar o sistema, clique em "Sair" no menu principal.';
            $m .= ' <a href="#aviso_sair" onclick="setcookie(\'omitir_avisos\', 1, 30); this.parentNode.style.display=\'none\'; return false;">[omitir este aviso]</a>';
            $m .= '</p>';
        }
        $m  .= "</div>\n\n";//id conteudo_secundario

        $hr = ($CFG->agent->engine == 'mshtml' && (int)$CFG->agent->versao_navegador < 7) ? '' : "<hr />\n\n";
        $m .= $hr;

        if ($return) {
            return $m;
        }
        echo $m;
    }


    //
    //     Imprime o rodape da pagina
    //
    public function rodape() {
        global $CFG;

        // O cabecalho tem que esta inicializado
        if (!self::$imprimiu_cabecalho) {
            $this->cabecalho();
            $e = "Erro ao imprimir o Rodap&eacute;: Cabe&ccedil;alho omitido.\n";
            mensagem::erro($e);

        // Se imprimiu incio de conteudo e nao o fechou
        } elseif (self::$imprimiu_inicio_conteudo && !self::$imprimiu_fim_conteudo) {
            $e = "Erro ao imprimir o Rodap&eacute;: Fim de conte&uacute;do omitido.\n";
            mensagem::erro($e);
            $this->fim_conteudo();
        }

        // Nao pode chamar a funcao mais de uma vez
        if (self::$imprimiu_rodape) {
            $e = "Erro ao imprimir o Rodap&eacute;: Rodap&eacute; duplicado.\n";
            mensagem::erro($e);
            return;
        }
        self::$imprimiu_rodape = true;

        // Obter SGBD e PHP
        $bd = new objeto_dao();
        try {
            $sgbd = $bd->get_nome();
            $sgbdv = $bd->get_versao();
            $sgbdv = $sgbdv ? ' v.'.$sgbdv : '';
            $sgbdv = DEVEL_BLOQUEADO ? $sgbd : $sgbd.$sgbdv;
        } catch (Exception $e) {
            $sgbd = '[SGBD indefinido]';
            $sgbdv = '?';
        }
        $php = DEVEL_BLOQUEADO ? 'PHP' : 'PHP v.'.phpversion();

        echo "</div>\n";
        echo $CFG->xml ? '' : "<!-- FIM CONTEUDO -->\n\n";

        $hr = ($CFG->agent->engine == 'mshtml' && (int)$CFG->agent->versao_navegador < 7) ? '' : "<hr />\n\n";
        echo $hr;

        if (!$CFG->xml) {
            echo "<!-- RODAPE -->\n";
            echo "<div id=\"rodape\">\n";
            echo "  <a id=\"voltar_topo\" href=\"{$CFG->site}#titulo_pagina\">Voltar ao Topo</a>\n";
            echo "  <h2 class=\"hide\">Cr&eacute;ditos</h2>\n";
            echo "  <p>Este sistema est&aacute; protegido sob os termos da Licen&ccedil;a ";
            echo "<a href=\"http://www.gnu.org/\"><acronym title=\"GNU is Not Unix\">GNU</acronym></a><span>-</span>";
            echo "<a href=\"http://www.gnu.org/licenses/old-licenses/gpl-2.0.html\" title=\"GPL v.2\"><acronym title=\"General Public License\">GPL</acronym> 2</a></p>\n";
            echo "  <div>\n";
            echo "    Desenvolvido por ";
            if (isset($CFG->link_autor) && !empty($CFG->link_autor)) {
                echo "<a id=\"autor_sistema\" href=\"{$CFG->link_autor}\" title=\"{$CFG->autor}\">{$CFG->autor}</a>";
            } else {
                echo $CFG->autor;
            }
            echo " com o ";
            echo "    <acronym title=\"SIMP v.".VERSAO_SIMP."\">SIMP</acronym>\n";
            echo "  </div>\n";
            echo "  <p>\n";
            if (function_exists('apache_get_version')) {
                $apache = DEVEL_BLOQUEADO ? 'Apache' : apache_get_version();
                echo "    <acronym title=\"{$apache}\">Apache</acronym> + \n";
            }
            echo "    <acronym title=\"{$php}\">PHP</acronym> + \n";
            echo "    <acronym title=\"{$sgbdv}\">{$sgbd}</acronym> + \n";
            if ($CFG->ajax) {
                echo "    <acronym title=\"Asynchronous Javascript And XML\">Ajax</acronym> + \n";
            }
            if ($CFG->content == 'text/html') {
                echo "    <acronym title=\"HyperText Markup Language v.4.01\">HTML</acronym> + \n";
            } elseif (strpos($CFG->content, 'xhtml') !== false) {
                echo "    <acronym title=\"eXtensible HyperText Markup Language v.1.0\">XHTML</acronym> + \n";
            }
            echo "    <acronym title=\"Cascading Style Sheet v.3\">CSS</acronym>\n";
            echo "  </p>\n";

            // Alerta do LOAD AVG
            if ($CFG->load_avg > LOAD_AVG_MIN_ALERTA) {
                echo '<p class="vermelho">';
                echo '<strong>Alerta de sobrecarga do servidor (';
                echo 'processamento: '.$CFG->load_avg.' / ';
                echo 'esperado: abaixo de '.LOAD_AVG_MIN_ALERTA.' / ';
                echo 'alerta: entre '.LOAD_AVG_MIN_ALERTA.' e '.LOAD_AVG_MAX_ALERTA;
                echo ")!</strong></p>\n";
            }
            echo "</div>\n";
            echo "<!-- FIM RODAPE -->\n\n";
        }

        echo "</div>\n";
        echo "</body>\n";
        echo "</html>\n";

        // Calcular estatisticas
        $vt_estatisticas = array();
        $tempo_carregamento = round(microtime(1) - $CFG->microtime, 3);
        $vt_estatisticas['Carregamento'] = 'Carregamento: '.$tempo_carregamento.' segundos';
        $vt_estatisticas['Load AVG'] = 'Load AVG: '.$CFG->load_avg;
        if (function_exists('memory_get_usage')) {
            $vt_estatisticas['Memoria'] = 'Memoria: '.memoria::formatar_bytes(memory_get_usage(true));
        }
        if (function_exists('memory_get_peak_usage')) {
            $pico_memoria = memory_get_peak_usage(true);
            $vt_estatisticas['Pico'] = 'Pico: '.memoria::formatar_bytes($pico_memoria);
        } else {
            $pico_memoria = 0;
        }
        $quantidade_sql = driver_base::get_quantidade_instrucoes();
        $vt_estatisticas['SQL'] = 'SQL: '.$quantidade_sql;
        $vt_estatisticas['Classes'] = 'Classes: '.$CFG->classes_carregadas;
        echo '<!-- '.implode(' / ', $vt_estatisticas).' -->';

/*
        $conteudo = ob_get_contents();
        ob_end_clean();

        $dom = new DOMDocument();
        $dom->xmlVersion = '1.0';
        $dom->formatOutput = false;
        $dom->xmlStandalone = false;
        $dom->preserveWhiteSpace = false;
        $dom->validateOnParse = false;
        $dom->resolveExternals = true;
        if ($CFG->xml) {
            $dom->substituteEntities = true;
        }
        $dom->loadXML($conteudo);
        if ($CFG->xml) {
            $d = $dom->firstChild;
            $p = $d;
            while ($p) {
                $p = $d->nextSibling;
                if ($d->nodeType == XML_DOCUMENT_TYPE_NODE) {
                    $dom->removeChild($d);
                }
                $d = $p;
            }
        }
        echo $dom->saveXML();
*/
    }


    //
    //     Inicio do Conteudo da Pagina
    //
    public function inicio_conteudo($titulo = '') {
    // String $titulo: titulo da pagina
    //
        global $CFG;

        // O Cabecalho deve ter sido inicializado
        if (!self::$imprimiu_cabecalho) {
            $this->cabecalho();
            $e = "Erro ao imprimir In&iacute;cio de Conte&uacute;do: Cabe&ccedil;alho omitido.\n";
            mensagem::erro($e);

        // Nao pode chamar a funcao mais de uma vez
        } elseif (self::$imprimiu_inicio_conteudo) {
            $e = "Erro ao imprimir In&iacute;cio de Conte&uacute;do: Conte&uacute;do duplicado.\n";
            mensagem::erro($e);
            return;
        }
        self::$imprimiu_inicio_conteudo = true;

        echo "<div id=\"conteudo_principal\">\n\n";

        echo $CFG->xml ? '' : "<!-- CENTRO -->\n";
        echo "<div id=\"centro\">\n";
        if ($titulo) {
            $this->imprimir_titulo($titulo);
        }
    }


    //
    //     Fim do Conteudo da Pagina
    //
    public function fim_conteudo() {
        global $CFG;

        // O inicio de conteudo deve ter sido inicializado
        if (!self::$imprimiu_inicio_conteudo) {
            $this->inicio_conteudo();
            $e = "Erro ao imprimir Fim de Conte&uacute;do: In&iacute;cio de Conte&uacute;do omitido.\n";
            mensagem::erro($e);

        // Nao pode chamar a funcao mais de uma vez
        } elseif (self::$imprimiu_fim_conteudo) {
            $e = "Erro ao imprimir Fim de Conte&uacute;do: Fim de Conte&uacute;do duplicado.\n";
            mensagem::erro($e);
            return;
        }
        self::$imprimiu_fim_conteudo = true;

        // Mostrar blocos de desenvolvimento
        if (!DEVEL_BLOQUEADO) {
            self::imprimir_bloco_desempenho();
            self::imprimir_bloco_devel();
        }

        echo "</div>\n";
        echo $CFG->xml ? '' : "<!-- FIM CENTRO -->\n\n";

        echo "</div>\n\n";//id conteudo_principal
    }


    //
    //     Imprime o titulo da pagina
    //
    public function imprimir_titulo($titulo, $return = false) {
    // String $titulo: titulo da pagina
    // Bool $return: retornar ou imprimir o titulo
    //
        // O inicio de Conteudo deve ter sido inicializado
        if (!self::$imprimiu_inicio_conteudo) {
            $this->inicio_conteudo();
            $e = "Erro ao imprimir T&iacute;tulo: In&iacute;cio de Conte&uacute;do omitido.\n";
            mensagem::erro($e);
        }
        $t = "<h2 class=\"titulo\">{$titulo}</h2>\n";
        if ($return) {
            return $t;
        }
        echo $t;
    }


    //
    //     Imprime um sub-titulo da pagina
    //
    public function imprimir_subtitulo($subtitulo, $return = false) {
    // String $subtitulo: subtitulo
    // Bool $return: retornar ou imprimir o subtitulo
    //
        // O inicio de Conteudo deve ter sido inicializado
        if (!self::$imprimiu_inicio_conteudo) {
            $this->inicio_conteudo();
            $e = "Erro ao imprimir Subt&iacute;tulo: In&iacute;cio de Conte&uacute;do omitido.\n";
            mensagem::erro($e);
        }
        $t = "<h3 class=\"subtitulo\">{$subtitulo}</h3>\n";
        if ($return) {
            return $t;
        }
        echo $t;
    }


    //
    //     Imprime abas
    //     O primeiro parametro deve ser um vetor de stdClass com os possiveis atributos:
    //     - String id = Identificador unico da aba
    //     - String link = Link para mudar de aba
    //     - String arquivo = Arquivo
    //     - String nome = Nome da aba
    //     - Bool foco = Flag indicando se deve ser dado o foco ao mudar de aba
    //     - String class = Classe CSS (opcional)
    //
    public function imprimir_abas($vt_abas, $id, $ativa = 0, $return = false) {
    // Array[String => String] $vt_abas: vetor associativo de abas
    // String $id: Id da caixa de abas
    // Int $ativa: numero da aba ativa
    // Bool $return: retornar ou imprimir o titulo
    //

        // O inicio de conteudo deve ter sido inicializado
        if (!self::$imprimiu_inicio_conteudo) {
            $this->inicio_conteudo();
            $e = "Erro ao imprimir Abas: Inico de conteudo omitido.\n";
            mensagem::erro($e);
        }
        self::$contador_abas++;

        $imprimiu_ativa = false;
        $abas = '';
        $id_conteudo = $id.'_conteudo';

        $span = "<span>|</span>\n";

        $abas .= "<div id=\"{$id}\" class=\"abas\">\n";
        $abas .= "<div class=\"nomes_abas\">\n";
        $abas .= "<span>Abas:</span>\n";
        foreach ($vt_abas as $id_aba => $dados) {

            // Class
            $vt_class = array();
            if (isset($dados->class)) {
                $vt_class[] = $dados->class;
            }
            if ($ativa == $id_aba) {
                $vt_class[] = 'ativa';
                $imprimiu_ativa = true;
            }
            $class = implode(' ', $vt_class);

            // Foco
            $foco = isset($dados->foco) ? $dados->foco : true;

            // Ajax
            $ajax = isset($dados->ajax) ? $dados->ajax : true;

            $aba = link::texto($dados->link, $dados->nome, '', 'aba_'.$id_aba, $class, 1, 'document.getElementById("'.$id_conteudo.'")', $foco, $ajax);
            $abas .= (isset($imprimiu_primeiro) ? $span : '').$aba."\n";
            $imprimiu_primeiro = true;
        }
        $abas .= "</div>\n";
        $abas .= "<div class=\"conteudo_aba\" id=\"{$id_conteudo}\">\n";
        if (!$imprimiu_ativa) {
            $abas .= '<p>Aba inv&aacute;lida.</p>';
        }

        if ($return) {
            return $abas;
        }
        echo $abas;

    }


    //
    //     Fecha um bloco de abas
    //
    public function fechar_abas($return = false) {
    // Bool $return: retorna ou imprime as abas
    //

        // Nao pode chamar a funcao mais de uma vez
        if (!self::$contador_abas) {
            $this->imprimir_abas(array('Erro'), 'aba_erro');
            $e = "Erro ao imprimir Fecha Abas: Abas omitido.\n";
            mensagem::erro($e);
        }
        self::$contador_abas--;

        $abas = "</div>\n".
                "</div>\n";
        if ($return) {
            return $abas;
        }
        echo $abas;
    }


    //
    //     Gera uma lista de opcoes
    //
    public function listar_opcoes($opcoes, $return = false) {
    // String || Array[String] $opcoes: string ou vetor com os links das opcoes
    // Bool $return: retornar ou imprimir a lista
    //
        global $CFG;

        if (!self::$imprimiu_inicio_conteudo) {
            $this->inicio_conteudo();
            $e = "Erro ao imprimir op&ccedil;&otilde;es: Inico de conteudo omitido.\n";
            mensagem::erro($e);
        }
        $opcoes_validas = array();

        // Imprimir opcoes
        $l = "<div class=\"opcoes\">\n";
        $l .= "  <strong>Op&ccedil;&otilde;es:</strong>\n";
        if (is_array($opcoes)) {
            foreach ($opcoes as $o) {
                if ($o) {
                    $opcoes_validas[] = $o;
                }
            }
            if (count($opcoes_validas) <= 4) {
                $l .= implode("  <span>|</span>\n", $opcoes_validas)."\n";
            } else {
                $l .= '<ul><li>'.implode('<span class="hide">;</span></li><li>', $opcoes_validas)."</li></ul>\n";
            }
        } else {
            $l .= $opcoes."\n";
        }
        $l .= "</div>\n";

        $l = !empty($opcoes_validas) ? $l : '';

        if ($return) {
            return $l;
        }
        echo $l;
    }


    //
    //     Exibe uma nota de rodape
    //
    public function nota_rodape($texto) {
    // String || Array[String] $texto: texto da nota de rodape ou vetor de textos
    //
        echo "<div class=\"observacao\">\n";
        if (is_string($texto)) {
            echo $texto;
        } elseif (is_array($texto)) {
            $i = 1;
            foreach ($texto as $t) {
                echo "<div id=\"nota_rodape_{$i}\">{$t}</div>\n";
                $i++;
            }
        }
        echo "</div>\n";
    }


    //
    //     Exibe uma pagina de erro
    //
    static public function erro($usuario, $mensagem_erro, $conteudo_pagina = '') {
    // Object $usuario: usuario que causou o erro
    // String $mensagem_erro: mensagem de erro
    // String $conteudo_pagina: conteudo a ser exibido na pagina
    //
        global $CFG;
        $titulo = 'Erro Inesperado';
        $nav = array($CFG->wwwroot => 'P&aacute;gina Principal',
                     ''            => 'Erro Inesperado');
        $estilos = false;

        $p = new self();
        $p->cabecalho($titulo, $nav, $estilos);
        if ($usuario) {
            $p->imprimir_menu($usuario);
        }
        $p->inicio_conteudo($titulo);
        mensagem::erro($mensagem_erro);
        echo $conteudo_pagina ? $conteudo_pagina : $mensagem_erro;
        $p->fim_conteudo();
        $p->rodape();
        exit(1);
    }


    //
    //     Exibe uma pagina de sistema indisponivel (para o caso do servidor estar sobrecarregado)
    //
    private function sistema_indisponivel() {
        global $CFG;

        self::$imprimiu_rodape = true;
        setlocale(LC_TIME, 'C');
        header('HTTP/1.1 503 Service Unavailable');
        header('Status: 503 Service Unavailable');
        header('Retry-After: 3600');
        header("Content-Type: {$CFG->content}; charset={$CFG->charset}");
        setlocale(LC_TIME, $CFG->localidade);

        echo "<?xml version=\"1.0\" encoding=\"{$CFG->charset}\" standalone=\"no\"?>\n";
        echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"{$CFG->wwwroot}dtd/xhtml1-20020801/DTD/xhtml1-strict.dtd\">\n";
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$CFG->lingua}\" dir=\"ltr\">";
        echo '<head>';
        echo '<title>Servidor Sobrecarregado</title>';
        echo '</head>';
        echo '<body>';
        echo '<h1>Servidor Sobrecarregado</h1>';
        echo '<p>O servidor est&aacute; operando acima do processamento esperado.</p>';
        echo '<p>Recomenda-se sair e voltar mais tarde.</p>';
        echo "<p>Tentar novamente: <a href=\"{$CFG->wwwroot}\">P&aacute;gina inicial</a>.</p>";
        echo '<h2>Detalhes</h2>';
        echo '<p>Processamento esperado: entre 0 e '.LOAD_AVG_MAX_ESPERADO.'</p>';
        echo '<p>Processamento alto: entre '.LOAD_AVG_MAX_ESPERADO.' e '.LOAD_AVG_MIN_ALERTA.'</p>';
        echo '<p>Processamento em alerta: entre '.LOAD_AVG_MIN_ALERTA.' e '.LOAD_AVG_MAX_ALERTA.'</p>';
        echo '<p>Processamento extremo: acima de '.LOAD_AVG_MAX_ALERTA.'</p>';
        echo '<p><strong>Processamento corrente:</strong> '.$CFG->load_avg.'</p>';
        echo '</body>';
        echo '</html>';
    }


    //
    //     Imprime o quadro de desempenho
    //
    private static function imprimir_bloco_desempenho() {
        global $CFG;
        $arq = util::get_arquivo();

        $vt_estatisticas = array();
        $tempo_carregamento = round(microtime(1) - $CFG->microtime, 3);
        $vt_estatisticas['tempo'] = $tempo_carregamento.' segundos';
        $vt_estatisticas['load_avg'] = $CFG->load_avg;
        if (function_exists('memory_get_usage')) {
            $vt_estatisticas['memoria'] = memoria::formatar_bytes(memory_get_usage(true), true);
        }
        if (function_exists('memory_get_peak_usage')) {
            $pico_memoria = memory_get_peak_usage(true);
            $vt_estatisticas['pico'] = memoria::formatar_bytes($pico_memoria, true);
        } else {
            $pico_memoria = 0;
            $vt_estatisticas['pico'] = '0';
        }
        $vt_estatisticas['limite'] = memoria::formatar_bytes(memoria::desformatar_bytes_php(ini_get('memory_limit')), true);

        $quantidade_sql = driver_base::get_quantidade_instrucoes();
        $quantidade_consultas_por_demanda = objeto::get_quantidade_consultas_por_demanda();
        $vt_estatisticas['sql'] = texto::numero($quantidade_sql);
        $vt_estatisticas['demanda'] = texto::numero($quantidade_consultas_por_demanda);
        $vt_estatisticas['classes_simp'] = texto::numero($CFG->classes_carregadas);
        $vt_estatisticas['classes_php'] = texto::numero(count(get_declared_classes()) - $CFG->classes_carregadas);

        if ($arq) {
            $alerta_documentacao = parser_simp::possui_erro_documentacao($arq);
            $vt_estatisticas['doc'] = $alerta_documentacao ? 'Erro' : 'OK';
        } else {
            $alerta_documentacao = true;
            $vt_estatisticas['doc'] = '?';
        }
        $alerta_tempo   = $tempo_carregamento >= TEMPO_ALERTA;
        $alerta_sql     = $quantidade_sql >= SQL_ALERTA;
        $alerta_demanda = $quantidade_consultas_por_demanda >= SQL_DEMANDA_ALERTA;
        $alerta_memoria = $pico_memoria >= memoria::desformatar_bytes(MEMORIA_ALERTA);

        if ($alerta_tempo || $alerta_sql || $alerta_memoria || $alerta_documentacao) {
            $class_alerta = ' class="alerta"';
        } else {
            $class_alerta = '';
        }

        echo '<div id="bloco_desempenho"'.$class_alerta.'>';
        echo '<p><strong>Arquivo:</strong> <tt>'.texto::codificar($arq).'</tt></p>';
        echo '<table class="tabela">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col" rowspan="2">Tempo</th>';
        echo '<th scope="col" rowspan="2"><abbr title="Load Average">AVG</abbr></th>';
        echo '<th scope="col" colspan="3">Mem&oacute;ria</th>';
        echo '<th scope="col" colspan="2">SQL</th>';
        echo '<th scope="col" colspan="2">Classes</th>';
        echo '<th scope="col" rowspan="2"><abbr title="Documenta&ccedil;&atilde;o">Doc.</abbr></th>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="col">Atual</th>';
        echo '<th scope="col">Pico</th>';
        echo '<th scope="col">Limite</th>';
        echo '<th scope="col">Total</th>';
        echo '<th scope="col">Demanda</th>';
        echo '<th scope="col">Simp</th>';
        echo '<th scope="col">PHP</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        echo '<tr>';
        echo '<td'.($alerta_tempo ? ' class="erro"' : '').'>'.$vt_estatisticas['tempo'].'</td>';
        echo '<td>'.$vt_estatisticas['load_avg'].'</td>';
        echo '<td'.($alerta_memoria ? ' class="erro"' : '').'>'.$vt_estatisticas['memoria'].'</td>';
        echo '<td'.($alerta_memoria ? ' class="erro"' : '').'>'.$vt_estatisticas['pico'].'</td>';
        echo '<td'.($alerta_memoria ? ' class="erro"' : '').'>'.$vt_estatisticas['limite'].'</td>';
        echo '<td'.($alerta_sql ? ' class="erro"' : '').'>'.$vt_estatisticas['sql'].'</td>';
        echo '<td'.($alerta_demanda ? ' class="erro"' : '').'>'.$vt_estatisticas['demanda'].'</td>';
        echo '<td>'.$vt_estatisticas['classes_simp'].'</td>';
        echo '<td>'.$vt_estatisticas['classes_php'].'</td>';
        echo '<td'.($alerta_documentacao ? ' class="erro"' : '').'>'.$vt_estatisticas['doc'].'</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

    }


    //
    //     Imprime o bloco de desenvolvimento
    //
    private static function imprimir_bloco_devel() {
        global $CFG;
        if (isset($GLOBALS['USUARIO'])) {
            $usuario = &$GLOBALS['USUARIO'];
        } else {
            $usuario = false;
        }
        $link_recarregar_permissoes = link::adicionar_atributo($CFG->wwwmods.'devel/recarregar_permissoes.php', 'url', base64_encode($CFG->site));
        $link_limpar_cache = link::adicionar_atributo($CFG->wwwmods.'devel/limpar_cache_arquivo.php', 'url', base64_encode($CFG->site));

        echo '<div id="bloco_devel">';
        echo '<h2>Sistema em modo de Desenvolvimento</h2>';
        echo '<ul>';
        echo '<li><a href="'.$CFG->wwwmods.'devel/">Acessar o m&oacute;dulo DEVEL</a></li>';
        echo '<li><a href="'.$link_recarregar_permissoes.'">Recarregar Permiss&otilde;es</a></li>';
        echo '<li><a href="'.$link_limpar_cache.'">Limpar Cache de Arquivos</a></li>';
        echo '</ul>';

        if ($CFG->instalacao) {
            echo '<hr />';
            echo '<p><strong>Logar como:</strong></p>';
            echo '<div class="usuarios_favoritos">';

            if (isset($_COOKIE['simp_usuarios'])) {
                $cod_usuarios = explode('.', $_COOKIE['simp_usuarios']);
                $condicoes = condicao_sql::sql_in('cod_usuario', $cod_usuarios);
                $campos = array('nome', 'login');
                $ordem = array('nome' => true);
                $usuarios = objeto::get_objeto('usuario')->consultar_varios_iterador($condicoes, $campos, $ordem);

                echo '<table class="tabela">';
                echo '<caption>'.icone::img('favoritos').' Usu&aacute;rios Favoritos</caption>';
                echo '<thead>';
                echo '<th scope="col">Usu&aacute;rio</th>';
                echo '<th scope="col">Op&ccedil;&otilde;es</th>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($usuarios as $u) {
                    echo '<tr>';
                    echo '<td><a href="'.$CFG->wwwmods.'devel/logar_como.php?op=logar&amp;cod_usuario='.$u->get_valor_chave().'">'.$u->exibir('nome').'</a><br /><small>'.$u->exibir('login').'</small></td>';
                    echo '<td><a href="'.$CFG->wwwmods.'devel/logar_como.php?op=remover&amp;cod_usuario='.$u->get_valor_chave().'" title="Remover">'.icone::img('excluir', 'Remover').'</a></td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            } else {
                $cod_usuarios = array();
                echo '<p>Nenhum usu&aacute;rio favorito.</p>';
            }
            echo '</div>';

            if ($usuario && !in_array($usuario->cod_usuario, $cod_usuarios)) {
                echo '<p><a href="'.$CFG->wwwmods.'devel/logar_como.php?op=adicionar&amp;cod_usuario='.$usuario->get_valor_chave().'">'.icone::img('adicionar', 'Adicionar').' Adicionar '.$usuario->exibir('nome').'</a></p>';
            }
            echo '<p><a href="'.$CFG->wwwmods.'devel/logar_como.php">Escolher outro usu&aacute;rio</a></p>';

        } else {
            echo '<p>O sistema ainda n&atilde;o foi instalado</p>';
        }
        echo '</div>';
    }

}//class
