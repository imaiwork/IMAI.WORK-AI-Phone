<template>
    <div class="w-full h-full bg-white flex flex-col">
        <div class="h-[58px] flex-shrink-0 flex items-center px-4 justify-between border-b border-[#E5E5E5]">
            <div>{{ currentFriend?.ShowName }}</div>
            <div class="flex items-center gap-x-2" v-if="currentFriend.UserName && !currentFriend.isRoom">
                <span>AI功能</span>
                <ElSwitch :model-value="friendInfo.open_ai == 1" @change="changeOpenAi" />
            </div>
        </div>
        <div class="grow min-h-0 bg-[#F3F3F3] relative">
            <Chatting ref="chattingRef" @top="emit('top')" />
            <!-- 回到底部 -->
            <div class="absolute w-full bottom-2" v-if="disabledScroll">
                <div class="flex justify-end mr-2" @click="scrollToBottom" v-if="newMsg > 0">
                    <div
                        class="rounded-full bg-white px-2 py-1 cursor-pointer flex items-center gap-x-2 text-xs shadow-lighter">
                        <div class="rotate-90">
                            <Icon name="el-icon-DArrowRight" color="#07C160" :size="12" />
                        </div>
                        <span class="text-[#07C160]">{{ newMsg }} 条新消息</span>
                    </div>
                </div>
                <div v-else class="w-full flex justify-center">
                    <div
                        class="shadow-lighter bg-white rounded-full px-2 py-1 text-xs cursor-pointer"
                        @click="scrollToBottom">
                        回到底部
                    </div>
                </div>
            </div>
        </div>
        <div class="h-[250px] flex-shrink-0 border-t border-[#E5E5E5] flex flex-col py-2 px-4 relative">
            <div class="flex items-center justify-between">
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
                            <EmojiContainer />
                        </div>
                    </ElPopover>
                </div>
                <div v-if="currentFriend?.UserName && !currentFriend.isRoom">
                    <ElSelect
                        v-model="friendInfo.takeover_mode"
                        class="!w-[120px]"
                        :disabled="!friendInfo.open_ai"
                        @change="changTakeoverMode">
                        <ElOption :value="1" label="AI接管"></ElOption>
                        <ElOption :value="0" label="人工介入"></ElOption>
                    </ElSelect>
                </div>
            </div>
            <div class="grow min-h-0 mt-2">
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
import { ContentTypeEnum } from "../../_enums";
import { cloneDeep } from "lodash";
import EmojiContainer from "../../_components/emoji.vue";
import useHandle from "../../_hooks/useHandle";

const emit = defineEmits(["contentPost", "top"]);

const {
    newMsg,
    currentWechat,
    currentFriend,
    wechatAiInfo,
    friendInfo,
    chattingRef,
    disabledScroll,
    updateUserInfo,
    triggerHandleEvent,
} = useHandle();

const userStore = useUserStore();
const { isLogin, toggleShowLogin } = userStore;

const inputContent = reactive<any>({
    contentType: ContentTypeEnum.Text,
    content: "",
    file: {},
});

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

const changeOpenAi = async (flag: boolean) => {
    await updateUserInfo({
        open_ai: flag ? 1 : 0,
    });
    if (!flag) {
        friendInfo.value.takeover_mode = 0;
        changTakeoverMode(0);
    }
};

const changTakeoverMode = (value: any) => {
    updateUserInfo({
        takeover_mode: value,
    });
};

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

//发送
const contentPost = () => {
    if (inputContent.contentType == ContentTypeEnum.Text && inputContent.content.replace(/(^\s*)|(\s*$)/g, "") == "") {
        feedback.msgError("输入为空！");
        return;
    }
    emit("contentPost", cloneDeep(inputContent));
    nextTick(() => {
        disabledScroll.value = false;
        scrollToBottom();
    });
};

// 设置inputContent
const setInputContent = (content: any, isAppend: boolean = false) => {
    if (isAppend) {
        inputContent.content += content;
    } else {
        inputContent.content = content;
    }
    inputContent.contentType = ContentTypeEnum.Text;
    inputContent.file = {};
};

const cleanInput = () => {
    inputContent.content = "";
    inputContent.contentType = ContentTypeEnum.Text;
    inputContent.file = {};
};

const scrollToBottom = () => {
    disabledScroll.value = false;
    newMsg.value = 0;
    chattingRef.value?.scrollToBottom();
};

const scrollToItem = (item: any) => {
    chattingRef.value?.scrollToItem(item);
};

const initScroll = () => {
    chattingRef.value?.initScroll();
};

defineExpose({
    cleanInput,
    setInputContent,
    scrollToBottom,
    scrollToItem,
    initScroll,
});
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
