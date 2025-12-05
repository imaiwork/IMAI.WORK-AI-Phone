<template>
    <div class="h-full flex gap-4 w-full">
        <div class="grow min-h-0 flex flex-col">
            <div>
                <ElTabs v-model="activeName" @tab-click="handleTabClick">
                    <ElTabPane v-for="item in typeLists" :key="item.id" :label="item.name" :name="item.id"></ElTabPane>
                </ElTabs>
                <div class="mt-2">
                    <div v-if="activeName === TypeLists.TEXT" class="relative">
                        <ElInput
                            v-model="fileData.text"
                            ref="textInputRef"
                            type="textarea"
                            :autosize="{
                                minRows: 7,
                            }"
                            placeholder="点击输入您要发送的文本内容" />

                        <div class="absolute bottom-2 w-full px-2 flex items-center justify-between">
                            <ElPopover
                                placement="bottom"
                                width="466"
                                trigger="click"
                                :show-arrow="false"
                                :popper-style="{
                                    padding: 0,
                                }">
                                <template #reference>
                                    <div class="rounded-lg hover:bg-token-sidebar-surface-secondary p-2 cursor-pointer">
                                        <Icon name="local-icon-phiz" :size="24" />
                                    </div>
                                </template>
                                <div>
                                    <EmojiContainer />
                                </div>
                            </ElPopover>
                            <ElButton type="primary" @click="insertRemark" v-if="false">插入备注</ElButton>
                        </div>
                    </div>
                    <div v-if="[TypeLists.IMAGE, TypeLists.VIDEO].includes(activeName)" class="w-full">
                        <upload
                            class="h-full w-full"
                            :show-file-list="false"
                            show-progress
                            :type="activeName == TypeLists.IMAGE ? 'image' : 'video'"
                            @success="getUploadFile">
                            <div
                                class="h-[202px] border border-dashed border-[#dcdfe6] rounded-lg w-full flex flex-col items-center justify-center hover:border-primary">
                                <template
                                    v-if="activeName === TypeLists.IMAGE ? !fileData.image.uri : !fileData.video.uri">
                                    <img src="../_assets/images/add.png" class="w-8" />
                                    <div class="text-[#8A8A8A] mt-4">
                                        点击上传{{ activeName === TypeLists.IMAGE ? "图片" : "视频" }}，或点击此<span
                                            class="text-primary cursor-pointer hover:underline"
                                            @click.stop="openMaterialLibrary"
                                            >从素材库选择</span
                                        >
                                    </div>
                                </template>
                                <div v-else class="h-full w-full flex justify-center relative" v-loading="parseLoading">
                                    <img
                                        v-if="!parseLoading"
                                        :src="activeName === TypeLists.IMAGE ? fileData.image.uri : fileData.video.pic"
                                        class="h-full rounded-lg object-cover" />
                                    <div
                                        v-if="fileData.video.uri"
                                        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 cursor-pointer z-30"
                                        @click.stop="previewVideo(fileData.video.uri)">
                                        <Icon name="local-icon-play" :size="50" color="var(--color-primary)"></Icon>
                                    </div>
                                    <div class="absolute right-2 top-2">
                                        <ElButton
                                            @click.stop="
                                                activeName === TypeLists.IMAGE
                                                    ? (fileData.image = {})
                                                    : (fileData.video = {})
                                            ">
                                            <Icon name="el-icon-Delete"></Icon>
                                        </ElButton>
                                    </div>
                                </div>
                            </div>
                        </upload>
                    </div>
                    <div class="flex justify-center mt-4">
                        <ElButton type="primary" @click="handleAddInfo"> 添加信息内容 </ElButton>
                    </div>
                </div>
                <div class="mt-8">
                    <div class="flex justify-center">
                        <img src="../_assets/images/arrow_down.png" class="w-[25px]" />
                    </div>
                </div>
            </div>
            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="px-4" v-draggable="draggableOptions">
                        <div class="material-list mt-4 flex flex-col gap-y-4">
                            <div
                                v-for="(item, index) in materialLists"
                                :key="index"
                                class="px-4 py-2 rounded-lg bg-[#2E2E2E] flex justify-between items-center">
                                <div class="text-white flex items-center gap-x-4 flex-1 w-0">
                                    <div class="flex-shrink-0">【{{ getTypeName(item.type) }}】</div>
                                    <div
                                        class="flex-shrink-0"
                                        :class="{
                                            'flex-1 w-0': item.type === TypeLists.TEXT,
                                        }">
                                        <div
                                            class="text-ellipsis overflow-hidden whitespace-nowrap"
                                            v-if="item.type === TypeLists.TEXT">
                                            {{ item.content }}
                                        </div>
                                        <template v-if="[TypeLists.IMAGE, TypeLists.VIDEO].includes(item.type)">
                                            <img :src="item.content" class="w-6 h-6 rounded-lg object-cover" />
                                        </template>
                                    </div>
                                    <div class="text-ellipsis overflow-hidden whitespace-nowrap">
                                        {{ item.content.name }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-x-2 flex-shrink-0">
                                    <div class="leading-[0] cursor-pointer" @click="delMaterial(index)">
                                        <Icon name="el-icon-Delete" color="#ffffff" :size="20" />
                                    </div>
                                    <div class="move-icon cursor-move leading-[0]">
                                        <Icon name="el-icon-Rank" color="#ffffff" :size="20" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>
        <div class="w-[400px] flex-shrink-0" v-if="showPreview">
            <div class="phone-preview bg-[#f7f7f7] p-10">
                <ElScrollbar>
                    <div class="py-14 px-5 flex flex-col gap-y-2">
                        <div v-for="(item, index) in materialLists" :key="index" class="flex gap-x-2">
                            <div class="flex-shrink-0">
                                <img :src="userInfo.avatar" class="w-8 h-8" />
                            </div>
                            <div class="bg-white p-2 rounded-lg" v-if="item.type === TypeLists.TEXT">
                                {{ item.content }}
                            </div>
                            <div class="w-[120px] h-[120px]" v-if="item.type === TypeLists.IMAGE">
                                <ElImage
                                    :src="item.content"
                                    :preview-src-list="[item.content]"
                                    fit="cover"
                                    class="w-full h-full" />
                            </div>
                            <div class="w-[120px] h-[120px] relative" v-if="item.type === TypeLists.VIDEO">
                                <ElImage
                                    :src="item.content.video.pic"
                                    :preview-src-list="[item.content.video.uri]"
                                    fit="cover"
                                    class="w-full h-full" />
                                <div
                                    class="absolute top-0 left-0 w-full h-full flex items-center justify-center bg-[rgba(0,0,0,0.5)] cursor-pointer"
                                    @click="previewVideo(item.content.video.uri)">
                                    <Icon name="local-icon-play" :size="50" color="#ffffff"></Icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </div>
    <PreviewVideo
        v-if="showVideo"
        ref="videoPreviewPlayerRef"
        :video-url="previewVideoUrl"
        @close="showVideo = false"></PreviewVideo>
</template>

<script setup lang="ts">
import { setRangeText } from "@/utils/dom";
import { dayjs, type InputInstance } from "element-plus";
import { uploadImage } from "@/api/app";
import { useUserStore } from "@/stores/user";
import EmojiContainer from "./emoji.vue";
import useHandle from "../_hooks/useHandle";
import { HandleEventEnum } from "../_enums";

const props = withDefaults(
    defineProps<{
        modelValue: any[];
        showPreview?: boolean;
    }>(),
    {
        showPreview: true,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: any[]): void;
}>();

const userStore = useUserStore();

const { userInfo } = toRefs(userStore);

const materialLists = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});
enum TypeLists {
    TEXT = 0,
    IMAGE = 1,
    VIDEO = 2,
    MINI_PROGRAM = 3,
    LINK = 4,
}

