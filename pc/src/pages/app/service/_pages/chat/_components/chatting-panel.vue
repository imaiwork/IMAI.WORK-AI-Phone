<template>
    <div class="w-full h-full bg-white flex flex-col">
        <div class="h-[58px] flex-shrink-0 flex items-center px-4 justify-between border-b border-[#E5E5E5]">
            <div>陈伟基</div>
            <!-- <div class="flex items-center gap-x-2">
                <span>自动回复</span>
                <ElSwitch :model-value="openAi" @change="changeOpenAi" />
            </div> -->
        </div>
        <div class="grow min-h-0 bg-white relative">
            <Chatting ref="chattingRef" @top="emit('top')" />
        </div>
        <div class="h-[250px] flex-shrink-0 border-t border-[#E5E5E5] flex flex-col py-2 px-4 relative">
            <div class="flex items-center justify-between" v-if="false">
                <div class="flex items-center gap-x-2">
                    <upload
                        type="file"
                        :accept="accept"
                        :show-file-list="false"
                        :max-size="10"
                        show-progress
                        @change="getUploadFile">
                        <div class="rounded-lg hover:bg-token-sidebar-surface-secondary p-2 cursor-pointer leading-[0]">
                            <Icon name="local-icon-file" :size="24" />
                        </div>
                    </upload>
                    <ElPopover
                        placement="top"
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
                            <EmojiContainer @chooseEmoji="handleChooseEmoji" />
                        </div>
                    </ElPopover>
                </div>
            </div>
            <div class="grow min-h-0">
                <template v-if="inputContent.contentType == ContentTypeEnum.Text">
                    <ElInput
                        v-model="inputContent.content"
                        type="textarea"
                        resize="none"
                        :rows="6"
                        @input="changeInputContent"
                        @keydown="handleInputEnter" />
                </template>
                <div class="flex justify-center h-full" v-else-if="inputContent.contentType == ContentTypeEnum.Picture">
                    <img :src="inputContent.file.uri" class="h-full" />
                </div>
                <div class="flex justify-center h-full" v-else-if="inputContent.contentType == ContentTypeEnum.Video">
                    <video :src="inputContent.file.uri" controls class="h-full" />
                </div>
                <div class="flex justify-center h-full" v-else>
                    <div>{{ inputContent.file.name }}</div>
                </div>
            </div>
            <div class="flex justify-end mt-1">
                <ElButton color="#E9E9E9" @click="contentPost">
                    <span class="text-[#07C160] font-bold">发送（Enter）</span>
                </ElButton>
            </div>
            <div class="absolute top-[60px] right-2 z-[1000]" v-if="inputContent.contentType != ContentTypeEnum.Text">
                <ElButton type="danger" @click="cleanInput">
                    <Icon name="el-icon-Delete" />
                </ElButton>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import Chatting from "./chatting.vue";
import EmojiContainer from "@/pages/app/person_wechat/_components/emoji.vue";
import { ContentTypeEnum } from "../../../_enums";

const emit = defineEmits(["contentPost", "top"]);

const userStore = useUserStore();
const { isLogin, toggleShowLogin } = userStore;

const openAi = ref(true);

const changeOpenAi = () => {
    openAi.value = !openAi.value;
};

const accept = ref<string>("image/png,image/jpeg,video/mp4,audio/mp3,audio/mp4,.zip");

const getUploadFile = (result: any) => {
    const {
        raw,
        response: { data },
    } = result;

    // 获取文件类型
    const fileType = raw.type;

    // 根据fileType判断文件类型, 设置inputContent.contentType
    if (fileType.includes("image")) {
        inputContent.contentType = ContentTypeEnum.Picture;
    } else if (fileType.includes("video")) {
        inputContent.contentType = ContentTypeEnum.Video;
    } else {
        inputContent.contentType = ContentTypeEnum.File;
    }
    inputContent.file.uri = data.uri;
    inputContent.file.name = data.name;
};

const handleChooseEmoji = ({ emoji, type }) => {
    inputContent.content += emoji;
};

const inputContent = reactive<any>({
    contentType: ContentTypeEnum.Text,
    content: "",
    file: {},
});

const changeInputContent = (e: any) => {
    inputContent.contentType = ContentTypeEnum.Text;
};

const handleInputEnter = (e: any) => {
    if (e.shiftKey && e.keyCode === 13) {
        return;
    }
    if (!isLogin) {
        toggleShowLogin();
        return;
    }
    if (e.keyCode === 13) {
        contentPost();
        return e.preventDefault();
    }
};

const contentPost = () => {};

const cleanInput = () => {
    inputContent.content = "";
    inputContent.contentType = ContentTypeEnum.Text;
    inputContent.file = {};
};
</script>

<style scoped lang="scss">
:deep(.upload-wrap) {
    line-height: 0;
}

:deep(.el-select__wrapper) {
    min-height: 29px !important;
}
:deep(.el-textarea__inner) {
    box-shadow: none;
    background-color: transparent;
}
:deep(.el-upload-list--picture) {
    display: none;
}
</style>
