<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px] px-[60px] py-[80px]" v-if="!isPublish">
        <div class="grid grid-cols-2 gap-5">
            <div
                class="bg-app-bg-2 border border-app-border-1 rounded-[20px] h-[200px] flex items-center justify-center gap-x-[18px] cursor-pointer hover:bg-app-bg-1"
                v-for="item in createTabs"
                :key="item.type"
                @click="handleCreate(item.type)">
                <img :src="item.icon" />
                <div>
                    <div class="font-bold text-[22px] text-white">{{ item.title }}</div>
                    <div class="text-xs text-[#ffffff66]">{{ item.subTitle }}</div>
                </div>
            </div>
        </div>
    </div>
    <PublishPanel :type="createType" @back="publishBack" v-if="isPublish" />
</template>

<script setup lang="ts">
import CreateImage from "@/pages/app/matrix/_assets/icons/create_image.svg";
import CreateVideo from "@/pages/app/matrix/_assets/icons/create_video.svg";
import PublishPanel from "@/pages/app/matrix/_components/publish-panel.vue";
import { PublishTaskTypeEnum, SidebarTypeEnum } from "@/pages/app/matrix/_enums";

const { query } = useRoute();

const createTabs = [
    {
        type: PublishTaskTypeEnum.IMAGE,
        icon: CreateImage,
        title: "发布图文",
        subTitle: "发布平台：小红书/视频号/快手",
    },
    {
        type: PublishTaskTypeEnum.VIDEO,
        icon: CreateVideo,
        title: "发布视频",
        subTitle: "发布平台：小红书/视频号/快手",
    },
];
const createType = ref(
    typeof query.type === "string"
        ? (Number(query.create_type) as unknown as PublishTaskTypeEnum)
        : PublishTaskTypeEnum.IMAGE
);
const isPublish = ref(query.is_publish == "1" && Number(query.type) == SidebarTypeEnum.CREATE);

const handleCreate = (type: PublishTaskTypeEnum) => {
    isPublish.value = true;
    createType.value = type;
    replaceState({
        is_publish: 1,
        create_type: type,
    });
};

const publishBack = () => {
    isPublish.value = false;
    window.history.replaceState("", "", `?type=${SidebarTypeEnum.CREATE}`);
};
</script>

<style scoped></style>
