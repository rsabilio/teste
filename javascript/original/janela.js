//
// SIMP
// Descricao: JavaScript para exibir janelas dinamicamente
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.2.2
// Data: 20/12/2007
// Modificado: 10/06/2011
// TODO: Funcionar no IE(ca)
// License: LICENSE.TXT
// Copyright (C) 2007  Rubens Takiguti Ribeiro
//


// Variaveis globais
{
    GLB.janela_ativa = null;

    class_janela.instancias     = new Array();
    class_seletor.instancias    = new Array();
    class_hierarquia.instancias = new Array();
    class_calendario.instancias = new Array();
    class_popup.instancias      = new Array();
}


//
//     Muda o foco de uma janela montada com Javascript
//
function foco_janela(j) {
// DIV j: janela para dar o foco
//
    if (GLB.janela_ativa) {
        GLB.janela_ativa.style.zIndex = GLB.janela_ativa.zIndex_original;
    }
    GLB.janela_ativa = j;
    GLB.janela_ativa.zIndex_original = GLB.janela_ativa.style.zIndex;
    GLB.janela_ativa.style.zIndex = 1000;
}


//
//     Classe janela
//
function class_janela(id) {
    var that = this;

    this.id_janela = id;
    this.id  = class_janela.instancias.length;
    class_janela.instancias[this.id] = this;

    this.caixa   = null;
    this.titulo  = null;
    this.pai     = null;
    this.visivel = false;


    //
    //     Abrir janela dentro de um elemento
    //
    this.abrir = function(pai) {
    // Object pai: algum DIV ou o proprio BODY para armazenar a janela
    //
        if (that.visivel) { return; }
        if (pai == undefined) {
            var b = document.getElementsByTagName("body");
            if (b.length) {
                pai = b.item(0);
            }
        }

        that.pai = pai;
        that.pai.appendChild(that.caixa);
        that.visivel = true;
        foco_janela(that.caixa);
    };


    //
    //     Fechar janela
    //
    this.fechar = function() {
        if (!that.visivel) { return; }
        that.pai.removeChild(that.caixa);
        that.visivel = false;

        if (that.simp_onclose) {
            that.simp_onclose();
        }
    };


    //
    //     Muda o titulo da janela
    //
    this.set_titulo = function(texto_titulo) {
    // String texto_titulo: novo titulo da janela
    //
        var divs = that.titulo.getElementsByTagName("div");
        var l = divs.length;
        for (var i = 0; i < l; i++) {
            var div = divs.item(i);
            if (get_classe(div) == "texto") {
                limpar(div);
                div.appendChild(document.createTextNode(texto_titulo));
                return;
            }
        }
    };


    //
    //     Criar uma janela
    //
    this.criar_janela = function(texto_titulo, x, y, w, h) {
    // String texto_titulo: titulo da janela
    // Int x: posicao x da janela na tela (em px)
    // Int y: posicao y da janela na tela (em px)
    // Int w: largura da janela (em px)
    // Int h: altura da janela (em px)
    //
        var id = (that.id_janela) ? that.id_janela : 'janela' + that.id;
        x = Math.max(0, parseInt(x));
        y = Math.max(0, parseInt(y));
        w = Math.abs(parseInt(w));
        h = Math.abs(parseInt(h));

        // Criar container
        that.caixa = criar_elemento(
            "div",
            {"class":"caixa", "id":id}
        );

        if (x) { that.caixa.style.left   = x + "px"; }
        if (y) { that.caixa.style.top    = y + "px"; }
        if (w) { that.caixa.style.width  = w + "px"; }
        if (h) { that.caixa.style.height = h + "px"; }
        {
            // Criar titulo da caixa
            that.titulo = criar_elemento("h2", {"class":"titulo"});
            {
                // Texto da caixa
                var div_texto = criar_elemento("div", {"class":"texto"}, {}, texto_titulo);

                // Area de botoes
                var div_botoes = criar_elemento("div", {"class":"botoes"});
                {
                    // Botao de fechar
                    that.bt_fechar = criar_elemento("a", {"class":"bt_fechar"}, {}, "fechar");
                    div_botoes.appendChild(that.bt_fechar);
                }//div_botoes

                that.titulo.appendChild(div_texto);
                that.titulo.appendChild(div_botoes);

                // Incluir div com clear both
                var div_clear = criar_elemento("div", {}, {"clear":"both"});
                that.titulo.appendChild(div_clear);
            }//that.titulo
        }//that.caixa
        that.caixa.appendChild(that.titulo);

        // Definir eventos a caixa
        that.caixa.onmousedown = function() {
            foco_janela(that.caixa);
        };

        // Definir eventos ao botao de fechar
        that.bt_fechar.onmousedown = function() {
            that.bt_fechar.style.borderStyle = "inset";
        };
        that.bt_fechar.onmouseup = function() {
            that.bt_fechar.style.borderStyle = "outset";
        };
        that.bt_fechar.onclick = that.fechar;

        // Tornar a caixa movel
        objeto_movel(that.titulo, that.caixa);

        return that.caixa;
    };
}


