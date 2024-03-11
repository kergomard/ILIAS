/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */
!function(e,t,s){"use strict";class l{#e;#t;#s;constructor(e,t,s,l,i){this.#e=t,this.#t=s,this.#s=l,e(document).on(i,(()=>{this.#l()})),this.#s.setFilterHandler((e=>{this.#i(e)})),this.#s.parseLevel(((e,t,s)=>this.#t.addLevel(e,t,s)),((e,t)=>this.#t.buildLeaf(e,t)),(e=>{this.#n(e)})),this.#n(this.#e.read())}#n(e){this.#t.engageLevel(e),this.#r()}#i(e){this.#t.engageLevel(0),this.#t.filter(e),this.#s.setFiltered(this.#t.getFiltered()),e.target.focus()}#l(){this.#t.upLevel(),this.#r()}#r(){const e=this.#t.getCurrent(),t=this.#t.getParent();let s=2;null===e.parent?s=0:"0"===e.parent&&(s=1),this.#s.setEngaged(e.id),this.#e.store(e.id),this.#s.setHeader(e.headerDisplayElement,t.headerDisplayElement),this.#s.setHeaderBacknav(s),this.#s.correctRightColumnPositionAndHeight(e.id)}}class i{#a="level_id";#d;constructor(e){this.#d=e}#h(){return this.#d}read(){return this.#d.items[this.#a]??0}store(e){this.#d.add(this.#a,e),this.#d.store()}}class n{#c={id:null,parent:null,engaged:!1,headerDisplayElement:"",leaves:[]};#o={index:null,text:null,filtered:!1};#E=[];#m(e,t,s,l){const i={...this.#c};return i.id=e,i.parent=s,i.headerDisplayElement=t,i.leaves=l,i}buildLeaf(e,t){const s={...this.#o};return s.index=e,s.text=t,s}addLevel(e,t,s){const l=this.#E.length.toString(),i=this.#m(l,e,t,s);return this.#E[i.id]=i,this.#E[i.id]}engageLevel(e){this.#E.forEach((t=>{const s=t;s.engaged=!1,t.id===e&&(s.engaged=!0)}))}getCurrent(){const e=this.#E.find((e=>e.engaged));return void 0!==e?e:this.#E[0]}getParent(){const e=this.getCurrent();return e.parent?this.#E[e.parent]:{}}upLevel(){const e=this.getCurrent();e.parent&&this.engageLevel(this.#E[e.parent].id)}#u(e){null!==e&&0!==e||(this.#E[e].filtered=!1,null!==this.#E[e].parent&&0!==this.#E[e].parent&&this.#u(this.#E[e].parent))}filter(e){const t=e.target.value.toLowerCase();this.#E.forEach((e=>{e.leaves.forEach((e=>{const s=e;""!==t?!1!==s.text.toLowerCase().includes(t)?s.filtered=!1:s.filtered=!0:s.filtered=!1}))}))}getFiltered(){const e=[];return this.#E.forEach((t=>{const s=t.leaves.filter((e=>e.filtered));if(s.length>0){const l=this.#m(t.id,t.headerDisplayElement,t.parent,[...s]);e.push(l)}})),e}}class r{#g={DRILLDOWN:"c-drilldown",MENU:"c-drilldown__menu",MENU_FILTERED:"c-drilldown--filtered",HEADER_ELEMENT:"c-drilldown__menulevel--trigger",MENU_BRANCH:"c-drilldown__branch",MENU_LEAF:"c-drilldown__leaf",FILTER:"c-drilldown__filter",MENU_FILTERED:"c-drilldown--filtered",ACTIVE:"c-drilldown__menulevel--engaged",ACTIVE_ITEM:"c-drilldown__menuitem--engaged",ACTIVE_PARENT:"c-drilldown__menulevel--engagedparent",FILTERED:"c-drilldown__menuitem--filtered",WITH_BACKLINK_ONE_COL:"c-drilldown__header--showbacknav",WITH_BACKLINK_TWO_COL:"c-drilldown__header--showbacknavtwocol",HEADER_TAG:"header",LIST_TAG:"ul",LIST_ELEMENT_TAG:"li",ID_ATTRIBUTE:"data-ddindex"};#L={dd:null,header:null,levels:[]};#p;constructor(e,t){this.#p=e,this.#L.dd=e.getElementById(t),[this.#L.header]=this.#L.dd.getElementsByTagName(this.#g.HEADER_TAG)}#v(){return this.#L.dd.querySelector(`.${this.#g.MENU}`)}setFilterHandler(e){this.#L.header.querySelector(`.${this.#g.FILTER} > input`).addEventListener("keyup",e)}parseLevel(e,t,s){this.#v().querySelectorAll(this.#g.LIST_TAG).forEach((l=>{const i=e(this.#_(l),this.#T(l),this.#I(l,t));this.#f(l,i.id),this.#A(l,s,i.id),this.#L.levels[i.id]=l}))}#f(e,t){e.setAttribute(this.#g.ID_ATTRIBUTE,t)}#_(e){const t=e.previousElementSibling;if(null===t)return null;let s=null;return s=this.#p.createElement("h2"),s.innerText=t.childNodes[0].nodeValue,s}#T(e){return e.parentElement.parentElement.getAttribute(this.#g.ID_ATTRIBUTE)}#I(e,t){const s=e.querySelectorAll(`:scope >.${this.#g.MENU_LEAF}`),l=[];return s.forEach(((e,s)=>{l.push(t(s,e.firstElementChild.innerText))})),l}#A(e,t,s){const l=e.previousElementSibling;null!==l&&l.addEventListener("click",(()=>{t(s)}))}setEngaged(e){this.#L.dd.querySelector(`.${this.#g.ACTIVE}`)?.classList.remove(`${this.#g.ACTIVE}`),this.#L.dd.querySelector(`.${this.#g.ACTIVE_ITEM}`)?.classList.remove(`${this.#g.ACTIVE_ITEM}`),this.#L.dd.querySelector(`.${this.#g.ACTIVE_PARENT}`)?.classList.remove(`${this.#g.ACTIVE_PARENT}`);const t=this.#L.levels[e];t.classList.add(this.#g.ACTIVE);const s=t.parentElement.parentElement;"UL"===s.nodeName?(t.parentElement.classList.add(this.#g.ACTIVE_ITEM),s.classList.add(this.#g.ACTIVE_PARENT)):t.classList.add(this.#g.ACTIVE_PARENT),t.parentElement;this.#L.levels[e].children[0].children[0].focus()}setFiltered(e){const t=this.#L.dd.querySelectorAll(`${this.#g.LIST_TAG}`),s=this.#L.dd.querySelectorAll(`.${this.#g.MENU_LEAF}`),l=e.map((e=>e.id)),i=this.#L.dd.querySelectorAll(`.${this.#g.MENU} > ul > .${this.#g.MENU_BRANCH}`);if(this.#L.levels.forEach((e=>{const t=e;t.style.removeProperty("top"),t.style.removeProperty("height")})),s.forEach((e=>{e.classList.remove(this.#g.FILTERED)})),0===e.length)return this.#L.dd.classList.remove(this.#g.MENU_FILTERED),i.forEach((e=>{const t=e;t.firstElementChild.disabled=!1,t.classList.remove(this.#g.FILTERED)})),void this.correctRightColumnPositionAndHeight("0");this.setEngaged(0),this.#L.dd.classList.add(this.#g.MENU_FILTERED),i.forEach((e=>{const t=e;t.firstElementChild.disabled=!0,t.classList.remove(this.#g.FILTERED)})),l.forEach(((s,l)=>{const[i]=[...t].filter((e=>e.getAttribute(this.#g.ID_ATTRIBUTE)===s)),n=i.querySelectorAll(`:scope >.${this.#g.MENU_LEAF}`);e[l].leaves.forEach((e=>n[e.index].classList.add(this.#g.FILTERED)))})),i.forEach((e=>{if(0===e.querySelectorAll(`.${this.#g.MENU_LEAF}:not(.${this.#g.FILTERED})`).length){e.classList.add(this.#g.FILTERED)}}))}setHeader(e,t){this.#L.header.children[1].replaceWith(this.#p.createElement("div")),null!==e?(this.#L.header.firstElementChild.replaceWith(e),null===t||this.#L.header.children[1].replaceWith(t)):this.#L.header.firstElementChild.replaceWith(this.#p.createElement("div"))}setHeaderBacknav(e){this.#L.header.classList.remove(this.#g.WITH_BACKLINK_TWO_COL),this.#L.header.classList.remove(this.#g.WITH_BACKLINK_ONE_COL),0!==e&&(e>1&&this.#L.header.classList.add(this.#g.WITH_BACKLINK_TWO_COL),this.#L.header.classList.add(this.#g.WITH_BACKLINK_ONE_COL))}correctRightColumnPositionAndHeight(e){var t=this.#L.levels[e];const s=this.#L.dd.querySelector(`.${this.#g.MENU}`),l=this.#L.dd.querySelector(`.${this.#g.MENU}`).offsetHeight;if(0!==l)this.#L.levels.forEach((e=>{const t=e;t.style.removeProperty("top"),t.style.removeProperty("height")})),"0"===e&&(t=t.querySelector(`:scope > .${this.#g.MENU_BRANCH} > ul`)),0!==t.offsetHeight&&(t.style.top=`-${t.offsetTop}px`,t.style.height=l+"px");else{const t=new ResizeObserver((l=>{l[0].target.offsetHeight>0&&(this.correctRightColumnPositionAndHeight(e),t.unobserve(s))}));t.observe(s)}}}e.UI=e.UI||{},e.UI.menu=e.UI.menu||{},e.UI.menu.drilldown=new class{#C=[];#p;construct(e){this.#p=e}init(e,t,a){if(void 0!==this.#C[e])throw new Error(`Drilldown with id '${e}' has already been initialized.`);this.#C[e]=new l(s,new i(new il.Utilities.CookieStorage(a)),new n,new r(document,e),t)}get(e){return this.#C[e]??null}}(t)}(il,document,$);
