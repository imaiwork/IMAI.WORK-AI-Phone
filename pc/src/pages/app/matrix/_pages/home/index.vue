<template>
    <HeaderContentDefineTemplate v-slot="{ title, index }">
        <div class="text-white flex items-center gap-x-2">
            <span class="flex items-center justify-center w-4 h-4 rounded-full bg-primary text-[10px]">{{
                index
            }}</span>
            {{ title }}
        </div>
    </HeaderContentDefineTemplate>
    <div
        class="bg-app-bg-2 rounded-[20px] h-full"
        style="background: linear-gradient(125deg, #dae1eb 0%, #1a4a8c 14.83%, rgba(3, 4, 6, 0) 35.01%)">
        <ElScrollbar>
            <div class="p-[18px] min-w-[1100px]">
                <div class="flex gap-x-5">
                    <div class="basis-1/2 px-[42px]">
                        <div class="text-white text-[40px] font-bold mt-[70px] title-gradient">
                            如何用自动化 <br />
                            工具搭建私域引流矩阵
                        </div>
                        <div class="text-[#ffffff80] mt-5">无需多人团队架构，四步开启全自动引流</div>
                    </div>
                    <div class="bg-app-bg-3 border border-[#ffffff1a] rounded-xl basis-1/2 px-[50px] py-6">
                        <div class="text-white font-bold text-[20px] mt-[32px]">私域引流全自动作战台</div>
                        <div class="text-[#ffffff80] mt-[36px] flex flex-col gap-y-5">
                            <HeaderContentUseTemplate title="智能（AI）手机硬件级授权（免封号）" index="1" />
                            <HeaderContentUseTemplate title="私信 → 加微全闭环（无需人工）" index="2" />
                            <HeaderContentUseTemplate title="全平台社媒账号覆盖" index="3" />
                        </div>
                        <div class="flex justify-end">
                            <ElButton
                                type="primary"
                                class="!rounded-full !h-[50px] w-[168px]"
                                @click="handleClickCourseUrl">
                                查看操作手册
                            </ElButton>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="grid grid-cols-4 gap-x-[20px] gap-y-[20px]">
                        <div
                            v-for="(item, index) in middleMenu"
                            :key="item.title"
                            class="flex items-center gap-x-[14px] bg-app-bg-3 border rounded-xl p-5 h-[96px] relative"
                            :class="[index == 0 ? 'border-primary' : 'border-[#ffffff1a]']">
                            <div class="absolute left-0 top-[50%] translate-y-[-50%]">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="4"
                                    height="36"
                                    viewBox="0 0 4 36"
                                    fill="none">
                                    <path
                                        d="M0 0C2.20914 0 4 1.79086 4 4V32C4 34.2091 2.20914 36 0 36V0Z"
                                        :fill="index == 0 ? 'var(--color-primary)' : 'var(--color-white)'" />
                                </svg>
                            </div>
                            <div class="flex-shrink-0">
                                <img :src="item.icon" class="w-12 h-12" />
                            </div>
                            <div>
                                <div class="text-white">{{ item.title }}</div>
                                <div class="text-[#ffffff80] mt-2">{{ item.desc }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-x-5 mt-8">
                    <div class="flex-1 px-[30px] bg-app-bg-3 rounded-xl border border-[#ffffff1a] bg-app-bg-2">
                        <div class="mb-[40px] mt-[30px]">
                            <div class="text-white text-[20px]">矩阵运营的最佳实践</div>
                            <div class="text-[#ffffff80] mt-[6px]">
                                一站式解决批量生产视频、多账号一键发布视频、统一查看运营数据
                            </div>
                        </div>
                        <div class="flex gap-x-4">
                            <div class="flex-1 -mt-10">
                                <img src="../../_assets/images/home_bg.png" />
                            </div>
                            <div class="flex-1 flex flex-col gap-y-6">
                                <div>
                                    <div class="text-white">批量剪辑视频</div>
                                    <div class="text-[#ffffff80] mt-[10px]">
                                        通过视频混剪，批量剪辑视频，剪辑后的素材存储在内容管理中
                                    </div>
                                </div>
                                <div>
                                    <div class="text-white">批量发布视频</div>
                                    <div class="text-[#ffffff80] mt-[10px]">一次性发布多个视频到多个账号/一个账号</div>
                                </div>
                                <div>
                                    <div class="text-white">自动接管私聊回复引流</div>
                                    <div class="text-[#ffffff80] mt-[10px]">
                                        发布后在AI智能体自动接管私信，发送加微话术，当触达微信号后自动加微
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col gap-y-6">
                        <div
                            class="flex items-center gap-x-5 bg-app-bg-2 border border-[#ffffff1a] rounded-xl h-[132px] px-5">
                            <img src="../../_assets/icons/mix_list.svg" class="w-12 h-12" />
                            <div>
                                <div class="text-white text-[20px] font-bold">小红书智能（AI）矩阵</div>
                                <div class="text-[#ffffff80] mt-3">
                                    在数据驱动的新时代，智能（AI）矩阵正在重构认知边界
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-x-5 bg-app-bg-2 border border-[#ffffff1a] rounded-xl px-5 h-[108px]">
                            <img src="../../_assets/icons/msg_wechat.svg" class="w-12 h-12" />
                            <div>
                                <div class="text-white flex items-center gap-x-2">
                                    私信<svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="20"
                                        height="20"
                                        viewBox="0 0 20 20"
                                        fill="none">
                                        <path
                                            d="M0 10C0 4.47715 4.47715 0 10 0C15.5228 0 20 4.47715 20 10C20 15.5228 15.5228 20 10 20C4.47715 20 0 15.5228 0 10Z"
                                            fill="#0065FB" />
                                        <path
                                            d="M8 7L12 10L8 13"
                                            stroke="white"
                                            stroke-width="1.2"
                                            stroke-linejoin="round" /></svg
                                    >加微全自动
                                </div>
                                <div class="text-[#ffffff80] mt-3">
                                    关键词触发+微信号自动识别，日均加微89+响应速度< 8秒
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-x-5 bg-app-bg-2 border border-[#ffffff1a] rounded-xl px-5 h-[108px]">
                            <img src="../../_assets/icons/flow_data.svg" class="w-12 h-12" />
                            <div>
                                <div class="text-white">收割全天候流量</div>
                                <div class="text-[#ffffff80] mt-3">自定义发布密度+智能错峰，单次操作覆盖多发布位</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import BindAccount from "@/pages/app/matrix/_assets/icons/bind_account.svg";
import AutoMsg from "@/pages/app/matrix/_assets/icons/auto_msg.svg";
import PhonePublish from "@/pages/app/matrix/_assets/icons/phone_publish.svg";
import MixAuto from "@/pages/app/matrix/_assets/icons/mix_auto.svg";

const appStore = useAppStore();

const getCourseUrl = computed(() => {
    const { course_url } = appStore.getAppConfig;
    return course_url;
});

const middleMenu = [
    {
        title: "第一步：绑定社媒账号池",
        desc: "通过绑定AI手机添加您的的所有抖音账号",
        icon: BindAccount,
    },
    {
        title: "第二步：选择发布内容",
        desc: "选择发布内容或进行内容创作",
        icon: PhonePublish,
    },
    {
        title: "第三步：矩阵自动发布",
        desc: "内容自定义时间自动批量发布至",
        icon: MixAuto,
    },
    {
        title: "第四步：24小时自动私信引流",
        desc: "自动回复预设「加微话术」，识别微信后自动触达",
        icon: AutoMsg,
    },
];

const handleClickCourseUrl = () => {
    window.open(getCourseUrl.value, "_blank");
};

const { DefineTemplate: HeaderContentDefineTemplate, UseTemplate: HeaderContentUseTemplate } = useTemplate();
</script>

<style scoped lang="scss">
.title-gradient {
    background: linear-gradient(282deg, #0065fb 33.53%, #fff 70.93%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
