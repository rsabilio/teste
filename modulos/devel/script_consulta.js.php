<?php
//
// SIMP
// Descricao: JavaScript para incluir eventos no select do tipo de atributo
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.4.7
// Data: 16/05/2008
// Modificado: 04/10/2012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../../config.php');

$js = <<<JAVASCRIPT

definir_eventos();

//
//     Define os eventos
//
function definir_eventos() {
    var select = document.getElementById("select_tipo2");
    if (select) {
        select.onchange = function() {
            var s = document.getElementById("select_enum");
            var c = document.getElementById("select_const");
            var i = document.getElementById("input_operando2");
            switch (this.value) {
            case 'enum':
                if (s) { s.style.display = 'inline'; }
                if (c) { c.style.display = 'none';   }
                if (i) { i.style.display = 'none';   }
                break;
            case 'const':
                if (s) { s.style.display = 'none';   }
                if (c) { c.style.display = 'inline'; }
                if (i) { i.style.display = 'none';   }
                break;
            default:
                if (s) { s.style.display = 'none';   }
                if (c) { c.style.display = 'none';   }
                if (i) { i.style.display = 'inline'; }
                break;
            }

        };
        select.onchange();
    }
}
JAVASCRIPT;

// Codificar
$js_packer = new javascriptpacker($js);
$js = $js_packer->pack();

$opcoes_http = array(
    'arquivo' => 'script_consulta.js',
    'tempo_expira' => TEMPO_EXPIRA,
    'compactacao' => true,
    'ultima_mudanca' => getlastmod()
);

// Exibir
http::cabecalho('text/javascript; charset='.$CFG->charset, $opcoes_http);
echo $js;
exit(0);
