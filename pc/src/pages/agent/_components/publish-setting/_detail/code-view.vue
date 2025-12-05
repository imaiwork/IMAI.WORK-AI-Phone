<template>
    <popup
        ref="popupRef"
        async
        width="900px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        :show-close="false">
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <div class="text-2xl font-bold mb-5">JS嵌入</div>
            <!-- Iframe嵌入方式 -->
            <div>
                <div class="form-tips">要在您网站的任何位置添加聊天智能体，请将此 iframe 添加到您的 html 代码中</div>
                <div>
                    <markdown :content="htmlCode" />
                </div>
            </div>
            <!-- JS脚本嵌入方式（聊天气泡） -->
            <div>
                <div class="form-tips">要在您网站的右下角添加聊天气泡，请复制添加到您的html中</div>
                <div>
                    <markdown :content="jsCode" />
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
/**
 * @description JS嵌入代码查看弹窗
 * @summary 提供两种将聊天机器人嵌入到第三方网站的方式：Iframe和JS脚本。
 */

const emit = defineEmits(["close"]);

const popupRef = shallowRef();

// 表单数据，用于接收apikey
const formData = reactive({
    apikey: "",
});

// 聊天页面链接
const chatLink = computed(() => {
    return `${location.origin}/chat/${formData.apikey}`;
});

// Iframe嵌入代码
const htmlCode = computed(() => {
    return `\`\`\`html
<iframe 
    src="${chatLink.value}" 
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
\`\`\``;
});

// JS脚本嵌入代码 (聊天气泡)
const jsCode = computed(() => {
    return `\`\`\`html
<script>
    window.chat_iframe_src = '${chatLink.value}'
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
`;
});

// 打开弹窗
const open = () => {
    popupRef.value.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

// 暴露方法
defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped lang="scss"></style>
