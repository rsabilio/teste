<?php
//
// SIMP
// Descricao: Classe de geracao de Graficos com a biblioteca GD ou em HTML
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.1.20
// Data: 20/06/2007
// Modificado: 04/10/2012
// Copyright (C) 2007  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
global $CFG;

// Tipos de Graficos
define('GRAFICO_LINHA', 1);
define('GRAFICO_BARRA', 2);
define('GRAFICO_PIZZA', 3);
define('GRAFICO_PILHA', 4);

// Posicoes
define('GRAFICO_DIREITA',  1);
define('GRAFICO_ESQUERDA', 2);
define('GRAFICO_CIMA',     3);
define('GRAFICO_BAIXO',    4);

// Tipos de Cores
define('GRAFICO_COR_CLARA',  1);
define('GRAFICO_COR_ESCURA', 2);
define('GRAFICO_COR_NORMAL', 3);

// Tipos de Bordas
define('GRAFICO_BORDA_SOLIDA', 1);
define('GRAFICO_BORDA_3D',     2);

// Tipos de Pontos sobre graficos de Linha
define('GRAFICO_PONTO_NENHUM',   0);
define('GRAFICO_PONTO_BOLA',     1);
define('GRAFICO_PONTO_QUADRADO', 2);

// Formatos de Arquivos
define('GRAFICO_TIPO_PNG',  1);
define('GRAFICO_TIPO_JPG',  2);
define('GRAFICO_TIPO_GIF',  3);
define('GRAFICO_TIPO_BMP',  4);
define('GRAFICO_TIPO_HTML', 5);

// Constantes
define('GRAFICO_LOCALIDADE',    $CFG->localidade);
define('GRAFICO_GD',            $CFG->gd);
define('GRAFICO_GMT',           $CFG->gmt);
define('GRAFICO_MARGEM',        10);  // Margem entre os elementos
define('GRAFICO_QUADRADO',      12);  // Tamanho dos lados do quadrado da legenda
define('GRAFICO_PRECISAO',      2);   // Precisao da porcentagem exibixa
define('GRAFICO_CLAREAR',       1.4); // Porcentagem do clareamento das cores
define('GRAFICO_ESCURECER',     0.7); // Porcentagem do escurecimento das cores
define('GRAFICO_FONTE_TITULO',  $CFG->dirclasses.'interface/fontes/AppleGaramond-Bold.ttf'); // Fonte do Titulo
define('GRAFICO_FONTE',         $CFG->dirclasses.'interface/fontes/AppleGaramond.ttf');      // Fonte do Texto

class grafico {

    // Geral/Controle
    private $gd             = false;        // Possui biblioteca GD instalada

    // Atributos principais
    private $titulo         = false;        // Titulo do Grafico
    private $tipo_grafico   = false;        // Tipo de Grafico (GRAFICO_BARRA, GRAFICO_LINHA, GRAFICO_PIZZA ou GRAFICO_PILHA)
    private $altura         = 200;          // Altura em pixels
    private $largura        = 300;          // Largura em pixels
    private $valor_topo     = false;        // Maior valor da escala vertical (graficos de barra ou de linha)
    private $valores        = false;        // Vetor ou Matriz de valores
    private $legenda        = false;        // Vetor de itens da legenda
    private $escala         = false;        // Vetor de itens da escala horizontal
    private $linhas         = false;        // Vetor de linhas que cortam os graficos de linha ou barra (pares de valores indicando inicio e fim da linha)
    private $legenda_linhas = false;        // Vetor de itens da legenda das linhas que cortam o grafico
    private $tamanho_titulo = 15;           // Altura da fonte do Titulo
    private $tamanho_texto  = 14;           // Altura da fonte do Texto

    // Opcoes adicionais
    private $angulo                   = 45;                   // Angulo do texto da escala horizontal (entre 30 e 90)
    private $pos_legenda              = GRAFICO_DIREITA;      // Posicao da legenda (GRAFICO_DIREITA, GRAFICO_ESQUERDA, GRAFICO_CIMA ou GRAFICO_BAIXO)
    private $tipo_cor                 = GRAFICO_COR_NORMAL;   // Tom das cores (GRAFICO_COR_NORMAL, GRAFICO_COR_CLARA ou GRAFICO_COR_ESCURA)
    private $borda                    = GRAFICO_BORDA_3D;     // Tipo de borda (GRAFICO_BORDA_SOLIDA ou GRAFICO_BORDA_3D)
    private $ponto                    = GRAFICO_PONTO_NENHUM; // Tipo de ponto (GRAFICO_PONTO_NENHUM, GRAFICO_PONTO_BOLA, GRAFICO_PONTO_QUADRADO)
    private $cores                    = false;                // Vetor com as cores a serem utilizadas
    private $cores_usuario            = false;                // Vetor com as tuplas RGB das cores definidas pelo usuario
    private $formato                  = GRAFICO_TIPO_PNG;     // Tipo de arquivo (GRAFICO_TIPO_PNG, GRAFICO_TIPO_JPG, GRAFICO_TIPO_GIF ou GRAFICO_TIPO_BMP)
    private $qualidade                = 100;                  // Qualidade da imagem para arquivos jpg (entre 0 e 100)
    private $nome_arquivo             = 'grafico';            // Nome do arquivo gerado
    private $conversao_valores        = false;                // Metodo para converter os valores
    private $codigo_conversao_valores = false;                // Codigo para converter o valor armazenado em $valor
    private $salvar                   = false;                // Opcao para fazer download da imagem
    private $cache                    = false;                // Tempo da imagem em cache


    //
    //     Construtor
    //
    public function __construct($titulo = '', $tipo_grafico = GRAFICO_BARRA, $altura = 200, $largura = 300) {
    // String $titulo: titulo do grafico
    // Int $tipo_grafico: tipo de grafico
    // Int $altura: altura em pixels
    // Int $largura: largura em pixels
    //
        $this->gd           = extension_loaded('gd');
        $this->titulo       = $titulo;
        $this->tipo_grafico = $tipo_grafico;
        $this->altura       = $altura;
        $this->largura      = $largura;
    }


    //
    //     Obtem um atributo
    //
    public function __get($campo) {
    // String $campo: nome do campo
    //
        if (!property_exists($this, $campo)) {
            return null;
        }
        return $this->$campo;
    }


    //
    //     Define os atributos
    //
    public function __set($campo, $valor) {
    // String $campo: nome do campo
    // Mixed $valor: valor a ser atribuido
    //
        if (!property_exists($this, $campo)) {
            trigger_error('Nao existe o atributo "'.$campo.'"', E_USER_NOTICE);
        }
        switch ($campo) {

        // STRING
        case 'titulo':
        case 'nome_arquivo':
        case 'codigo_conversao_valores':
            $this->$campo = (string)$valor;
            break;

        // FLOAT
        case 'valor_topo':
            $this->$campo = (float)$valor;
            break;

        // INTEGER
        case 'tipo_grafico':
        case 'altura':
        case 'largura':
        case 'pos_legenda':
        case 'tipo_cor':
        case 'borda':
        case 'ponto':
        case 'qualidade':
        case 'formato':
            $this->$campo = (int)$valor;
            break;
        case 'angulo': // entre 30 e 90
            $this->$campo = max(min((int)$valor, 90), 30);
            break;

        // ARRAY
        case 'valores':
        case 'legenda':
        case 'escala':
        case 'cores':
        case 'linhas':
        case 'legenda_linhas':
            $this->$campo = (array)$valor;
            break;

        // BOOLEAN
        case 'salvar':
            $this->$campo = (bool)$valor;
            break;

        // CALLBACK
        case 'conversao_valores':
            if (is_callable($valor)) {
                $this->$campo = $valor;
            }
            break;

        // OUTROS
        default:
            $this->$campo = $valor;
            break;
        }
    }


    //
    //     Retorna um grafico com imagem ou montado com estilos CSS
    //
    static public function exibir_grafico($nome, $link, $arquivo, $dados = null, $html = false) {
    // String $nome: nome do grafico
    // String $link: link onde e' processada a imagem do grafico
    // String $arquivo: arquivo onde e' processada a imagem do grafico
    // Object || Array[String => String] $dados: objeto ou vetor associativo com os parametros de filtragem
    // Bool $html: forcar que seja um grafico em HTML
    //
        static $i = 1;
        if (GRAFICO_GD && (!$html)) {
            $parametros = array();

            // Link da imagem
            $src = $link;
            if (is_array($dados) || is_object($dados)) {
                foreach ($dados as $chave => $valor) {
                    if (is_array($valor)) {
                        foreach ($valor as $chave2 => $valor2) {
                            if (is_array($valor2)) {
                                foreach ($valor2 as $chave3 => $valor3) {
                                    $src = link::adicionar_atributo($src, $chave.'['.$chave2.']['.$chave3.']', $valor3);
                                }
                            } else {
                                $src = link::adicionar_atributo($src, $chave.'['.$chave2.']', $valor2);
                            }
                        }
                    } else {
                        $src = link::adicionar_atributo($src, $chave, $valor);
                    }
                }
            }

            // Link de descricao
            $longdesc = link::adicionar_atributo($src, 'longdesc', 1);

            // Link do mapa
            $mapa = 'mapa'.($i++);
            $dados_src = parse_url($src);
            $dados_get = array();
            if (isset($dados_src['query'])) {
                $dados_src['query'] = str_replace('&amp;', '&', $dados_src['query']);
                parse_str($dados_src['query'], $dados_get);
            }
            $dados_get['mapa'] = $mapa;
            $link_mapa = $dados_src['scheme'].'://'.$dados_src['host'].$dados_src['path'].'?'.http_build_query($dados_get, null, '&');
            $conteudo_mapa = http::get_conteudo_link($link_mapa);
            if (stripos($conteudo_mapa, '<map') !== false) {
                $usemap = "usemap=\"#{$mapa}\"";
            } else {
                $conteudo_mapa = false;
                $usemap = '';
            }

            echo "<div class=\"area_grafico\">\n";
            echo "<img class=\"imagem\" src=\"{$src}\" longdesc=\"{$longdesc}\" {$usemap} alt=\"{$nome}\" title=\"{$nome}\" />\n";
            echo "<p><a rel=\"blank\" href=\"{$longdesc}\" class=\"acessivel\">Resultado Textual</a></p>\n";
            echo "</div>\n";

            if ($conteudo_mapa) {
                echo $conteudo_mapa;
            }
        } else {
            if (is_array($dados) || is_object($dados)) {
                foreach ($dados as $chave => $valor) { $_GET[$chave] = $valor; }
            }
            require_once($arquivo);
        }
    }


