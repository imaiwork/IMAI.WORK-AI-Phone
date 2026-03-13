import{_ as x}from"./index.vue.56475776.js";import{_ as g}from"./index.vue.2a0a10aa.js";import{g as b,bL as j,o as y,A as k,w as C,a as t,b as o,B as l,H as $,l as n,I as B,aL as D}from"./entry.252e648d.js";const R=b({__name:"code-view",emits:["close"],setup(E,{expose:m,emit:p}){const d=p,a=$(),s=D({apikey:""}),i=n(()=>`${location.origin}/chat/${s.apikey}`),f=n(()=>`\`\`\`html
<iframe 
    src="${i.value}" 
    class="chat-iframe"
    frameborder="0"
>
</iframe>
<style>
    /* iframe框默认占满全屏，可根据需求自行调整样式  */
    .chat-iframe {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        margin: 0;
        padding: 0;
        overflow: hidden;
        z-index: 9999;
    }
</style>
\`\`\``),_=n(()=>`\`\`\`html
<script>
    window.chat_iframe_src = '${i.value}'
    window.chat_iframe_width = '375px' //聊天窗口宽
    window.chat_iframe_height = '667px'  //聊天窗口高
    window.chat_icon_bg = '#3C5EFD' //聊天悬浮按钮背景
    window.chat_icon_color = '#fff' //聊天悬浮按钮颜色
    var js = document.createElement('script')
    js.type = 'text/javascript'
    js.async = true
    js.src = '${location.origin}/js-iframe.js'
    var header = document.getElementsByTagName('head')[0]
    header.appendChild(js)
<\/script>
\`\`\`
`),u=()=>{a.value.open()},h=()=>{d("close")};return m({open:u,setFormData:r=>j(r,s)}),(r,e)=>{const w=x,c=g,v=B;return y(),k(v,{ref_key:"popupRef",ref:a,async:"",width:"900px","confirm-button-text":"","cancel-button-text":"","header-class":"!p-0","show-close":!1},{default:C(()=>[t("div",null,[t("div",{class:"absolute w-6 h-6 right-4 top-4 cursor-pointer",onClick:h},[o(w)]),e[2]||(e[2]=t("div",{class:"text-2xl font-medium mb-5"},"JS嵌入",-1)),t("div",null,[e[0]||(e[0]=t("div",{class:"form-tips"},"要在您网站的任何位置添加聊天智能体，请将此 iframe 添加到您的 html 代码中",-1)),t("div",null,[o(c,{content:l(f)},null,8,["content"])])]),t("div",null,[e[1]||(e[1]=t("div",{class:"form-tips"},"要在您网站的右下角添加聊天气泡，请复制添加到您的html中",-1)),t("div",null,[o(c,{content:l(_)},null,8,["content"])])])])]),_:1},512)}}});export{R as _};
