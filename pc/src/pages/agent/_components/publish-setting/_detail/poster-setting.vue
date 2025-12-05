<template>
    <popup
        ref="popupRef"
        async
        width="400px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        :show-close="false">
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <div class="text-2xl font-bold mb-5">生成海报</div>
            <!-- 海报预览区域 -->
            <div class="w-full h-[556px] rounded-[10px] overflow-hidden relative" ref="posterRef">
                <img v-if="formData.bgUrl" class="object-cover w-full h-full" :src="formData.bgUrl" />
                <div class="absolute top-0 left-0 w-full h-full flex flex-col justify-center items-center">
                    <!-- 二维码 -->
                    <vue-qr :text="link" :size="200" class="rounded-[10px]" :dot-scale="1" :margin="12" />
                    <!-- 海报文案 -->
                    <div class="text-center text-white mt-[15px] text-[18px] font-bold">
                        <div>{{ formData.title }}</div>
                        <div>{{ formData.description }}</div>
                    </div>
                </div>
            </div>
            <!-- 操作按钮 -->
            <div class="flex py-3 items-center">
                <upload :limit="1" :show-file-list="false" show-progress @success="getUploadBgSuccess">
                    <ElButton type="primary" link>自定义背景图</ElButton>
                </upload>
                <div class="flex-1 ml-3">
                    <ElButton type="primary" link @click="useDefaultBg">使用默认图</ElButton>
                </div>
                <div class="text-tx-regular">背景图尺寸：430*670</div>
            </div>
            <div class="flex">
                <ElButton class="!rounded-full flex-1 !h-[50px]" @click="close">取消</ElButton>
                <ElButton type="primary" class="!rounded-full flex-1 !h-[50px]" :loading="isLock" @click="lockFn">
                    保存
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import VueQr from "vue-qr/src/packages/vue-qr.vue";
import { getApiUrl } from "@/utils/env"; // 假设从工具函数中导入

/**
 * @description 海报生成与设置弹窗
 * @summary 用户可以自定义海报背景，并生成包含二维码的海报图片进行下载。
 */

const emit = defineEmits(["close", "success"]);

const popupRef = shallowRef();
const posterRef = shallowRef(); // 海报DOM元素的引用

// 表单数据
const formData = reactive({
    id: "",
    apikey: "",
    bgUrl: "",
    title: "快来扫码",
    description: "和我的智能体对话吧",
});

// 默认背景图URL
const defaultBg = `${getApiUrl()}/static/images/ai_share_bg.png`;

// 二维码内容链接
const link = computed(() => `${location.origin}${getBaseUrl()}/chat/${formData.apikey}`);

/**
 * @description 自定义背景图上传成功回调
 */
const getUploadBgSuccess = (res: any) => {
    formData.bgUrl = res.data.uri;
};

/**
 * @description 使用默认背景图
 */
const useDefaultBg = () => {
    formData.bgUrl = defaultBg;
};

// 使用 useLockFn 防止重复点击
const { lockFn, isLock } = useLockFn(async () => {
    try {
        // 注意：API调用被注释，当前主要功能是下载海报
        // await publishPosterSetting({
        //     id: formData.id,
        //     url: formData.bgUrl,
        // });

        // 将DOM元素转换为图片并下载
        await downloadHtml2Image(posterRef.value, { name: `${formData.title}.png` });
        close();
        emit("success");
        feedback.msgSuccess("海报已保存到本地");
    } catch (error) {
        feedback.msgError("下载失败，请重试");
    }
});

// 打开弹窗，并设置默认背景
const open = () => {
    popupRef.value.open();
    formData.bgUrl = defaultBg;
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