    //
    //     Imprime o grafico (conteudo do arquivo)
    //
    public function imprimir() {

        // Se pediu para descrever o grafico, exibir um HTML textual
        if (isset($_GET['longdesc'])) {
            $this->descrever_grafico();
            return;

        // Se pediu para exibir o mapa da imagem, exibir um HTML
        } elseif (isset($_GET['mapa'])) {
            $this->imprimir_mapa($_GET['mapa']);
            return;
        }

        // [0] Checar se pode usar a GD
        if (($this->formato == GRAFICO_TIPO_HTML) || (!$this->gd)) {
            $this->imprimir_grafico_html();
            return;
        }

        // [1] Recuperar dados gerais
        list($nome, $mime, $funcao) = $this->get_dados();

        // [2] Calcular Tamanhos
        $this->tamanhos($vt_total, $vt_grafico, $vt_legenda);

        // Largura e altura total
        list($largura, $altura) = $vt_total;

        // Limites da area do grafico
        list($inicio_x, $inicio_y, $fim_x, $fim_y) = $vt_grafico;

        // Limites da area da legenda
        list($inicio_l_x, $inicio_l_y, $fim_l_x, $fim_l_y) = $vt_legenda;

        // [3] Criar a imagem
        $img = imagecreatetruecolor($largura, $altura);

        // [4] Alocar as cores padrao
        $this->alocar_cores($img);

        // [5] Pintar Fundo
        imagefill($img, 0, 0, $this->cores['fundo']);
        $this->borda($img, 0, 0, $largura - 1, $altura - 1, 'borda', 1);

        // [6] Imprimir a legenda
        if ($this->legenda || $this->legenda_linhas) {
            $this->imprimir_legenda($img, $inicio_l_x, $inicio_l_y, $fim_l_x, $fim_l_y);
        }

        // [7] Imprimir Grafico
        $this->imprimir_grafico($img, $inicio_x, $inicio_y, $fim_x, $fim_y);

        // [8] Gerar Titulo
        imagettftext($img, $this->tamanho_titulo, 0, GRAFICO_MARGEM, $this->tamanho_titulo + GRAFICO_MARGEM, $this->cores['texto'], GRAFICO_FONTE_TITULO, $this->titulo);

        // [9] Gerar o cabecalho HTTP
        $this->cabecalho($nome, $mime);

        // [10] Exibir a imagem e desalocar da memoria
        if ($this->formato == GRAFICO_TIPO_JPG) {
            $funcao($img, null, $this->qualidade);
        } else {
            $funcao($img);
        }
        imagedestroy($img);
    }


    //
    //     Retorna o nome do arquivo, o mime-type e a funcao para criar a imagem
    //
    private function get_dados() {
        switch ($this->formato) {
        case GRAFICO_TIPO_JPG:
            $nome   = $this->nome_arquivo.'.jpg';
            $mime   = 'image/jpeg';
            $funcao = 'imagejpeg';
            break;
        case GRAFICO_TIPO_PNG:
            $nome   = $this->nome_arquivo.'.png';
            $mime   = 'image/png';
            $funcao = 'imagepng';
            break;
        case GRAFICO_TIPO_GIF:
            $nome   = $this->nome_arquivo.'.gif';
            $mime   = 'image/gif';
            $funcao = 'imagegif';
            break;
        case GRAFICO_TIPO_BMP:
            $nome   = $this->nome_arquivo.'.bmp';
            $mime = 'image/vnd.wap.wbmp';
            $funcao = 'imagewbmp';
            break;
        }
        return array($nome, $mime, $funcao);
    }


    //
    //     Calcula o tamanho da imagem, do grafico e da legenda
    //
    private function tamanhos(&$total, &$grafico, &$legenda) {
    // Array[Int] $total: vetor com a largura e altura da imagem
    // Array[Int] $grafico: vetor com os limites do grafico
    // Array[Int] $legenda: vetor com os limites da legenda
    //
        // Calcular tamanho da legenda
        $possui_legenda = is_array($this->legenda) ? !empty($this->legenda) : false;
        $possui_legenda_linhas = is_array($this->legenda_linhas) ? !empty($this->legenda_linhas) : false;

        // Altura do titulo
        $altura_titulo = $this->altura_texto($this->titulo, $this->tamanho_titulo, GRAFICO_FONTE_TITULO);

        if ($possui_legenda || $possui_legenda_linhas) {

            // Achar texto mais largo da legenda ($max)
            $vt_legenda = array('Legenda:');
            if ($possui_legenda) {
                $vt_legenda = array_merge($vt_legenda, $this->legenda);
            }
            if ($possui_legenda_linhas) {
                $vt_legenda = array_merge($vt_legenda, $this->legenda_linhas);
            }

            $max = 0;
            foreach ($vt_legenda as $l) {
                $tam = $this->largura_texto($l);
                $max = ($max > $tam) ? $max : $tam;
            }
            $largura_legenda = GRAFICO_MARGEM + GRAFICO_QUADRADO + GRAFICO_MARGEM + $max + GRAFICO_MARGEM;
            $altura_legenda = GRAFICO_MARGEM + (($this->tamanho_texto + 5) * count($vt_legenda)) + GRAFICO_MARGEM;

            // Calcular maior altura e largura
            $max_largura = ($largura_legenda > $this->largura) ? $largura_legenda
                                                               : $this->largura;
            $max_altura = ($altura_legenda > $this->altura) ? $altura_legenda
                                                            : $this->altura;

            // Calcular tamanho da imagem de acordo com a posicao da legenda
            switch ($this->pos_legenda) {

            case GRAFICO_DIREITA:
                $largura    = GRAFICO_MARGEM + $this->largura + GRAFICO_MARGEM + $largura_legenda + GRAFICO_MARGEM;
                $altura     = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM + $max_altura + GRAFICO_MARGEM;
                $inicio_x   = GRAFICO_MARGEM;
                $inicio_y   = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM;
                $inicio_l_x = GRAFICO_MARGEM + $this->largura + GRAFICO_MARGEM;
                $inicio_l_y = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM;
                break;

            case GRAFICO_ESQUERDA:
                $largura    = GRAFICO_MARGEM + $largura_legenda + GRAFICO_MARGEM + $this->largura + GRAFICO_MARGEM;
                $altura     = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM + $max_altura + GRAFICO_MARGEM;
                $inicio_x   = GRAFICO_MARGEM + $largura_legenda + GRAFICO_MARGEM;
                $inicio_y   = GRAFICO_MARGEM + $algura_titulo + GRAFICO_MARGEM;
                $inicio_l_x = GRAFICO_MARGEM;
                $inicio_l_y = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM;
                break;

            case GRAFICO_CIMA:
                $largura    = GRAFICO_MARGEM + $max_largura + GRAFICO_MARGEM;
                $altura     = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM + $altura_legenda + GRAFICO_MARGEM + $this->altura + GRAFICO_MARGEM;
                $inicio_x   = GRAFICO_MARGEM;
                $inicio_y   = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM + $altura_legenda + GRAFICO_MARGEM;
                $inicio_l_x = GRAFICO_MARGEM;
                $inicio_l_y = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM;
                break;

            case GRAFICO_BAIXO:
                $largura    = GRAFICO_MARGEM + $max_largura + GRAFICO_MARGEM;
                $altura     = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM + $this->altura + GRAFICO_MARGEM + $altura_legenda + GRAFICO_MARGEM;
                $inicio_x   = GRAFICO_MARGEM;
                $inicio_y   = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM;
                $inicio_l_x = GRAFICO_MARGEM;
                $inicio_l_y = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM + $this->altura + GRAFICO_MARGEM;
                break;
            }
            $fim_x = $inicio_x + $this->largura;
            $fim_y = $inicio_y + $this->altura;
            $fim_l_x = $inicio_l_x + $largura_legenda;
            $fim_l_y = $inicio_l_y + $altura_legenda;

        // Se nao tem legenda
        } else {
            $largura  = GRAFICO_MARGEM + $this->largura + GRAFICO_MARGEM;
            $altura   = GRAFICO_MARGEM + $altura_titulo + GRAFICO_MARGEM + $this->altura + GRAFICO_MARGEM;
            $inicio_x = GRAFICO_MARGEM;
            $inicio_y = GRAFICO_MARGEM + $this->tamanho_titulo + GRAFICO_MARGEM;
            $fim_x    = $inicio_x + $this->largura;
            $fim_y    = $inicio_y + $this->altura;
        }

        // Checar largura do titulo
        $largura_titulo = GRAFICO_MARGEM + $this->largura_texto($this->titulo, $this->tamanho_titulo, GRAFICO_FONTE_TITULO) + GRAFICO_MARGEM;
        if ($largura_titulo > $largura) {
            $largura = $largura_titulo;
        }

        // Armazenar resultados nos vetores passados por parametro
        $total   = array($largura, $altura);
        $grafico = array($inicio_x, $inicio_y, $fim_x, $fim_y);
        if ($this->legenda || $this->legenda_linhas) {
            $legenda = array($inicio_l_x, $inicio_l_y, $fim_l_x, $fim_l_y);
        }
    }