const activeName = ref<number>(TypeLists.TEXT);
const typeLists = [
    {
        id: TypeLists.TEXT,
        key: "text",
        name: "文本",
        error_tips: "请输入文本内容",
    },
    {
        id: TypeLists.IMAGE,
        key: "image",
        name: "图片",
        error_tips: "请上传图片",
    },
    // {
    // 	id: TypeLists.VIDEO,
    // 	key: "video",
    // 	name: "视频",
    // 	error_tips: "请上传视频",
    // },
    // {
    // 	id: TypeLists.MINI_PROGRAM,
    // 	key: "miniProgram",
    // 	name: "小程序",
    // 	error_tips: "请选择小程序",
    // },
    // {
    // 	id: TypeLists.LINK,
    // 	key: "link",
    // 	name: "链接",
    // 	error_tips: "请选择链接",
    // },
];
const fileData = reactive<any>({
    text: "",
    image: {},
    video: {},
    miniProgram: {},
    link: {},
});

const draggableOptions = [
    {
        selector: ".material-list",
        options: {
            animation: 150,
            handle: ".move-icon",
            onEnd: ({ newIndex, oldIndex }: any) => {
                const arr = materialLists.value;
                const currRow = arr.splice(oldIndex, 1)[0];
                arr.splice(newIndex, 0, currRow);
                materialLists.value = [];
                nextTick(() => {
                    materialLists.value = arr;
                });
            },
        },
    },
];

