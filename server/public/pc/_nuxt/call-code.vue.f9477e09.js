import{_ as d}from"./index.vue.b0bee99e.js";import{_ as $}from"./index.vue.9e6bbe24.js";import{d as f,bM as T,k as C,A as R,s as O,m as e,q as s,H as P,aL as x,I as L}from"./entry.b08dce16.js";const S=f({__name:"call-code",props:{showChatType:{type:[Boolean,Number]}},emits:["close"],setup(k,{expose:n,emit:r}){const p=r,t=P(),o=x({apikey:""}),l=`
【接口地址】
请求方式: POST
接口地址: /api/v1/chat/commonChat
调用示例: http(s)://yourdomain.com/api/v1/chat/commonChat

【Body参数】
\`\`\` json
{
    "messages": [
        {
            "role": "user",
            "content": "你要提问的问题"
        }
    ]
}
\`\`\`

【Header参数】
Authorization: 此参数是发布渠道的 apikey (必须的)

【PHP代码示例】
\`\`\` php
public function chat()
{
    // 设置SSE响应
    header('Access-Control-Allow-Origin: *');
    header('Connection: keep-alive');
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    
    // 处理响应回调
    $response = true;
    $callback = function ($ch, $data) use (&$response, &$total) {
        if (str_starts_with($data, 'data:')) {
            echo $data;
        }

        if(!connection_aborted()){
            return strlen($data);
        } else {
            return 1;
        }
    };

    // 请求的参数
    $data = [
        'messages'  => [
            ['role'=>'user', 'content'=>'你好吗?']
        ]
    ];

    // 请求头参数
    $headers  = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: ${o.apikey}' // 此参数是 apikey (必须的)
    ];

    // 发起接口请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http(s)://【你自己的域名】/api/v1/chat/commonChat');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, $callback);
    curl_exec($ch);
    curl_close($ch);

    if(true !== $response){
        throw new Exception($response);
    }

    exit();
}
\`\`\`
`,i=()=>{t.value.open()},_=()=>{p("close")};return n({open:i,setFormData:a=>T(a,o)}),(a,c)=>{const h=d,u=$,m=L;return C(),R(m,{ref_key:"popupRef",ref:t,async:"",width:"900px","confirm-button-text":"","cancel-button-text":"","header-class":"!p-0","show-close":!1},{default:O(()=>[e("div",null,[e("div",{class:"absolute w-6 h-6 right-4 top-4",onClick:_},[s(h)]),c[0]||(c[0]=e("div",{class:"text-2xl font-medium mb-5"},"调用说明",-1)),e("div",null,[s(u,{content:l})])])]),_:1},512)}}});export{S as _};