    //
    //     Retorna o cabecalho HTTP da imagem
    //
    private function cabecalho($nome, $mime) {
    // String $nome: nome do arquivo
    // String $mime: mime-type do arquivo
    //
        $disposition  = ($this->salvar) ? 'attachment' : 'inline';

        if (isset($_SESSION[__CLASS__][$nome])) {
            $ultima_mudanca = $_SESSION[__CLASS__][$nome];
        } else {
            $ultima_mudanca = $_SERVER['REQUEST_TIME'];
            $_SESSION[__CLASS__][$nome] = $ultima_mudanca;
        }

        $opcoes_http = array(
            'arquivo' => $nome,
            'disposition' => $disposition,
            'tempo_expira' => $this->cache,
            'compactacao' => true,
            'ultima_mudanca' => $ultima_mudanca
        );

        http::cabecalho($mime, $opcoes_opcoes);
    }


    //
    //     Imprime a legenda em uma posicao
    //
    private function imprimir_legenda(&$img, $ix, $iy, $fx, $fy) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    //

        // Checar se existe legenda
        if (!$this->legenda && !$this->legenda_linhas) {
            return;
        }

        // Area da Legenda
        imagefilledrectangle($img, $ix, $iy, $fx, $fy, $this->cores['fundo_grafico']);
        $this->borda($img, $ix, $iy, $fx, $fy, 'borda', 1);

        // Imprimir titulo da legenda
        $y = $iy + GRAFICO_MARGEM;
        imagettftext($img, $this->tamanho_texto, 0, $ix + GRAFICO_MARGEM, $y + $this->tamanho_texto, $this->cores['texto'], GRAFICO_FONTE_TITULO, 'Legenda:');
        $y += 5 + $this->tamanho_texto;

        // Imprimir cada item
        $i = 0;
        if (is_array($this->legenda)) {
            foreach ($this->legenda as $l) {

                switch ($this->tipo_grafico) {

                // Imprimir quadrado com cor
                case GRAFICO_BARRA:
                    // Calcular valor para alinhar os quadrados da legenda no centro verticalmente
                    $c = (int)(($this->tamanho_texto - GRAFICO_QUADRADO) / 2);

                    imagefilledrectangle($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c + GRAFICO_QUADRADO, $this->cores[$i]);
                    $this->borda($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c + GRAFICO_QUADRADO, $i);
                    break;

                // Imprimir quadrado com cor
                case GRAFICO_PIZZA:

                    // Calcular valor para alinhar os quadrados da legenda no centro verticalmente
                    $c = (int)(($this->tamanho_texto - GRAFICO_QUADRADO) / 2);

                    imagefilledrectangle($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c +GRAFICO_QUADRADO, $this->cores[$i]);
                    $this->borda($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c + GRAFICO_QUADRADO, $i);
                    break;

                // Imprimir quadrado com cor
                case GRAFICO_PILHA:
                    // Calcular valor para alinhar os quadrados da legenda no centro verticalmente
                    $c = (int)(($this->tamanho_texto - GRAFICO_QUADRADO) / 2);

                    imagefilledrectangle($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c +GRAFICO_QUADRADO, $this->cores[$i]);
                    $this->borda($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c + GRAFICO_QUADRADO, $i);
                    break;

                // Imprimir Linha
                case GRAFICO_LINHA:

                    // Calcular valor para alinhar as linhas da legenda no centro verticalmente
                    $c = (int)(GRAFICO_QUADRADO / 2);

                    imagesetthickness($img, 2);
                    imageline($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c, $this->cores[$i]);
                    imagesetthickness($img, 1);
                    break;
                }

                // Imprimir texto
                imagettftext($img, $this->tamanho_texto, 0, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO + GRAFICO_MARGEM, $y + $this->tamanho_texto, $this->cores['texto'], GRAFICO_FONTE, $l);

                // Atualizar eixo Y
                $y += 5 + $this->tamanho_texto;
                $i++;
            }

        // Atualizar o $i, pois graficos de linha simples podem nao ter legenda
        } else {
            $i = 1;
        }

