<template>
    <!-- 发布渠道选择界面 -->
    <div class="h-full" v-if="!publishType">
        <ElScrollbar>
            <div class="px-[30px] py-6">
                <!-- Web App渠道 -->
                <div>
                    <div class="mb-3 font-bold">Web App</div>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="(item, index) in webApp" :key="index" class="card" @click="handlePublish(item.key)">
                            <img :src="item.icon" class="w-[50px] h-[50px] flex-shrink-0" />
                            <div>
                                <div class="">{{ item.label }}</div>
                                <div class="text-[#00000080] mt-[6px]">{{ item.desc }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- API对接渠道 -->
                <div class="mt-6">
                    <div class="mb-3 font-bold">APi对接</div>
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            v-for="(item, index) in apiPost"
                            :key="index"
                            class="card"
                            @click="handlePublish(item.key)">
                            <img :src="item.icon" class="w-[50px] h-[50px] flex-shrink-0" />
                            <div>
                                <div class="">{{ item.label }}</div>
                                <div class="text-[#00000080] mt-[6px]">{{ item.desc }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
    <!-- 根据选择的渠道，动态渲染对应的详情组件 -->
    <component
        v-else
        :is="getPublishDetail.components"
        :type="publishType"
        :agent-id="agentId"
        @close="publishType = null" />
</template>

<script setup lang="ts">
import PublishCodeImage from "@/assets/images/publish_code.png";
import PublishJSImage from "@/assets/images/publish_js.png";
import PublishGzhImage from "@/assets/images/publish_gzh.png";
import PublishPosterImage from "@/assets/images/publish_poster.png";
import PublishApiImage from "@/assets/images/publish_api.png";
import PublishWeb from "./_detail/web.vue";
import PublishJS from "./_detail/js.vue";
import PublishGzh from "./_detail/gzh.vue";
import PublishAPI from "./_detail/api.vue";
import { PublishTypeEnum } from "../../_enums";

/**
 * @description 发布设置的主组件
 * @summary 此组件作为分发器，根据用户选择的发布渠道，动态加载并显示相应的设置详情组件。
 */

const props = defineProps<{
    modelValue: any;
    agentId: string | number;
}>();

// 当前选择的发布渠道类型
const publishType = ref<PublishTypeEnum | null>(null);

// Web App 类型的渠道配置
const webApp = [
    {
        key: PublishTypeEnum.WEB,
        icon: PublishCodeImage,
        label: "网页",
        desc: "用户在此链接可以直接和您的智能体聊天",
        components: markRaw(PublishWeb),
    },
    {
        key: PublishTypeEnum.JS,
        icon: PublishJSImage,
        label: "JS嵌入",
        desc: "可添加到网站的任何位置，将此 iframe 添加到 html 代码中",
        components: markRaw(PublishJS),
    },
    {
        key: PublishTypeEnum.GZH,
        icon: PublishGzhImage,
        label: "微信公众号",
        desc: "可在微信公众号后台配置，提供智能体服务",
        components: markRaw(PublishGzh),
    },
    {
        key: PublishTypeEnum.POSTER,
        icon: PublishPosterImage,
        label: "朋友圈海报",
        desc: "用户扫码后，可直接和您的智能体聊天",
        components: markRaw(PublishWeb), // 海报功能复用Web组件
    },
];

// API 对接类型的渠道配置
const apiPost = [
    {
        key: PublishTypeEnum.API,
        icon: PublishApiImage,
        label: "API调用",
        desc: "用户在此链接可以直接和您的智能体聊天",
        components: markRaw(PublishAPI),
    },
];

// 根据当前选择的 `publishType` 获取对应的组件配置
const getPublishDetail = computed(() => {
    if (!publishType.value) return null;
    return [...webApp, ...apiPost].find((item) => item.key == publishType.value);
});

/**
 * @description 处理用户选择发布渠道的事件
 * @param type - 用户选择的渠道类型
 */
const handlePublish = (type: PublishTypeEnum) => {
    publishType.value = type;
};
</script>

<style scoped lang="scss">
.card {
    @apply rounded-xl border border-[var(--el-border-color)] p-4 flex items-center gap-x-2 cursor-pointer relative overflow-hidden hover:bg-[#f5f5f5];
}
</style>