const { onHandleEvent } = useHandle();

onHandleEvent("action", (data: any) => {
    const { type } = data;
    switch (type) {
        case HandleEventEnum.ChooseEmoji:
            fileData.text = setRangeText(textInputRef.value?.textarea!, data.emoji);
    }
});

const handleTabClick = (tab: any) => {
    resetFileData();
};

const getTypeName = (type: number) => {
    return typeLists.find((item) => item.id === type)?.name + "消息";
};

const getUploadFile = (result: any) => {
    const { uri, name } = result.data || {};
    if (activeName.value === TypeLists.IMAGE) {
        parseLoading.value = false;
        fileData.image = {
            uri,
            name,
        };
    } else {
        fileData.video = {
            uri,
            name,
            pic: "",
        };
        parseVideo(uri);
    }
};

const parseLoading = ref<boolean>(true);
const parseVideo = (url: string) => {
    const video = document.createElement("video");
    const canvas = document.createElement("canvas");
    const context = canvas.getContext("2d");
    video.src = url;
    video.muted = true;
    video.playsInline = true;
    video.preload = "auto";
    // 允许跨域
    video.crossOrigin = "anonymous";
    video.addEventListener("loadedmetadata", async () => {
        const aspectRatio = video.videoWidth / video.videoHeight;
        canvas.width = 443;
        canvas.height = canvas.width / aspectRatio;
        video.currentTime = 0.1;
    });
    video.addEventListener("seeked", async () => {
        try {
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const fileResult = await base64ToBlob(
                canvas.toDataURL("image/png"),
                `${dayjs().format("YYYYMMDDHHmmss")}.png`
            );
            uploadImage({
                file: fileResult,
            }).then((res) => {
                fileData.video.pic = res.uri;
                parseLoading.value = false;
            });
            URL.revokeObjectURL(video.src);
        } catch (error) {
            console.log("error", error);
            parseLoading.value = false;
        }
    });
    video.addEventListener("error", () => {
        parseLoading.value = false;
    });
};

const showVideo = ref<boolean>(false);
const videoPreviewPlayerRef = shallowRef();
const previewVideoUrl = ref<string>("");

const previewVideo = async (uri: string) => {
    previewVideoUrl.value = uri;
    showVideo.value = true;
    await nextTick();
    videoPreviewPlayerRef.value?.open();
};

const openMaterialLibrary = () => {
    console.log("openMaterialLibrary");
};

const textInputRef = shallowRef<InputInstance>();
const insertRemark = () => {
    fileData.text = setRangeText(textInputRef.value?.textarea!, `\${remark}`);
};

const handleAddInfo = () => {
    const currData = typeLists.find((item) => item.id === activeName.value);
    if (fileData[currData?.key] === "") {
        feedback.msgError(currData?.error_tips);
        return;
    }
    materialLists.value = [
        ...materialLists.value,
        {
            type: activeName.value,
            name: fileData[currData?.key]?.name,
            content: activeName.value == TypeLists.TEXT ? fileData.text : fileData.image.uri,
        },
    ];
    resetFileData();
};

const resetFileData = () => {
    Object.keys(fileData).forEach((key) => {
        fileData[key] = "";
    });
};

const delMaterial = async (index: number) => {
    await feedback.confirm("确定删除该素材吗？");
    materialLists.value.splice(index, 1);
};

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
:deep(.el-textarea__inner) {
    padding-bottom: 50px;
}
:deep(.el-tabs__nav-wrap::after) {
    display: none;
}

.phone-preview {
    width: 100%;
    height: 728px;
    background-image: url("../_assets/images/phone.png");
    background-size: 100% 100%;
    background-repeat: no-repeat;
}
</style>