        // Imprimir itens das linhas
        if (is_array($this->legenda_linhas)) {
            foreach ($this->legenda_linhas as $l) {
                switch ($this->tipo_grafico) {
                case GRAFICO_BARRA:
                case GRAFICO_LINHA:

                    // Calcular valor para alinhar as linhas da legenda no centro verticalmente
                    $c = (int)(GRAFICO_QUADRADO / 2);

                    imageline($img, $ix + GRAFICO_MARGEM, $y + $c, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO, $y + $c, $this->cores[$i]);
                    break;
                }

                // Imprimir texto
                imagettftext($img, $this->tamanho_texto, 0, $ix + GRAFICO_MARGEM + GRAFICO_QUADRADO + GRAFICO_MARGEM, $y + $this->tamanho_texto, $this->cores['texto'], GRAFICO_FONTE, $l);

                // Atualizar eixo Y
                $y += 5 + $this->tamanho_texto;
                $i++;
            }
        }
    }


    //
    //     Imprime o grafico
    //
    private function imprimir_grafico(&$img, $ix, $iy, $fx, $fy, $mapa = false) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        // Verificar se existem valores para desenhar o grafico
        if (!is_array($this->valores)) {
            return;
        }

        // Desenhar Area do Grafico
        if (!$mapa) {
            imagefilledrectangle($img, $ix, $iy, $fx, $fy, $this->cores['fundo_grafico']);
            $this->borda($img, $ix, $iy, $fx, $fy, 'borda', 1);
        }

        // Colocar margem para area onde o grafico sera impresso
        $ix += GRAFICO_MARGEM;
        $iy += GRAFICO_MARGEM;
        $fx -= GRAFICO_MARGEM;
        $fy -= GRAFICO_MARGEM;

        // Desenhar o grafico de acordo com o tipo
        switch ($this->tipo_grafico) {
        case GRAFICO_BARRA:
            $this->imprimir_grafico_barra($img, $ix, $iy, $fx, $fy, $mapa);
            break;
        case GRAFICO_LINHA:
            $this->imprimir_grafico_linha($img, $ix, $iy, $fx, $fy, $mapa);
            break;
        case GRAFICO_PIZZA:
            $this->imprimir_grafico_pizza($img, $ix, $iy, $fx, $fy, $mapa);
            break;
        case GRAFICO_PILHA:
            $this->imprimir_grafico_pilha($img, $ix, $iy, $fx, $fy, $mapa);
            break;
        }
    }


    //
    //     Imprime um grafico de barra
    //
    private function imprimir_grafico_barra(&$img, $ix, $iy, $fx, $fy, $mapa = false) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        $iy += GRAFICO_MARGEM;
        $valores = array_values($this->valores);

        // Obter maior valor da escala vertical
        if ($this->valor_topo === false) {
            $maior = $this->get_maior_valor($valores);
        } else {
            $maior = $this->valor_topo;
        }

        // Imprimir moldura
        $v = $this->imprimir_moldura($img, $maior, $ix, $iy, $fx, $fy, $mapa);
        list($div_h, $ix, $iy, $fx, $fy) = $v;

        // Imprimir conteudo do grafico
        $this->imprimir_conteudo_barra($img, $maior, $div_h, $ix, $iy + 1, $fx, $fy - 1, $mapa);

        // Imprimir linhas extras
        $this->imprimir_linhas_extras($img, $maior, $ix + 1 - $div_h / 2, $iy, $fx - 1 + $div_h / 2, $fy);
    }


    //
    //     Obtem o maior valor da lista
    //
    private function get_maior_valor($valores) {
    // Array[Float] || Array[Array[Float]] $valores: valores do grafico
    //
        $maior = 0;
        if (is_array($valores[0])) {
            foreach ($valores as $vet) {
                $vet = array_values($vet);
                $maior_vet = 0;
                foreach ($vet as $chave_vet => $valor_vet) {
                    $maior_vet = $valor_vet > $maior_vet ? $valor_vet : $maior_vet;
                }
                $maior = ($maior_vet > $maior) ? $maior_vet : $maior;
            }
        } else {
            foreach ($valores as $chave => $valor) {
                $maior = $valor > $maior ? $valor : $maior;
            }
        }
        if (is_array($this->linhas)) {
            foreach ($this->linhas as $valor) {
                $maior = $valor > $maior ? $valor : $maior;
            }
        }
        return $maior;
    }


    //
    //     Imprime o conteudo do grafico de barras
    //
    private function imprimir_conteudo_barra(&$img, $maior, $div_h, $ix, $iy, $fx, $fy, $mapa = false) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $maior: maior largura do grafico
    // Int $div_h: distancia entre as barras
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        $px = $ix;
        $c = (GRAFICO_QUADRADO / 2);
        $altura = $fy - $iy;
        $valores = array_values($this->valores);

        // Varias Barras (varias cores)
        if (is_array($valores[0])) {
            $count_valores = count($valores);
            $c = ($div_h - GRAFICO_MARGEM) / (2 * $count_valores);
            foreach ($valores as $i => $v) {
                $v = array_values($v);
                $px = $ix - (($div_h - GRAFICO_MARGEM) / 2);
                $px = $px + (2 * $c * $i) + $c;
                foreach ($v as $j => $valor) {
                    $py = $fy - ($valor * $altura / max($maior, 1));
                    $px1 = $px - $c;
                    $px2 = $px + $c - 1;
                    if (!$mapa) {
                        imagefilledrectangle($img, $px1, $py, $px2, $fy, $this->cores[$i]);
                        $this->borda($img, $px1, $py, $px2, $fy, $i);
                    } else {
                        $ponto_x = round($px1);
                        $ponto_y = round($py);
                        $ponto_x2 = round($px2);
                        $ponto_y2 = round($fy);
                        $title = ($this->legenda ? $this->legenda[$i].'/' : '').$this->escala[$j].': '.$this->converter_valor($valor);
                        echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" nohref=\"nohref\" title=\"{$title}\" />\n";
                    }
                    $px += $div_h;
                }
            }

        // Uma Barra (uma cor)
        } else {
            foreach ($valores as $i => $valor) {
                $py = $fy - ($valor * $altura / max($maior, 1));
                $px1 = $px - $c;
                $px2 = $px + $c - 1;
                if (!$mapa) {
                    imagefilledrectangle($img, $px1, $py, $px2, $fy, $this->cores[0]);
                    $this->borda($img, $px1, $py, $px2, $fy, 0);
                } else {
                    $ponto_x = round($px1);
                    $ponto_y = round($py);
                    $ponto_x2 = round($px2);
                    $ponto_y2 = round($fy);
                    $title = $this->escala[$i].': '.$this->converter_valor($valor);
                    echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" nohref=\"nohref\" title=\"{$title}\" />\n";
                }
                $px += $div_h;
            }
        }
    }


    //
    //     Imprime um grafico de linha
    //
    private function imprimir_grafico_linha(&$img, $ix, $iy, $fx, $fy, $mapa = false) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        $iy += GRAFICO_MARGEM;
        $valores = array_values($this->valores);

        // Obter maior valor da escala vertical
        if ($this->valor_topo === false) {
            $maior = $this->get_maior_valor($valores);
        } else {
            $maior = $this->valor_topo;
        }

        // Imprimir moldura
        $v = $this->imprimir_moldura($img, $maior, $ix, $iy, $fx, $fy, $mapa);
        list($div_h, $ix, $iy, $fx, $fy) = $v;

        // Imprimir conteudo do grafico
        $this->imprimir_conteudo_linha($img, $maior, $div_h, $ix, $iy, $fx, $fy, $mapa);

        // Imprimir linhas extras
        $this->imprimir_linhas_extras($img, $maior, $ix + 1 - $div_h / 2, $iy, $fx - 1 + $div_h / 2, $fy);
    }


    //
    //     Imprime o conteudo do grafico de linhas
    //
    private function imprimir_conteudo_linha(&$img, $maior, $div_h, $ix, $iy, $fx, $fy, $mapa = false) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $maior: maior valor do grafico
    // Int $div_h: distancia entre as linhas verticais
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        $altura = $fy - $iy;

        $valores = array_values($this->valores);

        $divisor = $maior !== 0 ? abs($maior) : 1;

        // Varias Linhas
        if (is_array($valores[0])) {

            $count_valores = count($valores);
            for ($i = 0; $i < $count_valores; $i++) {
                $v = array_values($valores[$i]);
                $px = $ix;
                $py = $fy - ($v[0] * $altura / $divisor);
                $primeiro = array_shift($v);

                // Pintar primeiro ponto
                if (!$mapa) {
                    switch ($this->ponto) {
                    case GRAFICO_PONTO_BOLA:
                        imagefilledellipse($img, $px, $py, 7, 7, $this->cores[$i]);
                        break;
                    case GRAFICO_PONTO_QUADRADO:
                        imagefilledrectangle($img, $px - 3, $py - 3, $px + 3, $py + 3, $this->cores[$i]);
                        break;
                    }

                // Mapa do primeiro ponto
                } else {
                    $ponto_x  = round($px - 5);
                    $ponto_x2 = round($px + 5);
                    $ponto_y  = round($py - 5);
                    $ponto_y2 = round($py + 5);
                    $title = ($this->legenda ? $this->legenda[$i].'/' : '').$this->escala[0].': '.$this->converter_valor($primeiro);
                    echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" title=\"{$title}\" nohref=\"nohref\" />\n";
                }

                foreach ($v as $j => $valor) {
                    $py2 = $fy - ($valor * $altura / $divisor);

                    // Linha e Ponto
                    if (!$mapa) {
                        imagesetthickness($img, 2);
                        imageline($img, $px, $py, $px + $div_h, $py2, $this->cores[$i]);
                        imagesetthickness($img, 1);

                        switch ($this->ponto) {
                        case GRAFICO_PONTO_BOLA:
                            imagefilledellipse($img, $px + $div_h, $py2, 7, 7, $this->cores[$i]);
                            break;
                        case GRAFICO_PONTO_QUADRADO:
                            imagefilledrectangle($img, $px + $div_h - 3, $py2 - 3, $px + $div_h + 3, $py2 + 3, $this->cores[$i]);
                            break;
                        }

                    // Mapa do ponto
                    } else {
                        $ponto_x  = round($px + $div_h - 5);
                        $ponto_x2 = round($px + $div_h + 5);
                        $ponto_y  = round($py2 - 5);
                        $ponto_y2 = round($py2 + 5);
                        $title = ($this->legenda ? $this->legenda[$i].'/' : '').$this->escala[$j + 1].': '.$this->converter_valor($valor);
                        echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" title=\"{$title}\" nohref=\"nohref\" />\n";
                    }
                    $py = $py2;
                    $px += $div_h;
                }
            }

        // Uma linha
        } else {
            $px = $ix;
            $py = $fy - ($valores[0] * $altura / $divisor);
            $primeiro = array_shift($valores);

            // Primeiro ponto
            if (!$mapa) {
                switch ($this->ponto) {
                case GRAFICO_PONTO_BOLA:
                    imagefilledellipse($img, $px, $py, 7, 7, $this->cores[0]);
                    break;
                case GRAFICO_PONTO_QUADRADO:
                    imagefilledrectangle($img, $px - 3, $py - 3, $px + 3, $py + 3, $this->cores[0]);
                    break;
                }

            // Mapa do primeiro ponto
            } else {
                $ponto_x  = round($px - 5);
                $ponto_x2 = round($px + 5);
                $ponto_y  = round($py - 5);
                $ponto_y2 = round($py + 5);
                $title = $this->escala[0].': '.$this->converter_valor($primeiro);
                echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" title=\"{$title}\" nohref=\"nohref\" />\n";
            }

            foreach ($valores as $i => $valor) {
                $py2 = $fy - ($valor * $altura / $divisor);

                // Linha e ponto
                if (!$mapa) {
                    imagesetthickness($img, 2);
                    imageline($img, $px, $py, $px + $div_h, $py2, $this->cores[0]);
                    imagesetthickness($img, 1);

                    switch ($this->ponto) {
                    case GRAFICO_PONTO_BOLA:
                        imagefilledellipse($img, $px + $div_h, $py2, 7, 7, $this->cores[0]);
                        break;
                    case GRAFICO_PONTO_QUADRADO:
                        imagefilledrectangle($img, $px + $div_h - 3, $py2 - 3, $px + $div_h + 3, $py2 + 3, $this->cores[0]);
                        break;
                    }

                // Mapa do ponto
                } else {
                    $ponto_x  = round($px + $div_h - 5);
                    $ponto_x2 = round($px + $div_h + 5);
                    $ponto_y  = round($py2 - 5);
                    $ponto_y2 = round($py2 + 5);
                    $title = $this->escala[$i + 1].': '.$this->converter_valor($valor);
                    echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" title=\"{$title}\" nohref=\"nohref\" />\n";
                }
                $py = $py2;
                $px += $div_h;
            }
        }
    }


    //
    //     Imprime um grafico de pizza
    //
    private function imprimir_grafico_pizza(&$img, $ix, $iy, $fx, $fy, $mapa) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        // Calcular a soma dos valores
        $soma = array_sum($this->valores);

        // Calcular raio X e Y da elipse
        $RX = (int)(($fx - $ix) / 2) - 10;
        $RY = (int)(($fy - $iy) / 2) - 10;

        // Calcular posicao do centro da elipse
        $CX = $ix + (int)(($fx - $ix) / 2);
        $CY = $iy + (int)(($fy - $iy) / 2);

        // Desenhar a Pizza!
        $inicio_angulo = 0;
        $max = count($this->valores);
        $soma_parcial = 0.0;
        $textos = array();
        for ($i = 0; $i < $max; $i++) {
            $soma_parcial += (double)$this->valores[$i];
            $angulo = ceil($this->valores[$i] * 360 / $soma);
            $porcentagem = round($this->valores[$i] * 100 / $soma, 2);
            $texto_porcentagem = texto::numero($porcentagem, 2, false, GRAFICO_LOCALIDADE).'%';
            $fim_angulo = $inicio_angulo + $angulo;

            // Imprimir Arco
            if (!$mapa) {
                if ($angulo) {
                    imagefilledarc($img, $CX, $CY, (2 * $RX), (2 * $RY), $inicio_angulo, $fim_angulo, $this->cores[$i], IMG_ARC_PIE);
                } else {
                    imageline($img, $CX, $CY, $CX + $RX * cos(deg2rad($inicio_angulo)), $CY + $RY * sin(deg2rad($inicio_angulo)), $this->cores[$i]);
                }
            }

            // Calcular porcentagem e posicao onde ela fica no grafico
            $meio_angulo = (int)(($inicio_angulo + $fim_angulo) / GRAFICO_PRECISAO);
            if ($i % 2 || $porcentagem > 20) {
                 $distancia = 0.5;
            } else {
                 $distancia = 0.7;
            }
            $x = $CX + ($RX * $distancia * cos(deg2rad($meio_angulo)));
            $y = $CY + ($RY * $distancia * sin(deg2rad($meio_angulo)));

            $largura = $this->largura_texto($texto_porcentagem);
            $x -= $largura / 2;
            $y += $this->tamanho_texto / 2;

            // Texto com sombra
            $obj = new stdClass();
            $obj->texto = $texto_porcentagem;
            $obj->x = $x;
            $obj->y = $y;
            $obj->title = $this->legenda[$i].': '.$this->converter_valor($this->valores[$i]).' ('.$texto_porcentagem.')';
            $textos[] = $obj;

            // Reiniciar angulo
            $inicio_angulo = (double)$soma_parcial * 360.0 / (double)$soma;
        }

        // Imprimir porcentagens
        foreach ($textos as $obj) {
            if (!$mapa) {
                $this->texto_sombra($img, $this->tamanho_texto, 0, $obj->x, $obj->y, $this->cores['fundo'], $this->cores['texto'], $obj->texto);
            } else {
                $ponto_x  = round($obj->x);
                $ponto_y  = round($obj->y);
                $ponto_x2 = round($obj->x + 50);
                $ponto_y2 = round($obj->y - 20);
                $title = $obj->title;
                echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" title=\"{$title}\" />\n";
            }
        }

        // Borda da Pizza
        if (!$mapa) {
            imagearc($img, $CX, $CY, (2 * $RX), (2 * $RY), 0, 360, $this->cores['borda']);
        }
    }


    //
    //     Imprime um grafico de pizza
    //
    private function imprimir_grafico_pilha(&$img, $ix, $iy, $fx, $fy, $mapa) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        $iy += GRAFICO_MARGEM;
        $valores = array_values($this->valores);

        // Obter maior valor da escala vertical
        if ($this->valor_topo === false) {
            $vetor_maior = array();
            foreach ($valores as $i => $vetor) {
                foreach ($vetor as $j => $item) {
                    if (!isset($vetor_maior[$j])) {
                        $vetor_maior[$j] = 0;
                    }
                    $vetor_maior[$j] += $item;
                }
            }
            $maior = max($vetor_maior);
        } else {
            $maior = $this->valor_topo;
        }

        // Imprimir moldura
        $v = $this->imprimir_moldura($img, $maior, $ix, $iy, $fx, $fy, $mapa);
        list($div_h, $ix, $iy, $fx, $fy) = $v;

        // Imprimir conteudo do grafico
        $this->imprimir_conteudo_pilha($img, $maior, $div_h, $ix, $iy + 1, $fx, $fy - 1, $mapa);

        // Imprimir linhas extras
        $this->imprimir_linhas_extras($img, $maior, $ix + 1 - $div_h / 2, $iy, $fx - 1 + $div_h / 2, $fy);
    }


    //
    //     Imprime o conteudo do grafico de pilha
    //
    private function imprimir_conteudo_pilha(&$img, $maior, $div_h, $ix, $iy, $fx, $fy, $mapa = false) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $maior: maior largura do grafico
    // Int $div_h: distancia entre as barras
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        $c = (GRAFICO_QUADRADO / 2);
        $altura = $fy - $iy;
        $valores = array_values($this->valores);

        $divisor = $maior !== 0 ? abs($maior) : 1;

        // Varias Barras (varias cores)
        $soma_parcial = array();
        foreach ($valores as $i => $v) {
            $v = array_values($v);
            $px = $ix;
            foreach ($v as $j => $valor) {
                if (!isset($soma_parcial[$j])) {
                    $soma_parcial[$j] = 0;
                }
                if ($valor) {
                    $py2 = $fy - ($soma_parcial[$j] * $altura / $divisor);
                    $soma_parcial[$j] += $valor;
                    $py1 = $fy - ($soma_parcial[$j] * $altura / $divisor);
                    $px1 = $px - $c;
                    $px2 = $px + $c - 1;
                    if (!$mapa) {
                        imagefilledrectangle($img, $px1, $py1, $px2, $py2, $this->cores[$i]);
                        $this->borda($img, $px1, $py1, $px2, $py2, $i);
                    } else {
                        $ponto_x = round($px1);
                        $ponto_y = round($py1);
                        $ponto_x2 = round($px2);
                        $ponto_y2 = round($py2);
                        $title = ($this->legenda ? $this->legenda[$i].'/' : '').$this->escala[$j].': '.$this->converter_valor($valor);
                        echo "<area shape=\"rect\" coords=\"{$ponto_x},{$ponto_y},{$ponto_x2},{$ponto_y2}\" nohref=\"nohref\" title=\"{$title}\" />\n";
                    }
                }
                $px += $div_h;
            }
        }
    }


    //
    //     Imprime as linhas extras nos graficos de barra ou de linha
    //
    private function imprimir_linhas_extras(&$img, $maior, $ix, $iy, $fx, $fy) {
    // Resource $img: imagem usada para imprimir as linhas
    // Float $maior: maior valor do grafico
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    //
        if (!$this->linhas) {
            return;
        }
        $max = $fy - $iy;
        if ($this->legenda) {
            $cor = count($this->legenda);
        } else {
            $cor = 1;
        }

        $tam = count($this->linhas);
        for ($i = 0; $i < $tam; $i += 2) {
            $p1  = $this->linhas[$i];
            $px1 = $ix;
            $py1 = $maior ? $fy - ($p1 * $max / $maior) : $fy;

            $p2  = $this->linhas[$i + 1];
            $px2 = $fx;
            $py2 = $maior ? $fy - ($p2 * $max / $maior) : $fy;

            imageline($img, $px1, $py1, $px2, $py2, $this->cores[$cor]);
            $cor++;
        }
    }


    //
    //     Imprime a moldura do grafico de linhas e de barras
    //
    private function imprimir_moldura(&$img, $maior, $ix, $iy, $fx, $fy, $mapa = false) {
    // Resource $img: imagem usada para imprimir a legenda
    // Float $maior: maior valor do grafico
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // Bool $mapa: indica se deve exibir o mapa da imagem (true) ou o grafico (false)
    //
        // Calcular tamanho do texto da maior escala horizontal
        $maior_altura_escala_h = 0;
        $vt_largura_escala_h = array();
        if ($this->escala) {
            foreach ($this->escala as $e) {
                $altura_e  = $this->altura_texto($e, $this->tamanho_texto, GRAFICO_FONTE, $this->angulo);
                $largura_e = $this->largura_texto($e, $this->tamanho_texto, GRAFICO_FONTE, $this->angulo);
                $vt_largura_escala_h[] = $largura_e;
                if ($altura_e > $maior_altura_escala_h) {
                    $maior_altura_escala_h = $altura_e;
                }
            }
            $maior_altura_escala_h  += GRAFICO_MARGEM;
        }

        // Calcular largura da maior escala vertical
        $maior_largura_escala_v = 0;
        $escala = 0;
        $num_escala_estimado = 10;
        $casas_decimais = 0.11;
        $div_escala = ($fy - $iy) / $num_escala_estimado;
        for ($i = $num_escala_estimado; $i > 0; $i--) {
            $texto_escala = $this->converter_valor($escala, 2, true);
            $largura_escala_v = $this->largura_texto(' '.$texto_escala.' ');
            $maior_largura_escala_v = $largura_escala_v > $maior_largura_escala_v ? $largura_escala_v : $maior_largura_escala_v;
            $escala += $div_escala + $casas_decimais;
        }

        // Em casos de escalas horizontais com angulo:
        // a largura ocupada pelos textos dos itens (especialmente os da esquerda)
        // podem ser superiores ao da largura da maior escala vertical
        $div = 35;
        if ($this->angulo !== false && $this->angulo !== 90) {
            $sub = GRAFICO_MARGEM + GRAFICO_MARGEM;
            foreach ($vt_largura_escala_h as $largura_escala_h) {
                $diferenca = $largura_escala_h - ($sub + $div);
                if ($diferenca < 0) { break; }
                if ($maior_largura_escala_v < $diferenca) {
                    $maior_largura_escala_v = GRAFICO_MARGEM + $diferenca + GRAFICO_MARGEM;
                }
                $div += 35;
            }
        }

        $igx = $ix + $maior_largura_escala_v + GRAFICO_MARGEM + GRAFICO_MARGEM;
        $igy = $iy;
        $fgx = $fx;
        $fgy = $fy - $maior_altura_escala_h;

        $altura     = $fgy - $igy;
        $num_escala = $altura / (GRAFICO_MARGEM + $this->tamanho_texto + GRAFICO_MARGEM);
        while ($altura % (GRAFICO_MARGEM + $this->tamanho_texto + GRAFICO_MARGEM)) {
            $altura--;
        }
        $num_escala = round($altura / (GRAFICO_MARGEM + $this->tamanho_texto + GRAFICO_MARGEM));
        $fgy = $igy + $altura;

        $div_escala = $maior / $num_escala;
        $div        = $altura / $num_escala;

        // Imprimir escala vertical

        // Imprimir o ponto zero
        $texto_escala = $this->converter_valor(0);
        $px = $ix + $maior_largura_escala_v - $this->largura_texto($texto_escala);
        if (!$mapa) {
            imagettftext($img, $this->tamanho_texto, 0, $px, $fgy + ($this->tamanho_texto / 2), $this->cores['texto'], GRAFICO_FONTE, $texto_escala);
        }
        $py = $fgy;
        $escala = 0;
        if (!$mapa && $div_escala) {
            for ($i = $num_escala; $i > 0; $i--) {
                $escala += $div_escala;
                $texto_escala = $this->converter_valor($escala);
                $py -= $div;
                $px = $ix + $maior_largura_escala_v - $this->largura_texto($texto_escala);
                imagettftext($img, $this->tamanho_texto, 0, $px, $py + ($this->tamanho_texto / 2), $this->cores['texto'], GRAFICO_FONTE, $texto_escala);
                imageline($img, $igx - GRAFICO_MARGEM, $py, $fgx, $py, $this->cores['pontilhado']);
            }
        }

        $valores = array_values($this->valores);
        if (is_array($valores[0])) {
            $count_valores = count($valores[0]);
        } else {
            $count_valores = count($valores);
        }

        // Imprimir escala horizontal
        $largura = $fgx - $igx;
        if ($count_valores > 0) {
            $div_h = $largura / $count_valores;
        } else {
            $div_h = 0;
        }
        $px = $igx + ($div_h / 2);
        $c = $this->tamanho_texto / 2;
        if (!$mapa) {
            for ($i = 0; $i < $count_valores; $i++) {
                if ($this->escala) {
                    $e = $this->escala[$i];
                    $altura_escala  = $this->altura_texto($e, $this->tamanho_texto, GRAFICO_FONTE, $this->angulo);
                    $largura_escala = $this->largura_texto($e, $this->tamanho_texto, GRAFICO_FONTE, $this->angulo);
                    $pey = $fgy + $altura_escala  + GRAFICO_MARGEM;
                    $pex = $px  - $largura_escala + GRAFICO_MARGEM + (10 * $this->angulo / 90);
                    imagettftext($img, $this->tamanho_texto, $this->angulo, $pex, $pey, $this->cores['texto'], GRAFICO_FONTE, $e);
                    imageline($img, $px, $igy, $px, $fgy + GRAFICO_MARGEM, $this->cores['pontilhado']);
                } else {
                    imageline($img, $px, $igy, $px, $fgy, $this->cores['pontilhado']);
                }
                $px += $div_h;
            }

            // Imprimir moldura
            imagerectangle($img, $igx, $igy, $fgx, $fgy, $this->cores['borda']);
        }

        $igx += ($div_h / 2);
        if ($count_valores == 1) {
            $fgx = $igx;
        } else {
            $fgx = $igx + ($div_h * ($count_valores - 1));
        }

        return array($div_h, $igx, $igy, $fgx, $fgy);
    }


    //
    //     Retorna a largura da imagem de um texto
    //
    public function largura_texto($texto, $altura = null, $fonte = GRAFICO_FONTE, $angulo = 0) {
    // String $texto: texto a ser conferido
    // Int $altura: altura da fonte
    // String $fonte: fonte utilizada
    // Int $angulo: angulo do texto
    //
        if ($altura === null) {
            $altura = $this->tamanho_texto;
        }
        $tamanho = $this->tamanho_texto($texto, $altura, $fonte, $angulo);
        return $tamanho[0];
    }


    //
    //     Retorna a altura da imagem de um texto
    //
    public function altura_texto($texto, $altura = null, $fonte = GRAFICO_FONTE, $angulo = 0) {
    // String $texto: texto a ser conferido
    // Int $altura: altura da fonte
    // String $fonte: fonte utilizada
    // Int $angulo: angulo do texto
    //
        if ($altura === null) {
            $altura = $this->tamanho_texto;
        }
        $tamanho = $this->tamanho_texto($texto, $altura, $fonte, $angulo);
        return $tamanho[1];
    }


    //
    //     Retorna a largura e altura da imagem de um texto (atraves de um array)
    //
    public function tamanho_texto($texto, $altura = null, $fonte = GRAFICO_FONTE, $angulo = 0) {
    // String $texto: texto a ser conferido
    // Int $altura: altura da fonte
    // String $fonte: fonte utilizada
    // Int $angulo: angulo do texto
    //
        if ($altura === null) {
            $altura = $this->tamanho_texto;
        }
        $aux = imagecreate(1000, 1000);
        $v = imagettftext($aux, $altura, 0, 10, 10, 0, $fonte, $texto);
        imagedestroy($aux);

        $altura  = $v[1] - $v[7];
        $largura = $v[4] - $v[0];

        if ($angulo) {
            $complemento = abs(180 - ($angulo + 90));
            while ($complemento >= 180) {
                $complemento -= 180;
            }

            $rad   = deg2rad($angulo);
            $rad_c = deg2rad($complemento);

            $sen_rad   = round(sin($rad), 2);
            $cos_rad   = round(cos($rad), 2);
            $sen_rad_c = round(sin($rad_c), 2);
            $cos_rad_c = round(cos($rad_c), 2);

            $altura_angulo  = $sen_rad * $largura + $sen_rad_c * $altura;
            $largura_angulo = $cos_rad * $largura + $cos_rad_c * $altura;
        }

        if ($angulo) {
            return array(abs($largura_angulo), abs($altura_angulo));
        }
        return array(abs($largura), abs($altura));
    }


    //
    //     Imprime um texto com sombra
    //
    private function texto_sombra(&$img, $altura, $angulo, $x, $y, $cor, $sombra, $texto) {
    // Resource $img: imagem usada para imprimir a legenda
    // Int $altura: altura do texto
    // Int $angulo: angulo do texto
    // Int $x: coordenada x
    // Int $y: coordenada y
    // Int $cor: cor do texto
    // Int $sombra: cor da sombra
    // String $texto: texto a ser impresso
    //
        $v1 = array($x - 1, $x, $x + 1);
        $v2 = array($y - 1, $y, $y + 1);
        foreach ($v1 as $xs) {
            foreach ($v2 as $ys) {
                imagettftext($img, $altura, $angulo, $xs, $ys, $sombra, GRAFICO_FONTE, $texto);
            }
        }
        imagettftext($img, $altura, $angulo, $x, $y, $cor, GRAFICO_FONTE, $texto);
    }


    //
    //     Imprime uma borda
    //
    private function borda(&$img, $ix, $iy, $fx, $fy, $cor = 'borda', $solida = false) {
    // Resource $img: imagem usada para imprimir a borda
    // Int $ix: coordenada x inicial (superior esquerdo)
    // Int $iy: coordenada y inicial
    // Int $fx: coordenada x final (inferior direito)
    // Int $fy: coordenada y final
    // String $cor: cor utilizada para fazer a borda
    // Bool $solida: forcar para que a borda seja solida
    //
        if ($solida || ($this->borda == GRAFICO_BORDA_SOLIDA)) {
            imagerectangle($img, $ix, $iy, $fx, $fy, $this->cores[$cor]);
        } elseif ($this->borda == GRAFICO_BORDA_3D) {

            // Horizontal Clara
            imageline($img, $ix + 1, $iy + 1, $fx - 1, $iy + 1, $this->cores['claro'.$cor]);

            // Vertical Clara
            imageline($img, $ix + 1, $iy + 1, $ix + 1, $fy, $this->cores['claro'.$cor]);

            // Vertical Escura
            imageline($img, $fx, $iy + 1, $fx, $fy, $this->cores['escuro'.$cor]);

            // Horizontal Escura
            imageline($img, $ix + 1, $fy, $fx, $fy, $this->cores['escuro'.$cor]);
        }
    }


    //
    //     Aloca as cores necessarias
    //
    private function alocar_cores(&$img) {
    // Resource $img: imagem usada para imprimir a legenda
    //
        // Criar as cores extras
        $cores = $this->get_cores();
        foreach ($cores as $i => $c) {

            // Cores indexadas (fundo, fundo_grafico, texto, borda e linha)
            if (is_string($i)) {
                list($r, $g, $b) = $c;
                $this->cores[$i] = imagecolorallocate($img, $r, $g, $b);
                continue;
            }

            // Cores usadas no grafico
            switch ($this->tipo_cor) {
            case GRAFICO_COR_NORMAL:
                list($r, $g, $b) = $c;
                break;
            case GRAFICO_COR_CLARA:
                $r = min($c[0] * GRAFICO_CLAREAR, 255);
                $g = min($c[1] * GRAFICO_CLAREAR, 255);
                $b = min($c[2] * GRAFICO_CLAREAR, 255);
                break;
            case GRAFICO_COR_ESCURA:
                $r = max($c[0] * GRAFICO_ESCURECER, 0);
                $g = max($c[1] * GRAFICO_ESCURECER, 0);
                $b = max($c[2] * GRAFICO_ESCURECER, 0);
                break;
            }

            // Cores clareadas
            $rc = min($r * GRAFICO_CLAREAR, 255);
            $gc = min($g * GRAFICO_CLAREAR, 255);
            $bc = min($b * GRAFICO_CLAREAR, 255);

            // Cores escurecidas
            $re = max($r * GRAFICO_ESCURECER, 0);
            $ge = max($g * GRAFICO_ESCURECER, 0);
            $be = max($b * GRAFICO_ESCURECER, 0);

            $this->cores[$i]          = imagecolorallocate($img, $r, $g, $b);
            $this->cores['claro'.$i]  = imagecolorallocate($img, $rc, $gc, $bc);
            $this->cores['escuro'.$i] = imagecolorallocate($img, $re, $ge, $be);
        }

        // Definir pontilhado
        $pontilhado = array($this->cores['linha'], $this->cores['linha'], $this->cores['fundo_grafico'], $this->cores['fundo_grafico']);
        imagesetstyle($img, $pontilhado);
        $this->cores['pontilhado'] = IMG_COLOR_STYLED;
    }


    //
    //     Define as cores personalizadas
    //     O vetor de cores deve ter os seguintes indices:
    //     * fundo: cor do fundo da imagem
    //     * fundo_grafico: cor do fundo do grafico
    //     * texto: cor do texto
    //     * borda: cor da borda
    //     * linha: cor da linha
    //     * 0..N: cores usadas no grafico (para pintar linhas, barras, circulos, etc.)
    //
    public function set_cores($cores) {
    // Array[(String || Int) => Array[Int] || String] $cores: vetor de cores indexado (vetor com R, G e B ou string no formato #RRGGBB)
    //
        $this->cores_usuario = array();
        foreach ($cores as $i => $rgb) {
            if (is_string($rgb)) {
                if (preg_match('/^#([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})$/', $rgb, $matches)) {
                    $rgb = array(hexdec($matches[1]), hexdec($matches[2]), hexdec($matches[3]));
                    unset($matches);
                } else {
                    trigger_error('RGB invalido: '.$rgb, E_USER_WARNING);
                    continue;
                }
            } elseif (is_array($rgb)) {
                //void
            } else {
                trigger_error('Tipo invalido para valor de cor: '.util::get_tipo($rgb), E_USER_WARNING);
                continue;
            }
            $valido = true;
            foreach ($rgb as $c) {
                if ($c < 0 || $c > 255) {
                    $valido = false;
                }
            }
            if ($valido) {
                $this->cores_usuario[$i] = $rgb;
            }
        }
    }


    //
    //     Retorna um vetor de cores
    //
    private function get_cores() {
        $cores = array();

        // Cores usadas no grafico
        $cores[] = array(200, 100, 100); // Vermelho
        $cores[] = array(100, 100, 200); // Azul
        $cores[] = array(200, 200,  50); // Amarelo
        $cores[] = array( 50, 200, 200); // Ciano
        $cores[] = array(180, 180, 180); // Cinza
        $cores[] = array(100, 200, 100); // Verde
        $cores[] = array(200, 100,   0); // Laranja
        $cores[] = array(170, 170,  70); // Marrom
        $cores[] = array(230, 230, 230); // Cinza Claro
        $cores[] = array( 51,   0, 102); // Roxo
        $cores[] = array(200, 100, 200); // Pink
        $cores[] = array(187,   0,   0); // Vermelho escuro
        $cores[] = array(  0, 187,   0); // Verde escuro
        $cores[] = array(  0,   0,  85); // Azul escuro
        $cores[] = array(100, 255, 204); // Aqua
        $cores[] = array(136,   0,   0); // Marrom escuro
        $cores[] = array(204,  51, 153); // Rosa
        $cores[] = array(255, 102,   0); // Laranja claro
        $cores[] = array(  0, 153, 153); // Verde Azulado
        $cores[] = array( 60,  60,  60); // Cinza Escuro

        // Checar se precisa de mais cores
        $count_cores = count($cores);
        if ($this->legenda) {
            $necessario = count($this->legenda);
        } elseif (is_array($this->valores)) {
            $necessario = count($this->valores);
        } else {
            $necessario = 0;
        }
        if (is_array($this->legenda_linhas)) {
            $necessario += count($this->legenda_linhas);
        }

        // Criar cores aleatorias
        while ($count_cores < $necessario) {
            $r = mt_rand(1, 255);
            $g = mt_rand(1, 255);
            $b = mt_rand(1, 255);
            $cores[]  = array($r, $g, $b);
            $count_cores++;
        }

        // Cores gerais
        $cores['fundo']         = array(255, 255, 240); // Amarelo claro
        $cores['fundo_grafico'] = array(255, 255, 255); // Branco
        $cores['texto']         = array(  0,   0,   0); // Preto
        $cores['borda']         = array( 20,  20,  20); // Cinza escuro
        $cores['linha']         = array(150, 150, 150); // Cinza claro

        // Sobrescrever com cores definidas pelo usuario
        if ($this->cores_usuario) {
            foreach ($this->cores_usuario as $i => $rgb) {
                $cores[$i] = $rgb;
            }
        }

        return $cores;
    }


    //
    //     Imprime um grafico com HTML
    //
    public function imprimir_grafico_html() {
        setlocale(LC_ALL, 'C');

        $vt_cores = $this->get_cores();
        $vt_cores['fundo']         = array(255, 255, 240);
        $vt_cores['fundo_grafico'] = array(255, 255, 255);
        $vt_cores['texto']         = array(  0,   0,   0);
        $vt_cores['borda']         = array( 20,  20,  20);
        $vt_cores['linha']         = array(150, 150, 150);

        foreach ($vt_cores as $i => $c) {
            $this->cores[$i] = sprintf('#%02X%02X%02X', $c[0], $c[1], $c[2]);
        }

        $largura = $this->largura;

        if ($this->legenda) {
            switch ($this->pos_legenda) {
            case GRAFICO_DIREITA:
                $antes = true;
                $lado  = 'position: relative; float: left;';
                $ladol = 'position: relative; float: right;';
                $largura += 200 + GRAFICO_MARGEM;
                break;
            case GRAFICO_ESQUERDA:
                $antes = true;
                $lado  = 'position: relative; float: right;';
                $ladol = 'position: relative; float: left;';
                $largura += 200 + GRAFICO_MARGEM;
                break;
            case GRAFICO_CIMA:
                $antes = false;
                $lado = 'margin-bottom: '.GRAFICO_MARGEM.'px;';
                $ladol = '';
                break;
            case GRAFICO_BAIXO:
                $antes = true;
                $lado = 'margin-top: '.GRAFICO_MARGEM.'px;';
                $ladol = '';
                break;
            }
        }

        echo "<div style=\"border: 1px solid ".$this->cores['borda']."; ".
             "background-color: ".$this->cores['fundo']."; padding: ".GRAFICO_MARGEM."px; width: {$largura}px;\">\n";
        echo "<strong style=\"color: ".$this->cores['borda']."; clear: both;\">{$this->titulo}</strong>\n";
        if ($this->legenda) {
            if ($antes) {
                $this->imprimir_legenda_html($ladol);
                $this->imprimir_grafico_barra_html($lado);
            } else {
                $this->imprimir_grafico_barra_html($lado);
                $this->imprimir_legenda_html($ladol);
            }
        } else {
            $this->imprimir_grafico_barra_html();
        }
        echo "<br style=\"font-size: 0px; clear: both;\" />\n";
        echo "</div>\n";

        setlocale(LC_ALL, GRAFICO_LOCALIDADE);
    }


    //
    //     Imprime uma legenda em HTML
    //
    private function imprimir_legenda_html($lado = '') {
    // String $lado: estilo indicando o lado
    //
        $largura = 200 - (2 * GRAFICO_MARGEM);

        echo "<div id=\"legenda\" style=\"border: 1px solid ".$this->cores['borda']."; ".
             "background-color: ".$this->cores['fundo_grafico']."; width: {$largura}px; ".
             "padding: ".GRAFICO_MARGEM."px; {$lado}\">\n";
        echo "<strong style=\"display: block;\">Legenda:</strong>\n";
        echo "<ul style=\"margin: 0px; padding: 0px; list-style-type: none; list-style-image: none;\">\n";
        foreach ($this->legenda as $i => $l) {
            if ($this->borda == GRAFICO_BORDA_SOLIDA) {
                $borda = "border: 1px solid ".$this->cores['borda']."; ";
            } else {
                $borda = "border: 1px outset ".$this->cores[$i]."; ";
            }

            $q = "<span style=\"display: block; width: ".GRAFICO_QUADRADO."px; height: ".GRAFICO_QUADRADO."px; ".
                 $borda."float: left; margin: 5px 3px; ".
                 "background-color: ".$this->cores[$i]."; line-height: 1px;\">&nbsp;</span>\n";

            echo "  <li style=\"clear: both;\">{$q} <span>{$l}</span></li>\n";
        }
        echo "</ul>\n";
        echo "</div>\n";
    }


    //
    //     Imprime um grafico de barra em HTML
    //
    private function imprimir_grafico_barra_html($lado = '') {
    // String $lado: codigo CSS que define o lado do grafico
    //
        $largura = $this->largura - (2 * GRAFICO_MARGEM);
        $divisor = $maior !== 0 ? abs($maior) : 1;

        echo "<div id=\"grafico\" style=\"border: 1px solid ".$this->cores['borda'].";".
             "background-color: ".$this->cores['fundo_grafico']."; ".
             "width: {$largura}px; min-height: {$this->altura}px; ".
             "padding: ".GRAFICO_MARGEM."px; {$lado}\">\n";

        echo "<div>\n";

        // Barras multiplas (varias cores)
        $valores = array_values($this->valores);
        if (is_array($valores[0])) {
            $maior = 0;
            foreach ($valores as $v) {
                $v = array_values($v);
                $maior_v = max($v);
                $maior = ($maior > $maior_v) ? $maior : $maior_v;
            }

            $w = $this->largura - (80 + (3 * GRAFICO_MARGEM) + 10);
            $h = (($this->altura - (2 * GRAFICO_MARGEM)) / count($this->escala) / count($this->legenda)) - 5;
            foreach ($this->escala as $i => $escala) {
                echo '<div style="display: table-row;">';
                echo '<p style="display: table-cell; vertical-align: middle; text-align: right;">';
                echo '<strong>'.$escala.':</strong>';
                echo '</p>';
                echo '<div style="display: table-cell;">';
                foreach ($valores as $j => $v) {
                    $valor = $v[$i];
                    $valor_impressao = $this->converter_valor($valor);

                    $porcentagem = round($valor * 100 / $divisor, 0);
                    $texto = '<div style="display: none;">'.$this->legenda[$j].': '.$valor_impressao.'</div>';
                    $p = "<div style=\"width: {$porcentagem}%; border: 1px outset ".$this->cores[$j]."; ".
                         "background-color: ".$this->cores[$j]."; ".
                         "height: {$h}px; font-size: {$h}px;\" >{$texto}</div>";

                    echo "<div style=\"border: 1px inset #F5F5F5; margin: 1px; ".
                         "background-color: #F5F5F5; width: {$w}px; height: {$h}px; line-height: 1px;".
                         "font-size: {$h}px; margin-top: 5px;\">{$p}</div>\n";
                }
                echo '</div>';
                echo '</div>';
            }


        // Uma barra (uma cor)
        } else {
            $maior = max($valores);
            $w_escala = 80;
            $w_valor  = 100;
            $w = $this->largura - ($w_escala + 3 * GRAFICO_MARGEM + $w_valor);
            $h = GRAFICO_QUADRADO;
            $h2 = $h - 2;
            foreach ($valores as $i => $valor) {
                $valor_impressao = $this->converter_valor($valor);
                $porcentagem = round($valor * 100 / ($maior ? $maior : 1), 0);
                $texto_porcentagem = texto::numero($porcentagem, 2, false, GRAFICO_LOCALIDADE).'%';

                echo "<div style=\"clear: both;\">\n";

                if ($this->escala) {
                    $e = $this->escala[$i];
                    echo "<div style=\"text-align: right; padding-right: 5px; width: {$w_escala}px; float: left;\" >{$e}:&nbsp;</div>";
                }

                $p = "<div style=\"width: {$porcentagem}%; border: 1px outset ".$this->cores[0]."; ".
                     "background-color: ".$this->cores[0]."; ".
                     "height: {$h2}px; font-size: {$h2}px;\" ></div>";

                echo "<div style=\"position: relative; top: 5px; float: left; border: 1px inset #F5F5F5; margin: 1px; ".
                     "background-color: #F5F5F5; width: {$w}px; height: {$h}px; line-height: 1px;".
                     "font-size: {$h}px;\">{$p}</div>\n";

                echo "<div style=\"text-align: right; width: {$w_valor}px; float: left;\" >{$valor_impressao} ({$texto_porcentagem})</div>";

                echo "</div>\n";
            }
        }

        echo "<div style=\"clear: both; height: 1px;\"></div>\n";
        echo "</div>\n";

        echo "</div>\n";
    }


    //
    //     Imprime a descricao do grafico em HTML
    //
    private function descrever_grafico() {
        parse_str($_SERVER['QUERY_STRING'], $vt_query);
        unset($vt_query['longdesc']);
        if (empty($vt_query)) {
            $query = '';
        } else {
            $query = '?'.http_build_query($vt_query);
        }
        $link = $_SERVER['SCRIPT_NAME'].$query;

        echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">\n";
        echo '<html>';
        echo '<head>';
        echo '  <title>'.texto::codificar($this->titulo).'</title>';
        echo '  <link rev="help" href="'.$link.'" />';
        echo '</head>';
        echo '<body>';
        echo '<h1>'.texto::codificar($this->titulo).'</h1>';

        // Bidimensional
        $valores = array_values($this->valores);
        if (is_array($valores[0])) {
            $maior = 0;
            $subtotal = array();
            foreach ($valores as $i => $v) {
                $v = array_values($v);
                foreach ($v as $j => $v2) {
                    if (!isset($subtotal[$j])) {
                        $subtotal[$j] = 0;
                    }
                    $subtotal[$j] += $v2;
                }
                $maior_v = max($v);
                $maior = ($maior > $maior_v) ? $maior : $maior_v;
            }
            foreach ($this->escala as $i => $escala) {
                echo '<p>';
                echo '<strong>'.texto::codificar($escala).'</strong>';
                echo '<ul>';
                foreach ($valores as $j => $v) {
                    $valor = $v[$i];
                    $texto_valor = $this->converter_valor($valor);
                    $porcentagem = round($valor * 100 / $subtotal[$i], 0);
                    $texto_porcentagem = texto::numero($porcentagem, 2, false, GRAFICO_LOCALIDADE).'%';
                    echo '<li><em>'.texto::codificar($this->legenda[$j]).':</em> '.$texto_valor.' ('.$texto_porcentagem.')</li>';
                }
                echo '</ul>';
                echo '</p>';
            }

        // Unidimensional
        } else {
            $total = array_sum($valores);
            foreach ($valores as $i => $valor) {
                $texto_valor = $this->converter_valor($valor);
                $porcentagem = round($valor * 100 / $total, 0);
                $texto_porcentagem = texto::numero($porcentagem, 2, false, GRAFICO_LOCALIDADE).'%';

                echo '<p>';
                if ($this->escala) {
                    echo '<strong>'.texto::codificar($this->escala[$i]).':</strong> ';
                } elseif ($this->legenda) {
                    echo '<strong>'.texto::codificar($this->legenda[$i]).':</strong> ';
                }
                echo $texto_valor.' ('.$texto_porcentagem.')';
                echo '</p>';
            }
            $texto_total = $this->converter_valor($total);
            echo '<p><strong>Total:</strong> '.$texto_total.'</p>';
        }

        if ($this->legenda_linhas) {
            echo '<hr />';
            $count_linhas = count($this->linhas);
            $j = 0;
            for ($i = 0; $i < $count_linhas; $i += 2) {
                $legenda = $this->legenda_linhas[$j++];
                $valor = $this->linhas[$i];
                if ($this->linhas[$i] != $this->linhas[$i + 1]) {
                    $valor .= '..'.$this->linhas[$i + 1];
                }
                echo '<p><strong>'.texto::codificar($legenda).':</strong> '.texto::codificar($valor).'</p>';
            }
        }

        echo '<body>';
        echo '</html>';
    }


    //
    //     Exibe o mapa da imagem
    //
    private function imprimir_mapa($id) {
    // String $id: identificador do mapa
    //

        // Calcular Tamanhos
        $this->tamanhos($vt_total, $vt_grafico, $vt_legenda);

        // Largura e altura total
        list($largura, $altura) = $vt_total;

        // Limites da area do grafico
        list($inicio_x, $inicio_y, $fim_x, $fim_y) = $vt_grafico;

        // Imprimir o Mapa
        $img = null;
        $img = imagecreatetruecolor($largura, $altura);

        echo "<map name=\"{$id}\" id=\"{$id}\">\n";
        $this->imprimir_grafico($img, $inicio_x, $inicio_y, $fim_x, $fim_y, true);
        echo "</map>\n";

        imagedestroy($img);
    }


    //
    //     Converte os valores para exibicao
    //
    public function converter_valor($valor, $casas_decimais = 2, $fixo = false) {
    // Int || Float $valor: valor a ser convertido
    // Int $casas_decimais: numero maximo de casas decimais apresentado
    // Bool $fixo: indica se deve usar um numero fixo de casas decimais
    //
        if ($this->conversao_valores) {
            $novo_valor = call_user_func($this->conversao_valores, $valor);
        } elseif ($this->codigo_conversao_valores) {
            $novo_valor = eval($this->codigo_conversao_valores);
        } else {
            $novo_valor = texto::numero($valor, $casas_decimais, $fixo, GRAFICO_LOCALIDADE);
        }
        return $novo_valor;
    }

}//class
