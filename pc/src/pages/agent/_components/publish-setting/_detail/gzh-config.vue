<template>
    <popup
        ref="popupRef"
        async
        width="620px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        :show-close="false"
    >
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <div class="text-2xl font-bold mb-5">公众号配置</div>

            <!-- 添加菜单说明 -->
            <div class="text-xl font-medium mt-[16px]">添加菜单</div>
            <div class="mt-4">
                <div>1.进入微信<span class="text-success">公众号后台</span></div>
                <div class="text-[#999] mt-2">
                    <div>请确保您的公众号已过微信认证</div>
                    <div class="flex items-center">
                        <span>路径：内容与互动 > 自定义菜单 > 添加菜单</span>
                        <ElLink :href="wxConfigMenuImg" target="_blank" type="primary" class="ml-2">
                            查看填写示例
                        </ElLink>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <div>2.创建菜单</div>
                <div class="text-[#999] mt-2">
                    <div>填写菜单名称，将以下链接或二维码，配置到菜单里</div>
                </div>
                <div class="flex items-center mt-2">
                    <span>{{ link }}</span>
                    <span class="ml-2 text-primary cursor-pointer flex-shrink-0" @click="copy(link)">复制链接</span>
                </div>
            </div>

            <!-- 自动回复说明 -->
            <div class="text-xl font-medium mt-[16px]">自动回复</div>
            <div class="mt-4">
                <div>1.进入微信<span class="text-success">公众号后台</span></div>
                <div class="text-[#999] mt-2">
                    <div>
                        <span>路径：内容与互动 > 自动回复 > 收到消息回复</span>
                        <ElLink :href="wxConfigReplyImg" target="_blank" type="primary" class="ml-2">
                            查看填写示例
                        </ElLink>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <div>2.创建自动回复</div>
                <div class="text-[#999] mt-2">
                    <div>选择自动回复类型，将以下链接或二维码，配置到回复里</div>
                </div>
                <div class="flex items-center mt-2">
                    <span>{{ link }}</span>
                    <span class="ml-2 text-primary cursor-pointer flex-shrink-0" @click="copy(link)">复制链接</span>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import wxConfigMenuImg from "@/assets/images/wx_config_menu.png";
import wxConfigReplyImg from "@/assets/images/wx_config_autoreply.png";

/**
 * @description 公众号配置说明弹窗
 * @summary 提供将聊天机器人对接到微信公众号的图文指引。
 */

const emit = defineEmits(["close"]);

const popupRef = shallowRef();

// 表单数据，用于接收apikey
const formData = reactive({
    apikey: ""
});

const { copy } = useCopy();

// 生成的聊天链接
const link = computed(() => {
    return `${location.origin}/chat/${formData.apikey}`;
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
    setFormData: (data: any) => setFormData(data, formData)
});
</script>

<style scoped lang="scss"></style>
