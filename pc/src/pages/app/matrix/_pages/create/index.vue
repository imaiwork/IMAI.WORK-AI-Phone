<template>
    <div class="pt-[5vh]" v-if="!isPublish">
        <div class="flex flex-col items-center gap-y-5">
            <div
                class="bg-white rounded-[20px] w-[520px] h-[200px] flex items-center justify-center gap-x-[18px] cursor-pointer hover:bg-gray-100"
                v-for="item in createTabs"
                :key="item.type"
                @click="handleCreate(item.type)">
                <img :src="item.icon" />
                <div>
                    <div class="font-medium text-[22px]">{{ item.title }}</div>
                    <div class="text-xs text-[#666666]">{{ item.subTitle }}</div>
                </div>
            </div>
        </div>
    </div>
    <publish-panel v-if="isPublish" :type="createType" @back="publishBack" />
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
