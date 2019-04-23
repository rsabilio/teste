//
// SIMP
// Descricao: JavaScript para mover objetos
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.2.0
// Data: 12/06/2007
// Modificado: 10/03/2011
// License: LICENSE.TXT
// Copyright (C) 2007  Rubens Takiguti Ribeiro
//
document.onmousemove = mover;
document.onmouseup   = soltar;


// Variaveis globais
{
    GLB.pos       = null;
    GLB.offset    = null;
    GLB.flutuante = null;
    GLB.obj_foco  = null;
    GLB.clone     = null;
}


//
//     Define um objeto que move outro
//
function objeto_movel(obj, obj_movel) {
// Object obj: objeto que servira como base para mover outro (por exemplo o titulo de uma janela)
// Object obj_movel: objeto que se movera
//
    if (obj == undefined || obj_movel == undefined) {
        return false;
    }

    obj.style.cursor = "move";
    obj.style.zIndex = 0;
    obj.onmousedown = function(e) {
        if (GLB.flutuante != null) {
            return false;
        }
        if (GLB.obj_foco != null) {
            GLB.obj_foco.style.zIndex = 0;
        }
        GLB.obj_foco = obj_movel;

        GLB.flutuante = obj_movel;
        GLB.offset = get_offset(GLB.flutuante, e);

        GLB.flutuante.opacity_original  = GLB.flutuante.style.opacity;
        GLB.flutuante.width_original    = GLB.flutuante.style.width;

        if (GLB.flutuante.clonar != undefined) {
            var pai = GLB.flutuante.parentNode;

            GLB.clone = GLB.flutuante.cloneNode(true);
            GLB.clone.style.display = "none";
            pai.insertBefore(GLB.clone, GLB.flutuante);
            GLB.flutuante.style.width = GLB.flutuante.offsetWidth + "px";
            GLB.flutuante.style.position = "absolute";
            GLB.clone.style.display = "block";

        } else {
            GLB.flutuante.style.width = (GLB.flutuante.offsetWidth - 2) + "px";
            GLB.flutuante.style.position = "absolute";
        }

        GLB.pos = get_posicao_mouse(e);
        GLB.flutuante.style.top  = (GLB.pos.y - GLB.offset.y) + "px";
        GLB.flutuante.style.left = (GLB.pos.x - GLB.offset.x) + "px";
        GLB.flutuante.style.opacity = GLB.flutuante.drag_opacity != undefined ? GLB.flutuante.drag_opacity : 0.5;
        GLB.flutuante.style.zIndex = 1;
        
        if (GLB.flutuante.simp_onmousedown) {
            GLB.flutuante.simp_onmousedown(GLB.pos);
        }

        return false;
    };
    return true;
}


//
//     Move um objeto
//
function mover(e) {
// Event e: evento ao mover o mouse
//
    if (GLB.flutuante) {
        e = e || window.event;
        GLB.pos = get_posicao_mouse(e);
        GLB.flutuante.style.top  = (GLB.pos.y - GLB.offset.y) + "px";
        GLB.flutuante.style.left = (GLB.pos.x - GLB.offset.x) + "px";
        if (GLB.flutuante.simp_onmove) {
            GLB.flutuante.simp_onmove(GLB.pos);
        }
    }
    return !GLB.flutuante;
}


//
//     Solta um objeto
//
function soltar(e) {
// Event e: evento
//
    if (GLB.flutuante) {
        e = e || window.event;
        GLB.pos = get_posicao_mouse(e);

        GLB.flutuante.style.opacity = GLB.flutuante.opacity_original;
        GLB.flutuante.style.width  = GLB.flutuante.width_original;
        GLB.flutuante.style.zIndex = 1;

        // Remover clone
        if (GLB.flutuante.clonar != undefined) {
            try {
                GLB.flutuante.parentNode.removeChild(GLB.clone);
            } catch (e) {}
        }

        if (GLB.flutuante.simp_ondrop) {
            GLB.flutuante.simp_ondrop(GLB.pos);
        }
    }
    GLB.pos       = null;
    GLB.flutuante = null;
    GLB.clone     = null;
    GLB.offset    = null;
}


//
//     Recupera a posicao do mouse
//
function get_posicao_mouse(e) {
// Event e: evento para obter a posicao do mouse
//
    e = e || window.event;
    if (e.pageX || e.pageY) {
        return { x:e.pageX, y:e.pageY };
    }
    if (document.body.scrollTop) {
        return {
            x:e.clientX + document.body.scrollLeft - document.body.clientLeft,
            y:e.clientY + document.body.scrollTop  - document.body.clientTop
        };
    }
    return {
        x:e.clientX + document.documentElement.scrollLeft - document.documentElement.clientLeft,
        y:e.clientY + document.documentElement.scrollTop  - document.documentElement.clientTop
    };
}


//
//     Recupera a posicao do objeto (retorna objeto com atributos x e y em px)
//
function get_posicao(obj) {
// Object obj: objeto que deseja-se saber a posicao
//
    var left = 0;
    var top  = 0;

    while (obj.offsetParent) {
        left += obj.offsetLeft;
        top  += obj.offsetTop;
        obj   = obj.offsetParent;
    }

    left += obj.offsetLeft;
    top  += obj.offsetTop;

    return { x:left, y:top };
}


//
//     Recupera a posicao onde clicou no objeto
//
function get_offset(obj, e) {
// Object obj: objeto a ser checado
// Event e: evento do mouse
//
    e = e || window.event;
    var pos_doc   = get_posicao(obj);
    var pos_mouse = get_posicao_mouse(e);
    return { x:pos_mouse.x - pos_doc.x, y:pos_mouse.y - pos_doc.y };
}
