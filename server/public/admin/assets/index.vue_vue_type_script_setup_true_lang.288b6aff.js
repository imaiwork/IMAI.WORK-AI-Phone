import{Q as L,C as q,D as P,G as j,H as z,w as G,F as H,E as N}from"./element-plus.b694a2f4.js";import{_ as O}from"./index.vue_vue_type_script_setup_true_lang.d9eca07d.js";import{g as Q}from"./cate.dd9a5c1a.js";import{u as W,g as $}from"./index.f10dd425.js";import T from"./agent-logo.b73f943a.js";import{d as J,bd as K,b as X,r as c,bu as Y,i as Z,ai as uu,o as E,c as m,a,Q as o,H as t,W as eu,u as r,P as f,a5 as g,G as v,M as b}from"./@vue.fa3e2f74.js";const lu="/admin/assets/agent_bg.449b20d4.png",ou={class:"h-full flex flex-col"},tu={class:"grow min-h-0"},au={class:"mt-4"},Fu={class:"mt-[10px]"},su=a("div",{class:"w-[72px] h-[29px] flex items-center justify-center rounded-[32px] bg-[#00000066] text-white"}," \u66F4\u6362\u80CC\u666F ",-1),nu={class:"flex mt-6 w-full gap-8"},Eu={class:"flex-1"},du={class:"flex justify-between"},ru=a("div",null,[a("span",{class:"font-bold"},"\u63D0\u793A\u8BCD"),a("span",{class:"ml-2 text-[#00000080]"},"\u89D2\u8272\u3001\u80CC\u666F\u3001\u804C\u8D23\u3001\u5DE5\u4F5C\u6D41\u7A0B\u3001\u6C9F\u901A\u65B9\u5F0F\u3001\u76EE\u7684")],-1),pu=J({__name:"index",props:K({modelValue:{default:()=>({})}},{modelValue:{default:()=>({image:"",name:"",intro:"",roles_prompt:"",model_id:"",model_sub_id:"",bg_image:"",cate_id:""})},modelModifiers:{}}),emits:["update:modelValue"],setup(x,{expose:h}){const V=W(),d=X(()=>{var F;return((F=V.config.ai_model)==null?void 0:F.channel)||[]}),B=c(),e=Y(x,"modelValue"),w={image:[{required:!0,message:"\u8BF7\u4E0A\u4F20\u673A\u5668\u4EBAlogo"}],name:[{required:!0,message:"\u8BF7\u8F93\u5165\u673A\u5668\u4EBA\u540D\u79F0"}],intro:[{required:!0,message:"\u8BF7\u8F93\u5165\u673A\u5668\u4EBA\u89D2\u8272\u7B80\u4ECB"}],cate_id:[{required:!0,message:"\u8BF7\u9009\u62E9\u7C7B\u76EE"}]},A=c([]),C=async F=>{const{lists:u}=await Q({name:F,type:1,page_size:25e3});return A.value=u,u},y=F=>{const{uri:u}=F;e.value.bg_image=u},_=F=>{const u=d.value.find(s=>s.model_id==F);if(u)e.value.model_sub_id=u.model_sub_id;else if(!F&&d.value.length>0){const s=d.value[0];e.value.model_id=s.model_id,e.value.model_sub_id=s.model_sub_id}},k=()=>{e.value.roles_prompt=`
        # Role: B2B2C AI \u6570\u5B57\u5458\u5DE5\u7CFB\u7EDF\u552E\u524D\u5BA2\u670D

        ## Profile

        - version: 1.0
        - language: \u4E2D\u6587
        - description: \u4F60\u662F\u4E00\u4F4D\u667A\u80FD\u552E\u524D\u5BA2\u670D\uFF0C\u670D\u52A1\u4E8EB2B2C\u4F01\u4E1A\u7EA7\u6570\u5B57\u5458\u5DE5\u7CFB\u7EDF\u3002\u4F60\u7684\u804C\u8D23\u662F\u89E3\u7B54\u6F5C\u5728\u5BA2\u6237\u5728\u8D2D\u4E70\u524D\u63D0\u51FA\u7684\u6240\u6709\u95EE\u9898\uFF0C\u5305\u62EC\u4EA7\u54C1\u529F\u80FD\u3001\u6280\u672F\u67B6\u6784\u3001\u90E8\u7F72\u65B9\u5F0F\u3001\u5B9A\u4EF7\u7B56\u7565\u3001\u884C\u4E1A\u9002\u914D\u3001\u6570\u636E\u5B89\u5168\u7B49\u65B9\u9762\u3002

        ## Skills

        - \u7CBE\u901A\u6570\u5B57\u5458\u5DE5\u7CFB\u7EDF\u7684\u6838\u5FC3\u6A21\u5757\u4E0E\u529F\u80FD\u573A\u666F
        - \u80FD\u63D0\u4F9B\u4E2A\u6027\u5316\u63A8\u8350\u4E0E\u884C\u4E1A\u89E3\u51B3\u65B9\u6848
        - \u7406\u89E3B2B2C\u4E1A\u52A1\u6A21\u578B\u53CA\u4F01\u4E1A\u5BA2\u6237\u9700\u6C42
        - \u80FD\u8BC6\u522B\u5BA2\u6237\u610F\u56FE\u5E76\u63D0\u4F9B\u7CBE\u51C6\u89E3\u7B54
        - \u7406\u89E3\u5E76\u7B80\u6D01\u8868\u8FF0\u590D\u6742\u6280\u672F\u672F\u8BED

        ## Background(\u53EF\u9009\u9879):

        \u672C\u7CFB\u7EDF\u4F5C\u4E3A\u4F01\u4E1A\u5BA2\u6237\u5F15\u5165AI\u52A9\u624B\u7684\u5173\u952E\u5165\u53E3\uFF0C\u552E\u524D\u5BA2\u670D\u5FC5\u987B\u5FEB\u901F\u3001\u51C6\u786E\u3001\u4E13\u4E1A\u5730\u56DE\u5E94\u5404\u79CD\u54A8\u8BE2\uFF0C\u51CF\u5C11\u4EBA\u5DE5\u5BA2\u670D\u8D1F\u62C5\uFF0C\u63D0\u9AD8\u5BA2\u6237\u8F6C\u5316\u7387\u3002

        ## Goals(\u53EF\u9009\u9879):

        - \u63D0\u5347\u5BA2\u6237\u9996\u6B21\u63A5\u89E6\u7CFB\u7EDF\u7684\u597D\u611F\u5EA6\u548C\u4FE1\u4EFB\u5EA6
        - \u964D\u4F4E\u83B7\u5BA2\u6C9F\u901A\u6210\u672C\uFF0C\u63D0\u5347\u7EBF\u7D22\u8D28\u91CF
        - \u4E3A\u9500\u552E\u63D0\u4F9B\u7CBE\u51C6\u5BA2\u6237\u753B\u50CF\u4E0E\u9700\u6C42\u53CD\u9988

        ## OutputFormat(\u53EF\u9009\u9879):

        \u9488\u5BF9\u6BCF\u4E2A\u95EE\u9898\uFF0C\u7B80\u5355\u8F93\u51FA\u4EE5\u4E0B\u5185\u5BB9\uFF1A
        1. \u95EE\u9898\u7B80\u8FF0
        2. \u56DE\u7B54\u6982\u8981
        3. \u53EF\u89C6\u5316\u8D44\u6E90\u5EFA\u8BAE\uFF08\u5982\u6587\u6863\u3001\u89C6\u9891\u3001\u622A\u56FE\u7B49\uFF09

        ## Rules

        1. \u56DE\u7B54\u5FC5\u987B\u6E05\u6670\u3001\u4E13\u4E1A\uFF0C\u907F\u514D\u5197\u957F\u6216\u6A21\u7CCA\u8868\u8FBE
        2. \u53EF\u6839\u636E\u7528\u6237\u89D2\u8272\uFF08\u4F01\u4E1A\u8001\u677F/CTO/HR\u8D1F\u8D23\u4EBA\uFF09\u8C03\u6574\u8BED\u8A00\u98CE\u683C
        3. \u82E5\u95EE\u9898\u6D89\u53CA\u654F\u611F\u5546\u4E1A\u6761\u6B3E\uFF0C\u9700\u63D0\u793A\u7528\u6237\u8054\u7CFB\u4EBA\u5DE5\u5BA2\u670D
        4. \u9F13\u52B1\u5F15\u5BFC\u7528\u6237\u6DF1\u5165\u4E86\u89E3\u66F4\u591A\u529F\u80FD\u6A21\u5757

        ## Workflows

        1. \u63A5\u6536\u5BA2\u6237\u63D0\u95EE\uFF08\u660E\u786E\u63D0\u95EE\u610F\u56FE\uFF09
        2. \u8C03\u7528\u4EA7\u54C1\u77E5\u8BC6\u5E93\u6216FAQ\u751F\u6210\u4E13\u4E1A\u89E3\u7B54
        3. \u6839\u636E\u884C\u4E1A\u6216\u5BA2\u6237\u80CC\u666F\u4E2A\u6027\u5316\u56DE\u7B54
        4. \u53EF\u9009\u63A8\u8350\u6587\u6863\u3001\u6848\u4F8B\u3001\u89C6\u9891\u6216\u9884\u7EA6\u6F14\u793A

        ## Init

        \u60A8\u597D\uFF0C\u8BF7\u95EE\u60A8\u60F3\u4E86\u89E3\u4EC0\u4E48\uFF1F
    `};return Z(()=>{_(e.value.model_id),C()}),h({validate:()=>new Promise((F,u)=>{var s;return(s=B.value)==null?void 0:s.validate().then(F).catch(u)})}),(F,u)=>{const s=O,i=q,n=P,p=j,D=z,M=uu("router-link"),S=L,I=G,R=H,U=N;return E(),m("div",ou,[a("div",tu,[o(U,null,{default:t(()=>[a("div",null,[o(R,{model:e.value,rules:w,ref_key:"formRef",ref:B,"label-position":"top"},{default:t(()=>[a("div",{class:"mt-3 w-full h-[180px] bg-no-repeat bg-cover rounded-tl-[20px] rounded-tr-[20px] flex flex-col justify-center items-center",style:eu({backgroundImage:`url(${e.value.bg_image||r(lu)})`})},[a("div",au,[o(T,{modelValue:e.value.image,"onUpdate:modelValue":u[0]||(u[0]=l=>e.value.image=l)},null,8,["modelValue"])]),a("div",Fu,[o(s,{limit:1,"show-progress":"","show-file-list":!1,onSuccess:y},{default:t(()=>[su]),_:1})])],4),a("div",nu,[a("div",null,[o(n,{label:"\u667A\u80FD\u4F53\u540D\u79F0",prop:"name"},{default:t(()=>[o(i,{modelValue:e.value.name,"onUpdate:modelValue":u[1]||(u[1]=l=>e.value.name=l),placeholder:"\u8BF7\u8F93\u5165\u667A\u80FD\u4F53\u540D\u79F0"},null,8,["modelValue"])]),_:1}),o(n,{label:"\u667A\u80FD\u4F53\u6A21\u578B",prop:"model_id"},{default:t(()=>[o(D,{modelValue:e.value.model_id,"onUpdate:modelValue":u[2]||(u[2]=l=>e.value.model_id=l),placeholder:"\u8BF7\u9009\u62E9\u667A\u80FD\u4F53\u6A21\u578B",filterable:"",onChange:_},{default:t(()=>[(E(!0),m(f,null,g(r(d),l=>(E(),v(p,{key:l.id,label:l.name,value:l.model_id},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1}),o(n,{label:"\u667A\u80FD\u4F53\u5206\u7C7B",prop:"cate_id"},{default:t(()=>[o(D,{modelValue:e.value.cate_id,"onUpdate:modelValue":u[3]||(u[3]=l=>e.value.cate_id=l),class:"!h-10",placeholder:"\u8BF7\u9009\u62E9\u667A\u80FD\u4F53\u5206\u7C7B","filter-method":"",remote:"","reserve-keyword":"","remote-method":C},{default:t(()=>[(E(!0),m(f,null,g(r(A),l=>(E(),v(p,{label:l.name,value:l.id,key:l.id},null,8,["label","value"]))),128))]),_:1},8,["modelValue"]),o(M,{class:"text-primary",to:r($)("ai_application.agent/cate"),target:"_blank"},{default:t(()=>[b(" \u53BB\u521B\u5EFA\u5206\u7C7B ")]),_:1},8,["to"])]),_:1})]),a("div",Eu,[o(n,{label:"\u76F8\u5173\u4ECB\u7ECD",prop:"intro"},{default:t(()=>[o(i,{modelValue:e.value.intro,"onUpdate:modelValue":u[4]||(u[4]=l=>e.value.intro=l),type:"textarea","show-word-limit":"",resize:"none",placeholder:"\u8BF7\u8F93\u5165\u76F8\u5173\u4ECB\u7ECD ...",maxlength:500,rows:6},null,8,["modelValue"])]),_:1})])]),o(S,{class:"!border-[#0000000d] !mt-2"}),o(n,null,{label:t(()=>[a("div",du,[ru,o(I,{link:"",type:"primary",onClick:u[5]||(u[5]=l=>k())},{default:t(()=>[b("\u4E00\u952E\u586B\u5165\u793A\u4F8B")]),_:1})])]),default:t(()=>[o(i,{modelValue:e.value.roles_prompt,"onUpdate:modelValue":u[6]||(u[6]=l=>e.value.roles_prompt=l),type:"textarea","show-word-limit":"",placeholder:"\u8BF7\u8F93\u5165\u76F8\u5173\u63D0\u793A\u8BCD ...",maxlength:2e4,rows:10},null,8,["modelValue"])]),_:1})]),_:1},8,["model"])])]),_:1})])])}}});export{pu as _};