//
//     Classe seletor de entidades
//
function class_seletor(link) {
// A link: link a ser transformado em seletor
//
    var that = this;
    this.id  = class_seletor.instancias.length;
    class_seletor.instancias[this.id] = this;

    // Atributos gerais
    this.link         = link;
    this.url          = link.getAttribute("href");
    this.input        = link.parentNode.getElementsByTagName("input").item(0);
    this.ultima_busca = "";
    this.seletor      = null;
    // seletor.janela
    // seletor.itens
    // seletor.input_busca

    // Atributos auxiliares
    this.timer_filtro  = null;
    this.ajax = new class_ajax();

    //
    //     Muda um item do seletor de acordo com a tecla digitada
    //
    this.mudar_item = function(e) {
    // Event e: evento ocorrido para chamada do metodo
    //
        var k = e ? (e.keyCode ? e.keyCode : e.which) : window.event.keyCode;
        switch (k) {

        // Enter
        case 13:
            var select = that.seletor.itens.firstChild;

            if (select.selectedIndex >= 0) {

                // Se o item esta visivel
                var item = select.options[select.selectedIndex];
                that.input.value = item.codigo;
                that.seletor.janela.fechar();
                try {
                    that.input.focus();
                    that.input.select();
                } catch (e) {}

            // Se nao ha nenhum item selecionado
            } else {
                var r = window.confirm("Nenhum item selecionado.\nVocê deseja limpar o campo de busca e procurar algum?");
                if (r) {
                    that.seletor.input_busca.value = "";
                    filtrar_seletor(that.id);
                    that.marcar(true);
                }
            }
            return false;

        // Seta para cima
        case 38:
            var select = that.seletor.itens.firstChild;
            if (select.selectedIndex >= 0) {
                var item_selecionado = select.options[select.selectedIndex];
                item = item_selecionado.previousSibling;
                while (item) {
                    if (item.visivel) {
                        item_selecionado.selected = false;
                        item.selected = true;
                        return false;
                    }
                    item = item.previousSibling;
                }
            }
            return false;

        // Seta para baixo
        case 40:
            var select = that.seletor.itens.firstChild;
            if (select.selectedIndex >= 0) {
                var item_selecionado = select.options[select.selectedIndex];
                item = item_selecionado.nextSibling;
                while (item) {
                    if (item.visivel) {
                        item_selecionado.selected = false;
                        item.selected = true;
                        return false;
                    }
                    item = item.nextSibling;
                }
            } else {
                var l = select.options.length;
                for (var i = 0; i < l; i++) {
                    var item = select.options[i];
                    if (item.visivel) {
                        item.selected = true;
                        return false;
                    }
                }
            }
            return false;

        // ESC
        case 27:
            that.seletor.janela.fechar();
            try {
                that.input.focus();
                that.input.select();
            } catch (e) {}
            return false;

        // Outra tecla
        default:
            that.ativar_timer_filtro();
            break;
        }
        return true;
    };


    //
    //     Obtem a lista de entidades consultadas e atualiza os itens do seletor
    //
    this.atualizar_itens = function(ajax) {
    // class_ajax ajax: objeto que devolve a requisicao
    //
        limpar(that.seletor.itens);

        var select = criar_elemento("select", {"size":"15"});
        select.onkeydown = that.mudar_item;

        var xml = ajax.get_retorno("xml");
        var entidades = xml.documentElement.getElementsByTagName("entidade");
        var l = entidades.length;
        for (var i = 0; i < l; i++) {
            var entidade = entidades.item(i);
            var codigo = entidade.getElementsByTagName("codigo").item(0).firstChild.nodeValue;
            var valor = entidade.getElementsByTagName("valor").item(0).firstChild.nodeValue;
            var texto_exibido = codigo + ": " + valor;
            
            // Criar linha
            var linha = criar_elemento("option", {"title":valor}, {}, texto_exibido);
            linha.pos = i;
            linha.visivel = true;
            linha.codigo = codigo;
            linha.valor = valor;
            linha.texto_exibido = texto_exibido.toLowerCase();

            // Inserir o codigo selecionado no input do formulario
            linha.ondblclick = function() {
                that.input.value = this.codigo;
                that.seletor.janela.fechar();
                try {
                    that.input.focus();
                    that.input.select();
                } catch (e) {}
            };

            // Adicionar linha na caixa de itens
            select.appendChild(linha);
        }
        that.seletor.itens.appendChild(select);

        that.seletor.input_busca.disabled = false;
        try {
            that.seletor.input_busca.focus();
            that.seletor.input_busca.select();
        } catch (e) {
            //void
        }
        filtrar_seletor(that.id);
    };


    //
    //     Ativa o timer para iniciar a filtragem
    //     (Faz a busca apenas apos o cliente ficar 1 segundo sem digitar)
    //
    this.ativar_timer_filtro = function() {
        if (that.timer_filtro) {
            cancelar_timer(that.timer_filtro);
        }
        that.timer_filtro = ativar_timer("filtrar_seletor('" + that.id + "')", 1000);
    };
  

    //
    //     Cria uma caixa de selecao
    //
    this.criar_caixa = function(pos) {
    // Object pos: posicao onde deve ser criada a caixa (atributos x e y em px)
    //
        // Criar janela
        var janela = new class_janela();
        var caixa = janela.criar_janela("Selecione um item", pos.x - 230, pos.y - 150);
        {
            // Criar espaco para busca entre os itens
            var busca = criar_elemento("div", {"class":"busca"});
            busca.link = link;
            {
                // Label
                var id = "busca_seletor_" + that.id;
                var label_busca = criar_elemento("label", {"id":id, "for":id}, {}, "Busca");

                // Input
                var input_busca = criar_elemento("input", {"type":"text", "maxlength":"40", "disabled":"disabled", "class":"input_busca", "id":id});
                input_busca.onkeydown = that.mudar_item;

                // Botao Fechar
                var atualizar = criar_elemento("img", {"class":"bt_atualizar", "alt":"Atualizar Lista", "src":CFG.wwwroot + "imgs/icones/atualizar.gif"});

                // So sera possivel clicar no atualizar 3 vezes
                // Caso contrario o cliente esta brincando com algo que consome muito recurso em segundo plano
                atualizar.pode = 3;

                // Acao ao clicar no icone de atualizar a lista
                atualizar.onclick = function() {
                    if (that.seletor.input_busca.disabled) {
                        window.alert("Aguarde o carregamento dos dados.");
                        return false;
                    }

                    this.pode--;
                    if (this.pode > 0)  {
                        that.seletor.input_busca.disabled = true;
                        that.carregar_itens();
                    } else {
                        window.alert("Atenção: você clicou em atualizar 3 vezes.\n" + 
                                     "Esta operação consome recursos e deve ser utilizada com moderação.");
                        this.parentNode.removeChild(this);
                    }
                    return true;
                }
            }//busca
            busca.appendChild(label_busca);
            busca.appendChild(input_busca);
            busca.appendChild(atualizar);

            // Criar espaco para listar os itens
            var itens = criar_elemento("div", {"class":"itens"});
        }//caixa

        // Adicionar elementos na caixa
        caixa.appendChild(busca);
        caixa.appendChild(itens);

        // Definir o seletor da classe
        that.seletor = {
            janela:janela,
            itens:itens,
            input_busca:input_busca
        };
    };
  
  
    //
    //     Carrega os elementos na caixa
    //
    this.carregar_itens = function() {
        that.ajax.set_callback([that, "atualizar_itens"]);
        that.ajax.exibir_carregando(that.seletor.itens);
        that.ajax.consultar("GET", that.url, true, null);
    };
  
  
    //
    //     Abre um seletor
    //
    this.link.onclick = function abrir_seletor(e) {
    // Event e: evento ao clicar sobre o link
    //
        e = e ? e : window.event;
        e.returnValue = false;
        if (!that.ajax.xmlhttp) {
            var l = adicionar_param(that.url, "input", that.input.id);
            window.open(l, "Busca", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=500, height=400");
            return false;
        }

        // Criar a caixa proximo a posicao do mouse e adiciona-la no documento
        if (that.seletor == null) {
            var pos = get_posicao_mouse(e);
            that.criar_caixa(pos);
            that.carregar_itens();
        }

        // Adicionar a caixa no documento
        that.seletor.janela.abrir(document.getElementsByTagName("body").item(0));

        // Dar o foco no campo de busca
        try {
            that.seletor.input_busca.focus();
            that.seletor.input_busca.select();
        } catch (e) {}
        return false;
    };
}


//
//     Filtra os elementos de um seletor
//
function filtrar_seletor(id_obj_filtro) {
// String id_obj_filtro: ID do objeto de filtro
//
    try {
        var obj_filtro = class_seletor.instancias[id_obj_filtro];
    } catch (e) {
        return false;
    }
    var busca = obj_filtro.seletor.input_busca.value.toLowerCase();

    if (busca == obj_filtro.ultima_busca) {
        return false;
    }
    obj_filtro.ultima_busca = busca;

    obj_filtro.seletor.input_busca.disabled = true;
    var select = obj_filtro.seletor.itens.firstChild;

    // Se o texto esta' vazio: tornar todos elemnetos visiveis
    if (busca.length == 0) {
        obj_filtro.seletor.itens.style.display = "none";
        for (var i = select.options.length - 1; i >= 0; i--) {
            var item = select.options[i];
            if (!item.visivel) {
                item.visivel = true;
                item.style.display = "block";
            }
        }
        if (select.selectedIndex >= 0) {
            select.options[select.selectedIndex].selected = false;
        }
        if (select.options.length > 0) {
            select.options[0].selected = true;
        }

        obj_filtro.seletor.itens.style.display = "block";
        obj_filtro.seletor.input_busca.disabled = false;
        return false;
    }

    // Buscar itens semelhantes ao valor informado
    var pos = 0;
    obj_filtro.seletor.itens.style.display = "none";
    for (var i = select.options.length - 1; i >= 0; i--) {
        var item = select.options[i];
        pos = item.texto_exibido.indexOf(busca);

        // Nao encontrou a busca no item atual
        if (pos < 0) {
            item.visivel = false;
            item.style.display = "none";

        // Encontrou a busca no item atual
        } else {
            item.visivel = true;
            item.style.display = "block";
        }
    }
    if (select.selectedIndex >= 0) {
        select.options[select.selectedIndex].selected = false;
        var l = select.options.length;
        for (var i = 0; i < l; i++) {
            var item = select.options[i];
            if (item.visivel) {
                item.selected = true;
                break;
            }
        }
    }
    obj_filtro.seletor.itens.style.display = "block";
    obj_filtro.seletor.input_busca.disabled = false;
}


//
//     Classe seletor de entidades na forma hierarquica
//
function class_hierarquia(link) {
// A link: link a ser transformado em seletor
//
    var that = this;
    this.id  = class_hierarquia.instancias.length;
    class_hierarquia.instancias[this.id] = this;

    // Atributos gerais
    this.nivel   = 0;
    that.ul      = null;
    that.pai     = null;
    this.link    = link;
    this.url     = link.getAttribute("href");
    this.ws      = CFG.wwwroot + "webservice/hierarquia.xml.php";
    this.input   = link.parentNode.getElementsByTagName("input").item(0);
    this.seletor = null;
    // seletor.janela
    // seletor.itens
    // seletor.status

    // Atributos auxiliares
    this.ajax = new class_ajax();


    //
    //     Alterna o texto da barra de status
    //
    this.set_status = function(texto) {
        var t = document.createTextNode(texto);
        limpar(that.seletor.status);
        that.seletor.status.appendChild(t);
    };


    //
    //     Alterna o botao de expandir/agrupar
    //
    this.set_botao = function(botao, tipo) {
    // IMG botao: botao a ser alterado
    // String tipo: tipo de botao ("+" para expandir ou "-" para agrupar)
    //
        switch (tipo) {
        case "+":
            botao.setAttribute("src", CFG.wwwroot + "imgs/icones/mais.gif");
            botao.setAttribute("alt", "+");
            botao.setAttribute("title", "Abrir Grupo");
            break;
        case "-":
            botao.setAttribute("src", CFG.wwwroot + "imgs/icones/menos.gif");
            botao.setAttribute("alt", "-");
            botao.setAttribute("title", "Fechar Grupo");
            break;
        }
    };


    //
    //     Obtem a lista de entidades consultadas e atualiza os itens do seletor
    //
    this.atualizar_itens = function(ajax) {
    // class_ajax ajax: objeto que devolve a requisicao
    //
        var xml = ajax.get_retorno("xml");
        var itens = xml.documentElement.getElementsByTagName("item");

        var ul = criar_elemento("ul", {"class":"hierarquia"});
        var l = itens.length;
        for (var i = 0; i < l; i++) {
            var item     = itens.item(i);
            var nome     = item.getAttribute("nome");
            var valor    = item.getAttribute("valor");
            var eh_grupo = parseInt(item.getAttribute("eh_grupo"));

            // Criar linha
            var linha     = criar_elemento("li");
            linha.nome    = nome;
            linha.valor   = valor;
            linha.nivel   = that.nivel;
            linha.posicao = i;
            {

                var linha_lb = criar_elemento("span", {"class":"lb"}, {}, " ");

                var linha_l = criar_elemento("span");
                if (i < (itens.length - 1)) {
                    definir_classe(linha_l, "l");
                }
                {
                    var linha_valor = criar_elemento("span", {"class":"valor"});
                    linha_l.appendChild(linha_valor);
                }

                linha.appendChild(linha_lb);
                linha.appendChild(linha_l);
            }

            {
                // Se e' um grupo
                if (eh_grupo == 1) {

                    // Criar botao de abrir/fechar
                    var botao = criar_elemento("img", {"class":"bt_expandir"});
                    botao.linha = linha;
                    botao.linha_valor = linha_valor;
                    that.set_botao(botao, "+");

                    // Eventos ao passar o mouse sobre o botao
                    botao.onmouseover = function() {
                        if (this.getAttribute("alt") == "+") {
                            that.set_status("Clique para abrir o Grupo");
                        } else {
                            that.set_status("Clique para fechar o Grupo");
                        }
                    };
                    botao.onmouseout = function() {
                        that.set_status("");
                    };

                    // Eventos ao clicar no botao
                    botao.onclick = function() {

                        // Buscar a lista do item a ser aberto
                        var ul = this.nextSibling;
                        while (ul && ul.nodeName.toLowerCase() != "ul") {
                            ul = ul.nextSibling;
                        }

                        // Se achou: mudar status (visivel/invisivel)
                        if (ul) {
                            ul.style.display = (ul.style.display == "none") ? "block" : "none";
                            that.set_botao(this, (ul.style.display == "none") ? "+" : "-");

                        // Se nao achou: consultar no web-service
                        } else {
                            that.pai = this.linha_valor;
                            that.nivel += 1;
                            var url = adicionar_param(that.ws, 'link', that.url);
                            var l = this.linha;
                            while (l.nivel >= 0) {
                                url = adicionar_param(url, "a[" + l.nivel + "]", l.posicao);
                                l = l.parentNode.parentNode.parentNode.parentNode;
                            }

                            // Requisicao AJAX
                            that.set_status("Carregando...");
                            that.ajax.set_callback([that, "atualizar_itens"]);
                            that.ajax.consultar("GET", url, true, null);
                            that.set_botao(this, "-");
                        }
                        this.onmouseover();
                    };

                    // Adicionar botao
                    linha_valor.appendChild(botao);
                }

                // Se e' um item selecionavel, criar link
                if (valor) {
                    var link = criar_elemento("a", {"title":"Selecionar"});
                    link.linha = linha;
                    var texto = document.createTextNode(valor + ": " + nome);

                    // Eventos ao passar o mouse sobre o link
                    link.onmouseover = function() {
                        that.set_status("Clique para selecionar o item");
                    };
                    link.onmouseout = function() {
                        that.set_status("");
                    };

                    // Evento ao clicar no link
                    link.onclick = function() {
                        that.input.value = this.linha.valor;
                        that.seletor.janela.fechar();
                        try {
                            that.input.focus();
                            that.input.select();
                        } catch (e) {}
                    };

                    // Adicionar link na linha
                    link.appendChild(texto);
                    linha_valor.appendChild(link);

                // Se e' apenas um grupo
                } else {
                    var strong = criar_elemento("strong", {}, {}, nome);
                    strong.linha = linha;
                    linha_valor.appendChild(strong);
                }
            }

            // Adicionar linha na lista
            ul.appendChild(linha);
        }

        // Adicionar lista na caixa de itens
        if (itens.length) {
            that.pai.appendChild(ul);
        }
        that.set_status("Itens carregados");
    };


    //
    //     Carrega os elementos na caixa
    //
    this.carregar_itens = function() {
        var url = adicionar_param(that.ws, "link", that.url);
        that.pai = that.seletor.itens;
        that.nivel = 0;
        that.ajax.set_callback([that, "atualizar_itens"]);
        that.ajax.exibir_carregando(that.seletor.itens);
        that.ajax.consultar("GET", url, true, null);
    };


    //
    //     Cria uma caixa de selecao
    //
    this.criar_caixa = function(pos) {
    // Object pos: posicao onde deve ser criada a caixa (atributos x e y em px)
    //
        // Criar janela
        var janela = new class_janela();
        var caixa = janela.criar_janela("Selecione um item", pos.x - 200, pos.y - 100, 400);
        {
            // Criar espaco para listar os itens
            var itens = criar_elemento("div", {"class":"itens_hierarquico"});

            // Criar espaco para o status
            var status = criar_elemento("div", {"class":"status"});
        }//caixa

        // Adicionar elementos na caixa
        caixa.appendChild(itens);
        caixa.appendChild(status);

        // Definir o seletor da classe
        that.seletor = {
            janela:janela,
            itens:itens,
            status:status
        };
    };


    //
    //     Abre um seletor
    //
    this.link.onclick = function abrir_seletor(e) {
    // Event e: evento ao clicar sobre o link
    //
        e = e ? e : window.event;
        e.returnValue = false;
        if (!that.ajax.xmlhttp) {
            var l = that.ws;
            l = adicionar_param(l, "link", that.url);
            l = adicionar_param(l, "input", that.input.id);
            window.open(l, "Busca", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=500, height=400");
            return false;
        }

        // Criar a caixa proximo a posicao do mouse e adiciona-la no documento
        if (that.seletor == null) {
            var pos = get_posicao_mouse(e);
            that.criar_caixa(pos);
            that.carregar_itens();
        }

        // Adicionar a caixa no documento
        that.seletor.janela.abrir(document.getElementsByTagName("body").item(0));

        return false;
    };
}


//
//     Classe seletor de datas
//
function class_calendario(div) {
// DIV div: elemento que armazena uma linha com um campo de data
//
    var that = this;
    this.id = class_calendario.instancias.length;
    class_calendario.instancias[this.id] = this;

    this.div_form   = div;
    this.seletor    = { janela:null, area_calendario:null };
    this.link       = null;
    this.mes        = 0;
    this.ano        = 0;
    this.min_ano    = 0;
    this.max_ano    = 0;
    this.pode_vazio = false;


    //
    //     Obtem o dia, mes ou ano (0, 1 ou 2) selecionado no div
    //
    this.get = function(item) {
    // Int item: codigo para obter um valor (0 = dia, 1 = mes, 2 = ano)
    //
        switch (item) {
        case 0:
        case 1:
            return that.div_form.getElementsByTagName("select").item(item).value;
        case 2:
            var elementos = that.div_form.getElementsByTagName("select");
            if (elementos.length == 3) {
                return elementos.item(2).value;
            }
            return that.div_form.getElementsByTagName("input").item(0).value;
        }
        return false;
    };
  
  
    //
    //     Obtem valor minimo do campo de ano
    //
    this.get_min_ano = function() {
        var elementos = that.div_form.getElementsByTagName("select");
        if (elementos.length != 3) {
            return 0;
        }
        var options = elementos.item(2).getElementsByTagName("option");
        if (that.pode_vazio) {
            return options.item(1).value;
        }
        return options.item(0).value;
    };


    //
    //    Obtem o valor maximo do campo de ano
    //
    this.get_max_ano = function() {
        var elementos = that.div_form.getElementsByTagName("select");
        if (elementos.length != 3) {
            var hoje = new Date();
            return hoje.getFullYear() + 500;
        }
        var options = elementos.item(2).getElementsByTagName("option");
        return options.item(options.length - 1).value;
    };
  

    //
    //     Define os eventos sobre uma celula que armazena um dia no calendario
    //
    this.definir_dia = function(td, dia) {
    // TD td: celula da tabela que contem o dia
    // Int dia: numero do dia que a celula contem
    //
        td.style.cursor = "pointer";

        // Ao passar o mouse sobre um dia
        td.onmouseover = function() {
            this.style.outline = "1px outset #FFFFFF";
            this.style.backgroundColor = "#FFFFFF";
        };

        // Ao tirar o mouse de um dia
        td.onmouseout = function() {
            this.style.outline = "none";
            this.style.backgroundColor = "transparent";
        };

        // Ao clicar em um dia
        td.onclick = function() {
            this.style.outline = "none";
            this.style.backgroundColor = "transparent";

            var elementos = that.div_form.getElementsByTagName("select");
            elementos.item(0).value = dia;
            if (elementos.item(0).onchange) { elementos.item(0).onchange(); }
            elementos.item(1).value = that.mes + 1;
            if (elementos.item(1).onchange) { elementos.item(1).onchange(); }
            if (elementos.length == 3) {
                elementos.item(2).value = that.ano;
                if (elementos.item(2).onchange) { elementos.item(2).onchange(); }
            } else {
                that.div_form.getElementsByTagName("input").item(0).value = that.ano;
            }
            that.seletor.janela.fechar();
            that.div_form.getElementsByTagName("select").item(0).focus();
        };
    };


    //
    //     Define o mes e ano do calendario de acordo com os dados do formulario
    //
    this.set_mes_ano = function() {
        var hoje = new Date();
        var mes = hoje.getMonth();
        var ano = hoje.getFullYear();

        if (that.get(1) == 0) {
            that.mes = mes;
        } else {
            that.mes = that.get(1) - 1;
        }
        if (that.get(2) == 0) {
            that.ano = ano;
        } else {
            that.ano = that.get(2);
        }
        that.min_ano = that.get_min_ano();
        that.max_ano = that.get_max_ano();
    };


    //
    //     Cria um seletor de data
    //
    this.criar_caixa = function(pos) {
    // Object pos: posicao da caixa de data (com os atributos x e y em px)
    //
        var janela = new class_janela();
        var caixa = janela.criar_janela("Selecione uma data", pos.x - 200, pos.y - 100, 250, "auto");

        // Criar area para o calendario
        that.seletor.area_calendario = criar_elemento("div");
        caixa.appendChild(that.seletor.area_calendario);

        // Criar o calendario
        that.atualizar_calendario(that.mes, that.ano);

        that.seletor.janela = janela;
    };
  
  
    //
    //     Atualiza o calendario para alguma data
    //
    this.atualizar_calendario = function() {
        var dias_semana = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
        var meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
                     "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

        var primeiro_dia = new Date(that.ano, that.mes, 1);
        var ultimo_dia = new Date(that.ano, that.mes + 1, 0);

        var cal = criar_elemento("table", {"class":"calendario"}, {"margin":"3px auto 5px auto"});
        {
            var thead = criar_elemento("thead");
            var tbody = criar_elemento("tbody");
        }//cal
        cal.appendChild(thead);
        cal.appendChild(tbody);

        // Preencher o thead
        var linha = criar_elemento("tr");

        // Seta para esquerda
        var th = criar_elemento("th");
        var a = criar_elemento("a", {"class":"seta"}, {}, "←");
        th.appendChild(a);
        linha.appendChild(th);

        // Evento ao clicar na seta a esquerda
        th.onclick = function() {
            that.mes--;
            if (that.mes == -1) {
                that.mes = 11;
                if (that.ano > that.min_ano) {
                    that.ano--;
                } else {
                    that.ano = that.max_ano;
                }
            }
            that.atualizar_calendario();
        };

        // Titulo com Mes e ano
        var th = criar_elemento("th", {"colspan":"5", "class":"titulo_calendario"}, {}, meses[that.mes] + "/" + that.ano);
        linha.appendChild(th);

        // Seta para direita
        var th = criar_elemento("th");
        var a = criar_elemento("a", {"class":"seta"}, {}, "→");
        th.appendChild(a);
        linha.appendChild(th);

        // Evento ao clicar na seta a direita
        th.onclick = function() {
            that.mes++;
            if (that.mes == 12) {
                that.mes = 0;
                if (that.ano < that.max_ano) {
                    that.ano++;
                } else {
                    that.ano = that.min_ano;
                }
            }
            that.atualizar_calendario();
        };

        thead.appendChild(linha);

        // Dias da semana
        linha = criar_elemento("tr");
        for (var i = 0; i < 7; i++) {
            var th = criar_elemento("th", {}, {"width":"2em"}, dias_semana[i]);
            linha.appendChild(th);
        }
        thead.appendChild(linha);

        // Preencher o tbody

        // Primeira linha
        linha = criar_elemento("tr");
        for (var i = 1; i <= primeiro_dia.getDay(); i++) {
            var td = criar_elemento("td");
            linha.appendChild(td);
        }
        for (var i = 1; i <= 7 - primeiro_dia.getDay(); i++) {
            var td = criar_elemento("td", {}, {}, i);
            linha.appendChild(td);
            that.definir_dia(td, i);
        }
        tbody.appendChild(linha);

        // Proximas linhas
        while (i <= ultimo_dia.getDate()) {
            var linha = criar_elemento("tr");
            for (var s = 0; s <= 6; s++) {
                var td = criar_elemento("td");
                if (i <= ultimo_dia.getDate()) {
                    var texto = document.createTextNode(i);
                    td.appendChild(texto);
                    that.definir_dia(td, i);
                }
                linha.appendChild(td);
                i++;
            }
            tbody.appendChild(linha);
        }

        that.seletor.area_calendario.style.textAlign = "center";
        that.seletor.area_calendario.style.border = "1px solid #CCCCCC";

        limpar(that.seletor.area_calendario);
        that.seletor.area_calendario.appendChild(cal);

        // Links de Atalho
        var div_links = criar_elemento("div", {}, {"fontSize":"small"}, "Atalhos: ");

        // Link para o dia de hoje
        var hoje = criar_elemento("a", {}, {}, "Hoje");
        hoje.onclick = function () {
            var hoje = new Date();
            var elementos = that.div_form.getElementsByTagName("select");
            elementos.item(0).value = hoje.getDate();
            if (elementos.item(0).onchange) { elementos.item(0).onchange(); }
            elementos.item(1).value = hoje.getMonth() + 1;
            if (elementos.item(1).onchange) { elementos.item(1).onchange(); }
            if (elementos.length == 3) {
                elementos.item(2).value = hoje.getFullYear();
                if (elementos.item(2).onchange) { elementos.item(2).onchange(); }
            } else {
                that.div_form.getElementsByTagName("input").item(0).value = hoje.getFullYear();
            }
            that.seletor.janela.fechar();
            that.div_form.getElementsByTagName("select").item(0).focus();
        };
        div_links.appendChild(hoje);

        // Link para o dia nenhum
        if (that.pode_vazio) {
            var nenhum = criar_elemento("a", {}, {}, "Nenhum");
            nenhum.onclick = function () {
                var elementos = that.div_form.getElementsByTagName("select");
                elementos.item(0).value = 0;
                if (elementos.item(0).onchange) { elementos.item(0).onchange(); }
                elementos.item(1).value = 0;
                if (elementos.item(1).onchange) { elementos.item(1).onchange(); }
                if (elementos.length == 3) {
                    elementos.item(2).value = 0;
                    if (elementos.item(2).onchange) { elementos.item(2).onchange(); }
                } else {
                    that.div_form.getElementsByTagName("input").item(0).value = 0;
                }
                that.seletor.janela.fechar();
                that.div_form.getElementsByTagName("select").item(0).focus();
            };
            div_links.appendChild(document.createTextNode(" | "));
            div_links.appendChild(nenhum);
        }

        that.seletor.area_calendario.appendChild(div_links);
    };


    //
    //     Abre a janela de selecao de data
    //
    this.abrir_calendario = function(e) {
    // Event e: evento ao abrir o calendario
    //
        that.set_mes_ano();
        if (that.seletor.janela == null) {
            var pos = get_posicao_mouse(e);
            that.criar_caixa(pos);
        } else {
            that.atualizar_calendario();
        }
        that.seletor.janela.abrir(document.getElementsByTagName("body").item(0));
    };


    //
    //     Adiciona um link ao div
    //
    this.adicionar_link = function() {

        // Incluir CSS de calendario como processing instruction
        var incluir_estilo = true;
        var regex = new RegExp(/\/calendario.css$/);
        var l = document.childNodes.length;
        for (var i = 0; i < l; i++) {
            var c = document.childNodes.item(i);
            if (c.nodeType == 7 && c.target.toLowerCase() == 'xml-stylesheet' && regex.test(c.data)) {
                incluir_estilo = false;
                break;
            }
        }
        if (incluir_estilo) {
            try {
                var estilo = document.createProcessingInstruction('xml-stylesheet', 'href="' + CFG.wwwroot + 'layout/calendario.css" type="text/css" media="screen" charset="utf-8"');
                document.insertBefore(estilo, document.firstChild);
            } catch (e) {
                var estilo = criar_elemento("link", {"rel":"stylesheet", "type":"text/css", "charset":"utf-8", "media":"screen", "href":CFG.wwwroot + "layout/calendario.css"});
                document.getElementsByTagName("head").item(0).appendChild(estilo);
            }
        }

        // Criar imagem de um calendario
        var s = "Selecionar pelo Calendário";
        that.link = criar_elemento("img", {"src":CFG.wwwroot + "imgs/icones/calendario.gif", "alt":s, "title":s}, {"cursor":"pointer"});
        that.link.onclick = that.abrir_calendario;

        var antigo = that.div_form.getElementsByTagName("img");
        if (antigo.length > 0) {
            that.div_form.removeChild(antigo.item(0));
        }

        that.div_form.appendChild(that.link);

        that.pode_vazio = that.div_form.getElementsByTagName("select").item(0).getElementsByTagName("option").item(0).value == 0;
        if (that.pode_vazio) {
            var s = "Nenhuma data";
            var anular = criar_elemento(
                "img",
                {"src":CFG.wwwroot + "imgs/icones/cancelar.gif", "alt":s, "title":s},
                {"cursor":"pointer", "marginLeft":"5px"}
            );
            anular.onclick = function() {
                var elementos = that.div_form.getElementsByTagName("select");
                elementos.item(0).value = 0;
                if (elementos.item(0).onchange) { elementos.item(0).onchange(); }
                elementos.item(1).value = 0;
                if (elementos.item(1).onchange) { elementos.item(1).onchange(); }
                if (elementos.length == 3) {
                    elementos.item(2).value = 0;
                    if (elementos.item(2).onchange) { elementos.item(2).onchange(); }
                } else {
                    that.div_form.getElementsByTagName("input").item(0).value = 0;
                }
            };
            that.div_form.appendChild(anular);
        }
    };

    this.adicionar_link();
}


//
//     Classe popup
//
function class_popup(link) {
// A link: link que deseja-se transformar em link para popup
//
    var that = this;
    this.id = class_popup.instancias.length;
    class_popup.instancias[this.id] = this;

    this.link          = link;
    this.url           = null;
    this.janela        = null;
    this.area_conteudo = null;
    this.ajax          = new class_ajax();


    //
    //     Cria uma caixa de popup
    //
    this.criar_caixa = function(pos) {
    // Object pos: posicao para criar a caixa de popup (com os atributos x e y em px)
    //
        // Criar janela
        var janela = new class_janela();
        var titulo = that.link.text.substr(0, 40);
        if (titulo != that.link.text) {
            titulo = titulo + "...";
        }
        var caixa = janela.criar_janela(titulo, pos.x - 450, pos.y - 300, 450, 300);
        {
            // Criar espaco para o conteudo
            that.area_conteudo = criar_elemento("div", {"class":"conteudo"});
        }
        caixa.appendChild(that.area_conteudo);
        that.janela = janela;
    };
  
  
    //
    //     Atualiza o conteudo da pagina no popup
    //
    this.atualizar_conteudo = function(ajax) {
    // class_ajax ajax: objeto que devolve a requisicao
    //
        limpar(that.area_conteudo);
        var xml = ajax.get_retorno("xml");
        var div = xml.getElementById("conteudo_popup");
        div.style.width = "auto";

        // Obter elementos filho do popup
        var filhos = div.getElementsByTagName("*");
        var l = filhos.length;
        for (var j = 0; j < l; j++) {
            filhos.item(j).style.width = "auto";
            filhos.item(j).style.margin = "0";
        }

        // Tentar carregar o conteudo do DIV para dentro do popup
        try {
            document.importNode(div, true);
            that.area_conteudo.appendChild(div);

        // Ou trocar o innerHTML de um pelo outro
        } catch (e) {
            that.area_conteudo.innerHTML = div.innerHTML;
        }
    };
  
  
    //
    //     Carrega o conteudo da pagina para o popup
    //
    this.carregar_conteudo = function() {
        var url = adicionar_param(that.url, "xml", "1");
        that.ajax.set_callback([that, "atualizar_conteudo"]);
        that.ajax.exibir_carregando(that.area_conteudo);
        that.ajax.consultar("GET", url, true, null);
    };
  
  
    //
    //     Abre um popup
    //
    this.abrir_popup = function(e) {
    // Event e: evento para abrir o popup
    //
        if (that.janela == null) {
            var pos = get_posicao_mouse(e);
            that.criar_caixa(pos);
            that.carregar_conteudo();
        }
        that.janela.abrir(document.getElementsByTagName("body").item(0));
        return false;
    };
  
  
    //
    //     Define o link como popup
    //
    this.definir_link = function() {
        if ((!that.ajax.xmlhttp) || (that.url != null)) { return; }
        that.url = that.link.getAttribute("href");
        that.link.onclick = that.abrir_popup;
    };
  
    this.definir_link();
}
