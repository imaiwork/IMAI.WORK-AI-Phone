import{_ as I}from"./index.28cc44d7.js";import{d as S,as as M,at as A,c as F,r as R,x as U,o as q,k as i,l as _,m as a,q as t,s,a3 as O,Z as f,J as j,K as D,I as N,v as z,au as L,av as P,aw as T,ax as W,ay as $}from"./entry.ca4d3d61.js";import{E as G,a as H}from"./index.5f182cdf.js";import{E as J}from"./index.8b07fb5c.js";/* empty css                  *//* empty css               *//* empty css                     */import{A as K}from"./agent_bg.9a01e512.js";import Q from"./agent-logo.be669b42.js";const Z={class:"h-full flex flex-col"},X={class:"grow min-h-0"},Y={class:"px-[30px]"},ee={class:"mt-4"},le={class:"mt-[10px]"},oe={class:"flex mt-6 w-full gap-8"},te={class:"flex-1"},ae={class:"flex justify-between"},_e=S({__name:"index",props:M({modelValue:{default:()=>({})}},{modelValue:{},modelModifiers:{}}),emits:["update:modelValue"],setup(v,{expose:g}){const x=A(),r=F(()=>x.getAiModelConfig.channel||[]),p=R(),l=U(v,"modelValue"),b={image:[{required:!0,message:"请上传机器人logo"}],name:[{required:!0,message:"请输入机器人名称"}],intro:[{required:!0,message:"请输入机器人角色简介"}]},V=d=>{const{uri:e}=d.data;l.value.bg_image=e},c=d=>{const e=r.value.find(n=>n.model_id==d);if(e)l.value.model_sub_id=e.model_sub_id;else if(!d&&r.value.length>0){const n=r.value[0];l.value.model_id=n.model_id,l.value.model_sub_id=n.model_sub_id}},E=()=>{l.value.roles_prompt=`
        # Role: B2B2C AI 数字员工系统售前客服

        ## Profile

        - version: 1.0
        - language: 中文
        - description: 你是一位智能售前客服，服务于B2B2C企业级数字员工系统。你的职责是解答潜在客户在购买前提出的所有问题，包括产品功能、技术架构、部署方式、定价策略、行业适配、数据安全等方面。

        ## Skills

        - 精通数字员工系统的核心模块与功能场景
        - 能提供个性化推荐与行业解决方案
        - 理解B2B2C业务模型及企业客户需求
        - 能识别客户意图并提供精准解答
        - 理解并简洁表述复杂技术术语

        ## Background(可选项):

        本系统作为企业客户引入AI助手的关键入口，售前客服必须快速、准确、专业地回应各种咨询，减少人工客服负担，提高客户转化率。

        ## Goals(可选项):

        - 提升客户首次接触系统的好感度和信任度
        - 降低获客沟通成本，提升线索质量
        - 为销售提供精准客户画像与需求反馈

        ## OutputFormat(可选项):

        针对每个问题，简单输出以下内容：
        1. 问题简述
        2. 回答概要
        3. 可视化资源建议（如文档、视频、截图等）

        ## Rules

        1. 回答必须清晰、专业，避免冗长或模糊表达
        2. 可根据用户角色（企业老板/CTO/HR负责人）调整语言风格
        3. 若问题涉及敏感商业条款，需提示用户联系人工客服
        4. 鼓励引导用户深入了解更多功能模块

        ## Workflows

        1. 接收客户提问（明确提问意图）
        2. 调用产品知识库或FAQ生成专业解答
        3. 根据行业或客户背景个性化回答
        4. 可选推荐文档、案例、视频或预约演示

        ## Init

        您好，请问您想了解什么？
    `};return q(()=>{c(l.value.model_id)}),g({validate:()=>new Promise((d,e)=>{var n;return(n=p.value)==null?void 0:n.validate().then(d).catch(e)})}),(d,e)=>{const n=I,u=L,m=P,h=G,w=H,B=T,k=W,y=$,C=J;return i(),_("div",Z,[a("div",X,[t(C,null,{default:s(()=>[a("div",Y,[t(y,{model:l.value,rules:b,ref_key:"formRef",ref:p,"label-position":"top"},{default:s(()=>[a("div",{class:"mt-3 w-full h-[180px] bg-no-repeat bg-cover rounded-tl-[20px] rounded-tr-[20px] flex flex-col justify-center items-center",style:O({backgroundImage:`url(${l.value.bg_image||f(K)})`})},[a("div",ee,[t(Q,{modelValue:l.value.image,"onUpdate:modelValue":e[0]||(e[0]=o=>l.value.image=o)},null,8,["modelValue"])]),a("div",le,[t(n,{limit:1,"show-progress":"","show-file-list":!1,onSuccess:V},{default:s(()=>[...e[6]||(e[6]=[a("div",{class:"w-[72px] h-[29px] flex items-center justify-center rounded-[32px] bg-[#00000066] text-white"}," 更换背景 ",-1)])]),_:1})])],4),a("div",oe,[a("div",null,[t(m,{label:"智能体名称",prop:"name"},{default:s(()=>[t(u,{modelValue:l.value.name,"onUpdate:modelValue":e[1]||(e[1]=o=>l.value.name=o),class:"!h-11",placeholder:"请输入智能体名称"},null,8,["modelValue"])]),_:1}),t(m,{label:"智能体模型",prop:"model_id"},{default:s(()=>[t(w,{modelValue:l.value.model_id,"onUpdate:modelValue":e[2]||(e[2]=o=>l.value.model_id=o),class:"!h-11",placeholder:"请选择智能体模型",filterable:"",onChange:c},{default:s(()=>[(i(!0),_(j,null,D(f(r),o=>(i(),N(h,{key:o.id,label:o.name,value:o.model_id},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1})]),a("div",te,[t(m,{label:"相关介绍",prop:"intro"},{default:s(()=>[t(u,{modelValue:l.value.intro,"onUpdate:modelValue":e[3]||(e[3]=o=>l.value.intro=o),type:"textarea","show-word-limit":"",resize:"none",placeholder:"请输入相关介绍 ...",maxlength:500,rows:6},null,8,["modelValue"])]),_:1})])]),t(B,{class:"!border-[#0000000d] !mt-2"}),t(m,null,{label:s(()=>[a("div",ae,[e[8]||(e[8]=a("div",null,[a("span",{class:"font-bold"},"提示词"),a("span",{class:"ml-2 text-[#00000080]"},"角色、背景、职责、工作流程、沟通方式、目的")],-1)),t(k,{link:"",type:"primary",onClick:e[4]||(e[4]=o=>E())},{default:s(()=>[...e[7]||(e[7]=[z("一键填入示例",-1)])]),_:1})])]),default:s(()=>[t(u,{modelValue:l.value.roles_prompt,"onUpdate:modelValue":e[5]||(e[5]=o=>l.value.roles_prompt=o),type:"textarea","show-word-limit":"",placeholder:"请输入相关提示词 ...",maxlength:2e4,rows:6},null,8,["modelValue"])]),_:1})]),_:1},8,["model"])])]),_:1})])])}}});export{_e as _};
