(()=>{var e={551:()=>{window.addEventListener("load",(function(){!function(){let e=document.getElementById("site-post-title");null===e&&(e=document.querySelector(".site-post-title input")),null!==e&&e.addEventListener("change",(function(){const t=document.querySelector(".editor-post-title__block");null!==t&&(e.checked?t.style.opacity="0.2":t.style.opacity="1.0")})),wp.data.subscribe((function(){setTimeout((function(){let e=document.querySelector(".edit-post-visual-editor__post-title-wrapper"),t=document;if(function(){let e=document.querySelector(".title-visibility"),t=document.querySelector(".edit-post-visual-editor__post-title-wrapper"),a=document;const s=void 0!==wp.data.select("core/editor")&&null!==wp.data.select("core/editor")&&void 0!==wp.data.select("core/editor").getEditedPostAttribute("meta")&&wp.data.select("core/editor").getEditedPostAttribute("meta")._swt_meta_site_title_display?wp.data.select("core/editor").getEditedPostAttribute("meta")._swt_meta_site_title_display:"";if(spectraOne.swt_wp_version_higher_6_3){const s=document.getElementsByClassName("is-desktop-preview"),n=document.getElementsByClassName("is-tablet-preview"),i=document.getElementsByClassName("is-mobile-preview");let r=s[0];n.length>0?r=n[0]:i.length>0&&(r=i[0]);const l=void 0!==r?r.getElementsByTagName("iframe")[0]:void 0;l&&null!==r.querySelector("iframe")&&(a=l.contentWindow.document||l.contentDocument),!l&&spectraOne.swt_wp_version_higher_6_5&&(document.querySelector(".editor-canvas__iframe")?.contentWindow&&(a=document.querySelector(".editor-canvas__iframe").contentWindow.document),document.querySelector('.block-editor-iframe__scale-container iframe[name="editor-canvas"]')&&(a=document.querySelector('.block-editor-iframe__scale-container iframe[name="editor-canvas"]').contentWindow.document)),e=a.querySelector(".title-visibility"),t=a.querySelector(".edit-post-visual-editor__post-title-wrapper")}if(null!==t&&null===e){let n='<span class="swt-title title-visibility" data-tooltip="Disable Title"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M572.52 241.4C518.29 135.59 410.93 64 288 64S57.68 135.64 3.48 241.41a32.35 32.35 0 0 0 0 29.19C57.71 376.41 165.07 448 288 448s230.32-71.64 284.52-177.41a32.35 32.35 0 0 0 0-29.19zM288 400a144 144 0 1 1 144-144 143.93 143.93 0 0 1-144 144zm0-240a95.31 95.31 0 0 0-25.31 3.79 47.85 47.85 0 0 1-66.9 66.9A95.78 95.78 0 1 0 288 160z"></path></svg> </span>';s&&(n='<span class="swt-title title-visibility" data-tooltip="Enable Title"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M320 400c-75.85 0-137.25-58.71-142.9-133.11L72.2 185.82c-13.79 17.3-26.48 35.59-36.72 55.59a32.35 32.35 0 0 0 0 29.19C89.71 376.41 197.07 448 320 448c26.91 0 52.87-4 77.89-10.46L346 397.39a144.13 144.13 0 0 1-26 2.61zm313.82 58.1l-110.55-85.44a331.25 331.25 0 0 0 81.25-102.07 32.35 32.35 0 0 0 0-29.19C550.29 135.59 442.93 64 320 64a308.15 308.15 0 0 0-147.32 37.7L45.46 3.37A16 16 0 0 0 23 6.18L3.37 31.45A16 16 0 0 0 6.18 53.9l588.36 454.73a16 16 0 0 0 22.46-2.81l19.64-25.27a16 16 0 0 0-2.82-22.45zm-183.72-142l-39.3-30.38A94.75 94.75 0 0 0 416 256a94.76 94.76 0 0 0-121.31-92.21A47.65 47.65 0 0 1 304 192a46.64 46.64 0 0 1-1.54 10l-73.61-56.89A142.31 142.31 0 0 1 320 112a143.92 143.92 0 0 1 144 144c0 21.63-5.29 41.79-13.9 60.11z"></path></svg> </span>'),null===e&&t.insertAdjacentHTML("beforeend",n);const i=a.querySelector(".title-visibility"),r=a.querySelector(".edit-post-visual-editor__post-title-wrapper");s&&!r.classList.contains("invisible")?r.classList.add("invisible"):r.classList.remove("invisible"),i.addEventListener("click",(function(){const e=s||"";this.parentNode.classList.contains("invisible")&&(e||""===e)?(this.parentNode.classList.remove("invisible"),this.dataset.tooltip="Disable Title",i.innerHTML="",i.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M572.52 241.4C518.29 135.59 410.93 64 288 64S57.68 135.64 3.48 241.41a32.35 32.35 0 0 0 0 29.19C57.71 376.41 165.07 448 288 448s230.32-71.64 284.52-177.41a32.35 32.35 0 0 0 0-29.19zM288 400a144 144 0 1 1 144-144 143.93 143.93 0 0 1-144 144zm0-240a95.31 95.31 0 0 0-25.31 3.79 47.85 47.85 0 0 1-66.9 66.9A95.78 95.78 0 1 0 288 160z"></path></svg>',wp.data.dispatch("core/editor").editPost({meta:{_swt_meta_site_title_display:!1}})):(this.parentNode.classList.add("invisible"),this.dataset.tooltip="Enable Title",i.innerHTML="",i.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M320 400c-75.85 0-137.25-58.71-142.9-133.11L72.2 185.82c-13.79 17.3-26.48 35.59-36.72 55.59a32.35 32.35 0 0 0 0 29.19C89.71 376.41 197.07 448 320 448c26.91 0 52.87-4 77.89-10.46L346 397.39a144.13 144.13 0 0 1-26 2.61zm313.82 58.1l-110.55-85.44a331.25 331.25 0 0 0 81.25-102.07 32.35 32.35 0 0 0 0-29.19C550.29 135.59 442.93 64 320 64a308.15 308.15 0 0 0-147.32 37.7L45.46 3.37A16 16 0 0 0 23 6.18L3.37 31.45A16 16 0 0 0 6.18 53.9l588.36 454.73a16 16 0 0 0 22.46-2.81l19.64-25.27a16 16 0 0 0-2.82-22.45zm-183.72-142l-39.3-30.38A94.75 94.75 0 0 0 416 256a94.76 94.76 0 0 0-121.31-92.21A47.65 47.65 0 0 1 304 192a46.64 46.64 0 0 1-1.54 10l-73.61-56.89A142.31 142.31 0 0 1 320 112a143.92 143.92 0 0 1 144 144c0 21.63-5.29 41.79-13.9 60.11z"></path></svg>',wp.data.dispatch("core/editor").editPost({meta:{_swt_meta_site_title_display:!0}}))}))}}(),spectraOne.swt_wp_version_higher_6_3){const a=document.getElementsByClassName("is-desktop-preview"),s=document.getElementsByClassName("is-tablet-preview"),n=document.getElementsByClassName("is-mobile-preview");let i=a[0];s.length>0?i=s[0]:n.length>0&&(i=n[0]);const r=void 0!==i?i.getElementsByTagName("iframe")[0]:void 0;r&&null!==i.querySelector("iframe")&&(t=r.contentWindow.document||r.contentDocument),!r&&spectraOne.swt_wp_version_higher_6_5&&(document.querySelector(".editor-canvas__iframe")?.contentWindow&&(t=document.querySelector(".editor-canvas__iframe").contentWindow.document),document.querySelector('.block-editor-iframe__scale-container iframe[name="editor-canvas"]')&&(t=document.querySelector('.block-editor-iframe__scale-container iframe[name="editor-canvas"]').contentWindow.document)),e=t.querySelector(".edit-post-visual-editor__post-title-wrapper")}const a=t.querySelector(".editor-styles-wrapper");null!==a&&(parseInt(a.offsetWidth)<870?(a.classList.remove("swt-stacked-title-visibility"),a.classList.add("swt-stacked-title-visibility")):a.classList.remove("swt-stacked-title-visibility"));const s=t.querySelector(".editor-post-title__input"),n=t.querySelector(".title-visibility");null!==s&&null!==n&&(spectraOne.swt_wp_version_higher_6_3||t.addEventListener("click",(function(t){e.contains(t.target)||(n.classList.remove("swt-show-visibility-icon"),s.classList.remove("swt-show-editor-title-outline"))})),t.addEventListener("visibilitychange",(function(){n.classList.remove("swt-show-visibility-icon"),s.classList.remove("swt-show-editor-title-outline")})),e.addEventListener("focusout",(function(){n.classList.remove("swt-show-visibility-icon"),s.classList.remove("swt-show-editor-title-outline")})),e.addEventListener("click",(function(){n.classList.add("swt-show-visibility-icon"),s.classList.add("swt-show-editor-title-outline")})),s.addEventListener("input",(function(){n.classList.add("swt-show-visibility-icon"),this.classList.add("swt-show-editor-title-outline")}))),document.querySelectorAll(".is-tablet-preview, .is-mobile-preview").length?document.body.classList.add("responsive-enabled"):document.body.classList.remove("responsive-enabled")}),1)}))}()}))}},t={};function a(s){var n=t[s];if(void 0!==n)return n.exports;var i=t[s]={exports:{}};return e[s](i,i.exports,a),i.exports}(()=>{"use strict";const e=window.React,t=window.wp.plugins,s=window.wp.editPost,n=window.wp.compose,i=window.wp.data,r=window.wp.i18n,l={logo:(0,e.createElement)("svg",{className:"swt-page-settings-button",xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 70 70",fill:"none"}," ",(0,e.createElement)("path",{fillRule:"evenodd",clipRule:"evenodd",d:"M35 70C54.33 70 70 54.33 70 35C70 15.67 54.33 0 35 0C15.67 0 0 15.67 0 35C0 54.33 15.67 70 35 70ZM24.4471 23.5112C18.9722 26.7403 20.2852 35.3759 26.5032 37.0351L36.8875 39.806C37.7533 40.037 37.91 41.224 37.135 41.6811L27.0972 47.5799L26.036 58L45.5529 46.4888C51.0278 43.2597 49.7148 34.6241 43.4968 32.9649L33.1125 30.1941C32.2467 29.963 32.09 28.776 32.865 28.3189L42.9028 22.4202L43.964 12L24.4471 23.5112Z"})," ")},o=window.wp.components,c=window.wp.blockEditor,d=window.wp.hooks,u=t=>{let a,s;return t.hasOwnProperty("setAttributes")?(a=!(!t?.attributes?.SWTStickyHeader||!t.attributes.SWTStickyHeader),s=!(!t?.attributes?.SWTTransparentHeader||!t.attributes.SWTTransparentHeader)):(a=!(!t?.meta._swt_meta_sticky_header||!t.meta._swt_meta_sticky_header),s=!(!t?.meta._swt_meta_transparent_header||!t.meta._swt_meta_transparent_header)),(0,e.createElement)(e.Fragment,null,!s&&(0,e.createElement)(o.PanelRow,null,(0,e.createElement)(o.ToggleControl,{label:(0,r.__)("Enable Sticky Header","spectra-one"),help:a?(0,r.__)("Transparent header option will be disabled on enabling this option.","spectra-one"):"",checked:a,onChange:e=>t.hasOwnProperty("setAttributes")?t.setAttributes({SWTStickyHeader:!a}):t.setMetaFieldValue(e,"_swt_meta_sticky_header")})),!a&&(0,e.createElement)(o.PanelRow,null,(0,e.createElement)(o.ToggleControl,{label:(0,r.__)("Enable Transparent Header","spectra-one"),help:s?(0,r.__)("Sticky header option will be disabled on enabling this option.","spectra-one"):"",checked:s,onChange:e=>t.hasOwnProperty("setAttributes")?t.setAttributes({SWTTransparentHeader:!s}):t.setMetaFieldValue(e,"_swt_meta_transparent_header")})))},p=(0,n.createHigherOrderComponent)((t=>a=>{const{attributes:s,name:n}=a;return s?.tagName&&"header"===s.tagName&&"core/template-part"===n?(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a}),(0,e.createElement)(c.InspectorControls,null,(0,e.createElement)(o.Panel,null,(0,e.createElement)(o.PanelBody,{title:"Header Settings",initialOpen:!0},(0,e.createElement)(u,{...a}))))):(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a}))}),"Header");(0,d.addFilter)("editor.BlockEdit","swt/header",p),(0,d.addFilter)("blocks.registerBlockType","swt/header-attributes",(function(e){return["core/template-part"].includes(e.name)&&e.attributes&&(e.attributes=Object.assign(e.attributes,{SWTStickyHeader:{type:"boolean",default:!1},SWTTransparentHeader:{type:"boolean",default:!1}})),e}));const m=(0,n.createHigherOrderComponent)((t=>a=>{const{name:s,attributes:n}=a;if(["core/template-part"].includes(s)){const{SWTTransparentHeader:s}=n,i="\n\t\t\t\t.block-editor-block-list__block.swt-transparent-header {\n\t\t\t\t\tposition: absolute;\n\t\t\t\t\ttop: 0;\n\t\t\t\t\tleft: 0;\n\t\t\t\t\twidth: 100%;\n\t\t\t\t\tz-index: 999;\n\t\t\t\t\tmargin-top: 0;\n\t\t\t\t}\n\n\t\t\t\t.swt-transparent-header > .has-background {\n\t\t\t\t\tbackground: transparent !important;\n\t\t\t\t}\n\t\t\t";return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a,className:`${a?.className?`${a.className} `:""}${s?"swt-transparent-header ":""}`}),s&&(0,e.createElement)("style",null,i))}return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a}))}),"HeaderCss");(0,d.addFilter)("editor.BlockListBlock","swt/header-css",m);const w=()=>{const{activationUrl:t,pluginSlug:a,pluginStatus:s}=spectraOne;let n=[];"activated"!==s&&"installed"===s&&(n={initialText:"Activate Spectra",progressText:"Activating Spectra...",errorText:"Error activating Spectra",SuccessText:"Spectra Activated"}),"installed"!==s&&"activated"!==s&&(n={initialText:"Install & Activate Spectra",progressText:"Installing & Activating Spectra...",errorText:"Error installing Spectra",SuccessText:"Spectra Installed & Activated"});const{initialText:i,progressText:l,errorText:c,SuccessText:d}=n,[u,p]=(0,e.useState)(!1),[m,w]=(0,e.useState)(i),[g,_]=(0,e.useState)(!1),b=async e=>{!0===(await async function(e){try{if(200===(await fetch(e)).status)return{success:!0}}catch(e){return{success:!1}}}(e)).success?(p(!1),w(d),location.reload()):(w(c),p(!1),setTimeout((()=>{w(d),p(!1),_(!1)}),2e3))};return(0,e.createElement)(o.PanelBody,{title:(0,r.__)("Spectra plugin","spectra-one"),initialOpen:!0},(0,e.createElement)("p",null,(0,r.__)("Power-up your website with advanced and powerful blocks that help you build websites in no time!","spectra-one")),(0,e.createElement)(o.Button,{className:`swt-full-width-btn ${!0===u?"is-busy ":""} `,onClick:()=>(async(e,t)=>{if(!1===g)if(w(l),p(!0),_(!0),"activated"!==s&&"installed"===s)b(t);else try{await async function(e){return new Promise((t=>{wp.updates.ajax("install-plugin",{slug:e,success:()=>{t({success:!0})},error:e=>{t({success:!1,code:e.errorCode})}})}))}(e),b(t)}catch(e){"folder_exists"===e.errorCode&&b(t)}})(a,t),isPrimary:!0,"aria-disabled":g},m))},g=t=>{const a=Object.entries(spectraOne.disable_sections).map((([a,s])=>{const n=!(!t?.meta[s.key]||!t.meta[s.key]);return(0,e.createElement)(o.PanelRow,{key:a},(0,e.createElement)(o.ToggleControl,{key:a,label:s.label,checked:n,onChange:e=>{t.setMetaFieldValue(e,s.key)}}))}));return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(o.PanelBody,{title:(0,r.__)("Disable Elements","spectra-one"),initialOpen:!0,className:"swt-disable-elements-panel"},a),!t?.meta?._swt_meta_header_display&&(0,e.createElement)(o.PanelBody,{title:(0,r.__)("Header Settings","spectra-one"),initialOpen:!0,className:"swt-header-settings-panel"},(0,e.createElement)(u,{...t,...spectraOne.header_settings})),"activated"!==spectraOne.pluginStatus&&(0,e.createElement)(w,{...t}))},_=(0,n.compose)((0,i.withSelect)((e=>{const t=e("core/editor").getEditedPostAttribute("meta"),a=e("core/editor").getCurrentPostAttribute("meta");return{meta:{...a,...t},oldMeta:a}})),(0,i.withDispatch)((e=>({setMetaFieldValue:(t,a)=>e("core/editor").editPost({meta:{[a]:t}})}))))((t=>(0,e.createElement)(e.Fragment,null,(0,e.createElement)(s.PluginSidebarMoreMenuItem,{target:"swt-page-settings-panel",icon:l.logo},(0,r.__)("Spectra Page Settings","spectra-one")),(0,e.createElement)(s.PluginSidebar,{isPinnable:!0,icon:l.logo,name:"swt-page-settings-panel",title:(0,r.__)("Spectra Page Settings","spectra-one"),className:"swt-sidebar"},(0,e.createElement)(g,{...t})))));a(551);const b=["core/archives","core/calendar","core/latest-comments","core/tag-cloud","core/rss"],v=(0,n.createHigherOrderComponent)((t=>a=>{const{attributes:s,name:n,setAttributes:i}=a,{SWTHideDesktop:l,SWTHideTab:d,SWTHideMob:u}=s;return n&&n.includes("core/")&&!b.includes(n)?(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a}),(0,e.createElement)(c.InspectorControls,null,(0,e.createElement)(o.Panel,null,(0,e.createElement)(o.PanelBody,{title:"Responsive Settings",initialOpen:!0},(0,e.createElement)(o.PanelRow,null,(0,e.createElement)(o.ToggleControl,{label:(0,r.__)("Hide Desktop","spectra-one"),checked:l,onChange:()=>i({SWTHideDesktop:!l})})),(0,e.createElement)(o.PanelRow,null,(0,e.createElement)(o.ToggleControl,{label:(0,r.__)("Hide Tablet","spectra-one"),checked:d,onChange:()=>i({SWTHideTab:!d})})),(0,e.createElement)(o.PanelRow,null,(0,e.createElement)(o.ToggleControl,{label:(0,r.__)("Hide Mobile","spectra-one"),checked:u,onChange:()=>i({SWTHideMob:!u})})))))):(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a}))}),"Responsive");(0,d.addFilter)("editor.BlockEdit","swt/responsive",v),(0,d.addFilter)("blocks.registerBlockType","swt/responsive-attributes",(function(e){const{name:t,attributes:a}=e;return t&&t.includes("core/")&&!b.includes(t)&&a&&(e.attributes=Object.assign(a,{SWTHideDesktop:{type:"boolean",default:!1},SWTHideTab:{type:"boolean",default:!1},SWTHideMob:{type:"boolean",default:!1}})),e}));const h=(0,n.createHigherOrderComponent)((t=>a=>{const{name:s,attributes:n}=a;if(s&&s.includes("core/")&&!b.includes(s)){const{SWTHideDesktop:s,SWTHideTab:i,SWTHideMob:r}=n;return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a,className:`${a?.className?`${a.className} `:""}${s?"swt-hide-desktop ":""}${i?"swt-hide-tablet ":""}${r?"swt-hide-mobile ":""}`}))}return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(t,{...a}))}),"responsiveCss");(0,d.addFilter)("editor.BlockListBlock","swt/responsive-css",h);const y=(0,n.createHigherOrderComponent)((t=>a=>{const{name:s,attributes:n}=a;if("core/latest-posts"!==s)return(0,e.createElement)(t,{...a});const{displayFeaturedImage:i}=n;return(0,e.createElement)(t,{...a,className:`${a?.className?`${a.className} `:""}${i?"swt-has-featured-image ":""}`})}),"latestPostList");(0,d.addFilter)("editor.BlockListBlock","swt/latest-post-list",y),spectraOne.is_spectra_plugin&&(0,d.addFilter)("spectra.page-sidebar.before","swt/setting-list",(function(t,a){return(0,e.createElement)(e.Fragment,null,t,spectraOne.is_spectra_plugin&&"site-editor"!==spectraOne.get_screen_id&&(0,e.createElement)(g,{...a}))}),10),spectraOne.is_spectra_plugin||(0,t.registerPlugin)("swt-page-level-settings",{render:_})})()})();